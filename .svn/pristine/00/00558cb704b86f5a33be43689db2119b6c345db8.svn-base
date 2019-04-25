<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Db;
use think\Exception;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;
use app\common\model\amazon\AmazonHeelSaleLog as AmazonHeelSaleLogModel;
use app\common\service\UniqueQueuer;
use app\common\model\amazon\AmazonUpOpenLog as AmazonUpOpenLogModel;

class AmazonTimerLowerQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon-定时下架队列-停止使用';
    }

    public function getDesc():string
    {
        return 'amazon-定时下架队列-停止使用';
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
        $params = $this->params;

        if (empty($params)) {
            return;
        }

        try {

            $where = [
                'listing_id' => ['=', $params['id']],
                'type' => 1
                ];

            $model = new AmazonHeelSaleLogModel;
            $list = $model->alias('m')->field('id, quantity, account_id, asin, price, account_id')->where($where)->find();

            if(empty($list)) {
                return;
            }

            $list = is_object($list) ? $list->toArray() : $list;

            (new UniqueQueuer(AmazonTimerUpLowerSyncQueuer::class))->push(['id' => $list['id'], 'listing_id' => $params['id']]);

            //1.根据类型查询库存. 上架:库存为初始库存
            $heelSale = [
                'sku' => $params['seller_sku'],
                'quantity' => 0,
                'account_id' => $list['account_id'],
            ];

            //2.提交xml数据
            $xmlhelp = new AmazonXsdToXmlService();
            $xml = $xmlhelp->heelSaleQuantityXml($heelSale);

            //以下开始上传XML，并保存提交结果ID；

            $publishHelper = new AmazonPublishHelper();
            $submissionId = $publishHelper->publishProductByType($heelSale['account_id'], $xml, '_POST_INVENTORY_AVAILABILITY_DATA_');

            if(empty($submissionId)){
                return;
            }

            $logModel = new AmazonHeelSaleLogModel();

            $where = [
                'listing_id' => ['=', $params['id']],
                'type' => ['=', 4],
                'account_id' => ['=', $heelSale['account_id']],
                'sku' => ['=', $params['seller_sku']],
                'asin' => ['=', $list['asin']],
            ];

            $loglist = $logModel->alias('m')->field('id')->where($where)->find();

            if(empty($loglist)){
                $data = [
                    'type' => 4,
                    'account_id' => $heelSale['account_id'],
                    'submission_id' => $submissionId,
                    'created_time' => time(),
                    'sku' => $params['seller_sku'],
                    'listing_id' => $params['id'],
                    'asin' => $list['asin'],
                    'seller_status' => $params['seller_status'],
                    'quantity' => 0,
                ];

                $id = $logModel->insertGetId($data);

                //更新加入队列字段
                (new AmazonUpOpenLogModel())->update(['is_up_open' => 1], ['id' => $params['up_open_id']]);

            }else{

                $loglist = is_object($loglist) ? $loglist->toArray() : $loglist;
                $id = $loglist['id'];

                if($logModel->update(['submission_id' => $submissionId, 'seller_status' => $params['seller_status'], 'status' => 0,'is_sync' => 0,'quantity' => 0,'last_request_time' => time()],['id' => $id])){
                    //更新加入队列字段
                    (new AmazonUpOpenLogModel())->update(['is_up_open' => 1], ['id' => $params['up_open_id']]);
                }
            }


            //写入定时下架上传结果队列
            (new UniqueQueuer(AmazonLowerResultQueuer::class))->push($id);

            return true;

        } catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }



}