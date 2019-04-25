<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-3
 * Time: 下午1:37
 */

namespace app\index\queue;


use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use app\common\service\SwooleQueueJob;
use app\index\service\AmazonAccountHealthService;
use think\Exception;

class AmazonAccountHealthSendQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "Amazon健康监控数据-数据发送";
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
        $id = $this->params;
        if (!is_numeric($id)) {
            throw new Exception('未知参数'. $id);
        }

        //发送数据；
        $serv = new AmazonAccountHealthService();
        return $serv->sendAccount2Spider($id, ChannelAccountConst::channel_amazon);
    }
}