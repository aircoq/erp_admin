<?php
namespace  app\report\queue;

use app\common\model\amazon\AmazonSettlementReportDetail;
use app\common\service\SwooleQueueJob;


class AmazonSettlementReportDetailFixQueue extends SwooleQueueJob

{
    public function getName(): string
    {
        return "亚马逊报告详情修复（临时）";
    }

    public function getDesc(): string
    {
        return "亚马逊报告详情修复（临时）";
    }

    public function getAuthor(): string
    {
        return "linpeng";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 50;
    }

    public function execute()
    {
        $data = $this->params;
        // $data = $param;
        $model = new AmazonSettlementReportDetail();
        $rows = $model->field('id,org_data')->where('amazon_settlement_report_id','=',$data)->select();
        foreach ($rows as $row){
            $org_data  = json_decode($row['org_data'],true);
            $posted_date = strtotime($org_data['posted_date']);
            $laza_up = [
                'posted_date' => $posted_date
            ];
            AmazonSettlementReportDetail::where(['id'=> $row['id']])->update($laza_up);
            unset($rows);
        }

    }
}