<?php
namespace app\index\queue;


use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\index\service\MemberShipService;

/**
 * 平台账号绑定导出
 * Created by PhpStorm.
 * User: zhaixueli
 * Date: 2019/4/10
 * Time: 16:50
 */
class ChannelUserMapExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "平台账号绑定导出队列";
    }

    public function getDesc(): string
    {
        return "平台账号绑定导出队列";
    }

    public function getAuthor(): string
    {
        return "zhaixueli";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 2;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new MemberShipService();
            $service->export($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:server_export', 'error_'.time(), $ex->getMessage());
        }
    }
}