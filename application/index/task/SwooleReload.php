<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-7-14
 * Time: 下午4:02
 */

namespace app\index\task;


use app\common\cache\Cache;
use app\index\cache\Swoole;
use app\index\service\AbsTasker;
use swoole\cmd\Reload;
use swoole\SwooleCmder;

class SwooleReload extends AbsTasker
{
    public function getName()
    {
        return "SwooleReload";
    }

    public function getDesc()
    {
        return "Swoole自动加载";
    }

    public function getCreator()
    {
        return "WCG";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        /**
         * @var $swoole Swoole
         */
        $swoole = Cache::moduleStore('swoole');
        if($swoole->modifyFileCount(true) > 0){
            $cmder = SwooleCmder::create();
            $cmder->send(new Reload());
        }
    }

}