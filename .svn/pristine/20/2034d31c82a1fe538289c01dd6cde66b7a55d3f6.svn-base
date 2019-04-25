<?php
namespace app\report\task;

use app\index\service\AbsTasker;
use app\common\model\amazon\AmazonSettlementReport;
use app\common\model\amazon\AmazonGetReport;
use app\report\queue\AmazonSettlementReportSummaryQueue;
use app\common\service\UniqueQueuer;

class AmazonSettlementReportSummary extends AbsTasker
{
    public function getCreator()
    {
        return 'wangwei';
    }
    
    public function getDesc()
    {
        return 'amazon结算报告数据汇总';
    }
    
    public function getName()
    {
        return 'amazon结算报告数据汇总';
    }
    
    public function getParamRule()
    {
        return [
            'asr_id|结算报告表id(可多个)'=>'',
        ];
    }
    
    public function execute()
    {
        /**
         * 1、接收参数
         */
        $asr_id_str = $this->getData('asr_id','');
        
        /**
         * 2、整理参数
         */
        $asr_where = [
            'wait_summary'=>1,
        ];
        if($asr_id_str && $asr_id_arr = array_filter(preg_split('/[^0-9\-]/',$asr_id_str))){
            //指定id，不管是否待统计，不管创建时间，都拉取一遍
            unset($asr_where['wait_summary']);
            //指定订单号
            if(count($asr_id_arr)==1){
                $asr_where['id'] = end($asr_id_arr);
            }else{
                $asr_where['id'] = ['in', array_values($asr_id_arr)];
            }
        }
        
        /**
         * 3、循环处理
         */
        $asr_field = 'id,report_id';
        $asr_page = 1;
        $asr_page_size = 500;
        $asr_order = 'id asc';
        while ($rows = AmazonSettlementReport::where($asr_where)->field($asr_field)->page($asr_page, $asr_page_size)->order($asr_order)->select()) {
            foreach ($rows as $row){
                if(empty($row['report_id'])){
                    AmazonSettlementReport::where(['id'=>$row['id']])->update(['wait_summary'=>-1]);
                    continue;
                }
                if(!$agr_row = AmazonGetReport::where(['generated_report_id'=>$row['report_id']])->field('report_type')->find()){
                    AmazonSettlementReport::where(['id'=>$row['id']])->update(['wait_summary'=>-1]);
                    continue;
                }
                if(!in_array($agr_row['report_type'], ['_GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE_','_GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE_V2_'])){
                    AmazonSettlementReport::where(['id'=>$row['id']])->update(['wait_summary'=>-1]);
                    continue;
                }
                //报告类型
                $report_type = '';
                if($agr_row['report_type'] == '_GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE_'){
                    $report_type = 'v1';
                }else if($agr_row['report_type'] == '_GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE_V2_'){
                    $report_type = 'v2';
                }
                //插入队列
                $data = [
                    'amazon_settlement_report_id'=>(int)$row['id'],
                    'report_id'=>$row['report_id'],
                    'report_type'=>$report_type,
                ];
                (new UniqueQueuer(AmazonSettlementReportSummaryQueue::class))->push(json_encode($data));
                
                //状态改为 加入队列
                AmazonSettlementReport::where(['id'=>$row['id']])->update(['wait_summary'=>2]);
                
            }
        }
        
    }
    
}