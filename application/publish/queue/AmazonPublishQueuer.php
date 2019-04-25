<?php

namespace app\publish\queue;

use think\Db;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\amazon\AmazonPublishProduct;
use app\common\model\amazon\AmazonPublishProductJson;
use app\common\model\amazon\AmazonPublishProductVariant;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;
use app\common\model\amazon\AmazonPublishProductSubmission;
use app\common\service\UniqueQueuer;
use app\goods\service\GoodsImage;
class AmazonPublishQueuer extends  SwooleQueueJob{
    private $accountCache;
    private $accountInfo;
    private $redisAmazonAccount;
    private $redisAmazonListing;
    private $marketplace;
    public function getName():string
    {
        return 'amazon读取产品父子关系并上传到亚马逊相关接口(队列)';
    }

    public function getDesc():string
    {
        return 'amazon读取产品父子关系并上传到亚马逊相关接口(队列)';
    }

    public function getAuthor():string
    {
        return 'hzy';
    }

    public function init()
    {
        $this->accountCache = Cache::store('Account');
        $this->redisAmazonAccount = Cache::store('AmazonAccount');
    }

    public function execute()
    {
        try{
            set_time_limit(0);
            $job = $this->params;
            if ($job) {
                $this->accountInfo = $this->accountCache->amazonAccount($job['account_id']);
                if(empty($this->accountInfo)){
                    throw new Exception($job["account_id"]."没有对应的帐号信息");
                }
                $this->marketplace = $this->accountInfo['site'];

                $type = $job['type'];
                if(empty($type)){
                    throw new Exception("上传类型不能为空");
                }

                if($type == '_POST_PRODUCT_DATA_'){
                    $this->handelProduct($job['account_id'],$this->accountInfo);
                }elseif($type == '_POST_PRODUCT_RELATIONSHIP_DATA_'){
                    $this->handelProductRelation($job['account_id'],$this->accountInfo);
                }elseif($type == '_POST_PRODUCT_PRICING_DATA_'){
                    $this->handelProductPrice($job['account_id'],$this->accountInfo);
                }elseif($type == '_POST_INVENTORY_AVAILABILITY_DATA_'){
                    $this->handelProductQuantity($job['account_id'],$this->accountInfo);
                }elseif($type == '_POST_PRODUCT_IMAGE_DATA_'){

                    $this->handelProductImages($job['account_id'],$this->accountInfo);
                }else{
                    $id = $job['id'];
                    $accountId = $job['account_id'];
                    $submissionId = $job['submission_id'];
                    $subType = $job['sub_type'];
                    $this->testUpload(array('account_id' => $accountId,'feedSubmissionId' => $submissionId,'type' => $subType,'id' => $id));
                }
            }
        }catch (QueueException $exp){
            throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
        }
    }

    public function handelProduct($accountId,$accountInfo){
        $xmlServiceModel = new AmazonXsdToXmlService();
        $productData = $xmlServiceModel->getProductDataByAccount($accountId);
        $xml = "";
        if(isset($productData['xml']) && $productData['xml']){
            $xml = $productData['xml'];
        }

        if(!$xml){
            return;
        }

        //任务执行中
        $model = new AmazonPublishProduct();
        $publishHelper  = new AmazonPublishHelper();
        $model->where(array('id' => array('IN',implode(',',$productData['productIds']))))->update(array('upload_product' => 1,'publish_status' => 2));
        $messageIdPairs = $productData['messageIdPair'];
        $submissionId = $publishHelper->publishProductByType($accountId,$xml,'_POST_PRODUCT_DATA_');

        if($submissionId){
            $submissionModel = new AmazonPublishProductSubmission();
            $submissionData = array(
                'account_id'    => $accountId,
                'submission_id' => $submissionId,
                'upload_status' => 0,
                'product_json'  => json_encode($messageIdPairs),

            );

            $id = $submissionModel->saveSubmission($submissionData);

            //压入队列取结果
            $queue = [
                'account_id'=>$accountId,
                'feedSubmissionId'=>$submissionId,
                'id'=>$id,
                'type'           => '_POST_PRODUCT_DATA_',
            ];
            (new UniqueQueuer(AmazonPublishProductResultQueuer::class))->push($queue, 60*10);

        }
    }


