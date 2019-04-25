<?php
namespace app\publish\task;
/**
 * rocky
 * 17-8-25
 * ebay监听同步listing数据账号信息
*/

use app\index\service\AbsTasker;
use app\publish\queue\EbayGetListQueuer;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;

class EbayGetListingsQueuer extends AbsTasker
{
    public function getName()
    {
        return "ebay获取在线listing账号管理队列";
    }
    
    public function getDesc()
    {
        return "ebay获取在线listing账号管理队列";
    }
    
    public function getCreator()
    {
        return "曾绍辉";
    }
    
    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        $this->syncEbayListingAccount();
    }

    public function syncEbayListingAccount()
    {
        $queuer = new UniqueQueuer(EbayGetListQueuer::class);        
        $cache = Cache::store('EbayAccount');
        $accounts = $cache->getAccount();#获取ebay销售账号
        $time = time();
        foreach($accounts as $ac){
            $info = $cache->getOrderSyncTime($ac['id']);
            if (empty($ac['download_listing']) || !$ac['is_vallid'] || empty($ac['token'])) {
                continue;
            }
            if($ac['download_listing'] && $info && $time - $info['endTime'] < $ac['download_listing']*60){
                continue;
            }
            $queuer->push($ac['id']);
        }
    }
}
