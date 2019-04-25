<?php

namespace app\report\task;

use app\common\model\amazon\AmazonSettlementReport;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\report\queue\AmazonSettlementReportDetailFixQueue;

/**
 * Class AmazonSettlementReportDetailFix
 * Created by linpeng
 * createTime: 2019/4/24 15:54
 * updateTime: 2019/4/24 15:54
 * @package app\report\task
 */
class AmazonSettlementReportDetailFix extends AbsTasker
{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return '亚马逊报告修复详情数据(临时)';
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return '亚马逊报告修复详情数据(临时)';
    }


    /**
     * 定义任务作者
     * @return string
     */
    public function getCreator()
    {
        return 'linpeng';
    }

    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {

        return ['type|处理类型:'       => 'require|select:更新主表:1,更新详细:2'];
    }

    /**
     * 执行方法
     */
    public function execute()
    {
        $type      = $this->getData('type');
        switch ($type){
            case 1:
                break;
            case 2:
                $this->fixDetail();
                break;

        }
    }

    public function fixReport()
    {
        // $model = new
    }

    public function fixDetail()
    {
        $reportModel = new AmazonSettlementReport();
        $res = $reportModel->alias('r')->field('distinct r.id')
            ->join('amazon_settlement_report_detail d','d.amazon_settlement_report_id = r.id and d.posted_date = 0','left')
            // ->where('r.id','=',41654)
            ->select();
        foreach ($res as $val)
        {
            (new UniqueQueuer(AmazonSettlementReportDetailFixQueue::class))->push($val['id']);
            unset($val);
        }
    }
}