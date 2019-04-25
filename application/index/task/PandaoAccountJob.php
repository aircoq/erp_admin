<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-11
 * Time: 上午9:35
 */

namespace app\index\task;


use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\index\queue\PandaoAccountQueue;
use app\index\service\AbsTasker;

class PandaoAccountJob extends AbsTasker
{
    private $accountCache = null;
    public function getName()
    {
        return 'pandao账号token刷新任务';
    }

    public function getDesc()
    {
        return 'pandao账号token刷新任务';
    }

    public function getCreator()
    {
        return 'joy';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $cacheDriver = Cache::store('PandaoAccountCache');
            $this->accountCache = $cacheDriver;
            $accounts  = $cacheDriver->getAllAccounts();
            $where=[
                [
                'expiry_time','<=',strtotime(' -1.2 hour')
                ],
                [
                    'is_authorization','==',1
                ]
            ];
            $unvalidAccounts = Cache::filter($accounts,$where);
            if($unvalidAccounts){
                $queueDriver = new UniqueQueuer(PandaoAccountQueue::class);
                foreach ($unvalidAccounts as $account){
                    $queueDriver->push($account['id']);
                }
            }
        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }
    private function pushToQueue($accounts)
    {
        //token一个小时有效
        $where=['expiry_time','>=',strtotime(' +1 hour')];
        $unvalidAccounts = Cache::filter($accounts,$where);

        if($unvalidAccounts){
            $queueDriver = new UniqueQueuer(PandaoAccountQueue::class);
            foreach ($unvalidAccounts as $account){
                $id = $account['id'];
                //如果账号的有效期小于当前时间
                if($account['expiry_time']<=time()){
                    //$this->accountCache->updateTableRecord($id, 'is_authorization', 0);
                    $this->accountCache->updateTableRecord($id,'enabled',0);
                }

                $queueDriver->push($account['id']);
            }
        }
    }
}