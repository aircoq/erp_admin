<?php
namespace app\index\task;

use app\common\service\UniqueQueuer;
use app\index\queue\EbayNotificationOperationQueue;
use app\index\service\AbsTasker;
use app\common\cache\Cache;



class EbayNotificationOperation extends AbsTasker{
    public function getName()
    {
        return "Ebay帐号开关通知";
    }

    public function getDesc()
    {
        return "Ebay帐号开关通知";
    }

    public function getCreator()
    {
        return "冬";
    }

    public function getParamRule()
    {
        return [
            'operation|全局通知开关' => 'require|select:关闭:0,开启:1'
        ];
    }

    public function execute()
    {
        set_time_limit(0);
        $operation = $this->getData('operation', 0);

        $queue = new UniqueQueuer(EbayNotificationOperationQueue::class);
        $accountList    = Cache::store('EbayAccount')->getTableRecord();
        foreach ($accountList as $k => $v) {
            //开发者信息不全的,直接跳过;
            if (empty($v['dev_id']) || empty($v['app_id']) || empty($v['cert_id']) || empty($v['token'])) {
                continue;
            }
            if ($v['is_invalid'] != 1) {
                continue;
            }
            $queue->push(['account_id' => $v['id'], 'operation' => $operation]);
        }
        return true;
    }
    
}