<?php
namespace app\cli\serve;
use app\common\cache\Cache;
use app\common\cache\TaskWorker;
use app\common\exception\TaskException;
use app\common\service\TaskExecuter;
use app\index\service\Task;
use swoole\cmd\Reload;
use swoole\SwooleCmder;
use swoole\SwooleTasker;
use swoole\TaskRunner;
use swoole\TaskRunResult;

/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-7-12
 * Time: 上午11:32
 */
class Tasker extends SwooleTasker
{
    /**
     * @var \app\common\cache\driver\TaskWorker
     */
    public function __construct(array $setting = [])
    {
        defined('RUNTIME_SWOOLE') || define('RUNTIME_SWOOLE', true);
        $cache = Cache::store('taskWorker');
        $cache->serverStatus('startTime', time());
        $cache->serverStatus('runStatus', true);
        parent::__construct($setting);
    }


    public function onQueuerStart(\swoole_server $server){
        echo "onQueuerStart\n";
    }

    public function onTaskShutdown()
    {
        $cache = Cache::store('taskWorker');
        $cache->serverStatus('runStatus', false);
    }


}