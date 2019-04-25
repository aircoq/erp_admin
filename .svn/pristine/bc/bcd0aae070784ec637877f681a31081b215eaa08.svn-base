<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressIssue as AliexpressIssueModel;

class AliexpressIssue extends Cache
{

    private $taskPrefix_issue = 'task:aliexpress:issue:';

    /**
     * @title 把纠纷ID和状态存到缓存中
     * @param $account_id 账号登录id
     * @param $issue_id 纠纷id
     * @param $issue_status 纠纷状态
     */
    public  function setIssueStatus($account_id, $issue_id, $issue_status)
    {
        $key = $this->taskPrefix_issue.$account_id;
        $this->redis->hset($key, $issue_id, $issue_status);
    }

    /**
     * @title 获取纠纷状态
     * @param $account_id 登录账号
     * @param $issue_id 纠纷ID
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public  function getIssueStatus($account_id, $issue_id)
    {
        /*$key = $this->taskPrefix_issue.$account_id;
        if ($this->redis->hexists($key, $issue_id)){//先去缓存拿
            $issue_status=$this->redis->hget($key, $issue_id);
        }
        else //如果缓存没有去数据库拿
        {*/
            $result = AliexpressIssueModel::where(['issue_id' => $issue_id])->find();
            $issue_status = $result ? $result['issue_status'] : '';
            /*if($result){//如果数据库有，缓存没有，把数据库数据存缓存
                $this->redis->hset($key, $issue_id, $issue_status);
            }
        }*/
        
        return $issue_status;

    }

}
