<?php
namespace app\customerservice\task;

use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\common\service\CommonQueuer;
use app\customerservice\queue\EbayFeedbackQueue;
use think\Exception;

class EbayFeedback extends AbsTasker{
    public function getName()
    {
        return "Ebay评价";
    }

    public function getDesc()
    {
        return "Ebay评价信息";
    }

    public function getCreator()
    {
        return "冬";
    }

    public function getParamRule()
    {
        return [
            'downTime|下载时间' => 'require|select:正常下载:0,1天前到现在:1,3天前到现在:3,5天前到现在:5,10天前到现在:10,20天前到现在:20,30天前到现在:30,40天前到现在:40,50天前到现在:50,60天前到现在:60'
        ];
    }

    public function execute()
    {
        try {
            $this->push_queue();
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
    
    
    /**
     * 推送队列
     */
    function push_queue()
    {
        try {
            $down_time = (int)$this->getData('downTime');
            $queuer = new UniqueQueuer(EbayFeedbackQueue::class);
            $accountList = Cache::store('EbayAccount')->getTableRecord();
            foreach ($accountList as $k => $v) {
                //开发者信息不全的,直接跳过;
                if ($v['is_invalid'] == 0  || $v['sync_feedback'] == 0|| empty($v['dev_id']) || empty($v['app_id']) || empty($v['cert_id']) || empty($v['token'])) {
                    continue;
                }
                //找出最后下载时间，此时与下载时间间隔不够时间，则跳过；
                $last_update = Cache::store('EbayAccount')->ebayLastUpdateTime($v['id'], 'feedback');
                if (isset($last_update['last_download_time'])) {
                    $last_download_time = strtotime($last_update['last_download_time']);
                    if(time() - $last_download_time < $v['sync_feedback'] * 60) {
                        continue;
                    }
                }
                $queuer->push(['account_id' => $v['id'], 'down_time' => $down_time]);
            }
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
     
}