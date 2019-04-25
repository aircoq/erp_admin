<?php
namespace app\publish\task;

/**
 * 曾绍辉
 * 17-11-2
 * 同步线上修改的listing信息
*/
use app\index\service\AbsTasker;
use think\Db;
use app\publish\queue\EbayGetSellerEventsQueuer;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;

class EbayGetSellerEvents extends AbsTasker
{
    public function getName()
    {
        return "同步线上修改的listing信息";
    }
    
    public function getDesc()
    {
        return "同步线上修改的listing信息";
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
        self::GetSellerEvents();      
    }

    public function GetSellerEvents()
    {
        set_time_limit(0);
        $queuer = new UniqueQueuer(EbayGetSellerEventsQueuer::class);
        $accounts = Cache::store('account')->ebayAccount();#获取ebay销售账号        
        $ids = [];
        $t = time();
        foreach($accounts as $ac){
            if($ac['download_listing']){
                $queuer->push($ac['id']);
            }
        }
    }

}