    public function handelProductRelation($accountId,$accountInfo){

        $model = new AmazonPublishProduct();
        $xmlServiceModel = new AmazonXsdToXmlService();
        //查询出需要传父子关系的产品
        $productList = $model->field('id,publish_spu')->where(array('account_id' => $accountId,'publish_status' => 2,'upload_product' => 2,'upload_relation' => 0,'is_variant' => 1))->limit(1)->select();
        if($productList){
            $productIds = array();
            foreach($productList as $item){
                $productIds[] = $item['id'];
            }
            $num = 0;
            $xml ="<?xml version=\"1.0\" encoding=\"utf-8\" ?><AmazonEnvelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"amznenvelope.xsd\"><Header><DocumentVersion>1.01</DocumentVersion><MerchantIdentifier>M_SELLER</MerchantIdentifier></Header><MessageType>Relationship</MessageType>";
            $messageIdPairs = array();
            $data = array();
            $publishHelper  = new AmazonPublishHelper();
            foreach($productList as $item){
                $num += 1;
                $messageIdPairs[$num] = $item['id'];
                $xml .= "<Message>";
                $xml .= "<MessageID>{$num}</MessageID><OperationType>Update</OperationType><Relationship>";
                $xml .= "<ParentSKU>{$item['publish_spu']}</ParentSKU>";
               $variants = $xmlServiceModel->getProductVariantInfoByProductId($productIds,$item['id'],'product_id,publish_sku');
                if($variants){
                    foreach($variants as $variant){
                      $xml .= " <Relation><SKU>{$variant['publish_sku']}</SKU><Type>Variation</Type></Relation>";
                    }
               }
                $xml .= "</Relationship></Message>";
            }

            if($xml){
                //任务执行中
                $model->where(['id' => $item['id']])->update(array('upload_relation' => 1));
                $uploadXml = $xml.$xmlServiceModel->floorXml();
                $submissionId = $publishHelper->publishProductByType($accountId,$uploadXml,'_POST_PRODUCT_RELATIONSHIP_DATA_');

                if($submissionId){
                    $submissionModel = new AmazonPublishProductSubmission();
                    $submissionData = array(
                        'account_id'    => $accountId,
                        'submission_id' => $submissionId,
                        'upload_status' => 0,
                        'product_json'  => json_encode($messageIdPairs),
                    );
                    $id = $submissionModel->saveSubmission($submissionData);

                    //压入队列取结果
                    $queue = [
                        'account_id'=>$accountId,
                        'feedSubmissionId'=>$submissionId,
                        'id'=>$id,
                        'type' => '_POST_PRODUCT_RELATIONSHIP_DATA_',
                    ];
                    (new UniqueQueuer(AmazonPublishProductResultQueuer::class))->push($queue, 60*10);

                }
            }

        }
    }





    public function handelProductPrice($accountId,$accountInfo){
        $model = new AmazonPublishProduct();
        $xmlServiceModel = new AmazonXsdToXmlService();
        //如果有多属性先传多属性
        $productList = $model->field('id,publish_spu')->where(array('account_id' => $accountId,'publish_status' => 2,'upload_price' => 0,'upload_relation' => 2,'is_variant' => 1))->limit(1)->select();
        //没有多属性查找单属性产品上传
        if(empty($productList)){
            $productList = $model->field('id,publish_spu')->where(array('account_id' => $accountId,'publish_status' => 2,'upload_product' => 2,'upload_price' => 0,'is_variant' => 0))->limit(1)->select();
        }
        if($productList){
            $productIds = array();
            foreach($productList as $item){
                $productIds[] = $item['id'];
            }
            $num = 0;

            $xml = <<<EOT
<?xml version="1.0" encoding="utf-8" ?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd">
<Header>
<DocumentVersion>1.01</DocumentVersion>
<MerchantIdentifier>M_SELLER</MerchantIdentifier>
</Header>
<MessageType>Price</MessageType>
EOT;

            $messageIdPairs = array();
            $data = array();
            $publishHelper  = new AmazonPublishHelper();
            foreach($productList as $item){
                $variants = $xmlServiceModel->getProductVariantInfoByProductId($productIds,$item['id'],'product_id,publish_sku,standard_price');
                $site = $accountInfo['site'];
                $currency = $this->getCurrencyBySite($site);
                if($variants){
                    foreach($variants as $variant){
                        $num += 1;
                        $messageIdPairs[$num] = $item['id'];
                        $xml .= "<Message><MessageID>{$num}</MessageID><Price>";
                        $xml .= "<SKU>{$variant['publish_sku']}</SKU>";
                        $xml .= "<StandardPrice currency='{$currency}'>{$variant['standard_price']}</StandardPrice>";
                        $xml .= "</Price></Message>";
                    }
                }
            }

            if($xml){
                //任务执行中
                $model->where(['id' => $item['id']])->update(array('upload_price' => 1));
                $uploadXml = $xml.$xmlServiceModel->floorXml();
                $submissionId = $publishHelper->publishProductByType($accountId,$uploadXml,'_POST_PRODUCT_PRICING_DATA_');

                if($submissionId){
                    $submissionModel = new AmazonPublishProductSubmission();
                    $submissionData = array(
                        'account_id'    => $accountId,
                        'submission_id' => $submissionId,
                        'upload_status' => 0,
                        'product_json'  => json_encode($messageIdPairs),
                    );
                    $id = $submissionModel->saveSubmission($submissionData);

                    //压入队列取结果
                    $queue = [
                        'account_id'=>$accountId,
                        'feedSubmissionId'=>$submissionId,
                        'id'=>$id,
                        'type' => '_POST_PRODUCT_PRICING_DATA_',
                    ];

                    (new UniqueQueuer(AmazonPublishProductResultQueuer::class))->push($queue, 60*2);
                }
            }

        }
    }


