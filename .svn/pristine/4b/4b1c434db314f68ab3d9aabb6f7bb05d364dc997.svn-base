<?php
/**
 * Created by PhpStorm.
 * User: RondaFul
 * Date: 2017-03-20
 * Time: 18:13
 */

namespace app\common\exception;


use app\index\service\TaskWorker;

class TaskException extends \Exception
{
    private $runStatus = 0;
    public function __construct($message, $runStatus = TaskWorker::RUN_STATUS_DOING)
    {
        parent::__construct($message, null, null);
        $this->runStatus = $runStatus;
    }

    public function recordLog($workerId)
    {
        TaskWorker::logWorker($workerId, $this->runStatus, $this->message);
    }
}