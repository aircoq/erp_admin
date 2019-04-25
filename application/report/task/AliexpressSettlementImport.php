<?php
namespace app\report\task;

use app\index\service\AbsTasker;
use Exception;
use app\common\exception\TaskException;
use app\common\service\UniqueQueuer;
use app\report\queue\AliexpressSettlementImportQueue;
use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressSettlementImport as AliexpressSettlementImportModel;

class AliexpressSettlementImport extends AbsTasker
{
    //任务redis键前缀
    private $task_key_prefix = 'task:report:AliexpressSettlementImport:';
    //任务过期时间（秒）
    private $task_expired_time = 30;
    //是否重试
    private $retry = null;
    //导入表id
    private $aliexpress_settlement_import_id = null;
    
    public function getCreator() {
        return 'wangwei';
    }

    public function getDesc() {
        return 'aliexpress结算报告导入';
    }

    public function getName() {
        return 'aliexpress结算报告导入';
    }

    public function getParamRule() {
        return [
            'retry|失败重试'=>'require|select:否:1,是:2',
            'aliexpress_settlement_import_id|导入表id'=>'',
        ];
    }
    
    public function execute(){        
        //运行检查
        if(!$this->runCheck()){
            return false;
        }
        //接收参数
        $this->retry = $this->getData('retry');
        $this->aliexpress_settlement_import_id = $this->getData('aliexpress_settlement_import_id');
        
        try {
            //处理失败更新为未处理
            $this->updateRetry();
            
            //循环查询数据
            $where = [
                'status'=>'0',
                'count'=>['<', AliexpressSettlementImportQueue::ERROR_MAX_COUNT],
            ];
            if($this->aliexpress_settlement_import_id){
                $where['id'] = $this->aliexpress_settlement_import_id;
            }
            
            $order = 'id asc';
            $page = 1;
            $pageSize = 1000;
            while ($rows = AliexpressSettlementImportModel::where($where)->order($order)->page($page, $pageSize)->select()){
                foreach ($rows as $row){
                    $queue_data = [
                        'id'=>$row['id'],//Y aliexpress结算报告导入表id
                        'file_md5'=>$row['file_md5'],//Y 文件md5值
                    ];
                    //状态改为“加入队列”
                    $row->save(['status'=>'1']);
                    //加入列队
                    (new UniqueQueuer(AliexpressSettlementImportQueue::class))->push(json_encode($queue_data));
                }
                //更新运行时间
                $this->updateRunTime();
                //休息一秒
                sleep(1);
            }
            
            //运行结束
            $this->runEnd();
            
        } catch (Exception $ex) {
            //运行结束
            $this->runEnd();
            
            throw new TaskException($ex->getMessage());
        }
    }
    
    /**
     * @desc 失败重新跑 
     * @author wangwei
     * @date 2018-12-14 20:05:06
     */
    private function updateRetry(){
        if($this->retry=='2'){
            $up_where = [
                'status'=>'3'
            ];
            if($this->aliexpress_settlement_import_id){
                $up_where['id'] = $this->aliexpress_settlement_import_id;
            }
            AliexpressSettlementImportModel::update(['status'=>'0','count'=>'0'],$up_where);
        }
    }
    
    /**
     * @desc 运行检查
     * @author wangwei
     * @date 2018-9-25 17:34:59
     * @return boolean
     */
    private function runCheck()
    {
        //设置超时时间
        set_time_limit(0);
        
        //获取redis
        $key = $this->task_key_prefix . $this->aliexpress_settlement_import_id;
        if($run_time = Cache::handler()->get($key)){
            return time() - $run_time > $this->task_expired_time;
        }else{
            return Cache::handler()->set($key, time());
        }
    }
    
    /**
     * @desc 更新运行时间
     * @author wangwei
     * @date 2018-9-25 18:34:48
     * @return unknown
     */
    private function updateRunTime()
    {
        $key = $this->task_key_prefix . $this->aliexpress_settlement_import_id;
        return Cache::handler()->set($key, time());
    }
    
    /**
     * @desc 运行结束
     * @author wangwei
     * @date 2018-9-25 18:43:46
     */
    private function runEnd()
    {
        $key = $this->task_key_prefix . $this->aliexpress_settlement_import_id;
        Cache::handler()->del($key);
    }
    
}
