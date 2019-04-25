<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-3
 * Time: 下午7:11
 */

namespace app\common\service;

use app\common\interfaces\QueueJob;

/**
 * @doc 唯一队列工作类
 */
abstract class UniqueQueueJob implements HandleQueueJob,QueueJob
{
    protected $queuer;
    public function __construct()
    {
        $this->queuer = new UniqueQueuer(static::class);
    }
    public abstract function production();

    public abstract function consumption();
}