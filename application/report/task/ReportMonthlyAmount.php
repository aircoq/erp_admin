<?php
namespace app\report\task;
use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByBuyer;
use app\index\service\AbsTasker;
use app\report\service\StatisticDeeps;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:01
 */
class ReportMonthlyAmount extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '统计回写人员月度销售额';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '统计回写人员月度销售额';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return '宇';
    }

    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }

    /**
     * 执行方法
     * @throws Exception
     */
    public function execute()
    {
        try{
            $data['begin_time'] = strtotime(date('Y-m-d',strtotime('-1 day')));
            (new StatisticDeeps())->writeBackMonthAccount($data['begin_time']);
        }catch (Exception $e){
            throw $e;
        }
    }
}