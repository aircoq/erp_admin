<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-3
 * Time: 上午11:19
 */

namespace app\index\controller;


use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\index\service\AbsTasker;
use app\index\service\Task as TaskServer;
use app\index\service\TaskWorker;
use swoole\cmd\Reload;
use swoole\SwooleCmder;
use swoole\SwooleServer;
use think\db\Query;
use think\Request;
use think\Validate;

/**
 * @module 基础设置
 * @title 任务
 * @package app\index\controller
 * @author WCG
 */
class Task extends Base
{
    const ruleNew = [
        'name|任务名称'=>'require|min:6',
        'task_id'=>'require',
        'loop_type|循环类型'=>'require|in:0,1,2,3,4,5,10,11,12,13',
        'loop_value|循环参数' =>'require',
        'param|任务执行参数'=>'require',
        'begin|开始时间' =>'require',
        'end|结束时间' =>'require',
    ];
    const ruleMdf = [
        'name|任务名称'=>'require|min:6',
        'loop_type|循环类型'=>'require|in:0,1,2,3,4,5,10,11,12,13',
        'loop_value|循环参数' =>'require',
        'param|任务执行参数'=>'require',
        'begin|开始时间'=>'require',
        'end|结束时间'=>'require',
    ];

    /**
     * @title 任务列表
     * @url /task
     * @return \think\response\Json
     */
    public function index()
    {
        $server = new TaskServer();
        $tasks = $server->getTasks();
        return json($tasks);
    }

    /**
     * @title 任务工作器类列表
     * @url /task/classes
     *
     */
    public function taskClasses(TaskServer $taskServer)
    {
        return json($taskServer->getTasksClass());
    }

    /**
     * @param TaskServer $server
     * @title 某任务工作器的执行列表
     * @url /task/workers
     */
    public function taskWorkers(TaskServer $server, Request $request)
    {
        $taskclass = $request->get('task_class');
        return json($server->getWorkers($taskclass));
    }

    /**
     * @param TaskServer $server
     * @title 某任务工作器安装
     * @url /task/install
     * @throws
     */
    public function taskInstall(TaskServer $server, Request $request)
    {
        $taskclass = $request->get('task_class');
        $result = $server->installTask($taskclass);
        return json(['message'=>'ok', $taskclass]);
    }

    /**
     * @param TaskServer $server
     * @title 某任务工作器卸载
     * @url /task/uninstall
     * @throws
     */
    public function taskUninstall(TaskServer $server, Request $request)
    {
        $taskclass = $request->get('task_class');
        $result = $server->uninstallTask($taskclass);
        return json(['message'=>'ok']);
    }

    /**
     * @title 重新加载类(任务)
     * @url /task/reloadclass
     */
    public function reload()
    {
        $cmder = SwooleCmder::create();
        $result = $cmder->send(new Reload([]));
        switch ($result->getCode()){
            case 'ok':
                $message = $result->getResult();
                return json(['message'=>$message['time']]);
            default:
                return json_error(['message'=>$result->getResult()]);
        }

    }

    /**
     * @title 任务参数规则
     * @url /task/:taskId/rules
     * @param Request $request
     * @param $taskId
     * @return \think\response\Json
     */
    public function rules(Request $request, $taskId)
    {
        $server = new TaskServer();
        $tasker = $server->getTasker($taskId);
        if($tasker && $tasker instanceof AbsTasker){
            return json($tasker->getParamConfigs());
        }else{
            return json_error('无效任务Class');
        }
    }

    /**
     * @title 启停任务
     * @url /task/:taskId/status
     * @method put
     * @param Request $request
     * @param $taskId
     * @return \think\response\Json
     */
    public function status(Request $request, $taskId)
    {
        $sever = new TaskServer();
        $status = $request->param('status');
        $sever->changeTaskStatus($taskId, $status);
        return json(["message"=>"设置成功"]);
    }

    /**
     * @title 任务信息
     * @url /task/worker/:workerId
     * @param $workerId
     * @return \think\response\Json
     */
    public function worker_get($workerId)
    {
        $server = new TaskServer();
        $worker = $server->getWorker($workerId);
        if($worker){
            return json($worker);
        }else{
            return json_error("无效任务Worker");
        }

    }

    /**
     * @title 启停工作任务
     * @url /task/worker/status/:workerId
     * @method put
     * @param Request $request
     * @param $workerId
     * @return \think\response\Json
     */
    public function worker_status(Request $request, $workerId)
    {
        $status = $request->param('data');
        $workerServer = TaskWorker::get($workerId);
        if($workerServer->exist()){
            $status = $status === 'true';
            $workerServer->changeStatus($status);
            return json(['message'=>'修改成功']);
        }else{
            return json_error('无效操作');
        }
    }

