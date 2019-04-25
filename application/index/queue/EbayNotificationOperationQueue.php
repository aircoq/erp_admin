<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-3
 * Time: 下午1:37
 */

namespace app\index\queue;


use app\common\service\SwooleQueueJob;
use app\index\service\EbaySetNotificationHelper;

class EbayNotificationOperationQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return "Ebay帐号开关通知队列";
    }

    public function getDesc(): string
    {
        return "Ebay帐号开关通知队列";
    }

    public function getAuthor(): string
    {
        return "冬";
    }

    public function execute()
    {
        set_time_limit(0);

        $data = $this->params;
        if (empty($data['account_id'])) {
            return;
        }

        $account_id = $data['account_id'];
        $operation = $data['operation'];

        $service = new EbaySetNotificationHelper();
        $eventArr = $service->events;
        if (empty($eventArr)) {
            return true;
        }
        $sendArr = [];
        //组合成参数；
        foreach ($eventArr as $event) {
            $tmp = [];
            $tmp['title'] = $event['title'];
            $tmp['events'] = [];
            foreach ($event['events'] as $val) {
                $tmpval = [];
                $tmpval['event'] = $val;
                $tmpval['enable'] = (int)$operation;
                $tmp['events'][] = $tmpval;
            }
            $sendArr[] = $tmp;
        }
        $service->setNotificationEvent($account_id, $sendArr);
        return true;
    }
}