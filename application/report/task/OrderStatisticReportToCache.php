<?php
namespace app\report\task;

use app\common\cache\Cache;
use app\common\model\report\ReportStatisticByOrder;
use app\index\service\AbsTasker;
use app\report\service\StatisticGoods;
use app\report\service\StatisticOrder;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2018/5/11
 * Time: 17:01
 */
class OrderStatisticReportToCache extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '订单统计信息写入缓存';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '';
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
            $begin_time = strtotime(date('Y-m-d',time()));
            $end_time = time();
            $hour = date('H');
            if($hour < 3){
                (new OrderStatisticReport())->execute();
            }
            (new StatisticOrder())->resetReportWriteToCache(0,$begin_time,$end_time);
        }catch (Exception $e){
            throw $e;
        }
    }
}