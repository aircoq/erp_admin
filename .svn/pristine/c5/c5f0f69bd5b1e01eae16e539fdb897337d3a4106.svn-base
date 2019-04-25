<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-10-16
 * Time: 下午2:50
 */

namespace app\listing\service;
use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\model\aliexpress\AliexpressActionLog;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\model\aliexpress\AliexpressProductInfo;
use app\common\model\aliexpress\AliexpressProductSku;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\listing\queue\AliexpressRsyncProductQueue;
use app\publish\service\AliexpressService;
use app\publish\service\AliexpressTaskHelper;
use app\publish\service\AliProductHelper;
use think\Exception;
use app\goods\queue\GoodsTortListingQueue;
use app\internalletter\service\InternalLetterService;
use app\goods\service\GoodsSkuMapService;

class AliexpressItemService
{
    private static $config;

    const FIELDS=[
        'subject',
        'detail',
        'deliveryTime',
        'groupId',
        'freightTemplateId',
        'packageLength',
        'packageWidth',
        'packageHeight',
        'grossWeight',
        'wsValidNum',
        'mobileDetail',
        'reduceStrategy',
    ];
    const CAN_EDIT_FIELDS=[
        'subject',
        'detail',
        //'delivery_time',
        'group_id',
        'mobile_detail',
        'reduce_strategy',
        'ws_valid_num',
        'imageurls',
        'relation_template_id',
        'custom_template_id',
    ];

    public static $product_image_type = [
        'product' => 1,
        'sku' => 2,
        'detail' => 3
    ];

