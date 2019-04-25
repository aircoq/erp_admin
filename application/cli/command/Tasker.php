<?php
namespace app\cli\command;
use app\index\service\Task;
use app\warehouse\task\LogisticsDelivery;
use swoole\cmd\Reload;
use swoole\cmd\Shutdown;
use swoole\SwooleCmder;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use swoole\SwooleServer;
use swoole\SwooleTasker;

/**
 * swoole 命令行类 swoole入口
 * @author RondaFul
 *
 */
class Tasker extends Command
{
    
    /**
     * 配置swoole 命令参数
     * @see \think\console\Command::configure()
     */
    protected function configure()
    {
        // 指令配置
        $this
        ->setName('task')
        ->addOption('start', null, Option::VALUE_OPTIONAL, 'command entrance all', null)
        ->addOption('start2', null, Option::VALUE_OPTIONAL, 'command entrance all', null)
        ->addOption('test', null, Option::VALUE_OPTIONAL, 'command entrance all', null)
        ->addOption('stop', null, Option::VALUE_OPTIONAL, 'command entrance all', null)
        ->addOption('reload', null, Option::VALUE_OPTIONAL, 'command argument', null)
        ->addOption('shutdown', null, Option::VALUE_OPTIONAL, 'command argument', null)
        ->addOption('daemon', null, Option::VALUE_OPTIONAL, 'command argument', null)
        ->addOption('process', null, Option::VALUE_OPTIONAL, 'command argument', null)
        ->setDescription('command entrance');
    }

    /**
     * 启动swoole，执行swoole程序文件
     * @see \think\console\Command::execute()
     */
    protected function execute(Input $input, Output $output)
    {

        if ($input->getOption('daemon')) {
            $tasker = new \app\cli\serve\Tasker(['daemonize'=>true]);
            $tasker->start();
        }
        if ($input->getOption('start')) {
            $tasker = new \app\cli\serve\Tasker(['daemonize'=>false,'task_tick'=>6000]);
            $tasker->start();
        }
        if ($input->getOption('reload')) {
            $cmder = SwooleCmder::create();
            $cmder->send(new Reload());
        }
        if( $input->getOption('test')){
            SwooleServer::sendServerCmd('test', [], $ret, true);
            var_dump($ret);
        }
        if( $input->getOption('stop')){
            SwooleServer::sendServerCmd('stop');
        }
        if( $input->getOption('shutdown')){
            $cmder = SwooleCmder::create();
            $cmder->send(new Shutdown());
        }
        if( $input->getOption('process')){
            $ret = null;
            SwooleServer::sendProcessCmd("task", ['tag'=>LogisticsDelivery::class, 'num'=>5]);
            echo json_encode($ret);
        }
    }
        
}