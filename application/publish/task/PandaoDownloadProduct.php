<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-21
 * Time: 上午10:57
 */

namespace app\publish\task;


use app\common\cache\Cache;
use app\common\exception\TaskException;
use app\common\model\pandao\PandaoAccount;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\queue\PandaoProductJobQueue;
use app\publish\service\PandaoApiService;
use think\Exception;

class PandaoDownloadProduct extends AbsTasker
{

    public function getName()
    {
        return "pandao批量下载商品数据";
    }

    public function getDesc()
    {
        return "pandao批量下载商品数据";
    }

    public function getCreator()
    {
        return "joy";
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
            $caches= $cacheDriver->getAccounts();
            $where=[
                ['is_authorization','==',1],
                ['is_invalid','==',1],
                ['sync_listing','>',0],
            ];
            $accounts = Cache::filter($caches,$where);
            $this->download($accounts);
        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }
    /**
     * 执行下载csv文件
     * @param $accounts
     * @throws Exception
     */
    private function download($accounts){
        foreach ($accounts as $account)
        {
            if($this->getGrapListingStatus($account))
            {
                $response = PandaoApiService::downloadJob($account);
                if(isset($response['code']) && $response['code']==0){
                    $queue = $account['id'].'|'.$response['data']['job_id'];
                    (new UniqueQueuer(PandaoProductJobQueue::class))->push($queue);
                    //$this->setGrapListingStatus($account);
                }
            }
        }
    }
    private function getGrapListingStatus($account)
    {
        $last_execute_time = Cache::store('PandaoAccountCache')->getListingSyncTime($account['id']);
        if (!isset($last_execute_time)) {
            return true;
        }
        $leftTime = time() - $last_execute_time;
        return $leftTime >= $account['sync_listing'] * 60 ? true : false;
    }
    private function setGrapListingStatus($account)
    {
        Cache::store('PandaoAccountCache')->setListingSyncTime($account['id'],time());
    }


}