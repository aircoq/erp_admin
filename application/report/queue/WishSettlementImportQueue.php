<?php
namespace  app\report\queue;

use Exception;
use app\common\service\SwooleQueueJob;
use app\common\model\wish\WishSettlementImport;
use app\report\service\WishSettlementReportService;

class WishSettlementImportQueue extends SwooleQueueJob
{
    const ERROR_MAX_COUNT = 3;//最大错误次数
    
    private $wish_settlement_import_id = null;//wish结算报告导入表id
    private $file_md5 = null;//文件md5值
    private $cover = false;//是否覆盖更新
  
    public function getName(): string
    {
        return "wish处理结算报告文件队列";
    }

    public function getDesc(): string
    {
        return "wish处理结算报告文件队列";
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
     * @date 2018-12-6 21:24:58
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
        $service = new WishSettlementReportService();
        return $service->processSettlementFile($this->wish_settlement_import_id, $this->cover);
    }
    
    /**
     * @desc 处理完成回调
     * @author wangwei
     * @date 2018-12-6 21:30:04
     */
    private function processComplete($re){
        if(!$this->wish_settlement_import_id){
            return true;
        }
        $row = WishSettlementImport::where('id', $this->wish_settlement_import_id)->field('id,count')->find();
        if(!$row){
            return true;
        }
        $update = [
            'process_time'=>time(),
            'count'=>['exp','count+1'],
            'error_msg'=>param($re, 'message', '未返回错误消息')
        ];
        $ask = param($re, 'ask', 2);
        if($ask==0){//处理失败
            if($row['count']+1 < self::ERROR_MAX_COUNT){
                $update['status'] = '0';//失败次数小于最大次数，再次处理
            }else{
                $update['status'] = '3';
            }
        }else if($ask==1){//处理成功
            $update['status'] = '2';
        }else if($ask==2){//未返回错误消息
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
//             'id'=>'',//Y wish结算报告导入表id
//             'file_md5'=>'',//Y 文件md5值
//             'cover'=>'',//N 是否覆盖更新,0:否,1:是
//         ];
        if(!$file_md5 = param($data, 'file_md5')){
            throw new Exception('文件md5值不能为空!');
        }
        $this->file_md5 = $file_md5;//文件md5值
        if(!$wish_settlement_import_id = param($data, 'id',0)){
            throw new Exception('id不能为空!');
        }
        $this->wish_settlement_import_id = $wish_settlement_import_id;//wish结算报告导入表id
        $this->cover = param($data, 'cover')=='1' ? true : false;
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