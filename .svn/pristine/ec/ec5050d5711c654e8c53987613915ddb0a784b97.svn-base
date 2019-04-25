<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-3
 * Time: 下午7:11
 */

namespace app\common\service;


use app\common\cache\Cache;
use app\common\cache\driver\QueuerLog;
use app\common\interfaces\QueueJob;
use app\common\model\Queue as QueueModel;

abstract class CommonQueueJob implements QueueJob
{
    /**
     * @var string 队列key
     */
    protected $key;

    protected $queuer;

    /**
     * @var $log QueuerLog
     */
    protected $log;

    protected function getKey()
    {
        return $this->key ?:static::class;
    }
    public function __construct()
    {
        $this->queuer = new CommonQueuer($this->getKey());
        $this->log = Cache::store('queuerLog');
    }

    public static function jobInfo($boolean = true, $tableData = null):array
    {
        $jober = new static();
        $cache = Cache::store('queuer');
        $queue = $jober->getKey();
        $elements = $cache->members($queue);
        $elements = array_map(function($element){return unserialize($element);}, $elements);
        return [
            'key'	=> $jober->getKey(),
            'name'	=> $jober->getName(),
            'desc'	=> $jober->getDesc(),
            'author'=> $jober->getAuthor(),
            'type'	=> 'common',
            'hosttype'	=> $tableData['host_type'] ?? QueueModel::get(['queue_class' => class2path($queue)])->host_type,
            'timers'	=> $cache->timers($queue),
            'elements'	=> $elements
        ];
    }
    public function production($params){
    }

    public function consumption(){
    }

    protected final function recordLog($element, $type, $result){
        $this->log->recordLog($this->getKey(), $element, $type, $result);
    }
}
