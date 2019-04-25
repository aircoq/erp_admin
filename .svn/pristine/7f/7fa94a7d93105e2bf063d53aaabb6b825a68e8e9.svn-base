<?php
namespace app\index\queue;


use app\common\cache\Cache;
use app\common\exception\QueueAfterDoException;
use app\common\service\ChannelAccountConst;
use app\common\service\SwooleQueueJob;
use app\index\service\AliexpressAccountHealthService;
use think\Exception;

class AliexpressAccountHealthSendQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "速卖通健康监控数据-数据发送";
    }

    public function getDesc(): string
    {
        return "速卖通健康监控数据发送";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    public function execute()
    {
        $id = $this->params;
        if (!is_numeric($id)) {
            throw new Exception('未知参数'. $id);
        }

        try {
            //发送数据；
            $serv = new AliexpressAccountHealthService();
            $result = $serv->sendAccount2Spider($id);
            if (!$result) {
                throw new QueueAfterDoException('发送失败', 1000 * 60 * 60);
            }
        } catch (Exception $e) {
            throw new QueueAfterDoException($e->getMessage(). '|'. $e->getCode(). '|'. $e->getFile(), 1000 * 60 * 60);
        }
    }
}