    /**
     * 修改更新状态
     */
    public static function updateStatus($product_id,$status=0, $virtual_send = 0)
    {
        try{
            if($product_id)
            {
                AliexpressProduct::update(['lock_update'=>$status, 'virtual_send' => $virtual_send],['product_id'=>$product_id]);

//                $where=[
//                    'product_id'=>['=',$product_id],
//                    'status'=>['=',2],
//                ];
//
//                //更新异常
//                if($status==2)
//                {
//                    AliexpressProduct::update(['lock_update'=>$status],['product_id'=>$product_id]);
//                }else{
//
//                    //有状态为2的则表示有更新异常，优先级更高
//                    if(((new AliexpressActionLog)->where($where)->limit(1)->find()))
//                    {
//                        AliexpressProduct::update(['lock_update'=>2],['product_id'=>$product_id]);
//                    }elseif(((new AliexpressActionLog)->where(['product_id'=>$product_id,'status'=>0])->limit(1)->find())){
//                        //状态为0表示未提交，则表示产品待同步，优先级第二
//                        AliexpressProduct::update(['lock_update'=>1],['product_id'=>$product_id]);
//                    }else{
//                        //没有更新异常和待更新的，则表示都执行成功了，那么商品就没有要更新的了，优先级最低吗
//                        AliexpressProduct::update(['lock_update'=>0],['product_id'=>$product_id]);
//                    }
//                }

            }
        }catch (Exception $exp){
            throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }


    }

    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @param string  $name 字符串
     * @param integer $type 转换类型
     * @param bool    $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    public static function parseName($name, $type = 1, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucfirst ? ucfirst($name) : lcfirst($name);
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }
    /**
     *延长商品有效期
     */
    public static function renewExpire($product)
    {
        try{
            self::$config = $product['product']['account'];

            $post['productId']=$product['product_id'];

            $post['module']='product';
            $post['class']='product';
            $post['action']='renewexpire';

            $params=array_merge(self::$config ,$post);
            $response = AliexpressService::execute(snakeArray($params));

            if(isset($response['modifyCount']) && $response['modifyCount']) {
                    $log['status']=1;
                    $log['message']="";

            }else{
                    $log['status']=2;

                if(isset($response['errorDetails'])) {
                    $log['message'] = json_encode(['error_message'=>'','error_code'=>$response['errorDetails']['json'][0]]);

                    $log['status']= -1;
                }
            }

            $log['run_time']=time();
            AliexpressActionLog::update($log,['id'=>$product['id']]);
            (new self())->updateStatus($post['productId'],$log['status']);
        }catch (Exception $exp){
            throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }
    /**
     * 下架
     */
    public static function offlineAeProduct($product)
    {
        try{
            self::$config = $product['product']['account'];

            $productId=$product['product_id'];

            $post['productId']=$productId;
            $post['module']='product';
            $post['class']='product';
            $post['action']='offlineaeproduct';
            $params=array_merge(self::$config ,$post);
            $response = AliexpressService::execute(snakeArray($params));


            //下架成功,产品已经下架.如侵权下架
            if(isset($response['success']) && in_array($response['success'], [0,1]))
            {
                $log['status']=1;
                $log['message']="";
                (new AliexpressProduct())->where('product_id','=',$productId)
                    ->update(['product_status_type'=>2]);

            }else{
                $log['status']=2;


                $response['error_message'] = isset($response['error_message']) ? $response['error_message'] : '';
                $data = ['error_message' => $response['error_message']];

                if(isset($response['error_code']) && $response['error_code']){
                    $data['error_code'] = $response['error_code'];
                }
                $log['message']=json_encode($data);
            }
            $log['run_time']=time();
            AliexpressActionLog::update($log,['id'=>$product['id']]);
            (new self())->updateStatus($product['product_id'],$log['status']);


            //判断是否是侵权下架
            $log = (new AliexpressActionLog())->field('id')->where(['create_id' => ['=',0], 'product_id' => ['=', $productId]])->find();
            if(isset($log['status']) && $log['status'] == 1 && $log){

                //如果是侵权下架的话,则发送钉钉,同时,回写日志
                self::offlineTortResult($productId);
            }
        }catch (Exception $exp){
            throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }


    /**
     *侵权下架成功,回写
     *type 13侵权下架
     */
    public static function offlineTortResult($productId){

        $product = (new AliexpressProduct())->field('salesperson_id,id,product_id,goods_spu')->where('product_id','=', $productId)->find();

        //发送侵权下架钉钉
        $content = '产品侵权了,下架成功';
        self::sendTortOffineLetter($product, $content);

        $key = 'AliExpressTortoffLine:'.$productId;
        if(Cache::handler()->exists($key)){

            $data = \GuzzleHttp\json_decode(Cache::handler()->get($key), true);

            //回写
            $data = [
                'goods_id'=> $product['goods_id'],//商品id
                'goods_tort_id'=> $data['tort_id'],//侵权下架id
                'listing_id'=>$product['id'],//listing_id
                'channel_id'=> 4,//平台id
                'item_id'=> $product['product_id'],//平台listing唯一码
                'status'=>'1'//状态 0 待下架   1 下架成功 2 下架失败
            ];

            //删除缓存数据
            Cache::handler()->delete($key);

            //初始化回写
            (new UniqueQueuer(GoodsTortListingQueue::class))->push($data);
        }

    }


    /**
     * @param $salesperson_id
     * 发送侵权下架钉钉
     */
    public static function sendTortOffineLetter($product, $content = '')
    {
        //发送钉钉消息
        $InternalLetterService = new InternalLetterService();
        $params = [
            'receive_ids'=> $product['salesperson_id'],
            'title'=>'SPU:'.$product['goods_spu'].'因侵权原因已在erp平台已下架，请及时处理对应平台',
            'content'=>$content,
            'type'=>13,
            'dingtalk'=>1,
            'create_id' => 1,
        ];
        $InternalLetterService->sendLetter($params);
    }


    /**
     * 上架
     */
    public static function onlineAeProduct($product)
    {
        try{
            self::$config = $product['product']['account'];

            $productId=$product['product_id'];
            $post['productId']=$productId;
            $post['module']='product';
            $post['class']='product';
            $post['action']='onlineaeproduct';

            $params=array_merge(self::$config ,$post);
            $response = AliexpressService::execute(snakeArray($params));

            if(isset($response['success']) && $response['success'])
            {
                $log['status']=1;
                $log['message']="";
                (new AliexpressProduct())->where('product_id','=',$productId)
                    ->update(['product_status_type'=>1]);
            }else{
                $log['status']=2;
                $log['message']=json_encode(['error_message'=>$response['error_message'],'error_code'=>$response['error_code']]);
            }
            $log['run_time']=time();
            AliexpressActionLog::update($log,['id'=>$product['id']]);
            (new self())->updateStatus($product['product_id'],$log['status']);
        }catch (Exception $exp){
            throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 商品编辑
     */
    public static function editAeProduct($job)
    {
        try{

            self::$config = $job['product']['account'];

            $post['productId']=$job['product_id'];

            $data = $job['new_data'];

            //商品信息基本
            $model_info = (new AliexpressProduct())->where(['product_id'=>$job['product_id']])->find();

            //是否是海外仓
            $virtual_send = isset($data['virtual_send']) ? $data['virtual_send'] : 0;

            if($model_info && $data)
            {
                //账号
                $account = $model_info->account->toArray();

                //商品sku
                $skus = $model_info->productSku;

                $code = $account['code'];

                $product = $model_info->toArray();

                if(isset($data['aeop_national_quote_configuration']) && $data['aeop_national_quote_configuration'] && isset($data['configuration_type']))
                {
                    $post['aeopNationalQuoteConfiguration'] = (new AliexpressTaskHelper())->managerNationalQuoteConfiguration($data['aeop_national_quote_configuration'],$data['configuration_type']);
                }

                if(isset($data['imageurls']) && $data['imageurls'])
                {
                    $product['imageurls']=$data['imageurls'];
                }else{
                    if($productImage = $model_info->getData('imageurls'))
                    {
                        $product['imageurls']=$productImage;
                    }
                }


                //商品信息
                $product_info = $model_info->productInfo->toArray();

                $product['detail']=$product_info['detail'];

                //商品图片
                $image_type = [
                    'ali_product_id' => $product['id'],
                    'type' => self::$product_image_type['detail']
                ];

                //处理描述详情里面的图片
                if(isset($data['detail']))
                {
                    $detailResponse = (new AliexpressTaskHelper())->managerDetail($data['detail'],$account,$code, $image_type);
                    if(isset($detailResponse['result']) && $detailResponse['result']){
                        $detail = $detailResponse['data'];
                    }
                }else{
                    if(isset($product_info['detail']) && $product_info['detail'])
                    {
                        $detail=$product_info['detail'];
                        $detailResponse = (new AliexpressTaskHelper())->managerDetail($detail,$account,$code, $image_type);
                        if(isset($detailResponse['result']) && $detailResponse['result']){
                            $detail = $detailResponse['data'];
                        }
                    }else{
                        $detail='';
                    }
                }

                if(isset($data['relation_template_id']) || isset($data['custom_template_id']))
                {
                    $detailResponse = (new AliexpressTaskHelper())->combineRelationCustomDescription($data,$detail,$account,$code);
                    if(isset($detailResponse['result']) && $detailResponse['result']){

                        $detail = $detailResponse['data'];
                    }

                    $detail = isset($data['relation_template_id']) ? str_replace($data['relation_template_id'],'',$detail) : $detail;

                    $detail = isset($data['custom_template_id']) ? str_replace($data['custom_template_id'],'',$detail) : $detail;

                }

                if(isset($data['mobile_detail']))
                {
                    $mobileDetail =$data['mobile_detail']?(new AliexpressTaskHelper())->managerMobileDetail($data['mobile_detail'],$account, $code):'';

                }else{
                    $mobileDetail =$product_info['mobile_detail']?(new AliexpressTaskHelper())->managerMobileDetail($product_info['mobile_detail'],$account, $code):'';
                }

                $aeopAeProductSKUs=[];


                if(isset($data['sku']))
                {
                    $goodsSkuMapService = new GoodsSkuMapService();
                    $productSkuModel = new AliexpressProductSku();

                    foreach ($data['sku'] as $k=>$sku)
                    {
                        $sku = (is_object($sku))?$sku->toArray():$sku;
                        $aeopAeProductSKUs[$k]['skuPrice']=$sku['sku_price'];

                        $sku_code = $sku['sku_code'];

                        $createRandSkuArray=[
                            'sku_code'=>$sku['sku_code'],
                            'channel_id'=>4,
                            'account_id'=>$account['id'],
                            'is_virtual_send' => isset($product['virtual_send']) ? $product['virtual_send'] : 0,
                        ];

                        if(isset($sku['combine_sku']) && !empty($sku['combine_sku']))
                        {
                            $createRandSkuArray['combine_sku']=$sku['combine_sku'];
                            $newSku = $goodsSkuMapService->addSkuCodeWithQuantity($createRandSkuArray,$product['publisher_id']);
                        }else{
                            $newSku = $goodsSkuMapService->addSku($createRandSkuArray,$product['publisher_id']);
                        }

                        if(isset($newSku['sku_code']) && $newSku['sku_code']) {
                            $sku_code = $newSku['sku_code'];
                        }

                        $aeopAeProductSKUs[$k]['skuCode']=$sku_code;
                        $aeopAeProductSKUs[$k]['skuStock']=$sku['sku_stock']?true:false;
                        $aeopAeProductSKUs[$k]['ipmSkuStock']=$sku['ipm_sku_stock'];
                        $aeopAeProductSKUs[$k]['currencyCode']=$sku['currency_code'];
                        $aeopSKUProperty=[];
                        $skuAttrVal = json_decode($sku['sku_attr'],true);
                        if($skuAttrVal)
                        {
                            foreach ($skuAttrVal as $k1=>$val)
                            {
                                $aeopSKUProperty[$k1]['skuPropertyId']=$val['skuPropertyId'];


                                if(isset($val['propertyValueDefinitionName']) && $val['propertyValueDefinitionName'])
                                {
                                    $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];
                                    $aeopSKUProperty[$k1]['propertyValueDefinitionName']=$val['propertyValueDefinitionName'];
                                }else{
                                    $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];
                                }

                                if(isset($val['skuImage']) && $val['skuImage'])
                                {
                                    if(strpos($val['skuImage'],'alicdn.com')==false)
                                    {
                                        $image_type['type'] = self::$product_image_type['sku'];
                                        $skuImage = (new AliexpressTaskHelper())->uploadOneImage($account,$val['skuImage'],$code, $image_type);
                                        if($skuImage)
                                        {
                                            $aeopSKUProperty[$k1]['skuImage']=$skuImage;
                                        }
                                    }else{
                                        $aeopSKUProperty[$k1]['skuImage']=$val['skuImage'];
                                    }
                                }
                            }
                        }
                        $aeopAeProductSKUs[$k]['aeopSKUProperty']=$aeopSKUProperty;
                    }
                }else{
                    if($skus)
                    {
                        $goodsSkuMapService = new GoodsSkuMapService();

                        foreach ($skus as $k=>$sku)
                        {
                            $sku = (is_object($sku))?$sku->toArray():$sku;
                            $aeopAeProductSKUs[$k]['skuPrice']=$sku['sku_price'];

                            $sku_code = $sku['sku_code'];

                            $createRandSkuArray=[
                                'sku_code'=>$sku['sku_code'],
                                'channel_id'=>4,
                                'account_id'=>$account['id'],
                                'is_virtual_send' => isset($product['virtual_send']) ? $product['virtual_send'] : 0,
                            ];

                            if(isset($sku['combine_sku']) && !empty($sku['combine_sku']))
                            {
                                $createRandSkuArray['combine_sku']=$sku['combine_sku'];
                                $newSku = $goodsSkuMapService->addSkuCodeWithQuantity($createRandSkuArray,$product['publisher_id']);
                            }else{
                                $newSku = $goodsSkuMapService->addSku($createRandSkuArray,$product['publisher_id']);
                            }

                            $sku_code = isset($newSku['sku_code']) && $newSku['sku_code'] ? $newSku['sku_code'] : $sku_code;

                            $aeopAeProductSKUs[$k]['skuCode']=$sku_code;
                            $aeopAeProductSKUs[$k]['skuStock']=$sku['sku_stock']?true:false;
                            $aeopAeProductSKUs[$k]['ipmSkuStock']=$sku['ipm_sku_stock'];
                            $aeopAeProductSKUs[$k]['currencyCode']=$sku['currency_code'];
                            $aeopSKUProperty=[];
                            $skuAttrVal = json_decode($sku['sku_attr'],true);
                            if($skuAttrVal)
                            {
                                foreach ($skuAttrVal as $k1=>$val)
                                {
                                    $aeopSKUProperty[$k1]['skuPropertyId']=$val['skuPropertyId'];

                                    if(isset($val['propertyValueDefinitionName']) && $val['propertyValueDefinitionName'])
                                    {
                                        $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];
                                        $aeopSKUProperty[$k1]['propertyValueDefinitionName']=$val['propertyValueDefinitionName'];
                                    }else{
                                        $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];
                                    }

                                    if(isset($val['skuImage']) && $val['skuImage'])
                                    {
                                        if(strpos($val['skuImage'],'alicdn.com')==false)
                                        {
                                            $image_type['type'] = self::$product_image_type['sku'];
                                            $skuImage = (new AliexpressTaskHelper())->uploadOneImage($account,$val['skuImage'],$code, $image_type);
                                            if($skuImage)
                                            {
                                                $aeopSKUProperty[$k1]['skuImage']=$skuImage;
                                            }
                                        }else{
                                            $aeopSKUProperty[$k1]['skuImage']=$val['skuImage'];
                                        }
                                    }
                                }
                            }
                            $aeopAeProductSKUs[$k]['aeopSKUProperty']=$aeopSKUProperty;
                        }
                    }
                }


                $image_type['type'] = self::$product_image_type['product'];
                $post=[
                    'productId'=>$product['product_id'],//产品ID
                    'subject'=>isset($data['subject'])?$data['subject']:$product['subject'], //标题
                    'detail'=>$detail, //详情描述
                    'aeopAeProductSKUs'=>json_encode($aeopAeProductSKUs), //sku
                    'aeopAeProductPropertys'=>isset($data['product_attr'])?$data['product_attr']:$product_info['product_attr'],//产品属性，以json格式进行封装后提交
                    'categoryId'=>isset($data['category_id'])?$data['category_id']:$product['category_id'], //分类id
                    'imageURLs'=>(new AliexpressTaskHelper)->uploadImage($account,$product['imageurls'],$code, $image_type), //产品的主图URL列表
                ];

                //手机详情

                if($mobileDetail)
                {
                    $post['mobileDetail']=$mobileDetail;
                }

                //货币单位
                $post['currencyCode']=$product['currency_code'];

                //备货期，非必填
                if(isset($data['delivery_time']) && $data['delivery_time'])
                {
                    $post['deliveryTime'] = $data['delivery_time'];
                }else{
                    $post['deliveryTime'] = $product['delivery_time'];
                }


                if(isset($data['gross_weight']) && $data['gross_weight'])
                {
                    $post['grossWeight'] = $data['gross_weight'];
                }else{
                    $post['grossWeight'] = $product['gross_weight'];
                }

                //服务模板设置
                if(isset($data['promise_template_id']) && $data['promise_template_id'])
                {
                    $post['promiseTemplateId'] = $data['promise_template_id'];
                }else{

                    $post['promiseTemplateId'] = $product['promise_template_id'] ? $product['promise_template_id'] :0;
                }

                ////商品一口价
                if(isset($data['product_price']) && $data['product_price'])
                {
                    $post['productPrice'] = number_format($data['product_price'],2);
                }else{
                    $post['productPrice'] = number_format($product['product_price'],2);

                }

                //运费模版ID
                if(isset($data['freight_template_id']))
                {
                    $post['freightTemplateId'] = $data['freight_template_id'];
                }else{
                    $post['freightTemplateId'] = $product['freight_template_id'];
                }


                //商品单位 (存储单位编号)
                if(isset($data['product_unit']))
                {
                    $post['productUnit'] = $data['product_unit'];
                }else{
                    $post['productUnit'] = $product['product_unit'];
                }

                //打包销售: true 非打包销售:false,
                if(isset($data['package_type']))
                {
                    if($data['package_type'])
                    {
                        $post['packageType'] = 'true';
                        //每包件数。 打包销售情况，lotNum>1,非打包销售情况,lotNum=1
                        $post['lotNum']=$data['lot_num'];
                    }else{
                        $post['packageType'] = 'false';
                        $post['lotNum']=1;
                    }
                }else{
                    if($product['package_type'])
                    {
                        $post['packageType'] = 'true';
                        //每包件数。 打包销售情况，lotNum>1,非打包销售情况,lotNum=1
                        $post['lotNum']=$product['lot_num'];
                    }else{
                        $post['packageType'] = 'false';
                        $post['lotNum']=1;
                    }
                }

                //包装长宽高
                if(isset($data['package_length']))
                {
                    $post['packageLength'] = (int)$data['package_length'];
                }else{
                    $post['packageLength'] = (int)$product['package_length'];
                }

                if(isset($data['package_width']))
                {
                    $post['packageWidth'] = (int)$data['package_width'];
                }else{
                    $post['packageWidth'] = (int)$product['package_width'];
                }

                if(isset($data['package_height']))
                {
                    $post['packageHeight'] = (int)$data['package_height'];
                }else{
                    $post['packageHeight'] = (int)$product['package_height'];
                }

                if(isset($data['is_pack_sell']))
                {
                    if($data['is_pack_sell'])
                    {
                        $post['isPackSell'] = 'true';
                        $post['baseUnit'] = $data['base_unit'];
                        $post['addUnit']=$data['add_unit'];
                        $post['addWeight']=$data['add_weight'];
                    }else{
                        $post['isPackSell'] = 'false';
                    }
                }else{
                    if($product['is_pack_sell'])
                    {
                        $post['isPackSell'] = 'true';
                        $post['baseUnit'] = $product['base_unit'];
                        $post['addUnit']=$product['add_unit'];
                        $post['addWeight']=$product['add_weight'];
                    }else{
                        $post['isPackSell'] = 'false';
                    }
                }
                //商品有效天数。取值范围:1-30,单位:天

                if(isset($data['ws_valid_num']))
                {
                    $post['wsValidNum']=$data['ws_valid_num'];
                }else{
                    $post['wsValidNum']=$product['ws_valid_num'];
                }

                //批发最小数量，批发折扣,取值范围:1-99
                if(isset($data['bulk_order']) && isset($data['bulk_discount']))
                {
                    $post['bulkOrder']=$data['bulk_order'];
                    $post['bulkDiscount']=$data['bulk_discount'];
                }else{
                    if($product['bulk_discount'] && $product['bulk_order'])
                    {
                        $post['bulkOrder']=$product['bulk_order'];
                        $post['bulkDiscount']=$product['bulk_discount'];
                    }

                }
                //尺码表模版ID
                if(isset($data['sizechart_id']))
                {
                    $post['sizechartId']=$data['sizechart_id'];
                }

                //库存扣减策略，总共有2种：下单减库存(place_order_withhold)和支付减库存(payment_success_deduct)。
                if(isset($data['reduce_strategy']))
                {
                    if($data['reduce_strategy']==1)
                    {
                        $post['reduceStrategy']='place_order_withhold';
                    }elseif($data['reduce_strategy']==2){
                        $post['reduceStrategy']='payment_success_deduct';
                    }
                }else{
                    if($product['reduce_strategy']==1)
                    {
                        $post['reduceStrategy']='place_order_withhold';
                    }elseif($product['reduce_strategy']==2){
                        $post['reduceStrategy']='payment_success_deduct';
                    }
                }


                //产品分组ID
                if(isset($data['group_id']) && is_numeric($data['group_id']))
                {
                    $post['groupId']=$data['group_id'];
                }

                //货币单位
                if(isset($data['currency_code']))
                {
                    $post['currencyCode']=$data['currency_code'];
                }else{
                    $post['currencyCode']=$product['currency_code'];
                }



                //卡券商品开始有效期
                if($product['coupon_start_date'])
                {
                    $post['couponStartDate']=$product['coupon_start_date'];
                }

                //卡券商品结束有效期
                if($product['coupon_end_date'])
                {
                    $post['couponEndDate']=$product['coupon_end_date'];
                }

                $post['module']='product';
                $post['class']='product';
                $post['action']='editaeproduct';

                $params=array_merge(self::$config ,$post);

                $response = AliexpressService::execute(snakeArray($params));
                if(isset($response['productId']) && $response['productId'])
                {
                    $log['status']=1;
                    $log['message']="";
                    (new AliexpressListingHelper())->findAeProductById($product['product_id']);
                }elseif(isset($response['error_code']) && $response['error_code']=='13001030'){
                    //产品处于活动中，产品属性、产品标题、产品详情描述、产品有效期
                    //$response = self::editAeProductWithoutPrice($must,$model_info,$accountInfo);
                    $data['productId']=$product['product_id'];
                    $data['categoryId']=$product['category_id'];

                    $response = self::specialEdit($data,$account,$product);

                    if(isset($response['success']) && $response['success'] && isset($response['productId']))
                    {
                        $log['status']=1;
                        $log['message']="该商品正在进行活动,只能更改:标题,描述,产品分组,手机详情,库存扣减策略,商品的有效天数";
                        (new CommonQueuer(AliexpressRsyncProductQueue::class))->push($response['productId']);
                    }else{
                        if(isset($response['error_message']) && isset($response['error_code']))
                        {
                            $log['status']=2;
                            $log['message']=json_encode(['error_message'=>$response['error_message'],'error_code'=>$response['error_code']]);
                        }elseif(isset($response['error_message'])){
                            $log['message']=json_encode(['error_message'=>$response['error_message']]);
                            $log['status']=2;
                        }else{
                            $log['status']=2;
                            $log['message']="";
                        }
                    }
                }else{
                    $log['status']=2;
                    $log['message']=json_encode(['error_message'=>$response['error_message'],'error_code'=>$response['error_code']]);
                }
                $log['run_time']=time();
                (new AliexpressActionLog())->update($log,['id'=>$job['id']]);

                $virtual_send = $log['status'] == 1 ? $virtual_send : 0;


                if($log['status'] == 1) {
                    self::rsyncEditAeProductInfo($job['new_data'], $product['product_id']);
                }

                (new self())->updateStatus($product['product_id'],$log['status'], $virtual_send);
            }
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * 商品编辑
     */
    public static function editAeProduct1($job)
    {
        try{


            self::$config = $job['product']['account'];

            $post['productId']=$job['product_id'];

            $data = $job['new_data'];

            //商品信息基本
            $model_info = (new AliexpressProduct())->where(['product_id'=>$job['product_id']])->find();

            //是否是海外仓
            $virtual_send = isset($data['virtual_send']) ? $data['virtual_send'] : 0;

            if($model_info && $data)
            {
                //账号
                $account = $model_info->account->toArray();

                //商品sku
                $skus = $model_info->productSku;

                $code = $account['code'];

                $product = $model_info->toArray();

                if(isset($data['aeop_national_quote_configuration']) && $data['aeop_national_quote_configuration'] && isset($data['configuration_type']))
                {
                    $post['aeopNationalQuoteConfiguration'] = (new AliexpressTaskHelper())->managerNationalQuoteConfiguration($data['aeop_national_quote_configuration'],$data['configuration_type']);
                }

                if(isset($data['imageurls']) && $data['imageurls'])
                {
                    $product['imageurls']=$data['imageurls'];
                }else{
                    if($productImage = $model_info->getData('imageurls'))
                    {
                        $product['imageurls']=$productImage;
                    }
                }


                //商品信息
                $product_info = $model_info->productInfo->toArray();

                $marketImages = $product_info['market_images'];

                if($marketImages) {
                    $marketImages = \GuzzleHttp\json_decode($marketImages, true);

                    $marketImageUrl = $marketImages['url'];
                    $imageURLsResponse = (new AliexpressTaskHelper())->uploadOneImage($account, $marketImageUrl, $code, 0);

                    if(isset($imageURLsResponse) && $imageURLsResponse) {
                        $marketImageUrl = $imageURLsResponse;

                    }

                    $marketImages['url'] = $marketImageUrl;

                    $post['marketImages'] = json_encode($marketImages);
                }


                $product['detail']=$product_info['detail'];

                //处理描述详情里面的图片
                if(isset($data['detail']))
                {
                    $detailResponse = (new AliexpressTaskHelper())->managerDetail($data['detail'],$account,$code);
                    if(isset($detailResponse['result']) && $detailResponse['result']){
                        $detail = $detailResponse['data'];
                    }
                }else{
                    if(isset($product_info['detail']) && $product_info['detail'])
                    {
                        $detail=$product_info['detail'];
                        $detailResponse = (new AliexpressTaskHelper())->managerDetail($detail,$account,$code);
                        if(isset($detailResponse['result']) && $detailResponse['result']){
                            $detail = $detailResponse['data'];
                        }
                    }else{
                        $detail='';
                    }
                }

                if(isset($data['relation_template_id']) || isset($data['custom_template_id']))
                {
                    $detailResponse = (new AliexpressTaskHelper())->combineRelationCustomDescription($data,$detail,$account,$code);
                    if(isset($detailResponse['result']) && $detailResponse['result']){

                        $detail = $detailResponse['data'];
                    }

                    $detail = isset($data['relation_template_id']) ? str_replace($data['relation_template_id'],'',$detail) : $detail;

                    $detail = isset($data['custom_template_id']) ? str_replace($data['custom_template_id'],'',$detail) : $detail;

                }

                if(isset($data['mobile_detail']))
                {
                    $mobileDetail =$data['mobile_detail']?(new AliexpressTaskHelper())->managerMobileDetail($data['mobile_detail'],$account, $code):'';

                }else{
                    $mobileDetail =$product_info['mobile_detail']?(new AliexpressTaskHelper())->managerMobileDetail($product_info['mobile_detail'],$account, $code):'';
                }

                $aeopAeProductSKUs=[];


                if(isset($data['sku']))
                {
                    $goodsSkuMapService = new GoodsSkuMapService();
                    $productSkuModel = new AliexpressProductSku();

                    foreach ($data['sku'] as $k=>$sku)
                    {
                        $sku = (is_object($sku))?$sku->toArray():$sku;
                        $aeopAeProductSKUs[$k]['skuPrice']=$sku['sku_price'];

                        $sku_code = $sku['sku_code'];

                       /* $createRandSkuArray=[
                            'sku_code'=>$sku['sku_code'],
                            'channel_id'=>4,
                            'account_id'=>$account['id'],
                            'is_virtual_send' => isset($product['virtual_send']) ? $product['virtual_send'] : 0,
                        ];

                        if(isset($sku['combine_sku']) && !empty($sku['combine_sku']))
                        {
                            $createRandSkuArray['combine_sku']=$sku['combine_sku'];
                            $newSku = $goodsSkuMapService->addSkuCodeWithQuantity($createRandSkuArray,$product['publisher_id']);
                        }else{
                            $newSku = $goodsSkuMapService->addSku($createRandSkuArray,$product['publisher_id']);
                        }

                        if(isset($newSku['sku_code']) && $newSku['sku_code']) {
                            $sku_code = $newSku['sku_code'];
                        }*/

                        $aeopAeProductSKUs[$k]['skuPrice']=$sku['sku_price'];
                        $aeopAeProductSKUs[$k]['skuCode']=$sku_code;
                        $aeopAeProductSKUs[$k]['ipmSkuStock']=$sku['ipm_sku_stock'];
                        $aeopAeProductSKUs[$k]['currencyCode']=$sku['currency_code'];
                        $aeopSKUProperty=[];
                        $skuAttrVal = json_decode($sku['sku_attr'],true);
                        if($skuAttrVal)
                        {
                            foreach ($skuAttrVal as $k1=>$val)
                            {
                                $aeopSKUProperty[$k1]['skuPropertyId']=$val['skuPropertyId'];


                                if(isset($val['propertyValueDefinitionName']) && $val['propertyValueDefinitionName'])
                                {
                                    $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];
                                    $aeopSKUProperty[$k1]['propertyValueDefinitionName']=$val['propertyValueDefinitionName'];
                                }else{
                                    $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];
                                }

                                if(isset($val['skuImage']) && $val['skuImage'])
                                {
                                    if(strpos($val['skuImage'],'alicdn.com')==false)
                                    {
                                         $skuImage = (new AliexpressTaskHelper())->uploadOneImage($account,$val['skuImage'],$code);
                                         if($skuImage)
                                         {
                                             $aeopSKUProperty[$k1]['skuImage']=$skuImage;
                                         }
                                    }else{
                                        $aeopSKUProperty[$k1]['skuImage']=$val['skuImage'];
                                    }
                                }
                            }
                        }
                        $aeopAeProductSKUs[$k]['aeopSKUProperty']=$aeopSKUProperty;
                    }
                }else{
                    if($skus)
                    {
                        $goodsSkuMapService = new GoodsSkuMapService();

                        foreach ($skus as $k=>$sku)
                        {
                            $sku = (is_object($sku))?$sku->toArray():$sku;
                            //$aeopAeProductSKUs[$k]['id']=$sku['merchant_sku_id'];
                            $aeopAeProductSKUs[$k]['skuPrice']=$sku['sku_price'];

                            $sku_code = $sku['sku_code'];
                            $createRandSkuArray=[
                                'sku_code'=>$sku['sku_code'],
                                'channel_id'=>4,
                                'account_id'=>$account['id'],
                                'is_virtual_send' => isset($product['virtual_send']) ? $product['virtual_send'] : 0,
                            ];

                            if(isset($sku['combine_sku']) && !empty($sku['combine_sku']))
                            {
                                $createRandSkuArray['combine_sku']=$sku['combine_sku'];
                                $newSku = $goodsSkuMapService->addSkuCodeWithQuantity($createRandSkuArray,$product['publisher_id']);
                            }else{
                                $newSku = $goodsSkuMapService->addSku($createRandSkuArray,$product['publisher_id']);
                            }

                            $sku_code = isset($newSku['sku_code']) && $newSku['sku_code'] ? $newSku['sku_code'] : $sku_code;


                            $aeopAeProductSKUs[$k]['skuPrice']=$sku['sku_price'];
                            $aeopAeProductSKUs[$k]['skuCode']=$sku_code;
                            $aeopAeProductSKUs[$k]['ipmSkuStock']=$sku['ipm_sku_stock'];
                            $aeopAeProductSKUs[$k]['currencyCode']=$sku['currency_code'];
                            $aeopSKUProperty=[];
                            $skuAttrVal = json_decode($sku['sku_attr'],true);
                            if($skuAttrVal)
                            {
                                foreach ($skuAttrVal as $k1=>$val)
                                {
                                    $aeopSKUProperty[$k1]['skuPropertyId']=$val['skuPropertyId'];

                                    if(isset($val['propertyValueDefinitionName']) && $val['propertyValueDefinitionName'])
                                    {
                                        $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];
                                        $aeopSKUProperty[$k1]['propertyValueDefinitionName']=$val['propertyValueDefinitionName'];
                                    }else{
                                        $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];
                                    }

                                    if(isset($val['skuImage']) && $val['skuImage'])
                                    {
                                        if(strpos($val['skuImage'],'alicdn.com')==false)
                                        {
                                            $skuImage = (new AliexpressTaskHelper())->uploadOneImage($account,$val['skuImage'],$code);
                                            if($skuImage)
                                            {
                                                $aeopSKUProperty[$k1]['skuImage']=$skuImage;
                                            }
                                        }else{
                                            $aeopSKUProperty[$k1]['skuImage']=$val['skuImage'];
                                        }
                                    }
                                }
                            }
                            $aeopAeProductSKUs[$k]['aeopSKUProperty']=$aeopSKUProperty;
                        }
                    }
                }


                $detailSourceList = ['locale' => true, 'mobile_detail' =>$mobileDetail, 'web_detail' => $detail];

                $subject = isset($data['subject'])?$data['subject']:$product['subject']; //标题
                $subjectList = ['locale' => true, 'value' => $subject];

                $post=[
                    'productId'=>$product['product_id'],//产品ID
                    'subjectList'=>json_encode($subjectList), //标题
                    'detailSourceList'=>json_encode($detailSourceList), //详情描述
                    'aeopAeProductSKUs'=>json_encode($aeopAeProductSKUs), //sku
                    'aeopAeProductPropertys'=>isset($data['product_attr'])?$data['product_attr']:$product_info['product_attr'],//产品属性，以json格式进行封装后提交
                    'categoryId'=>isset($data['category_id'])?$data['category_id']:$product['category_id'], //分类id
                    'imageURLs'=>(new AliexpressTaskHelper)->uploadImage($account,$product['imageurls'],$code), //产品的主图URL列表
                    'locale' => true
                ];


                //货币单位
                $post['currencyCode']=$product['currency_code'];

                //备货期，非必填
                if(isset($data['delivery_time']) && $data['delivery_time'])
                {
                    $post['deliveryTime'] = $data['delivery_time'];
                }else{
                    $post['deliveryTime'] = $product['delivery_time'];
                }


                if(isset($data['gross_weight']) && $data['gross_weight'])
                {
                    $post['grossWeight'] = $data['gross_weight'];
                }else{
                    $post['grossWeight'] = $product['gross_weight'];
                }

                //服务模板设置
                if(isset($data['promise_template_id']) && $data['promise_template_id'])
                {
                    $post['promiseTemplateId'] = $data['promise_template_id'];
                }else{

                    $post['promiseTemplateId'] = $product['promise_template_id'] ? $product['promise_template_id'] :0;
                }

                ////商品一口价
                if(isset($data['product_price']) && $data['product_price'])
                {
                    $post['productPrice'] = number_format($data['product_price'],2);
                }else{
                    $post['productPrice'] = number_format($product['product_price'],2);

                }

                //运费模版ID
                if(isset($data['freight_template_id']))
                {
                    $post['freightTemplateId'] = $data['freight_template_id'];
                }else{
                    $post['freightTemplateId'] = $product['freight_template_id'];
                }


                //商品单位 (存储单位编号)
                if(isset($data['product_unit']))
                {
                    $post['productUnit'] = $data['product_unit'];
                }else{
                    $post['productUnit'] = $product['product_unit'];
                }

                //打包销售: true 非打包销售:false,
                if(isset($data['package_type']))
                {
                    if($data['package_type'])
                    {
                        $post['packageType'] = 'true';
                        //每包件数。 打包销售情况，lotNum>1,非打包销售情况,lotNum=1
                        $post['lotNum']=$data['lot_num'];
                    }else{
                        $post['packageType'] = 'false';
                        $post['lotNum']=1;
                    }
                }else{
                    if($product['package_type'])
                    {
                        $post['packageType'] = 'true';
                        //每包件数。 打包销售情况，lotNum>1,非打包销售情况,lotNum=1
                        $post['lotNum']=$product['lot_num'];
                    }else{
                        $post['packageType'] = 'false';
                        $post['lotNum']=1;
                    }
                }

                //包装长宽高
                if(isset($data['package_length']))
                {
                    $post['packageLength'] = (int)$data['package_length'];
                }else{
                    $post['packageLength'] = (int)$product['package_length'];
                }

                if(isset($data['package_width']))
                {
                    $post['packageWidth'] = (int)$data['package_width'];
                }else{
                    $post['packageWidth'] = (int)$product['package_width'];
                }

                if(isset($data['package_height']))
                {
                    $post['packageHeight'] = (int)$data['package_height'];
                }else{
                    $post['packageHeight'] = (int)$product['package_height'];
                }

                if(isset($data['is_pack_sell']))
                {
                    if($data['is_pack_sell'])
                    {
                        $post['isPackSell'] = 'true';
                        $post['baseUnit'] = $data['base_unit'];
                        $post['addUnit']=$data['add_unit'];
                        $post['addWeight']=$data['add_weight'];
                    }else{
                        $post['isPackSell'] = 'false';
                    }
                }else{
                    if($product['is_pack_sell'])
                    {
                        $post['isPackSell'] = 'true';
                        $post['baseUnit'] = $product['base_unit'];
                        $post['addUnit']=$product['add_unit'];
                        $post['addWeight']=$product['add_weight'];
                    }else{
                        $post['isPackSell'] = 'false';
                    }
                }
                //商品有效天数。取值范围:1-30,单位:天

                if(isset($data['ws_valid_num']))
                {
                    $post['wsValidNum']=$data['ws_valid_num'];
                }else{
                    $post['wsValidNum']=$product['ws_valid_num'];
                }

                //批发最小数量，批发折扣,取值范围:1-99
                if(isset($data['bulk_order']) && isset($data['bulk_discount']))
                {
                    $post['bulkOrder']=$data['bulk_order'];
                    $post['bulkDiscount']=$data['bulk_discount'];
                }else{
                    if($product['bulk_discount'] && $product['bulk_order'])
                    {
                        $post['bulkOrder']=$product['bulk_order'];
                        $post['bulkDiscount']=$product['bulk_discount'];
                    }

                }
                //尺码表模版ID
                if(isset($data['sizechart_id']))
                {
                    $post['sizechartId']=$data['sizechart_id'];
                }

                //库存扣减策略，总共有2种：下单减库存(place_order_withhold)和支付减库存(payment_success_deduct)。
                if(isset($data['reduce_strategy']))
                {
                    if($data['reduce_strategy']==1)
                    {
                        $post['reduceStrategy']='place_order_withhold';
                    }elseif($data['reduce_strategy']==2){
                        $post['reduceStrategy']='payment_success_deduct';
                    }
                }else{
                    if($product['reduce_strategy']==1)
                    {
                        $post['reduceStrategy']='place_order_withhold';
                    }elseif($product['reduce_strategy']==2){
                        $post['reduceStrategy']='payment_success_deduct';
                    }
                }


                //产品分组ID
                if(isset($data['group_id']) && is_numeric($data['group_id']))
                {
                    $post['groupId']=$data['group_id'];
                }

                //货币单位
                if(isset($data['currency_code']))
                {
                    $post['currencyCode']=$data['currency_code'];
                }else{
                    $post['currencyCode']=$product['currency_code'];
                }



                //卡券商品开始有效期
                if($product['coupon_start_date'])
                {
                    $post['couponStartDate']=$product['coupon_start_date'];
                }

                //卡券商品结束有效期
                if($product['coupon_end_date'])
                {
                    $post['couponEndDate']=$product['coupon_end_date'];
                }

                $post['module']='product';
                $post['class']='product';
                $post['action']='productedit';

                $params=array_merge(self::$config ,$post);

                $response = AliexpressService::execute1(snakeArray($params));
                print_r($response);
                exit;
                if(isset($response['productId']) && $response['productId'])
                {
                    $log['status']=1;
                    $log['message']="";
                    (new AliexpressListingHelper())->findAeProductById($product['product_id']);
                }elseif(isset($response['error_code']) && $response['error_code']=='13001030'){
                    //产品处于活动中，产品属性、产品标题、产品详情描述、产品有效期
                    //$response = self::editAeProductWithoutPrice($must,$model_info,$accountInfo);
                    $data['productId']=$product['product_id'];
                    $data['categoryId']=$product['category_id'];

                    $response = self::specialEdit($data,$account,$product);

                    if(isset($response['success']) && $response['success'] && isset($response['productId']))
                    {
                        $log['status']=1;
                        $log['message']="该商品正在进行活动,只能更改:标题,描述,产品分组,手机详情,库存扣减策略,商品的有效天数";
                        (new CommonQueuer(AliexpressRsyncProductQueue::class))->push($response['productId']);
                    }else{
                        if(isset($response['error_message']) && isset($response['error_code']))
                        {
                            $log['status']=2;
                            $log['message']=json_encode(['error_message'=>$response['error_message'],'error_code'=>$response['error_code']]);
                        }elseif(isset($response['error_message'])){
                            $log['message']=json_encode(['error_message'=>$response['error_message']]);
                            $log['status']=2;
                        }else{
                            $log['status']=2;
                            $log['message']="";
                        }
                    }
                }else{
                    $log['status']=2;
                    $log['message']=json_encode(['error_message'=>$response['error_message'],'error_code'=>$response['error_code']]);
                }
                $log['run_time']=time();
                (new AliexpressActionLog())->update($log,['id'=>$job['id']]);

                $virtual_send = $log['status'] == 1 ? $virtual_send : 0;


                if($log['status'] == 1) {
                    self::rsyncEditAeProductInfo($job['new_data'], $product['product_id']);
                }

                (new self())->updateStatus($product['product_id'],$log['status'], $virtual_send);
            }
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }


    /**
     *同步编辑erp本地刊登信息
     *
     */
    public static  function rsyncEditAeProductInfo($data, $product_id)
    {

        $update_data = [];
        if(isset($data['relation_template_id'])) {
            $update_data['relation_template_id'] = $data['relation_template_id'];
        }

        if(isset($data['relation_template_postion'])) {
            $update_data['relation_template_postion'] = $data['relation_template_postion'];
        }

        if(isset($data['custom_template_id'])) {
            $update_data['custom_template_id'] = $data['custom_template_id'];
        }

        if(isset($data['custom_template_postion'])) {
            $update_data['custom_template_postion'] = $data['custom_template_postion'];
        }


        if($update_data) {
            (new AliexpressProduct())->update($update_data, ['product_id' => $product_id]);
        }

        if(isset($data['detail']) && $data['detail']) {
            (new AliexpressProductInfo())->update(['detail' => $data['detail']], ['product_id' => $product_id]);
        }
    }


    /**
     * 商品编辑
     */
    public static function editAeProductWithoutPrice($post,$product,$account)
    {
        try{

            $images = $product->getData('imageurls');

            $product = $product->toArray();

            self::$config = $account;

            $service = (new self())->common();

            $aeopAeProductSKUs=[];

            $skus = $product['productSku'];

            if($skus)
            {
                foreach ($skus as $k=>$sku)
                {
                    $sku = (is_object($sku))?$sku->toArray():$sku;
                    //$aeopAeProductSKUs[$k]['id']=$sku['merchant_sku_id'];
                    $aeopAeProductSKUs[$k]['skuPrice']=$sku['sku_price'];
                    $aeopAeProductSKUs[$k]['skuCode']=$sku['sku_code'];
                    $aeopAeProductSKUs[$k]['skuStock']=$sku['sku_stock']?true:false;
                    $aeopAeProductSKUs[$k]['ipmSkuStock']=$sku['ipm_sku_stock'];
                    $aeopAeProductSKUs[$k]['currencyCode']=$sku['currency_code'];
                    $aeopSKUProperty=[];
                    $skuAttrVal = json_decode($sku['sku_attr'],true);
                    if($skuAttrVal)
                    {
                        foreach ($skuAttrVal as $k1=>$val)
                        {
                            $aeopSKUProperty[$k1]['skuPropertyId']=$val['skuPropertyId'];

                            if(isset($val['propertyValueDefinitionName']) && $val['propertyValueDefinitionName'])
                            {
                                $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];
                                $aeopSKUProperty[$k1]['propertyValueDefinitionName']=$val['propertyValueDefinitionName'];
                            }else{
                                $aeopSKUProperty[$k1]['propertyValueId']=$val['propertyValueId'];
                            }

                            if(isset($val['skuImage']) && $val['skuImage'])
                            {
                                if(strpos($val['skuImage'],'alicdn.com')==false)
                                {
                                    $skuImage = (new AliexpressTaskHelper())->uploadOneImage($api,$val['skuImage'],$code);
                                    if($skuImage)
                                    {
                                        $aeopSKUProperty[$k1]['skuImage']=$skuImage;
                                    }
                                }else{
                                    $aeopSKUProperty[$k1]['skuImage']=$val['skuImage'];
                                }
                            }
                        }
                    }
                    $aeopAeProductSKUs[$k]['aeopSKUProperty']=$aeopSKUProperty;
                }
            }

            $post['aeopAeProductSKUs']=json_encode($aeopAeProductSKUs);

//            $post['deliveryTime'] = $product['delivery_time'];
//            //$post['grossWeight'] = $product['gross_weight'];
////            if($product['promise_template_id']){
////                $post['promiseTemplateId'] = $product['promise_template_id'];
////            }
//            //$post['productPrice'] = number_format($product['product_price'],2);
//
////            $post['freightTemplateId'] = $product['freight_template_id'];
//
//            $post['productUnit'] = $product['product_unit'];
//            if($product['package_type'])
//            {
//                $post['packageType'] = 'true';
//                //每包件数。 打包销售情况，lotNum>1,非打包销售情况,lotNum=1
//                $post['lotNum']=$product['lot_num'];
//            }else{
//                $post['packageType'] = 'false';
//                $post['lotNum']=1;
//            }
//
////            $post['packageLength'] = (int)$product['package_length'];
////            $post['packageWidth'] = (int)$product['package_width'];
////            $post['packageHeight'] = (int)$product['package_height'];
//
//            if($product['is_pack_sell'])
//            {
//                $post['isPackSell'] = 'true';
//                $post['baseUnit'] = $product['base_unit'];
//                $post['addUnit']=$product['add_unit'];
//                $post['addWeight']=$product['add_weight'];
//            }else{
//                $post['isPackSell'] = 'false';
//            }
//
//            $post['wsValidNum']=$product['ws_valid_num'];
//
//            //批发最小数量，批发折扣,取值范围:1-99
//            if($product['bulk_discount'] && $product['bulk_order'])
//            {
//                $post['bulkOrder']=$product['bulk_order'];
//                $post['bulkDiscount']=$product['bulk_discount'];
//            }
//
//            //尺码表模版ID
//            if(isset($product['sizechart_id']) && $product['sizechart_id'])
//            {
//                $post['sizechartId']=$product['sizechart_id'];
//            }
//
//            //库存扣减策略，总共有2种：下单减库存(place_order_withhold)和支付减库存(payment_success_deduct)。
//            if($product['reduce_strategy']==1)
//            {
//                $post['reduceStrategy']='place_order_withhold';
//            }elseif($product['reduce_strategy']==2){
//                $post['reduceStrategy']='payment_success_deduct';
//            }
//
//            //产品分组ID
//            if(isset($product['group_id']) && $product['group_id'] && !is_json($product['group_id']))
//            {
//                $post['groupId']=$product['group_id'];
//            }
//
//            if(isset($product['aeop_national_quote_configuration']) && $product['aeop_national_quote_configuration'])
//            {
//                $post['aeopNationalQuoteConfiguration'] = (new AliexpressTaskHelper())->managerNationalQuoteConfiguration($product['aeop_national_quote_configuration']);
//            }
//
//            //卡券商品开始有效期
//            if($product['coupon_start_date'])
//            {
//                $post['couponStartDate']=$product['coupon_start_date'];
//            }
//
//            //卡券商品结束有效期
//            if($product['coupon_end_date'])
//            {
//                $post['couponEndDate']=$product['coupon_end_date'];
//            }


            $data=[
                'productId'=>$product['product_id'],
                'detail'=>$product['productInfo']['detail'],
                'aeopAeProductSKUs'=>$post['aeopAeProductSKUs'],
                'categoryId'=>$product['category_id'],
                'subject'=>$product['subject'],
                'imageURLs'=>$images,
                'aeopAeProductPropertys'=>$product['productInfo']['product_attr'],
                'currencyCode'=>'USD',
            ];
            dump($data);
            $response = $service->editAeProduct($data);
            dump($response);

            return $response;

        }catch (QueueException $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * 处于活动中
     * @param array $post
     */
    public static function specialEdit(array $post,$account,$product=[])
    {
        try{
            $response=[];
            $code = $account['code'];

            foreach ($post as $name=>$item)
            {

                if(in_array($name,self::CAN_EDIT_FIELDS))
                {
                    $field=self::parseName($name,1,0);

                    if ($field=='detail')
                    {

                        //如果产品关联信息模板和自定义信息模板都是0,则可以重新生成，否则不予处理
                        if((isset($post['relation_template_id']) || isset($post['custom_template_id'])) && ( $product['relation_template_id']==0 && $product['custom_template_id']==0 ))
                        {
                            $detailResponse = (new AliexpressTaskHelper())->combineRelationCustomDescription($post,$item,$account,$code,'','');
                            if(isset($detailResponse['result']) && $detailResponse['result']){
                                $item = $detailResponse['data'];
                            }
                        }
                    }elseif ($field=='mobileDetail'){
                        $item = (new AliexpressTaskHelper())->managerMobileDetail($item,$account,$code);
                    }elseif($field=='imageurls'){
                        $field = 'imageURLs';
                        $imagesResponse = (new AliexpressTaskHelper())->uploadImageURLs('','', $account,$item,$code);
                        if(isset($imagesResponse['result']) && $imagesResponse['result']){
                            $item = $imagesResponse['data'];
                        }
                    }elseif($field=='relationTemplateId'){
                        $detailResponse = (new AliexpressTaskHelper())->combineRelationCustomDescription($post,$product['detail'],$account,$code);
                        if(isset($detailResponse['result']) && $detailResponse['result']){
                            $item = $detailResponse['data'];
                        }
                        $field='detail';
                    }elseif($field=='customTemplateId'){
                        $detailResponse = (new AliexpressTaskHelper())->combineRelationCustomDescription($post,$product['detail'],$account,$code);
                        if(isset($detailResponse['result']) && $detailResponse['result']){
                            $item = $detailResponse['data'];
                        }
                        $field='detail';
                    }

                    if($item){
                        $data=[
                            'productId'=>$post['productId'],
                            'fiedName'=>$field,
                            'fiedvalue'=>$item,
                        ];

                        $response = self::editSpecialFields($data,$account);
                    }
                }elseif($name=='product_attr'){
                    $data=[
                        'productId'=>$post['productId'],
                        'categoryId'=>$post['categoryId'],
                        'productProperties'=>$post['product_attr'],
                    ];
                    $response = self::editProductCidAttIdSku($data,$account);
                }
                if($response && !isset($response['productId'])){
                    return $response;
                    break;
                }
            }
            return $response;
        }catch (\Throwable $exp){
            throw new QueueException($exp->getMessage());
        }
    }

    /**
     * 编辑产品类目、属性、sku
     * @param $post
     * @param $account
     * @return mixed
     * @throws Exception
     */
    public static function editProductCidAttIdSku($post,$account)
    {
        try{
            self::$config = $account;
            $post['module']='product';
            $post['class']='product';
            $post['action']='editproductcidattidsku';
            $params=array_merge(self::$config ,$post);
            $response = AliexpressService::execute(snakeArray($params));
            return $response;
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    public static function editSpecialFields($post,$account)
    {
        try{
            self::$config = $account;

            $post['module']='product';
            $post['class']='product';
            $post['action']='editsimpleproductfiled';
            $params=array_merge(self::$config ,$post);
            $response = AliexpressService::execute(snakeArray($params));
            return $response;
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 编辑商品的单个字段
     */
    public static function editSimpleProductFiled($product)
    {
        try{
            self::$config = $product['product']['account'];
            $productId = $product['product_id'];
            $post['productId']=$productId;
            if($product['new_data'])
            {
                foreach ($product['new_data'] as $name=>$value)
                {
                    if(is_array($value))
                    {

                    }else{
                        $post['fiedName']=lcfirst(self::parseName($name));
                        $post['fiedvalue']=$value;
                    }
                }

                $post['module']='product';
                $post['class']='product';
                $post['action']='editsimpleproductfiled';
                $post['product_id']=$productId;
                $params=array_merge(self::$config ,$post);
                $response = AliexpressService::execute(snakeArray($params));
                if(isset($response['success']) && $response['success'])
                {
                    $log['status']=1;
                    $log['message']="";
                    AliexpressProduct::update($product['new_data'],['product_id'=>$productId]);
                }else{
                    $log['status']=2;
                    $log['message']=json_encode(['error_message'=>$response['error_message'],'error_code'=>$response['error_code']]);
                }
                $log['run_time']=time();
                AliexpressActionLog::update($log,['id'=>$product['id']]);
                (new self())->updateStatus($productId,$log['status']);
            }
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * 设置单个产品的产品分组信息，最多设置三个分组
     */
    public static function setGroups($product)
    {
        self::$config = $product['product']['account'];

        $post['productId']=$product['product_id'];
        $post['groupIds']=$product['new_data']['group_id'];

        $post['module']='product';
        $post['class']='product';
        $post['action']='setgroups';

        $params=array_merge(self::$config ,$post);
        $response = AliexpressService::execute(snakeArray($params));

        if(isset($response['success']) && $response['success'])
        {
            $log['status']=1;
            $log['message']="";
        }else{
            $log['message']=json_encode(['error_message'=>$response['error_message'],'error_code'=>$response['error_code']]);
            $log['status']=2;
        }
        $log['run_time']=time();
        AliexpressActionLog::update($log,['id'=>$product['id']]);
        (new self())->updateStatus($product['product_id'],$log['status']);
    }

    /**
     * 编辑商品单个SKU的库存信息.
     */
    public static function editSingleSkuStock($product)
    {

        try{


            self::$config = $product['product']['account'];

            $productId=$product['product_id'];

            $post['productId']=$product['product_id'];
            $post['skuId']=$product['variant_id'];
            $post['ipmSkuStock']=$product['new_data']['stock'];

            $post['module']='product';
            $post['class']='product';
            $post['action']='editsingleskustock';
            $post['product_id']=$productId;

            $params=array_merge(self::$config ,$post);
            $response = AliexpressService::execute(snakeArray($params));

            if(isset($response['success']) && $response['success'])
            {
                $log['status']=1;
                $log['message']="";
                (new AliexpressProductSku)->where(['merchant_sku_id'=>$product['variant_id'],'product_id'=>$productId])->update(['ipm_sku_stock'=>$product['new_data']['stock']]);
            }else{
                $log['status']=2;
                $log['message']=json_encode(['error_message'=>$response['error_message'],'error_code'=>$response['error_code']]);
            }
            $log['run_time']=time();
            AliexpressActionLog::update($log,['id'=>$product['id']]);
            (new self())->updateStatus($productId, $log['status']);

          //批量sku停售回写
            if(empty($product['create_id'])) {
                (new AliProductHelper())->skuOfflineWriteBack($log['status'], $productId);
            }

        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    /**
     * 编辑商品的单个SKU价格信息
     */
    public static function editSingleSkuPrice($product)
    {
        try{
            self::$config = $product['product']['account'];

            $productId=$product['product_id'];
            $post['productId']=$productId;
            $post['skuId']=$product['variant_id'];
            $post['skuPrice']=$product['new_data']['price'];

            $post['module']='product';
            $post['class']='product';
            $post['action']='editsingleskuprice';
            $post['product_id']=$productId;
            $params=array_merge(self::$config ,$post);
            $response = AliexpressService::execute(snakeArray($params));
            if(isset($response['success']) && $response['success'])
            {
                $log['status']=1;
                $log['message']="";
                (new CommonQueuer(AliexpressRsyncProductQueue::class))->push($productId);
                (new AliexpressProductSku)->where(['merchant_sku_id'=>$product['variant_id'],'product_id'=>$productId])->update(['sku_price'=>$product['new_data']['price']]);
            }else{
                $log['status']=2;
                $log['message']=json_encode(['error_message'=>$response['error_message'],'error_code'=>$response['error_code']]);
            }
            $log['run_time']=time();
            AliexpressActionLog::update($log,['id'=>$product['id']]);
            (new self())->updateStatus($productId, $log['status']);
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }

    }

    public static function rsyncListing($product)
    {
        try{
            $product_id = $product['product_id'];

            $response = (new AliexpressListingHelper())->findAeProductById($product_id);
            if(isset($response['success']) && $response['success'])
            {
                $log['status']=1;
                $log['message']="";
            }else{
                $log['status']=2;
                $log['message']=json_encode(['error_message'=>$response['message']]);
            }
            $log['run_time']=time();
            AliexpressActionLog::update($log,['id'=>$product['id']]);
            (new self())->updateStatus($product_id, $log['status']);
        }catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }

    }


    /**
     *修改产品信息模板
     *
     */
    public static function editTemplate($job)
    {
        try{
            self::$config = $job['product']['account'];

            $post['productId']=$job['product_id'];

            $data = $job['new_data'];

            if(empty($data)) {
                return;
            }

            //商品信息基本
            $productInfoModel = new AliexpressProductInfo();
            $model_info = $productInfoModel->field('id, detail')->where(['product_id'=>$job['product_id']])->find();

            if($model_info && $data) {
                //账号
                $account = $job['product']['account'];

                $code = $account['code'];
                //商品信息
                $product_info = $model_info->toArray();

                //处理描述详情里面的图片
                $detail = '';
                if(isset($product_info['detail']) && $product_info['detail']) {
                    $detail = $product_info['detail'];
                    $detailResponse = (new AliexpressTaskHelper())->managerDetail($detail, $account, $code);
                    if (isset($detailResponse['result']) && $detailResponse['result']) {
                        $detail = $detailResponse['data'];
                    }
                }

                if(isset($data['relation_template_id']) || isset($data['custom_template_id']))
                {
                    $detailResponse = (new AliexpressTaskHelper())->combineRelationCustomDescription($data,$detail,$account,$code);
                    if(isset($detailResponse['result']) && $detailResponse['result']){

                        $detail = $detailResponse['data'];
                    }

                    $detail = isset($data['relation_template_id']) ? str_replace($data['relation_template_id'],'',$detail) : $detail;

                    $detail = isset($data['custom_template_id']) ? str_replace($data['custom_template_id'],'',$detail) : $detail;

                }

                if(empty($detail) && is_int($detail)) {
                    return;
                }

                $productId = $job['product_id'];
                $post['productId']=$job['product_id'];
                if($job['new_data'])
                {
                    $post['fiedName']='detail';
                    $post['fiedvalue']=$detail;

                    $post['module']='product';
                    $post['class']='product';
                    $post['action']='editsimpleproductfiled';
                    $post['product_id']=$productId;
                    $params=array_merge(self::$config ,$post);
                    $response = AliexpressService::execute(snakeArray($params));
                    if(isset($response['success']) && $response['success'])
                    {
                        $log['status']=1;
                        $log['message']="";
                        AliexpressProduct::update($job['new_data'],['product_id'=>$productId]);
                        $productInfoModel->update(['detail' => $product_info['detail']], ['id' => $product_info['id']]);
                    }else{
                        $log['status']=2;
                        $log['message']=json_encode(['error_message'=>$response['error_message'],'error_code'=>$response['error_code']]);
                    }
                    $log['run_time']=time();
                    AliexpressActionLog::update($log,['id'=>$job['id']]);
                    (new self())->updateStatus($productId,$log['status']);
                }
            }
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
}