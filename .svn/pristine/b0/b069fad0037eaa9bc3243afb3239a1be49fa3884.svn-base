<?php

namespace app\customerservice\task;

use app\common\cache\Cache;
use app\index\service\AbsTasker;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\PaypalDisputeQueue;


class PaypalDispute extends AbsTasker
{
    public function getName()
    {
        return 'Paypal纠纷';
    }

    public function getDesc()
    {
        return 'Paypal纠纷';
    }

    public function getCreator()
    {
        return 'Paypal纠纷';
    }

    public function getParamRule()
    {
        $strstart = '';
        $strend = '';
        for ($i = 1; $i <= 730; $i++) {
            if ($i > 30 && $i <= 60 && $i % 2 != 0) {
                continue;
            } else if ($i > 60 && $i % 10 != 0) {
                continue;
            }
            $strstart .= ','. $i. '天前零点:'. $i;
        }
        return [
            'startTime|下载时间' => 'require|select:正常下载:0'. $strstart
        ];
    }

    //执行方法
    public function execute()
    {
        set_time_limit(0);
        $down_time = intval($this->getData('startTime'));
        $queue = new UniqueQueuer(PaypalDisputeQueue::class);
        //这里读取缓存文件中的信息，并且发起一个或多个任务调用
        $accountList = Cache::store('PaypalAccount')->getTableRecord();
        foreach ($accountList as $k => $v) {
            //var_dump($v);
            if (
                $v['is_invalid'] == 1 &&
                $v['download_dispute'] >= 30 &&
                !empty($v['rest_client_id']) &&
                !empty($v['rest_secret'])
            ) {
                //获取PAYPAL最后下载时间
                $last_update = Cache::store('PaypalAccount')->getDisputeSyncTime($v['id']);
                //下载时间限制；
                if(!empty($last_update['last_update_time'])){
                    //现在的抓取时间减去之前抓取时间超过了设定的时间，才进行抓取，否则跳过，减去60秒给task的时间容错；
                    if (time()-strtotime($last_update['last_update_time']) < $v['download_dispute'] * 60 + 60) {
                        continue;
                    }
                }

                $data = [
                    'account_id' => $v['id'],
                    'down_time' => $down_time
                ];
                $queue->push($data);
            }
        }
    }

}