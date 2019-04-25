<?php
namespace app\customerservice\task;

use app\index\service\AbsTasker;
use app\common\exception\TaskException;
use think\Exception;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\AliExpressMsgQueueNew;
use app\customerservice\queue\AliExpressMsgQueue;
class AliexpressMsg extends AbsTasker
{
    public function getCreator() {
        return '黄创松';
    }

    public function getDesc() {
        return 'Aliexpress-拉取站内信关系列表';
    }

    public function getName() {
        return 'Aliexpress-拉取站内信关系列表';
    }

    public function getParamRule() {
        return [
            'status|状态' => '',
        ];
    }
    

    public function execute()
    {
        try {
            $accountList = Cache::store('AliexpressAccount')->getTableRecord();
            //拉取所有账号的订单信息
            foreach ($accountList as $n) {
                //已启用，并且已授权过
                if ($n && $n['is_invalid'] && $n['is_authorization'] && $n['download_message'] > 0) {
                    //下载周期验证
                    $timeArr = Cache::store('AliexpressAccount')->taskMsgTime($n['id']);
                    if(isset($timeArr['aliexpressMsg']) && !empty($timeArr['aliexpressMsg'])){
                        $diff = time()-strtotime($timeArr['aliexpressMsg']);//上次抓取和这次抓取的差值（秒）
                        if($diff < $n['download_message']*60){
                            continue;
                        }
                        $data['only_un_dealed'] = false;//是否只查询未处理会话
                    }
                   /* else{
                       $data['start_time']=strtotime('-7 days');
                    }*/
                    $data['id'] = $n['id'];
                    $data['only_un_readed'] = false;//是否只查询未读会话
                    $data['rank'] = null;//标签值(0,1,2,3,4,5)依次表示为白，红，橙，绿，蓝，紫

                    $data['task_type'] = 1;//标志task拉取拉取
                    (new UniqueQueuer(AliExpressMsgQueueNew::class))->push(json_encode($data));
                    unset($data);
                }
            }
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }
}
