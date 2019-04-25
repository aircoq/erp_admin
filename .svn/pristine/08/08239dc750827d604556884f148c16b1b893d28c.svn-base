<?php


namespace app\goods\task;
use app\common\exception\TaskException;
use app\goods\service\GoodsHelp;
use app\index\service\AbsTasker;

class GoodsCountDevelop extends AbsTasker
{
    public function getCreator()
    {
        return '詹老师';
    }

    public function getDesc()
    {
        return '统计当天开发数';
    }

    public function getName()
    {
        return '统计当天开发数';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        try{
            $date = date("Y-m-d",strtotime("-1 day"));
            $GoodsHelp = new GoodsHelp();
            $GoodsHelp->countDevelop($date);
        }catch (\Exception $ex){
            throw new TaskException($ex->getMessage());
        }
    }
}