<?php

namespace app\publish\queue;


use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;
use app\common\model\amazon\AmazonHeelSaleLog as AmazonheelSaleLogModel;
use app\common\service\UniqueQueuer;

class AmazonHeelSalePriceQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon跟卖-上传价格';
    }

    public function getDesc():string
    {
        return 'amazon跟卖-上传价格';
    }

    public function getAuthor():string
    {
        return 'hao';
    }

    public function init()
    {
    }

    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    public function execute()
    {
        set_time_limit(0);
        $id = $this->params;
        if (empty($id)) {
            return;
        }

        try {

            $where = [
                'status' => ['=', 3],
                'id' => ['=', $id],
                'type' => ['=', 1]
            ];

            //根据id查询未跟卖,跟卖失败的数据
            $heelSaleModel = new AmazonheelSaleLogModel();
            $heelSale = $heelSaleModel->where($where)->find();

            if ($heelSale) {

                $heelSale = is_object($heelSale) ? $heelSale->toArray() : $heelSale;

                //加入价格队列
                if (isset($heelSale) && $heelSale['price']) {

                    //查询价格
                    $where = [
                        'account_id' => ['=', $heelSale['account_id']],
                        'asin' => ['=', $heelSale['asin']],
                        'sku' => ['=', $heelSale['sku']],
                        'type' => ['=', 3]
                    ];

                    $newHeelSale = $heelSaleModel->where($where)->find();
                    $newHeelSale = is_object($newHeelSale) ? $newHeelSale->toArray() : $newHeelSale;

                    if (isset($newHeelSale['submission_id']) && $newHeelSale['submission_id']) {

                        //如果失败了,则不在获取,将错误结果返回到上传产品
                        if($newHeelSale['status'] == 2){

                            $heelSaleModel->update(['status' => 2, 'error_desc' => $newHeelSale['error_desc']], ['id' => $id, 'type' => 1]);
                            return;
                        }

                        (new UniqueQueuer(AmazonHeelSalePriceResultQueuer::class))->push($newHeelSale['id']);
                        return;
                    }


                    //提交数据
                    $xmlhelp = new AmazonXsdToXmlService();
                    $xml = $xmlhelp->heelSalePriceXml($heelSale);

                    $account = $xmlhelp->getPublishAccount();

                    //以下开始上传XML，并保存提交结果ID；
                    $publishHelper = new AmazonPublishHelper();
                    $submissionId = $publishHelper->publishProductByType($account['id'], $xml, '_POST_PRODUCT_PRICING_DATA_');

                    //没有获取submissionId，放入重新放到亚马逊跟卖队列；在task里面拿出来进行刊登
                    if (empty($submissionId)) {
                        (new UniqueQueuer(AmazonHeelSalePriceQueuer::class))->push($id);
                        return;
                    }


                    if(empty($newHeelSale)){
                        $submissionData = [
                            'type' => 3,
                            'account_id' => $account['id'],
                            'submission_id' => $submissionId,
                            'created_time' => time(),
                            'price' => $heelSale['price'],
                            'asin' => $heelSale['asin'],
                            'sku' => $heelSale['sku'],
                        ];

                        //保存的记录的ID，放入队列用来取值；
                        $insert_id = $heelSaleModel->insertGetId($submissionData);

                        //放入取通知队列；
                        (new UniqueQueuer(AmazonHeelSalePriceResultQueuer::class))->push($insert_id);
                    }
                }

                return true;
            }
        } catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }
}