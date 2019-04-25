<?php

namespace app\publish\queue;

use app\common\service\SwooleQueueJob;
use think\Exception;
use app\publish\service\AmazonPublishHelper;
use app\common\model\amazon\AmazonNotice as AmazonNoticeModel;
use Waimao\AmazonMws\AmazonConfig;
use app\common\service\UniqueQueuer;

class AmazonSetNoticeQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon-设置通知';
    }

    public function getDesc():string
    {
        return 'amazon-设置通知';
    }

    public function getAuthor():string
    {
        return 'hao';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    public function init()
    {
    }

    public function execute()
    {
        //检测参数，如果不是数字，则停止；
        $id = $this->params;
        if (empty($id) || !is_numeric($id)) {
            return false;
        }


        //1.检测是否注册账号
        //2.开启和关闭通知
        try{
            set_time_limit(0);

            $model = new AmazonNoticeModel;

            $data = $model->where(['id' => $id])->find();

            $queueUrl = AmazonConfig::$AmazonSqsQueueUrl['AnyOfferChanged'];
            $data['sqsQueueUrl'] = $queueUrl;


            //1.未指定要接收通知的新目标
            if(!$data['is_register_destinate']){
                //指定要接收通知的新目标
                (new AmazonPublishHelper())->registerDestination($data);

                //获取成功,则更新注册目标状态
                $model->update(['is_register_destinate' => 1], ['id' => $id]);
            }


            //2.为指定的通知类型和目标创建新订阅
            (new AmazonPublishHelper())->createSubscription($data);

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}