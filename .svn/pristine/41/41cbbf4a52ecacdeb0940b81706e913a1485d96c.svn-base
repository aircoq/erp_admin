<?php
namespace app\report\queue;

use app\common\service\SwooleQueueJob;
use app\report\service\StatisticDeeps;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:01
 */
class ReportMonthlyAmountQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "统计回写人员月度销售额";
    }

    public function getDesc(): string
    {
        return "统计回写人员月度销售额";
    }

    public function getAuthor(): string
    {
        return "phill";
    }

    /**
     * 执行方法
     * @throws Exception
     */
    public function execute()
    {
        try{
            $data['begin_time'] =  strtotime(date('Y-m-d',strtotime('-1 day')));
            (new StatisticDeeps())->writeBackMonthAccount($data['begin_time']);
        }catch (Exception $e){
            throw $e;
        }
    }
}