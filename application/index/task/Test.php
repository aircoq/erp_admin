<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-7-11
 * Time: 下午5:16
 */

namespace app\index\task;


use app\common\model\AccountUserMap;
use app\common\service\CommonQueuer;
use app\index\service\AbsTasker;
use app\index\service\AccountUserMapService;
use app\warehouse\service\ReturnWaitShelfService;

class Test extends AbsTasker
{
    protected $queuer = CommonQueuer::class;


    public function __construct()
    {

    }

    public function getName()
    {
        return "任务测试专用类";
    }

    public function getDesc()
    {
        return "任务测试专用类";
    }

    public function getCreator()
    {
        return "WCG";
    }

    public function getParamRule()
    {
        return [];
    }

    public function runExe1()
    {

    }

    public function execute()
    {
        (new ReturnWaitShelfService())->writeBackByWarehouse();
        $now = now();
        echo "run $now\n";
        sleep(5);
    }

}