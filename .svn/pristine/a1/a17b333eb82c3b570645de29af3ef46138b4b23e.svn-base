<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2018/11/27
 * Time: 13:43
 */

namespace app\listing\task;


use app\common\cache\Cache;
use app\common\model\report\ReportStatisticPublishByChannel;
use app\index\service\AbsTasker;
use think\Db;
use think\Exception;

class StatisticsBackWrite extends AbsTasker
{
    public function getName()
    {
        return '分平台回写SPU统计数据';
    }

    public function getDesc()
    {
        return '分平台回写SPU统计数据';
    }

    public function getCreator()
    {
        return 'wlw2533';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            $start = Cache::handler()->get('backWriteReportStatisticPublishByShelf');
//            $isClear = Cache::handler()->get('isClearReportStatisticPublishByShelf');
//            if (empty($isClear)) {//清除脏数据
//                $sql = 'truncate report_statistic_publish_by_shelf';
//                Db::query($sql);
//                Cache::handler()->set('isClearReportStatisticPublishByShelf', 1);
//            }
            $start = empty($start) ? 0 : $start;
            $sql = 'SELECT * from report_statistic_publish_by_shelf limit ' .$start . ',1000';
            $shelfModel = new ReportStatisticPublishByChannel();

            $rows = Db::query($sql);
            if (!$rows) {
                throw new Exception('执行完毕');
            }
            foreach ($rows as $val) {
                $shelfModel->add($val);
            }
            Cache::handler()->set('backWriteReportStatisticPublishByShelf',$start+1000);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}