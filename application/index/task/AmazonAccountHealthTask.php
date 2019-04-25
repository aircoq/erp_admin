<?php

namespace app\index\task;

use app\common\cache\Cache;
use app\common\model\amazon\AmazonAccountHealthList;
use app\index\queue\AmazonAccountHealthSendQueue;
use app\index\service\AbsTasker;
use app\common\service\UniqueQueuer;


class AmazonAccountHealthTask extends AbsTasker
{
    public function getName()
    {
        return "亚马逊健康监控";
    }
    
    public function getDesc()
    {
        return "亚马逊健康监控发起任务";
    }
    
    public function getCreator()
    {
        return "libaimin";
    }
    
    public function getParamRule()
    {
        return [];
    }
    
    public function execute()
    {
        $this->startHealth();
    }
    
    /***
     * 获取订单数据 - sdk
     * @return boolean
     */
    public function startHealth()
    {
        $cache = Cache::store('AmazonAccount');
        $time = time();

        $oldAccount = (new AmazonAccountHealthList())->column('update_time','amazon_account_id');
        //这里读取缓存文件中的信息，并且发起一个或多个任务调用
        $account_list = $cache->getAccount();
        foreach ($account_list as $k => $v) {
            if ($v['status'] != 1 || empty($v['download_health'])) {
                continue;
            }

            if(isset($oldAccount[$v['id']]) && $oldAccount[$v['id']] > 0){
                $diff = $time - $oldAccount[$v['id']];
                if ($diff < $v['download_health'] * 60) {
                    continue;
                }
            }
            //往往入队列去发送信息；
            (new UniqueQueuer(AmazonAccountHealthSendQueue::class))->push($v['id']);
        }
    }
}