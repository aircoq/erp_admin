<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-4
 * Time: 下午2:29
 */

namespace app\index\service;


use app\common\cache\Cache;
use app\common\exception\TaskException;
use app\common\cache\TaskWorker as TaskWorkerCache;
use Carbon\Carbon;
use swoole\SwooleServer;
use think\Exception;
use think\Log;

class TaskScheduler
{
    private static $globalTasks = [];

    /**
     * @param $taskClassParam
     * @param TaskTimer $time
     * @param $name
     * @return array|bool
     */
    public static function register($taskClassParam, TaskTimer $time, $name, $isGloabl = true)
    {
        if(is_array($taskClassParam)){
            $class = $taskClassParam[0];
            $param = isset($taskClassParam[1]) ? $taskClassParam[1] : [];
        }else{
            $class = $taskClassParam;
            $param = [];
        }
        $obj = new $class();
        if($obj instanceof AbsTasker){
            $server = new Task();
            $params = $time->param();
            $params['name'] = $name;
            $params['task_id'] = $obj->getId();
            $params['param'] = json_encode($param);
            $params['status'] = true;
            $params['mode'] = 2;//临时
            if($isGloabl){
                $params['type'] = md5(serialize($taskClassParam).serialize($time->param()));
            }
            try{
                if(!$server->createWorker($params)){
                    return $server->getError();
                }
            }catch (TaskException $exception){
                
            }
            return true;
        }else{
            return false;
        }
    }

    public static function startSwoole()
    {
        $cache = Cache::store('TaskWorker', true);
        $cache->lastTickTime(1);
        $cache->removeall();
        $tasks = TaskWorkerCache::all(function($worker){
            return $worker->deleted_at === '';
        });
        foreach ($tasks as $task){
            static::setScoreWorker($task, $task->run_tag);
        }
    }

    /**
     * 事件型任务监听
     * @param $event string 事件名
     * @param $param array 传参
     * @param AbsTasker $tasker 处理器
     */
    public static function listen($event, $params, AbsTasker $tasker)
    {
        $tasker = get_class($tasker);
        if(isset(self::$globalTasks[$event])){
            self::$globalTasks[$event][] = ['params'=>$params,'tasker'=>$tasker];
        }else{
            self::$globalTasks[$event] = [['params'=>$params,'tasker'=>$tasker]];
        }
    }


    public static function getEventTaskers($event)
    {
        return isset(self::$globalTasks[$event]) ? self::$globalTasks[$event] : [];
    }

    /**
     * 事件型任务触发
     * @param $event 事件名
     * @param array $params 传参
     */
    public static function trigger($event, array $params)
    {
        self::sendServer('taskEvent',['event'=>$event,'params'=>$params]);
    }


    public static function sendServer($cmd, $params)
    {
        SwooleServer::sendServerCmd($cmd, $params);
    }

    /**
     * 执行事件任务
     * @param $taskers array
     * @param $param array
     */
    public static function executeTaskers($taskers, $param)
    {
        foreach ($taskers as $tasker){
            $params = array_merge_recursive($param, $tasker['params']);
            $tasker = $tasker['tasker'];
            echo "tasker:\n";
            var_dump($tasker);
            try{
                self::executeTask(new $tasker, $params);
            }catch (\Exception $exception){

            }

        }
    }

    public static function tickGetWorkers()
    {
        $now = millisecond();
        $taskWorker = Cache::store('TaskWorker', true);
        $last = $taskWorker->lastTickTime($now);
        $cacheWorkers = $taskWorker->getWorkers($last, $now);
        if(!is_array($cacheWorkers)){
            $cacheWorkers = [];
            static::log("tickGetWorkers", json_encode($cacheWorkers));
        }
        $globalWorkers= $taskWorker->globalWorkers($last, $now);
        if(!is_array($globalWorkers)){
            $globalWorkers = [];
            static::log("globalWorkers", json_encode($globalWorkers));
        }
        $allWorkers = array_merge($cacheWorkers, $globalWorkers);
        return $allWorkers;
    }

    private static function worker(TaskWorkerCache $worker, AbsTasker $task)
    {
        self::executeWorker($task, $worker);
    }

