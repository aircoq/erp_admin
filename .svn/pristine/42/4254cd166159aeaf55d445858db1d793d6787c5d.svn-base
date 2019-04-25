<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-25
 * Time: 下午5:23
 */

namespace app\listing\task;


use app\common\cache\Cache;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\listing\queue\ShopeeGetItemsListQueue;
use think\Exception;

class ShopeeGetItemsList extends AbsTasker
{
    public function getName()
    {
        return 'shopee获取商品列表';
    }

    public function getDesc()
    {
        return 'shopee获取商品列表';
    }

    public function getCreator()
    {
        return 'joy';
    }

    public function getParamRule()
    {
       return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $accounts = Cache::store('ShopeeAccount')->getAllCount();

            $filter=[
                ['shop_id','>','0'],
                ['key','!=',''],
            ];
            $invalidAccounts = Cache::filter($accounts,$filter);
            if($invalidAccounts){
                foreach ($invalidAccounts as $account){
                    if($this->getGrapListingStatus($account)){
                        (new UniqueQueuer(ShopeeGetItemsListQueue::class))->push($account['id']);
                    }
                }
            }
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }

    private function getGrapListingStatus($account)
    {
        $last_execute_time = Cache::store('ShopeeAccount')->getListingSyncTime($account['id']);
        if (!isset($last_execute_time)) {
            return true;
        }
        $leftTime = time() - $last_execute_time;
        return $leftTime >= $account['download_listing'] * 60 ? true : false;
    }
}