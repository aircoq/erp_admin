<?php
namespace app\customerservice\task;

use app\common\cache\Cache;
use app\index\service\AbsTasker;
use app\common\service\CommonQueuer;
use app\customerservice\queue\EbayQuiriesQueue;


class EbayInquiries extends AbsTasker
{
    public function getName()
    {
        return "EbayInquiries";
    }

    public function getDesc()
    {
        return "EbayInquiries";
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
        $this->push_queue();
    }


    /**
     * 推送队列
     */
    function push_queue()
    {
        //下载时间
        $down_time = (int)$this->getParam('downTime');

        $queuer = new CommonQueuer(EbayQuiriesQueue::class);
        $accountList = Cache::store('EbayAccount')->getTableRecord();
        foreach ($accountList as $k => $v) {
            //开发者信息不全的,直接跳过;
            if ($v['is_invalid'] == 0 || $v['download_message'] == 0 || empty($v['dev_id']) || empty($v['app_id']) || empty($v['cert_id']) || empty($v['token'])) {
                continue;
            }
            //找出最后下载时间，此时与下载时间间隔不够时间，则跳过；
            $last_update = Cache::store('EbayAccount')->ebayLastUpdateTime($v['id'], 'inquiries');
            if (isset($last_update['last_download_time'])) {
                $last_download_time = strtotime($last_update['last_download_time']);
                if (time() - $last_download_time < $v['download_message'] * 60) {
                    continue;
                }
            }
            $params = ['account_id' => $v['id'], 'down_time' => $down_time];
            $queuer->push($params);
        }

    }

}