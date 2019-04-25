<?php
namespace app\customerservice\task;

use app\index\service\AbsTasker;
use app\common\exception\TaskException;
use think\Exception;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\AliIssueQueue;
use app\common\model\aliexpress\AliexpressIssue;

class AliIssue extends AbsTasker
{
    public function getCreator() {
        return 'Johnny';
    }

    public function getDesc() {
        return '拉取Aliexpress纠纷列表';
    }

    public function getName() {
        return '拉取Aliexpress纠纷列表';
    }

    public function getParamRule() {
        return [];
    }

    public function execute()
    {
        try {
            $accountList = Cache::store('AliexpressAccount')->getTableRecord();
            $syncTimeList = Cache::store('AliexpressAccount')->taskIssueTime();
            foreach($accountList as $k=>&$v){
                //已启用，并且已授权过
                if ($v && $v['is_invalid'] && $v['is_authorization']) {
                    $v['issue_sync_time'] = $syncTimeList[$v['id']] ?? 0;
                }else{
                    unset($accountList[$k]);
                }
            }
            $sortField = array_column($accountList,'issue_sync_time');
            array_multisort($sortField, SORT_ASC, SORT_NUMERIC, $accountList);//按更新时间升序
            //拉取所有账号的订单信息
            foreach ($accountList as $n) {
                $data['page_size']= 50;
                $data['current_page'] = 1;
                $data['buyer_login_id']=null;
                $data['issue_status']=null;
                $data['order_no'] =null;
                $data['id'] = $n['id'];
                //$data['task_type'] = 1;//标志task拉取拉取
                
                /*
                 * 分别抓取各个状态的纠纷
                 * wangwei 2018-9-18 11:15:58
                 */
                foreach (AliexpressIssue::ISSUE_STATUS as $issue_status){
                    $data['issue_status'] = $issue_status;
                    (new UniqueQueuer(AliIssueQueue::class))->push(json_encode($data));
                }
                unset($data);
            }
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }

}

