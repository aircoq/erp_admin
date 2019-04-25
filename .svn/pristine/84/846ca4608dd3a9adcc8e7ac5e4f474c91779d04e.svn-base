<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-3
 * Time: 下午1:37
 */

namespace app\index\queue;


use app\common\service\SwooleQueueJob;
use app\index\service\AliexpressAccountHealthService;
use think\Exception;

class AliexpressAccountHealthReceiveQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "速卖通健康监控-接收数据";
    }

    public function getDesc(): string
    {
        return "速卖通健康监控数据发送";
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

        try {
            //发送数据；
            $serv = new AliexpressAccountHealthService();
            $serv->saveHealthData($data);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(). '|'. $e->getLine(). '|'. $e->getFile());
        }
    }
}