<?php
namespace app\report\task;
use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByBuyer;
use app\index\service\AbsTasker;
use app\report\service\StatisticDeeps;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/4/29
 * Time: 15:01
 */
class ReportDeepsCache extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '每天统计前一天的订单发货数据(写入表)';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '每天统计前一天的订单发货数据(写入表)';
    }

    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return 'libaimin';
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
            //1.先统计写入缓存
            $deepsService = new StatisticDeeps();
            $deepsService->writeBackPackageTable($data['begin_time'],$data['begin_time'] + 86399);
        }catch (Exception $e){
            throw $e;
        }
    }

}