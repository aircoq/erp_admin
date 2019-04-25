<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-9-7
 * Time: 上午10:19
 */

namespace app\index\task;

use app\common\cache\Cache;
use app\common\cache\driver\QueuerLog;
use app\common\exception\TaskException;
use app\index\service\AbsTasker;
use Carbon\Carbon;
use think\exception\ErrorException;

class QueueLog extends AbsTasker
{
    public  function getName()
    {
        return "队列日志回写到FILE";
    }

    public function getDesc()
    {
        return "把队列N天前的数据回写到文件上,减少日志占用缓存";
    }

    public  function getCreator()
    {
        return "WCG";
    }

    public function getParamRule()
    {
        return [
            'day' => [
                'type'=>'input',
                'default' => 5,
                'validate' => [
                    'min' => 1,
                    'max' => 10,
                ],
                'name'=>'天数',
            ]
        ];
    }

    public  function execute()
    {
        $day = $this->getParam('day');
        $time = Carbon::now()->subDay($day)->getTimestamp();
        /**
         * @var $cacheLog QueuerLog
         */
        $cacheLog = Cache::store('queuerLog');
        $keys = $cacheLog->getKeys();
        $logDir = LOG_PATH."swoole/";
        foreach ($keys as $key) {
            $name = $this->key2name($key);
            $logFile = $logDir."queue-remove-$name.log";
            $handle = fopen($logFile, 'a');
            try{
                while ($log = $cacheLog->popLog($key, function($log)use ($time){
                    return $log->t < $time;
                })){
                    fwrite($handle, json_encode($log)."\n");
                }
            }catch (ErrorException $exception){
                throw new TaskException($exception->getMessage());
            }finally{
                fclose($handle);
            }
        }
    }

    private function key2name($key)
    {
        $key = str_replace("queue:logs:","", $key);
        return preg_replace('/(\\\)/', "-", $key);
    }
}