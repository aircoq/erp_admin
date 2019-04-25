<?php
namespace app\publish\task;
/**
 * rocky
 * 17-12-25
 * ebay获取促销方式账号队列
*/

use app\index\service\AbsTasker;
use think\Db;
use app\publish\queue\EbayGetPromotionListQueuer;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;

class EbayGetPromotionList extends AbsTasker
{
    public function getName()
    {
        return "ebay获取促销方式账号队列";
    }
    
    public function getDesc()
    {
        return "ebay获取促销方式账号队列";
    }
    
    public function getCreator()
    {
        return "zengsh";
    }
    
    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        $this->syncEbayListingAccount();
    }

    public function syncEbayListingAccount(){
        set_time_limit(0);
        $queuer = new UniqueQueuer(EbayGetPromotionListQueuer::class);
        $accounts = Cache::store('account')->ebayAccount();#获取ebay销售账号
        $ids = [];
        $t = time();
        foreach($accounts as $ac){
            if($ac['download_listing'] && $t-$ac['end']>$ac['download_listing']*60){
                $queuer->push($ac['id']);
            }
        }
    }
}
