<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;
use app\common\model\amazon\AmazonHeelSaleLog as AmazonheelSaleLogModel;
use app\common\service\UniqueQueuer;

class AmazonHeelSaleQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon跟卖-上传产品-停止使用';
    }

    public function getDesc():string
    {
        return 'amazon跟卖-上传产品-停止使用';
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
                'status' => ['in', [0,2]],
                'id' => ['=', $id],
                'type' => ['=', 1],
            ];

            //根据id查询未跟卖,跟卖失败的数据
            $heelSaleModel = new AmazonheelSaleLogModel();
            $heelSale = $heelSaleModel->where($where)->find();

            if ($heelSale) {

                $heelSale = is_object($heelSale) ? $heelSale->toArray() : $heelSale;

               if ($heelSale['submission_id']) {
                    (new UniqueQueuer(AmazonHeelSaleResultQueuer::class))->push($heelSale['id']);
                    return;
               }

                //提交产品数据
                $xmlhelp = new AmazonXsdToXmlService();
                $xml = $xmlhelp->heelSaleProductXml($heelSale);

                //以下开始上传XML，并保存提交结果ID；
                $publishHelper = new AmazonPublishHelper();
                $submissionId = $publishHelper->publishProductByType($heelSale['account_id'], $xml, '_POST_PRODUCT_DATA_');

                if(empty($submissionId)){
                    (new UniqueQueuer(AmazonHeelSaleQueuer::class))->push($heelSale['id']);
                    return;
                }

                //type:1产品
                $submissionData = [
                    'submission_id' => $submissionId,
                ];

                $where = ['id' => $id];

                //保存的记录的ID，放入队列用来取值；
                $heelSaleModel->update($submissionData, $where);

                //写入跟卖上传结果队列
                (new UniqueQueuer(AmazonHeelSaleResultQueuer::class))->push($heelSale['id']);


                return true;
            }
        } catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }



}