<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/7/12
 * Time: 21:00
 */

namespace app\index\task;


use app\common\cache\Cache;
use app\common\model\ebay\EbayAccountHealth;
use app\index\service\AbsTasker;
use app\common\service\UniqueQueuer;
use app\common\model\ebay\EbayAccount;
use app\index\queue\EbayAccountHealthQueue;
use think\Exception;

class EbayAccountHealthTask extends AbsTasker
{
    public function getName()
    {
        return "Ebay健康监控入队";
    }

    public function getDesc()
    {
        return "Ebay健康监控入队";
    }

    public function getCreator()
    {
        return "wlw2533";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        try {
            $this->pushQueue();
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * 将需要同步健康状况数据的所有账号id压入队列
     * @throws Exception
     */
    public function pushQueue()
    {
        try {
            $map['is_invalid'] = 1;
            $map['account_status'] = 1;
            $map['health_monitor'] = ['gt',0];
            $ids = EbayAccount::where($map)->column('id,health_monitor');

            foreach ($ids as $id => $interval) {
                //找出最后抓取时间，此时与最后抓取时间间隔不够时间，则跳过；
                $last_update = Cache::store('EbayAccount')->ebayLastUpdateTime($id, 'health');
                if (isset($last_update['last_update_time'])) {
                    $lastExcuteTime = $last_update['last_update_time'];
                    if (time() - $lastExcuteTime < $interval * 60) {
                        continue;
                    }
                }

                (new UniqueQueuer(EbayAccountHealthQueue::class))->push($id);
            }
//            $logAccountIds = EbayAccountHealth::distinct(true)->column('account_id');//有记录的账号
//            //获取之前抓取过，当天没有记录的账号
//            $wh['account_id'] = ['in', $ids];
//            $todayBeginTime = strtotime(date('Y-m-d', time()));//当天开始时间
//            $wh['create_time'] = ['gt', $todayBeginTime];
//            $todayLogAccountIds = EbayAccountHealth::distinct(true)->where($wh)->column('account_id');//当天有记录的账号
//            $noTodayLogAccountIds = array_diff($logAccountIds, $todayLogAccountIds);//没有当天记录的账号
//            $noLogAccountIds = array_diff($ids, $logAccountIds);//没有记录的账号
//            $needExecuteAccountIds = array_merge($noLogAccountIds, $noTodayLogAccountIds);//实际需要执行抓取的账号
//            $needExecuteAccountIds = array_unique($needExecuteAccountIds);//去除重复的
//
//            foreach ($needExecuteAccountIds as $k => $needExecuteAccountId) {
//                (new UniqueQueuer(EbayAccountHealthQueue::class))->push($needExecuteAccountId);
//            }
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

}