    public function handelProductImages($accountId,$accountInfo){
        $model = new AmazonPublishProduct();
        $xmlServiceModel = new AmazonXsdToXmlService();
        $productList = $model->field('id,publish_spu')->where(array('account_id' => $accountId,'publish_status' => 2,'upload_quantity' => 2,'upload_image' => 0))->limit(1)->select();


        if($productList){
            $xml = <<<EOT
<?xml version="1.0" encoding="utf-8" ?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd">
<Header>
<DocumentVersion>1.01</DocumentVersion>
<MerchantIdentifier>M_SELLER</MerchantIdentifier>
</Header>
<MessageType>ProductImage</MessageType>
EOT;


            $productIds = array();
            foreach($productList as $item){
                $productIds[] = $item['id'];
            }
            $messageIdPairs = array();
            $data = array();
            $publishHelper  = new AmazonPublishHelper();
            foreach($productList as $item){
             //   $item['id'] = 20;
                $imageJson = $xmlServiceModel->getProductJsonByProductId($productIds,$item['id'],'image');
                $imageList = \GuzzleHttp\json_decode($imageJson,true);

//                foreach($imageList as $sku => $imageInfo){
//                    if(isset($imageInfo['main']) && $imageInfo['main']){
//                        if($imageInfo['main']['real_sku'] == 'BL9989201'){
//                            $imageList[$sku]['main']['publish_sku'] = 'LG00687|109503167473';
//                        }elseif($imageInfo['main']['real_sku'] == 'BL9989202'){
//                            $imageList[$sku]['main']['publish_sku'] = 'LG00687|727057766700';
//                        }elseif($imageInfo['main']['real_sku'] == 'BL9989203'){
//                            $imageList[$sku]['main']['publish_sku'] = 'LG00687|799727819441';
//                        }elseif($imageInfo['main']['real_sku'] == 'BL9989205'){
//                            $imageList[$sku]['main']['publish_sku'] = 'LG00687|698157423827';
//                        }
//                    }
//                    if(isset($imageInfo['Swatch']) && count($imageInfo['Swatch'])){
//                        foreach($imageInfo['Swatch'] as $key => $swatchItem){
//                            if($swatchItem['real_sku'] == 'BL9989201'){
//                                $imageList[$sku]['Swatch'][$key]['publish_sku'] = 'LG00687|109503167473';
//                            }elseif($swatchItem['real_sku'] == 'BL9989202'){
//                                $imageList[$sku]['Swatch'][$key]['publish_sku'] = 'LG00687|727057766700';
//                            }elseif($swatchItem['real_sku'] == 'BL9989203'){
//                                $imageList[$sku]['Swatch'][$key]['publish_sku'] = 'LG00687|799727819441';
//                            }elseif($swatchItem['real_sku'] == 'BL9989205'){
//                                $imageList[$sku]['Swatch'][$key]['publish_sku'] = 'LG00687|698157423827';
//                            }
//                        }
//                    }
//
//                    if(isset($imageInfo['detail']) && count($imageInfo['detail'])){
//                        foreach($imageInfo['detail'] as $key => $detailItem){
//                            if($detailItem['real_sku'] == 'BL9989201'){
//                                $imageList[$sku]['detail'][$key]['publish_sku'] = 'LG00687|109503167473';
//                            }elseif($detailItem['real_sku'] == 'BL9989202'){
//                                $imageList[$sku]['detail'][$key]['publish_sku'] = 'LG00687|727057766700';
//                            }elseif($detailItem['real_sku'] == 'BL9989203'){
//                                $imageList[$sku]['detail'][$key]['publish_sku'] = 'LG00687|799727819441';
//                            }elseif($detailItem['real_sku'] == 'BL9989205'){
//                                $imageList[$sku]['detail'][$key]['publish_sku'] = 'LG00687|698157423827';
//                            }
//                        }
//                    }
//                }


                if($imageList){
                    $num = 0;
                    $messageIdPairs = array();
                    foreach($imageList as $sku => $imageInfo){
                            if(isset($imageInfo['main']) && $imageInfo['main']){
                                $num+= 1;
                                $messageIdPairs[$num] = $item['id'];
                                $xml .= "<Message><MessageID>{$num}</MessageID><OperationType>Update</OperationType><ProductImage>";
                                $xml .= "<SKU>{$imageInfo['main']['publish_sku']}</SKU><ImageType>Main</ImageType> ";
                                $xml .= "<ImageLocation>{$this->getUploadImageUrl($imageInfo['main']['image_url'],$accountInfo['account_name'])}</ImageLocation> ";
                                $xml .= "</ProductImage></Message>";
                            }


                            if(isset($imageInfo['Swatch']) && count($imageInfo['Swatch'])){
                                foreach($imageInfo['Swatch'] as $swatchItem){
                                    $num+= 1;
                                    $messageIdPairs[$num] = $item['id'];
                                    $xml .= "<Message><MessageID>{$num}</MessageID><OperationType>Update</OperationType><ProductImage>";
                                    $xml .= "<SKU>{$swatchItem['publish_sku']}</SKU><ImageType>Swatch</ImageType> ";
                                    $xml .= "<ImageLocation>{$this->getUploadImageUrl($swatchItem['image_url'],$accountInfo['account_name'])}</ImageLocation> ";
                                    $xml .= "</ProductImage></Message>";
                                }
                            }


                            if(isset($imageInfo['detail']) && count($imageInfo['detail'])){
                                foreach($imageInfo['detail'] as $key => $detailItem){
                                    $num+= 1;
                                    $messageIdPairs[$num] = $item['id'];
                                    $xml .= "<Message><MessageID>{$num}</MessageID><OperationType>Update</OperationType><ProductImage>";
                                    $xml .= "<SKU>{$detailItem['publish_sku']}</SKU><ImageType>PT".($key+1)."</ImageType> ";
                                    $xml .= "<ImageLocation>{$this->getUploadImageUrl($detailItem['image_url'],$accountInfo['account_name'])}</ImageLocation> ";
                                    $xml .= "</ProductImage></Message>";
                                }
                            }
                    }
                }

            }
            if($xml){
                //任务执行中

                $model->where(['id' => $item['id']])->update(array('upload_image' => 1));
                $uploadXml = $xml.$xmlServiceModel->floorXml();

                $submissionId = $publishHelper->publishProductByType($accountId,$uploadXml,'_POST_PRODUCT_IMAGE_DATA_');

                if($submissionId){
                    $submissionModel = new AmazonPublishProductSubmission();
                    $submissionData = array(
                        'account_id'    => $accountId,
                        'submission_id' => $submissionId,
                        'upload_status' => 0,
                        'product_json'  => json_encode($messageIdPairs),

                    );
                    $id = $submissionModel->saveSubmission($submissionData);
                    //压入队列取结果
                    $queue = [
                        'account_id'=>$accountId,
                        'feedSubmissionId'=>$submissionId,
                        'id'=>$id,
                        'type' => '_POST_PRODUCT_IMAGE_DATA_',
                    ];
                    (new UniqueQueuer(AmazonPublishProductResultQueuer::class))->push($queue, 60*2);
                }
            }

        }
    }


