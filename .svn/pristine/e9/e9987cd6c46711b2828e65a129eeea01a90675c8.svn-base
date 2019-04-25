<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/5/10
 * Time: 14:10
 */

namespace app\publish\task;

use app\common\exception\TaskException;
use app\index\service\AbsTasker;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\amazon\AmazonAccount as AmazonAccountModel;
use app\common\service\UniqueQueuer;
use app\publish\queue\AmazonHeelSaleComplaintQueuer;
use Waimao\AmazonMws\AmazonSqs;
use Waimao\AmazonMws\AmazonConfig;

class AmazonHeelSaleComplain extends AbsTasker
{
    public  function getName()
    {
        return 'Amazon-投诉管理检测跟卖';
    }

    public  function getDesc()
    {
        return 'Amazon-投诉管理检测跟卖';
    }

    public  function getCreator()
    {
        return 'hao';
    }

    public  function getParamRule()
    {
        return [];
    }

    public  function execute()
    {
        set_time_limit(0);
        try{

            $amazonSqs = new AmazonSqs();

            //1分钟取1
            $queueUrl = AmazonConfig::$AmazonSqsQueueUrl['AnyOfferChanged'];
            $response = $amazonSqs->receive_message($queueUrl);
            $response = (array)$response;

            //1.取数据
            //2.将数据写入到缓存中
            //3.同时推送到队列中,获取数据,检测是否跟卖,同时删除缓存数据;
            $hash_key = 'AnyOfferChangedQueue';
            foreach($response as $val){
                if(isset($val['Messages']) && $val['Messages']){

                    foreach ($val['Messages'] as $key => $messageVal){


                        if(isset($messageVal['MD5OfBody']) && $messageVal['MD5OfBody']){

                            $result = (array)simplexml_load_string($messageVal['Body']);
                            $result = \GuzzleHttp\json_encode($result);
                            //写入到缓存中
                            Cache::handler()->hSet($hash_key, $messageVal['MD5OfBody'], $result);

                            $params = ['id' => $messageVal['MD5OfBody']];
                            (new UniqueQueuer(AmazonHeelSaleComplaintQueuer::class))->push($params);
                        }
                        
                        //查询之后,将查询的相应队列消费掉
                        if(isset($messageVal['ReceiptHandle']) && $messageVal['ReceiptHandle']) {

                            $receiptHandle = $messageVal['ReceiptHandle'];
                            $amazonSqs->delete_message($queueUrl, $receiptHandle);
                        }
                    }
                }
            }

            return true;
        }catch (Exception $ex){
            throw new TaskException($ex->getMessage());
        }
    }
}