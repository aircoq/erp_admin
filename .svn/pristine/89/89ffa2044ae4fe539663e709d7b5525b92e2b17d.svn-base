<?php
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 17-8-3
 * Time: 下午1:37
 */

namespace app\index\queue;


use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use app\common\service\SwooleQueueJob;
use app\index\service\AmazonAccountHealthService;
use think\Exception;

class AmazonAccountHealthReceiveQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "Amazon健康监控-接收数据";
    }

    public function getDesc(): string
    {
        return "Amazon健康监控数据发送";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public function execute()
    {
        $data = $this->params;//本身就是数组
        if (empty($data)) {
            return;
        }
        //接收数据；
        try{
            $serv = new AmazonAccountHealthService();
            $serv->saveHealthData($data);
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }

    }
}