    public function getUploadImageUrl($imageUrl,$accountName){
        static $imageModel = null;
        if($imageModel == null){
            $imageModel = new GoodsImage();
        }
        return $imageModel->getThumbPath($imageUrl,1001,1001,$accountName);
    }

    public function handelProductQuantity($accountId,$accountInfo){
        $model = new AmazonPublishProduct();
        $xmlServiceModel = new AmazonXsdToXmlService();

        $productList = $model->field('id,publish_spu')->where(array('account_id' => $accountId,'publish_status' => 2,'upload_product' => 2,'upload_price' => 2,'upload_quantity' => 0))->limit(1)->select();

        if($productList){
            $productIds = array();
            foreach($productList as $item){
                $productIds[] = $item['id'];
            }
            $num = 0;

            $xml = <<<EOT
<?xml version="1.0" encoding="utf-8" ?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amznenvelope.xsd">
<Header>
<DocumentVersion>1.01</DocumentVersion>
<MerchantIdentifier>M_SELLER</MerchantIdentifier>
</Header>
<MessageType>Inventory</MessageType>
EOT;

            $messageIdPairs = array();
            $data = array();
            $publishHelper  = new AmazonPublishHelper();
            foreach($productList as $item){
                $variants = $xmlServiceModel->getProductVariantInfoByProductId($productIds,$item['id'],'product_id,publish_sku,quantity');
                $site = $accountInfo['site'];
                $currency = $this->getCurrencyBySite($site);
                if($variants){
                    foreach($variants as $variant){
                        $num += 1;
                        $messageIdPairs[$num] = $item['id'];
                        $xml .= "<Message><MessageID>{$num}</MessageID><OperationType>Update</OperationType><Inventory>";
                        $xml .= "<SKU>{$variant['publish_sku']}</SKU>";
                        $xml .= "<Quantity>{$variant['quantity']}</Quantity>";
                        $xml .= "</Inventory></Message>";
                    }
                }

            }

            if($xml){
                //任务执行中
                $model->where(['id' => $item['id']])->update(array('upload_quantity' => 1));
                $uploadXml = $xml.$xmlServiceModel->floorXml();

                $submissionId = $publishHelper->publishProductByType($accountId,$uploadXml,'_POST_INVENTORY_AVAILABILITY_DATA_');

                if($submissionId){
                    $submissionModel = new AmazonPublishProductSubmission();
                    $submissionData = array(
                        'account_id'    => $accountId,
                        'submission_id' => $submissionId,
                        'upload_status' => 0,
                        'product_json'  => json_encode($messageIdPairs),

                    );
                    $id = $submissionModel->saveSubmission($submissionData);
                    //压入队列取结果
                    $queue = [
                        'account_id'=>$accountId,
                        'feedSubmissionId'=>$submissionId,
                        'id'=>$id,
                        'type' => '_POST_INVENTORY_AVAILABILITY_DATA_',
                    ];
                    (new UniqueQueuer(AmazonPublishProductResultQueuer::class))->push($queue, 60*2);
                }
            }

        }
    }




