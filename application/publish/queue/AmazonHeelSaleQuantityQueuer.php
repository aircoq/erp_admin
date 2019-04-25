<?php

namespace app\publish\queue;

use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\publish\service\AmazonPublishHelper;
use app\publish\service\AmazonXsdToXmlService;
use app\common\model\amazon\AmazonHeelSaleLog as AmazonheelSaleLogModel;
use app\common\service\UniqueQueuer;


class AmazonHeelSaleQuantityQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon跟卖-上传库存';
    }

    public function getDesc():string
    {
        return 'amazon跟卖-上传库存';
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

    /** @var array 重进队列的元素 */
    public $errArr = [
        'General error: 1205 Lock wait timeout exceeded',
        'General error: 2006 MySQL server has gone away',
    ];

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
                'type' => ['=', 1],
            ];

            //根据id查询未跟卖,跟卖失败的数据
            $heelSaleModel = new AmazonheelSaleLogModel();
            $heelSale = $heelSaleModel->where($where)->find();

            if ($heelSale) {

                $heelSale = is_object($heelSale) ? $heelSale->toArray() : $heelSale;
                //加入库存队列
                if (isset($heelSale['quantity'])) {

                    //查询价格
                    $where = [
                        'account_id' => ['=', $heelSale['account_id']],
                        'asin' => ['=', $heelSale['asin']],
                        'type' => ['=', 2],
                        'sku' => ['=', $heelSale['sku']],
                    ];

                    $newHeelSale = $heelSaleModel->where($where)->find();
                    $newHeelSale = is_object($newHeelSale) ? $newHeelSale->toArray() : $newHeelSale;

                    if (isset($newHeelSale) && $newHeelSale['submission_id']) {

                        //如果失败了,则不在获取,将错误结果返回到上传产品
                        if($newHeelSale['status'] == 2){

                            $heelSaleModel->update(['status' => 2, 'error_desc' => $newHeelSale['error_desc']], ['id' => $id, 'type' => 1]);
                            return;
                        }

                        (new UniqueQueuer(AmazonHeelSaleQuantityResultQueuer::class))->push($newHeelSale['id']);
                        return;
                    }

                    //提交数据
                    $xmlhelp = new AmazonXsdToXmlService();
                    $xml = $xmlhelp->heelSaleQuantityXml($heelSale);
                    $account = $xmlhelp->getPublishAccount();

                    //以下开始上传XML，并保存提交结果ID；
                    $publishHelper = new AmazonPublishHelper();
                    $submissionId = $publishHelper->publishProductByType($account['id'], $xml, '_POST_INVENTORY_AVAILABILITY_DATA_');

                    //没有获取submissionId，放入重新刊登队列；在task里面拿出来进行刊登
                    if (empty($submissionId)) {
                        (new UniqueQueuer(AmazonHeelSaleQuantityQueuer::class))->push($id);
                        return;
                    }


                    if(empty($newHeelSale)){
                        //type:1数量，2价钱
                        $submissionData = [
                            'type' => 2,
                            'account_id' => $account['id'],
                            'submission_id' => $submissionId,
                            'created_time' => time(),
                            'quantity' => $heelSale['quantity'],
                            'asin' => $heelSale['asin'],
                            'sku' => $heelSale['sku'],
                        ];

                        //保存的记录的ID，放入队列用来取值；
                        $insert_id = $heelSaleModel->insertGetId($submissionData);

                        //放入取通知队列；
                        (new UniqueQueuer(AmazonHeelSaleQuantityResultQueuer::class))->push($insert_id);
                    }
                }

                return true;
            }
        } catch (TaskException $exp){
            throw new QueueException($exp->getFile().'<->'.$exp->getLine().'<->'.$exp->getMessage());
        }
    }
}