    public static function runWorker($workerIndex)
    {
        $workerE = explode('|', $workerIndex);
        $workerId = $workerE[0];
        $server = new Task();
        $worker = $server->getWorker($workerId);
        if (!$worker) {
            throw new TaskException("not find worker $workerId");
        }
        if(!($worker instanceof TaskWorkerCache)){
            throw new TaskException("not TaskWorker instance $workerId");
        }
        $nextTime = self::nextExec($worker);
        $taskWorker = Cache::store('TaskWorker');
        if($nextTime){
            $worker = $server->updateRunTag($workerId, $nextTime);
            $taskWorker->scoreWorker($workerIndex, $nextTime);
        }else{
            $taskWorker->remWorker($workerIndex);
        }
        $task = $server->getTasker($worker->task_id);
        if($task){
            self::worker($worker, $task);
        }else{
            $taskWorker->remWorker($workerIndex);
            echo $worker->task_id."\n";
            throw new TaskException("$workerIndex ; $worker->task_id ; task not found");
        }

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

    public static function executeWorker(AbsTasker $task, TaskWorkerCache $worker)
    {
        if(!$task){
            throw new TaskException("任务器不存在");
        }
        if(!($task instanceof AbsTasker)){
            throw new TaskException("无效的任务器");
        }
        if(!$worker->status){
            throw new TaskException("任务已暂停");
        }
        $task->setData($worker->param);
        if (!$task->beforeExec()) {
            throw new TaskException("任务条件不满足");
        }
        $worker->run_status = Task::RUN_STATUS_DOING;
        $worker->save();
        self::logWorker($worker->id, Task::RUN_STATUS_DOING);
        $starttime = millisecond();
        $task->execute();
        $need = (millisecond() - $starttime) / 1000;
        self::logWorker($worker->id, Task::RUN_STATUS_FINISH, "完成任务所需时间:{$need}(ms)");
        $worker->run_status = Task::RUN_STATUS_FINISH;
        $worker->save();
        $task->afterExec();
    }

    public static function nextExec($worker)
    {
        if(!($worker instanceof TaskWorkerCache)){
            throw new TaskException("nextExec only support TaskWorker instrance");
        }
        if($worker->end < time()){
            $worker->run_status = Task::RUN_STATUS_END;
            $worker->save();
            self::logWorker($worker->id, Task::RUN_STATUS_END);
            return false;
        }
        if($worker->max_count > 0 && $worker->use_count >= $worker->max_count){
            $worker->run_status = Task::RUN_STATUS_END;
            $worker->save();
            self::logWorker($worker->id, Task::RUN_STATUS_END);
            return false;
        }
        if($worker->max_count > 0){
            $worker->use_count = $worker->use_count + 1;
        }
        $worker->run_status = Task::RUN_STATUS_WAITNEXT;
        self::logWorker($worker->id, Task::RUN_STATUS_WAITNEXT);
        $worker->save();
        //{label:'-无-',value:0},
        //{label:'按周',value:5},
        //{label:'按天',value:4},
        //{label:'按时',value:3},
        //{label:'按分',value:2},
        //{label:'按秒',value:1},
        $nextTime = $worker->run_tag;
        switch ($worker->loop_type){
            case 0:
            case '0':
                break;
            case 1:
            case '1':
                $nextTime  += ($worker->loop_value * 1000);
                break;
            case 2:
            case '2':
                $nextTime += ($worker->loop_value * 1000 * 60);
                break;
            case 3:
            case '3':
                $nextTime += ($worker->loop_value * 1000 * 60 * 60);
                break;
            case 4:
            case '4':
                $nextTime += ($worker->loop_value * 1000 * 60 * 60 * 24);
                break;
            case 5:
            case '5':
                $nextTime += ($worker->loop_value * 1000 * 60 * 60 * 24 * 7);
                break;

            case 11:
                $now = Carbon::createFromTimestamp(floor($worker->run_tag / 1000));
                $now->nextWeekday();
                $now->hour = 0;
                $now->minute = 0;
                $now->second = 0;
                $now->addSecond($worker->loop_value);
                $nextTime = $now->getTimestamp() * 1000;
                break;
            case 12:
                $now = Carbon::tomorrow();
                $now->addSecond($worker->loop_value);
                $nextTime = $now->getTimestamp() * 1000;
                break;
            case 13:
                $now = Carbon::now();
                $now->addHour(1);
                $now->minute = 0;
                $now->second = 0;
                $now->addSecond($worker->loop_value);
                $nextTime = $now->getTimestamp() * 1000;
                break;
            default:
                break;
        }
        return max($nextTime, millisecond() + 3 * 1000);
    }

    private static function log($tag, $log)
    {
        Log::record("{$tag}:{$log}");
        Log::save();
    }

    public static function beginExec(TaskWorkerCache $worker)
    {

        if($worker->loop_type < 10){
            $scope = max(millisecond() + 3000, $worker->begin * 1000 + 2000);
        }else{
            switch ($worker->loop_type){
                case 11:
                    $now = Carbon::createFromTimestamp(floor($worker->run_tag / 1000));
                    $now->nextWeekday();
                    $now->hour = 0;
                    $now->minute = 0;
                    $now->second = 0;
                    $now->addSecond($worker->loop_value);
                    $scope = $now->getTimestamp() * 1000;
                    break;
                case 12:
                    $now = Carbon::tomorrow();
                    $now->addSecond($worker->loop_value);
                    $scope = $now->getTimestamp() * 1000;
                    break;
                case 13:
                    $now = Carbon::now();
                    $now->addHour(1);
                    $now->minute = 0;
                    $now->second = 0;
                    $now->addSecond($worker->loop_value);
                    $scope = $now->getTimestamp() * 1000;
                    break;
                default:
                    $scope = max(millisecond() + 3000, $worker->begin * 1000 + 2000);
            }
        }
        static::setScoreWorker($worker, $scope);
        return $scope;
    }

    public static function remScoreWorker($worker)
    {
        $cache = Cache::store('TaskWorker');
        $cache->remWorker("{$worker->id}|{$worker->name}");
    }
    public static function setScoreWorker($worker, $scope)
    {
        $cache = Cache::store('TaskWorker');
        $cache->scoreWorker("{$worker->id}|{$worker->name}", $scope);
    }

    public static function loadGlobalTask()
    {
        $include = APP_PATH."task.php";
        try{
            require($include);
        }catch (Exception $exp){
            echo "task global config file {$include} not define {$exp->getMessage()}\n";
        }

    }

    private static function logWorker($workId, $runstatus, $remark="")
    {
        Task::logWorker($workId, $runstatus, $remark);
    }
}