    public function getCurrencyBySite($site){
        $list = array(
            'DE'    => 'EUR','UK' => 'GBP', 'US' => 'USD', 'CA' => 'CAD',
            'FR'    => 'EUR', 'JP' => 'JPY', 'IN' => '',
            'ES'   => 'EUR', 'IT' => 'EUR',
        );

        if(isset($list[$site])){
            return $list[$site];
        }else{
            return '';
        }
    }



    public function testUpload($job){
        if($job) {
            set_time_limit(0);
            $accountId = $job['account_id'];
            $feedSubmissionId = $job['feedSubmissionId'];
            $postType = $job['type'];
            $id = $job['id'];
            $submissionModel = new AmazonPublishProductSubmission();
            $submissionModel->where(array('id' => $id))->update(array('upload_status' => 1));
            $productModel = new AmazonPublishProduct();
            $productJsonModel = new AmazonPublishProductJson();
            $submissionInfo = $submissionModel->field('product_json')->where(['id' => $id])->find();
            $messageIdPairs = json_decode($submissionInfo->product_json, true);
            $productIds = array_unique(array_values($messageIdPairs));

            $feedResult = (new AmazonPublishHelper())->publishResult($accountId, $feedSubmissionId);

            if ($feedResult) {
                $resultArr = (new AmazonPublishHelper())->xmlToArray($feedResult);
                $errorNum = $resultArr['Message']['ProcessingReport']['ProcessingSummary']['MessagesWithError'];
                if($errorNum == 0){ //无错误
                    $submissionInfo = $submissionModel->field('product_json')->where(['id' => $id])->find();
                    $messageIdPairs = json_decode($submissionInfo->product_json,true);
                    $productIds = array_unique(array_values($messageIdPairs));
                    if($postType == '_POST_PRODUCT_PRICING_DATA_'){
                        $updata = array('upload_price' => 2);
                    }elseif($postType == '_POST_PRODUCT_DATA_'){
                        $updata = array('upload_product' => 2);
                    }
                    elseif($postType == '_POST_PRODUCT_RELATIONSHIP_DATA_'){
                        $updata = array('upload_relation' => 2);
                    }elseif($postType == '_POST_INVENTORY_AVAILABILITY_DATA_'){
                        $updata = array('upload_quantity' => 2);
                    }elseif($postType == '_POST_PRODUCT_IMAGE_DATA_'){
                        $updata = array('upload_image' => 2,'publish_status' => 3);
                    }

                    foreach($productIds as $productId){
                        $productModel->where(array('id' => $productId))->update($updata);
                    }

                }else{
                    if(isset( $resultArr['Message']['ProcessingReport']['Result']['ResultDescription']) &&  $resultArr['Message']['ProcessingReport']['Result']['ResultDescription']){
                        $errMsg = $resultArr['Message']['ProcessingReport']['Result']['ResultDescription'];
                    }else{
                        $errMsg = "";
                        foreach($resultArr['Message']['ProcessingReport']['Result'] as $errItem){
                            $errMsg .= $errItem['ResultDescription'];
                        }
                    }

                    if($postType == '_POST_PRODUCT_PRICING_DATA_'){
                        $updata = array('upload_price' => 0,'publish_status' => 4);
                    }elseif($postType == '_POST_PRODUCT_DATA_'){
                        $updata = array('upload_product' => 0,'publish_status' => 4);
                    }
                    elseif($postType == '_POST_PRODUCT_RELATIONSHIP_DATA_'){
                        $updata = array('upload_relation' => 0,'publish_status' => 4);
                    }elseif($postType == '_POST_INVENTORY_AVAILABILITY_DATA_'){
                        $updata = array('upload_quantity' => 0,'publish_status' => 4);
                    }elseif($postType == '_POST_PRODUCT_IMAGE_DATA_'){
                        $updata = array('upload_image' => 0,'publish_status' => 4);
                    }
                    foreach($productIds as $productId){
                        $productModel->where(array('id' => $productId))->update($updata);
                        $productJsonModel->where(['product_id' => $productId])->update(array('error_message' => $errMsg));
                    }
                }
                $submissionModel->where(array('id' => $id))->update(array('upload_status' => 2));
            }

        }
    }




