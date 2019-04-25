<?php
namespace app\cli\command;

use rpc\ModuleClient;
use rpc\ModuleServer;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * @doc Module服务器
 * @author RondaFul
 *
 */
class Module extends Command
{

    /**
     * 配置swoole 命令参数
     * @see \think\console\Command::configure()
     */
    protected function configure()
    {
        // 指令配置
        $this
            ->setName('module')
            ->addOption('start', null, Option::VALUE_OPTIONAL, 'start module sync server', null)
            ->addOption('stop', null, Option::VALUE_OPTIONAL, 'command entrance all', null)
            ->addOption('test', null, Option::VALUE_OPTIONAL, 'command entrance all', null)
            ->addOption('reload', null, Option::VALUE_OPTIONAL, 'command argument', null)
            ->addOption('shutdown', null, Option::VALUE_OPTIONAL, 'command argument', null)
            ->addOption('daemon', null, Option::VALUE_OPTIONAL, 'command argument', null)
            ->setDescription('模块服务器');
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
            $server = new ModuleServer();
            $server->start();
            return;
        }
        if($input->getOption('test')){
            $client = new ModuleClient('test');
            $ret = $client->remoteCall('module/rpcs','param1','param2','param3');
            dump_detail($ret);
            return;
        }
        if ($input->getOption('reload')) {
        }
        if( $input->getOption('stop')){
        }
        if( $input->getOption('shutdown')){
        }
    }

}