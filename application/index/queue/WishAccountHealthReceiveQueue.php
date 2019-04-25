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
use app\index\service\WishAccountHealthService;
use think\Exception;

class WishAccountHealthReceiveQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "Wish健康监控-接收数据";
    }

    public function getDesc(): string
    {
        return "Wish健康监控数据发送";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public function execute()
    {
        $data = $this->params;
        if (empty($data)) {
            return;
        }

        //发送数据；
        $serv = new WishAccountHealthService();
        $serv->saveHealthData($data);
    }
}