    public function test($accountId,$feedSubmissionId,$id){
        $submissionModel = new AmazonPublishProductSubmission();
        $submissionModel->where(array('id' => $id))->update(array('upload_status' => 1));
        $productModel = new AmazonPublishProduct();
        $productJsonModel = new AmazonPublishProductJson();
        $submissionInfo = $submissionModel->field('product_json')->where(['id' => $id])->find();
        $messageIdPairs = json_decode($submissionInfo->product_json,true);
        $productIds = array_values($messageIdPairs);

        $feedResult = (new AmazonPublishHelper())->publishResult($accountId,$feedSubmissionId);
        if ($feedResult) {
            $resultArr = (new AmazonPublishHelper())->xmlToArray($feedResult);
          //  print_r($resultArr);

            $errorNum = $resultArr['Message']['ProcessingReport']['ProcessingSummary']['MessagesWithError'];
            if($errorNum == 0){ //无错误
                $submissionInfo = $submissionModel->field('product_json')->where(['id' => $id])->find();
                $messageIdPairs = json_decode($submissionInfo->product_json,true);
                $productIds = array_values($messageIdPairs);
                foreach($productIds as  $productId){
                    $productModel->where(array('id' => $productId))->update(array('upload_relation' => 2));
                }
            }else{
                if(isset($resultArr['Message']['ProcessingReport']['Result']['ResultDescription'])){
                    $errMsg = $resultArr['Message']['ProcessingReport']['Result']['ResultDescription'];
                }else{
                    if(is_array($resultArr['Message']['ProcessingReport']['Result'])){
                        $errMsg = "";
                        foreach($resultArr['Message']['ProcessingReport']['Result'] as $errItem){
                            $errMsg .= $errItem['ResultDescription'];
                        }
                    }
                }

                foreach($productIds as  $productId){
                    $productModel->where(array('id' => $productId))->update(array('upload_relation' => 0,'publish_status' => 4));
                    $productJsonModel->where(['product_id' => $productId])->update(array('error_message' => $errMsg));

                }
            }
            $submissionModel->where(array('id' => $id))->update(array('upload_status' => 2));
        }
    }

}