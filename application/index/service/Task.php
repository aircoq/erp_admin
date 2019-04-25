<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-3
 * Time: 上午11:20
 */

namespace app\index\service;

use app\common\cache\Cache;
use app\common\cache\TaskWorker;
use app\common\exception\JsonErrorException;
use app\common\exception\TaskException;
use app\common\model\Tasks;
use app\index\cache\TaskWorkerLog;
use Carbon\Carbon;
use erp\AbsServer;
use erp\ErpQuery;
use swoole\TaskProcess;
use think\Db;
use app\common\model\Task as TaskModel;
use think\db\Query;

class Task extends AbsServer
{
    /**
     * @var \app\common\cache\driver\TaskWorker
     */
    private $cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->cache = Cache::store('taskWorker');

    }

    /**
     * @return TaskModel[]|array|false
     * @throws \think\exception\DbException
     */
    public function getTasks()
    {
        $tasks = TaskModel::all(function(Query $query){
            $query->where('status', 1);
        });
        $notExists = [];
        foreach ($tasks as $task){
            $task['task_class'] = path2class($task['task_class']);
            $task['task_status'] = $this->getStatus($task['task_class']);
            if(!class_exists($task['task_class'])){
                $notExists[] = $task['task_class'];
            }
        }
        $lastReload = $this->lastReload();
        return ['tasks' => $tasks, 'last_reload'=>$lastReload, 'not_exists' => $notExists];
    }

    public function getTasksClass()
    {
        $tasks = [];
        $expTasks = [];
        $this->list_dir(APP_PATH, function($file)use(&$expTasks){
            $ds = DIRECTORY_SEPARATOR;
            $preg = "/(\w+)\\{$ds}task\\{$ds}(\w+)\.php/";
            if(preg_match($preg, $file, $match)){
                $task = "app\\{$match[1]}\\task\\{$match[2]}";
                try{
                    $installed = TaskModel::get(['status'=>1, 'task_class' => class2path($task)]);
                    $class = new $task();
                    if(!$installed && $class instanceof AbsTasker){
                        return [
                            'task_class' => $class->getId(),
                            'name' => $class->getName(),
                            'desc' => $class->getDesc(),
                            'creator' => $class->getCreator(),
                        ];
                    }else{
                        return false;
                    }
                }catch (\Exception $exp){
                    $expTasks[] = [
                        'path' => $task,
                        'expinfo' => "FILE:{$exp->getFile()};LINE:{$exp->getLine()};exp:{$exp->getMessage()}"
                    ];
                    return false;
                }
            }
            if(preg_match('/\/([\w]+)\/task\/([\w]+)\.php/', $file, $match)){

            }
            return false;

        }, $tasks);
        return ['tasks'=>$tasks, 'exception' => $expTasks];
    }

    public function lastReload($reload = 0)
    {
        return $this->cache->serverStatus('lastReload');
    }

    public function list_dir($dir, $callback, &$files)
    {
        $break = false;
        if(is_dir($dir))
        {
            if($handle = opendir($dir))
            {
                while(!$break && $file = readdir($handle))
                {
                    if($file != '.' && $file != '..')
                    {
                        if(is_dir($dir.DIRECTORY_SEPARATOR.$file))
                        {
                            $this->list_dir($dir.DIRECTORY_SEPARATOR.$file, $callback, $files);
                        }else{
                            $file = str_replace(APP_PATH, "",$dir.DIRECTORY_SEPARATOR.$file);
                            if($ok = $callback($file, $break)){
                                $files[] = $ok;
                            }
                        }
                    }
                }
            }
            closedir($handle);
        }else{
        }
    }

    public function synchronous(\Redis $sredis, \Redis $dredis)
    {
        TaskWorker::transplantCache($sredis, $dredis);
    }

    public function getTasker($key)
    {
        $task = new TaskModel();
        $task = $task->where(['task_class'=>class2path($key)])->find();
        if(!$task){
            list('tasks'=>$tasks) = $this->getTasksClass();
            $result = array_search(function($element)use($key){
                if($key === $element['task_class']){
                    return true;
                }
            }, $tasks);
            if($result){
                throw new JsonErrorException("该任务未安装！");
            }
            throw new JsonErrorException("找不到该任务");
        }
        if(!class_exists($key)){
            throw new JsonErrorException("任务已不存在");
        }
        $taskClass = path2class($task['task_class']);
        return new $taskClass;
    }

    public function getTaskWorker($workerId)
    {
    }

    /**
     * @param $workerId
     * @param $runTag
     */
    public function updateRunTag($workerId, $runTag)
    {
        $worker = $this->getWorker($workerId);
        if(!($worker instanceof TaskWorker)){
            throw new TaskException("not find taskworker $workerId");
        }
        $worker->run_tag = $runTag;
        $worker->save();
        return $worker;
    }

    public function changeTaskStatus($id, $status)
    {
        $this->cache->setStatus($id, $status);
        return true;
    }

    public function getStatus($taskId)
    {
        return $this->cache->getStatus($taskId);
    }

    public function initTask($id)
    {
        $task = new Tasks();
        $task->id = $id;
        $task->status = 1;
        $task->created_at = time();
        $task->save();
    }

    public function createWorker($params)
    {
        $task = $this->getTasker($params['task_id']);
        if(!$task){
            throw new JsonErrorException("不存在的任务");
        }
        if(!($task instanceof AbsTasker)){
            throw new JsonErrorException("不合格的任务");
        }
        $task->setData($params['param']);
        if(!$task->checkRule()){
            throw new JsonErrorException($task->getError());
        }
        if($params['type'] != 1 && $params['type'] != 0){
            if($this->isExistGlobal($params['type'])){
                throw new TaskException('全局任务已存在',2);
            }
        }
        $begin = Carbon::parse($params['begin'])->getTimestamp();
        $end = Carbon::parse($params['end'])->getTimestamp();
        $worker = new TaskWorker();
        $worker->task_id = $params['task_id'];
        $worker->name = $params['name'];
        $worker->loop_type = $params['loop_type'];
        $worker->loop_value = $params['loop_value'];
        $worker->begin = $begin;
        $worker->end = $end;
        $worker->created_at = time();
        $worker->updated_at = time();
        $worker->max_count = $params['max_count'];
        $worker->use_count = 0;
        $worker->type = $params['type'];
        $worker->mode = $params['mode'];
        $worker->param = $params['param'];
        $worker->run_status = static::RUN_STATUS_NOT_BEGIN;
        $worker->status = !!$params['status'];
        $runTag = TaskScheduler::beginExec($worker);
        $worker->run_tag = $runTag;
        $ret = $worker->save();
        return $ret;
    }

    public function isExistGlobal($type)
    {
        return !!Db::table('task_workers')->where('type',$type)->find();
    }

    public function removeWorker($id)
    {
        $model = TaskWorker::get($id);
        if($model){
            if($model->deleted_at > 0){
                return false;
            }else{
                $model->deleted_at = time();
                $model->save();
                $cache = Cache::moduleStore('taskWorkerLog');
                $cache->remove($id);
                return true;
            }
        }else{
            return false;
        }
    }

    public function deleteWorker($workerId)
    {
        $model = TaskWorker::get($workerId);
        if($model){
            $model->delete();
        }else{
            return false;
        }
    }

    /**
     * @param $workerId
     * @param $runStatus
     * @param $remark
     */
    public static function logWorker($workerId, $runStatus, $remark)
    {
        $cache = Cache::moduleStore('taskWorkerLog');
        $cache->addLog($workerId, [
            'worker_id' => $workerId,
            'created_at' => millisecond(),
            'run_status' => $runStatus,
            'remark' => $remark
        ]);
    }

    public function modifyWorker(TaskWorker $worker, $params)
    {

    }

    public function changeWorkerStatus(TaskWorker $worker, $status)
    {
        $worker->status = $status;
        $worker->save();
        return true;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getWorkers($id)
    {
        $workers = TaskWorker::all(function(TaskWorker $model)use($id){
            $model->where('task_id',$id);
            $model->where('deleted_at','');
        });
        return $workers;
    }

    public function installTask($taskClass)
    {
        $task = TaskModel::get(['task_class' => class2path($taskClass)]);
        $object = new $taskClass();
        if(!$task){
            /**
             * @var $object AbsTasker
             */
            return TaskModel::create([
                'task_class' => class2path($object->getId()),
                'run_type' =>'common',
                'name' => $object->getName(),
                'author' => $object->getCreator(),
                'desc' => $object->getDesc(),
                'status' => 1,
            ]);
        }else{
            $task->status = 1;
            $task->name = $object->getName();
            $task->author = $object->getCreator();
            $task->desc = $object->getDesc();
            $task->save();
            return $task;
        }
    }

    public function uninstallTask($taskClass)
    {
        $task = new TaskModel();
        $task = $task->where('task_class', class2path($taskClass))->find();
        if($task){
            $task->status = 0;
            $task->save();
        }else{
            throw new TaskException("任务类{$taskClass}未安装");
        }
    }

    /**
     * @param $workerId
     * @return TaskWorker|bool
     */
    public function getWorker($workerId)
    {
        $worker = TaskWorker::get($workerId);
        if($worker && $worker->deleted_at === ''){
            return $worker;
        }else{
            return false;
        }
    }

    public function getSchedulers()
    {
        $now = millisecond();
        $model = new TaskWorker();
        $model->order('run_tag', 'desc');
        $model->where('run_tag', $now, '>=');
        $model->where('deleted_at',  '');
        $workers = $model->select();
        $result = [];
        foreach ($workers as $worker){
            $task = $this->getTasker($worker->task_id);
            if($task){
                $result[] = [
                    'id' => $worker->id,
                    'taskName' => $task->getName(),
                    'workerName' =>$worker->name,
                    'run_tag' => $worker->run_tag
                ];
            }
        }
        return $result;
    }

    public function changeWorkerTime($id, $time)
    {
        if($worker = $this->getWorker($id)){
            $worker->run_tag = $time;
            $worker->save();
            $taskWorker = new \app\index\service\TaskWorker($worker);
            $taskWorker->setRunTag($time);
            return true;
        }else{
            return false;
        }
    }

    public function getLogs($workerId, $count)
    {
        /**
         * @var $cache TaskWorkerLog
         */
        $cache = Cache::moduleStore('taskWorkerLog');
        return $cache->getLogs($workerId, 0, $count);
    }

    public function getGlobalTasks()
    {
        $cache = Cache::store('process');
        $result = [];
        foreach ($cache->tasks() as $task=>$num){
            $result[] = [
                'task' =>$task,
                'num'=>$num
            ];
        }
        return $result;
    }

    public function addGlobalTask($task, $num = 1)
    {
        TaskProcess::addTask($task, $num);
    }

    public function changeGlobalTask($task, $num)
    {
        TaskProcess::mdfTask($task, $num);
    }
}
