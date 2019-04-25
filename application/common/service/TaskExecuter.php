<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-7-12
 * Time: 上午11:39
 */

namespace app\common\service;


use app\common\cache\Cache;
use app\common\exception\TaskException;
use app\index\service\AbsTasker;
use app\index\service\Task;
use app\index\service\TaskWorker;
use Carbon\Carbon;
use swoole\TaskRunner;

class TaskExecuter
{
    private $taskRunner;
    private $taskServer;
    private $jumpCount = 0;
    /**
     * @var TaskWorker
     */
    private $taskWorker;
    public function __construct(TaskRunner $taskRunner)
    {
        $this->taskRunner = $taskRunner;
        $this->taskServer = new Task();
        $worker = $this->taskServer->getWorker($this->taskRunner->getWorkerId());
        $this->taskWorker = new TaskWorker($worker);
    }

    public static function trigger($event, $params)
    {

    }
    
    public function getTaskworkerTaskid(){
    	return $this->taskWorker->task_id ?? '';
    }

    /**
     * @doc 设置下个执行时间，true为有下一次，false为任务完成
     */
    public function resetNextRuntime(): bool
    {
        if($this->taskWorker->end < time()){
            $this->taskWorker->remScheduler();
            $this->taskWorker->run_status = TaskWorker::RUN_STATUS_END;
            $this->taskWorker->save();
            throw new TaskException("任务工作器已过期");
        }
        if($this->taskWorker->max_count > 0 && $this->taskWorker->use_count >= $this->taskWorker->max_count){
            $this->taskWorker->run_status = TaskWorker::RUN_STATUS_END;
            $this->taskWorker->save();
            return false;
        }
        if($this->taskWorker->max_count > 0){
            $this->taskWorker->use_count = $this->taskWorker->use_count + 1;
        }
        $this->taskWorker->run_status = TaskWorker::RUN_STATUS_WAITNEXT;
        $nextRunTime = $this->calcNextTime();
        $this->taskWorker->run_tag = $nextRunTime;
        $this->taskWorker->save();
        $this->taskWorker->setRunTag($nextRunTime);
        return true;
    }

    private function calcNextTime($lastRunTag =null)
    {
        $lastRunTime = $lastRunTag?:$this->taskWorker->run_tag;
        $loopValue = $this->taskWorker->loop_value;
        switch ($this->taskWorker->loop_type){
            case 0:
                break;
            case 1:
                $lastRunTime  += ($loopValue * 1000);
                break;
            case 2:
                $lastRunTime += ($loopValue * 1000 * 60);
                break;
            case 3:
                $lastRunTime += ($loopValue * 1000 * 60 * 60);
                break;
            case 4:
                $lastRunTime += ($loopValue * 1000 * 60 * 60 * 24);
                break;
            case 5:
                $lastRunTime += ($loopValue * 1000 * 60 * 60 * 24 * 7);
                break;
            case 11:
                $now = Carbon::createFromTimestamp(floor($lastRunTime/1000));
                $now->endOfWeek();
                $now->hour = 0;
                $now->minute = 0;
                $now->second = 0;
                $now->addSecond($this->taskWorker->loop_value);
                $lastRunTime = $now->getTimestamp() * 1000;
                break;
            case 12:
                $now = Carbon::createFromTimestamp(floor($lastRunTime/1000));
                $now = $now->startOfDay();
                $now->addDay();
                $now->addSecond($this->taskWorker->loop_value);
                $lastRunTime = $now->getTimestamp() * 1000;
                break;
            case 13:
                $now = Carbon::createFromTimestamp(floor($lastRunTime/1000));
                $now->addHour(1);
                $now->minute = 0;
                $now->second = 0;
                $now->addSecond($this->taskWorker->loop_value);
                $lastRunTime = $now->getTimestamp() * 1000;
                break;
            default:
                $lastRunTime = max(millisecond() + 3000, $this->taskWorker->begin * 1000 + 2000);
        }
        $now = millisecond();
        if($lastRunTime > $now){
            return $lastRunTime;
        }else{
            $this->jumpCount+=1;
            return $this->calcNextTime($lastRunTime);
        }
    }

    /**
     * @param bool $hasNext
     * @return array logs
     * @throws TaskException
     */
    public function triger($hasNext = false)
    {
        if(!$this->taskWorker){
            throw new TaskException("任务不存在");
        }
        if($this->taskWorker->status === false){
            throw new TaskException("任务已暂停");
        }
        $task = $this->taskWorker->task_id;
        if(!class_exists($task)){
            $this->taskServer->deleteWorker($this->taskWorker->id);
            throw new TaskException("任务工作器已不存在");
        }
        if(!$this->taskServer->getStatus($task)){
            throw new TaskException("任务工作器已暂停");
        }
        /**
         * @var $task AbsTasker
         */
        $task = new $task();
        $task->setData($this->taskWorker->param);
        if(!$task->beforeExec()){
            throw new TaskException("任务条件不满足");
        }
        $before = millisecond();
        $task->execute();
        $after = millisecond();
        $time = $after - $before;
        $timer = Carbon::createFromTimestamp(floor($this->taskRunner->getTime()/1000))->format('Y-m-d H:i:s');
        $this->logWorker(TaskWorker::RUN_STATUS_FINISH, "本次应时：{$timer};花费{$time}（毫秒）;执行成功");
        $task->afterExec();
        if($hasNext && ($this->jumpCount > 0)){
            $time = Carbon::createFromTimestamp(floor($this->taskWorker->run_tag / 1000));
            $time = $time->format("Y-m-d H:i:s");
            $this->logWorker(TaskWorker::RUN_STATUS_WAITNEXT, "跳过：{$this->jumpCount}次;下次执行时间：$time");
        }else{
            $this->logWorker(TaskWorker::RUN_STATUS_END);
        }
        return $task->getLogs();
    }

    public static function executeTask(AbsTasker $task, $data)
    {
        $task->setData($data);
        if ($task->beforeExec()) {
            $task->execute();
            $task->afterExec();
        }else{
            throw new TaskException("beforeExec false");
        }
    }

    public function logWorker($status, $msg = '')
    {
        TaskWorker::logWorker($this->taskWorker->id, $status, $msg);
    }

    public static function tickGetWorkers()
    {
        $now = millisecond();
        /**
         * @var $taskWorker \app\common\cache\driver\TaskWorker
         */
        global $lastTickTime;
        $taskWorker = Cache::store('TaskWorker', true);
        $last = $lastTickTime ?: 1;
        $lastTickTime = $now;
        $cacheWorkers = $taskWorker->getWorkers($last, $now);
        $globalWorkers= $taskWorker->globalWorkers($last, $now);
        if(!is_array($globalWorkers)){
            $globalWorkers = [];
        }

        $allWorkers = array_merge($cacheWorkers, $globalWorkers);
        return $allWorkers;
    }
}