    /**
     * @title 修改任务信息
     * @url /task/worker/:workerId
     * @method put
     * @param Request $request
     * @param $workerId
     * @return \think\response\Json
     */
    public function worker_mdf(Request $request, $workerId)
    {
        $workerServer = TaskWorker::get($workerId);
        if(!$workerServer->exist()){
            return json_error("无效的操作");
        }
        $valid = new Validate(self::ruleMdf);
        $params = $request->param();
        if(!$valid->check($params)){
            return json_error($valid->getError());
        }
        $workerServer->modify($params);
        return json(['message'=>'修改成功']);
    }

    /**
     * @title 添加工作任务
     * @url /task/worker
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function worker_new(Request $request)
    {
        $params = $request->param();
        $valid = new Validate(self::ruleNew);
        if($valid->check($params)){
            $workerServer = new TaskWorker();
            $params['type'] = 1;
            $workerServer->createWorker($params);
            return json(['message'=>'新增成功']);
        }else{
            return json_error($valid->getError());
        }
    }

    /**
     * @title 删除工作任务
     * @url /task/worker
     * @method delete
     * @param Request $request
     * @return \think\response\Json
     */
    public function worker_rem(Request $request)
    {
        $params = $request->param();
        if(!$id = param($params, 'id')){
            throw new JsonErrorException("非法操作");
        }
        $workerServer = TaskWorker::get($id);
        if(!$workerServer->exist()){
            throw new JsonErrorException("非法操作");
        }
        $workerServer->destory();
        return json(['message'=>'移除成功']);
    }

    /**
     * @title 查看工作任务日志
     * @url /task/worker/:workerId/logs
     * @param $workerId
     * @param TaskServer $task
     * @return \think\response\Json
     */
    public function worker_log($workerId, TaskServer $task)
    {
        $count = Request::instance()->param('count', 20);
        $logs = $task->getLogs($workerId, $count);
        return json($logs);
    }

    /**
     * @title 修改任务时间
     * @url /task/worker/:workerId/changetime
     * @method put
     * @param Request $request
     * @param $workerId
     * @param TaskServer $task
     * @return \think\response\Json
     */
    public function worker_changetime(Request $request, $workerId, TaskServer $task)
    {
        $time = $request->put('time');
        $workerServer = TaskWorker::get($workerId);
        if($workerServer->exist()){
            $workerServer->setRunTag($time);
            return json(['message'=>'成功']);
        }else{
            return json_error('失败');
        }
    }

    /**
     * @title 同步任务
     * @url /task/synchronous
     * @method post
     */
    public function synchronous(Request $request)
    {
        $params = $request->param();
        $sconfig['host'] = param($params, 'sip');
        $sconfig['port'] = (int)param($params, 'sport');
        $sconfig['password'] = param($params, 'spass');
        $sredis = createRedis($sconfig);
        if(!isConnectRedis($sredis)){
            dump_detail($sconfig);
            throw new JsonErrorException("原redis连接失败");
        }

        $dconfig['host'] = param($params, 'dip');
        $dconfig['port'] = (int)param($params, 'dport');
        $dconfig['passsword'] = param($params, 'dpass');
        $dredis = createRedis($dconfig);
        if(!isConnectRedis($dredis)){
            throw new JsonErrorException("原redis连接失败");
        }
        $taskServer = new TaskServer();
        $taskServer->synchronous($sredis, $dredis);
        return json(['message'=>'同步成功!']);
    }

    /**
     * @title 时间任务调度信息
     * @url /task/worker_schedulers
     * @param TaskServer $task
     * @return \think\response\Json
     */
    public function worker_schedulers(TaskServer $task)
    {
        $schedulers = $task->getSchedulers();
        return json(['process'=>$schedulers, 'time'=>millisecond()]);
    }

    /**
     * @title 获取全局任务
     * @url /task/global_tasks
     *
     * @param TaskServer $task
     * @return \think\response\Json
     */
    public function global_tasks(TaskServer $task)
    {
        $tasks = $task->getGlobalTasks();
        return json($tasks);
    }

    /**
     * @title 添加全局任务
     * @url /task/global_task
     * @method put
     * @param Request $request
     * @param TaskServer $task
     * @return \think\response\Json
     */
    public function global_task_add(Request $request, TaskServer $task)
    {
        $taskClass = $request->param('task');
        $task->addGlobalTask($taskClass);
        return json(['message'=>'添加成功']);
    }

    /**
     * @title 改数全局任务进程数
     * @url /task/global_task_change
     * @method put
     */
    public function global_task_change(Request $request, TaskServer $server)
    {
        $task = $request->param('task');
        $num = $request->param('num');
        $server->changeGlobalTask($task, $num);
        return json(['message'=>'修改成功']);
    }
}
