<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-11
 * Time: 上午9:32
 */

namespace app\index\queue;


use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\index\service\ManagerServer;

/**
 * 报表服务器成员调整
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/8/3
 * Time: 17:23
 */
class ServerExportQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "服务器成员导出队列";
    }

    public function getDesc(): string
    {
        return "服务器成员导出队列";
    }

    public function getAuthor(): string
    {
        return "libaimin";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 2;
    }

    public function execute()
    {
        try {
            $data = $this->params;
            $service = new ManagerServer();
            $service->exportUser($data);
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:server_export', 'error_'.time(), $ex->getMessage());
        }
    }
}