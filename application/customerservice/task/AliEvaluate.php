<?php
namespace app\customerservice\task;

use app\index\service\AbsTasker;
use app\common\exception\TaskException;
use think\Exception;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\AliEvaluateQueue;

class AliEvaluate extends AbsTasker
{
    public function getCreator() {
        return 'Jhohnny';
    }
    
    public function getDesc() {
        return '拉取Aliexpress待评价订单';
    }
    
    public function getName() {
        return '拉取Aliexpress待评价订单';
    }
    
    public function getParamRule() {
        return [];
    }
    
    public function execute() {
        try {
            $accountList = Cache::store('AliexpressAccount')->getTableRecord();

            //拉取所有账号的订单信息
            foreach ($accountList as $n) {
                //已启用，并且已授权过
                if ($n && $n['is_invalid'] && $n['is_authorization'] && $n['download_evaluate'] > 0) {
                    //下载周期验证
                    $timeArr = Cache::store('AliexpressAccount')->taskEvaluationTime($n['id']);
                    if(isset($timeArr['evaluationOrderList']) && !empty($timeArr['evaluationOrderList'])){
                        $diff = time()-strtotime($timeArr['evaluationOrderList']);//上次抓取和这次抓取的差值（秒）
                        if($diff < $n['download_evaluate']*60){
                            continue;
                        }
                    }
                   /* else{
                        $data['start_time']=strtotime('-7 days');
                    }*/
                    $data['id'] = $n['id'];
                    $data['task_type'] = 1;//标志task拉取拉取
                    (new UniqueQueuer(AliEvaluateQueue::class))->push(json_encode($data));
                    unset($data);
                }
            }
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }
    
}

