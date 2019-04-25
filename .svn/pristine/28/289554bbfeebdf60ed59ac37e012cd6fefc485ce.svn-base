<?php
namespace  app\report\queue;

use Exception;
use app\common\service\SwooleQueueJob;
use app\common\model\aliexpress\AliexpressSettlementImport;
use app\report\service\AliexpressSettlementReportService;

class AliexpressSettlementImportQueue extends SwooleQueueJob
{
    const ERROR_MAX_COUNT = 3;//最大错误次数
    
    private $aliexpress_settlement_import_id = null;//aliexpress结算报告导入表id
    private $file_md5 = null;//文件md5值
    
    public function getName(): string
    {
        return "aliexpress处理结算报告文件队列";
    }
    
    public function getDesc(): string
    {
        return "aliexpress处理结算报告文件队列";
    }
    
    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }
    
    public function getAuthor(): string
    {
        return "wangwei";
    }
    
    /**
     * @desc 执行
     * @author wangwei
     * @date 2019-1-9 18:54:42
     */
    public function execute(){
        try {
            //设置执行不超时
            set_time_limit(0);
            
            //获取执行的参数信息
            $this->getParams();
            
            //处理文件
            $pfRe = $this->processFile();
            
            //处理完成回调
            $this->processComplete($pfRe);
            
        } catch (Exception $ex) {
            $error_msg = 'error_msg:' . $ex->getMessage().';file:'.$ex->getFile().';line:'.$ex->getLine();
            //处理完成回调
            $this->processComplete(['ask'=>0,'message'=>$error_msg]);
        }
        
        return true;
    }
    
    /**
     * @desc 处理文件
     * @author wangwei
     * @date 2018-12-6 21:49:50
     */
    private function processFile(){
        $service = new AliexpressSettlementReportService();
        return $service->processSettlementFile($this->aliexpress_settlement_import_id);
    }
    
    /**
     * @desc 处理完成回调
     * @author wangwei
     * @date 2018-12-6 21:30:04
     */
    private function processComplete($re){
        if(!$this->aliexpress_settlement_import_id){
            return true;
        }
        $row = AliexpressSettlementImport::where('id', $this->aliexpress_settlement_import_id)->field('id,count')->find();
        if(!$row){
            return true;
        }
        $update = [
            'process_time'=>time(),
            'count'=>['exp','count+1'],
            'error_msg'=>param($re, 'message', '未返回错误消息')
        ];
        if($account_id = param($re, 'account_id',0)){
            $update['account_id'] = $account_id;
        }
        if($min_time = param($re, 'min_time',0)){
            $update['min_time'] = $min_time;
        }
        if($max_time = param($re, 'max_time',0)){
            $update['max_time'] = $max_time;
        }
        if($currency_code = param($re, 'currency_code','')){
            $update['currency_code'] = $currency_code;
        }
        $ask = param($re, 'ask', 3);
        if($ask==0){//处理失败
            if($row['count']+1 < self::ERROR_MAX_COUNT){
                $update['status'] = '0';//失败次数小于最大次数，再次处理
            }else{
                $update['status'] = '3';
            }
        }else if($ask==1){//处理成功
            $update['status'] = '2';
        }else if($ask==2){//处理中
            $update['status'] = '0';//认为未处理，下次再处理
        }else if($ask==3){//未返回错误消息
            $update['status'] = '3';
        }
        return $row->save($update);
    }
    
    /**
     * @desc 获取任务执行的参数,过滤参数信息
     * @return array $data 检验后的数据信息
     * @author wangwei
     * @date 2018-12-6 21:22:42
     */
    private function getParams(){
        //获取任务参数
        $data = json_decode($this->params, true);
//         $data = [
//             'id'=>'',//Y aliexpress结算报告导入表id
//             'file_md5'=>'',//Y 文件md5值
//         ];
        if(!$file_md5 = param($data, 'file_md5')){
            throw new Exception('文件md5值不能为空!');
        }
        $this->file_md5 = $file_md5;//文件md5值
        if(!$aliexpress_settlement_import_id = param($data, 'id',0)){
            throw new Exception('id不能为空!');
        }
        $this->aliexpress_settlement_import_id = $aliexpress_settlement_import_id;//aliexpress结算报告导入表id
        return $data;
    }
    
    /**
     * @desc 设置消息
     * @author wangwei
     * @date 2018-12-6 21:15:38
     * @see \app\common\service\SwooleQueueJob::setParams()
     * @param string $params
     */
    public function setParams($params){
        $this->params = $params;
    }
    
}