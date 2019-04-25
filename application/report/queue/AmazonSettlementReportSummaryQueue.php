<?php
namespace app\report\queue;

use app\common\service\SwooleQueueJob;
use Exception;
use app\common\model\amazon\AmazonSettlementReport;
use app\order\service\AmazonReportCallback;

/**
 * amazon结算报告数据汇总
 * @author wangwei
 * @date 2019-4-10 10:16:20
 */
class AmazonSettlementReportSummaryQueue extends SwooleQueueJob
{
    /**
     * @doc 每次task执行队列消费最大次数
     * @var int
     */
    protected static $maxRunnerCount = 100;
    
    //结算报告表id
    private $amazon_settlement_report_id = null;
    //报告id
    private $report_id = null;
    //报告类型v1、v2
    private $report_type = '';
    
    public function getName(): string
    {
        return 'amazon结算报告数据汇总';
    }

    public function getDesc(): string
    {
        return 'amazon结算报告数据汇总';
    }

    public function getAuthor(): string
    {
        return "wangwei";
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 10;
    }

    public function execute()
    {
        
        try {
            //接收参数，简单校验，组装接口参数
            $this->genApiParams();
            
            //汇总报告
            $this->summaryReport();
            
        } catch (Exception $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $error_msg = "msg:{$msg},code:{$code},file:{$file},line:{$line}";
            throw new Exception($error_msg);
        }
    }
    
    /**
     * @desc 汇总报告
     * @author wangwei
     * @date 2019-4-10 10:26:49
     */
    private function summaryReport(){
        
        //开始执行
        AmazonSettlementReport::where(['id'=>$this->amazon_settlement_report_id])->update(['wait_summary'=>3]);
        
        //根据报告版本分发处理
        if($this->report_type=='v1'){
            (new AmazonReportCallback())->reCalculateAmazonSeSummaryV1($this->report_id);
        }else{
            (new \app\order\service\AmazonSettlementReport())->reCalculateAmazonSeSummaryV2($this->report_id);
        }
        
        //执行完成
        AmazonSettlementReport::where(['id'=>$this->amazon_settlement_report_id])->update(['wait_summary'=>0]);
    }
    
    /**
     * @desc 组装api参数
     * @author wangwei
     * @date 2019-4-10 10:24:22
     */
    private function genApiParams()
    {
        //获取任务参数
        $data = json_decode($this->params, true);
//         $data = [
//             'amazon_settlement_report_id'=>253,
//             'report_id'=>4578784,
//             'report_type'=>'v1',
//         ];

        //结算报告表id
        if(!$this->amazon_settlement_report_id = param($data, 'amazon_settlement_report_id')){
            throw new Exception('amazon_settlement_report_id 不能为空!',1001);
        }
        //结算报告表id
        if(!$this->report_id = param($data, 'report_id')){
            throw new Exception('report_id 不能为空!',1001);
        }
        //结算报告表id
        if(!$this->report_type = param($data, 'report_type')){
            throw new Exception('report_type 不能为空!',1001);
        }
        if(!in_array($this->report_type, ['v1','v2'])){
            throw new Exception('report_type 只能为v1、v2',1001);
        }
    }
    
    /**
     * @desc 根据错误代码返回错误分类
     * @author wangwei
     * @date 2019-4-10 10:29:17
     * @param int $code
     * @return string
     */
    private function getErrorTypeByCode($code)
    {
        $map = [
            //初始化错误
            '1001'=>'参数错误',
        ];
        
        return ($code && isset($map[$code])) ? $map[$code] : "未定义的错误代码:{$code}";
    }
    
    public function setParams($params)
    {
        $this->params = $params;
    }
    
}