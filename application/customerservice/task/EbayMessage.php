<?php
namespace app\customerservice\task;

use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\common\service\CommonQueuer;
use app\customerservice\queue\EbayMessageQueue;
use app\customerservice\queue\EbayMyMessageQueue;
use app\customerservice\queue\EbayOutboxMessageQueue;

class EbayMessage extends AbsTasker{
    public function getName()
    {
        return "Ebay站内信";
    }

    public function getDesc()
    {
        return "Ebay站内信";
    }

    public function getCreator()
    {
        return "TanBin";
    }

    public function getParamRule()
    {
        return [
            'downTime|下载时间' => 'require|select:正常下载:0,1天前到现在:1,3天前到现在:3,5天前到现在:5,10天前到现在:10,20天前到现在:20,30天前到现在:30,40天前到现在:40,50天前到现在:50,60天前到现在:60'
        ];
    }

    public function execute()
    {
           $this->push_queue();
    }

    /**
     * 推送队列
     */
    function push_queue()
    {
        $accountList = Cache::store('EbayAccount')->getTableRecord();

        $down_time = (int)$this->getData('downTime');

        $msgQueue = new UniqueQueuer(EbayMessageQueue::class);
        $mmsgQueue = new UniqueQueuer(EbayMyMessageQueue::class);
        $outMsgQueuenew = new UniqueQueuer(EbayOutboxMessageQueue::class);
        foreach ($accountList as $k => $v) {
            //开发者信息不全的,直接跳过;
            if ($v['is_invalid'] == 0 || $v['download_message'] == 0 || empty($v['dev_id']) || empty($v['app_id']) || empty($v['cert_id']) || empty($v['token'])) {
                continue;
            }
            //找出最后下载时间，此时与下载时间间隔不够时间，则跳过；
            $last_update = Cache::store('EbayAccount')->ebayLastUpdateTime($v['id'], 'memberMessage');
            if (isset($last_update['last_download_time'])) {
                $last_download_time = strtotime($last_update['last_download_time']);
                if(time() - $last_download_time < $v['download_message'] * 60) {
                    continue;
                }
            }

            $param = ['account_id' => $v['id'], 'down_time' => $down_time];

            $msgQueue->push($param);
            $mmsgQueue->push($param);
            $outMsgQueuenew->push($param);
        }
    }


}