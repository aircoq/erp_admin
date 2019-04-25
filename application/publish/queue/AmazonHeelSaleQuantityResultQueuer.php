<?php

namespace app\publish\queue;

use app\common\model\amazon\AmazonHeelSaleLog as AmazonheelSaleLogModel;
use app\common\service\CommonQueuer;
use app\common\service\SwooleQueueJob;
use app\publish\service\AmazonPublishHelper;
use app\common\service\UniqueQueuer;
use think\Exception;

class AmazonHeelSaleQuantityResultQueuer extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'amazon跟卖库存-获取跟卖上传结果';
    }

    public function getDesc(): string
    {
        return 'amazon跟卖库存-获取跟卖上传结果';
    }

    public function getAuthor(): string
    {
        return 'hao';
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 20;
    }


    public function execute()
    {
        set_time_limit(0);
        //这个ID是amazon_heel_sale_log表里的自增ID！！！
        $id = $this->params;
        if (empty($id)) {
            return;
        }

        if (!is_numeric($id)) {
            throw new Exception('获取Amazon跟卖结果参数错误，应该为记录的ID');
        }

        $saleLogModel = new AmazonheelSaleLogModel();
        $saleLogData = $saleLogModel->where(['id' => $id])->find();

        if (empty($saleLogData)) {
            throw new Exception('获取Amazon跟卖结果ID错误');
        }

        //需要更新的字段 ；
        $field = '';
        switch ($saleLogData['type']) {
            //上传了数量/上下架
            case 2:
                $field = 'upload_quantity';
                break;
            default:
                throw new Exception('跟卖记录表，存在未知类型:' . $saleLogData['type']);
        }


        $saleLogData = $saleLogData->toArray();

        //通过submission_id获取刊登结果；
        $publishHelp = new AmazonPublishHelper();
        $resultXml = $publishHelp->publishResult($saleLogData['account_id'], $saleLogData['submission_id']);
        $feedResult = $publishHelp->xmlToArray($resultXml);

        //请求一次，则加一次请求次数，记录最后请求时间
        $saleLogModel->update(['request_number' => $saleLogData['request_number'] + 1], ['id' => $id]);


        //结果是false,代表报告还没有生成好，放入队列过两分钟再取；
        if ($feedResult === false) {
            (new UniqueQueuer(AmazonHeelSaleQuantityResultQueuer::class))->push($id);
            return true;
        }

        //错误数量；
        $errorNum = $feedResult['Message']['ProcessingReport']['ProcessingSummary']['MessagesWithError'];

        //总共提交的message的总量
        $messageNum = $feedResult['Message']['ProcessingReport']['ProcessingSummary']['MessagesProcessed'];

        //刊登结果，可能有警告信息，也可能有失败信息；
        $results = [];
        if (!empty($feedResult['Message']['ProcessingReport']['Result'])) {
            $results = $feedResult['Message']['ProcessingReport']['Result'];
            if (isset($results['MessageID'])) {
                $results = [$results];
            }
        }

        //没有报错些次跟卖完成；
        if ($errorNum == 0){

            //更新跟卖日志状态
            $saleLogModel->update(['quantity_status' => 1], ['asin' => $saleLogData['asin'], 'type' => 1]);

        }else{

            //上传失败
            if($errorNum != 0){
                //更新跟卖日志状态
                $results = \GuzzleHttp\json_encode($results);
                $saleLogModel->update(['status' => 2, 'error_desc' => $results], ['id' => $id]);
            }
        }

        return true;

    }
}