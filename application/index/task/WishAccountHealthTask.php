<?php

namespace app\index\task;

use app\common\cache\Cache;
use app\common\model\wish\WishAccountHealthList;
use app\index\queue\WishAccountHealthSendQueue;
use app\index\service\AbsTasker;
use app\common\service\UniqueQueuer;


class WishAccountHealthTask extends AbsTasker
{
    public function getName()
    {
        return "Wish健康监控";
    }
    
    public function getDesc()
    {
        return "Wish健康监控发起任务";
    }
    
    public function getCreator()
    {
        return "冬";
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
        $cache = Cache::store('wishAccount');
        $account_list = $cache->getAccount();

        $queue = new UniqueQueuer(WishAccountHealthSendQueue::class);

        $model = new WishAccountHealthList();
        $lists = $model->where(['status' => 1])->column('id', 'wish_account_id');

        //这里读取缓存文件中的信息，并且发起一个或多个任务调用
        foreach ($account_list as $k => $v) {
            if ($v['is_invalid'] != 1 || $v['download_health'] == 0) {
                continue;
            }

            //所有在这里面的
            if (isset($lists[$v['id']])) {
                unset($lists[$v['id']]);
            }

            $time = $cache->getWishLastDownloadHealthTime($v['id']);
            if (!empty($time)) {
                $diff = time() - $time;
                if ($diff < $v['download_health'] * 60 - 60) {
                    continue;
                }
            }

            //往往入队列去发送信息；
            $queue->push($v['id']);
        }

        //标记不在上述列里面的记录，是因为帐号无效，或者没有开启帐号健康，所以应该关闭
        if (!empty($lists)) {
            $model->update(['status' => 0], ['id' => ['in', array_values($lists)]]);
        }
    }
}