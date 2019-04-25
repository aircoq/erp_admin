<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-25
 * Time: 下午6:00
 */

namespace app\listing\queue;


use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\publish\service\ShopeeApiService;
use think\Exception;

class ShopeeGetItemDetail extends SwooleQueueJob
{
    private $cacheDriver = null;
    private $accountId=0;
    public function getName(): string
    {
        return 'shopee获取商品详情队列';
    }

    public function getDesc(): string
    {
        return 'shopee获取商品详情队列';
    }

    public function getAuthor(): string
    {
        return 'joy';
    }
    public function init()
    {
        $this->cacheDriver = Cache::store('ShopeeAccount');
    }

    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    public function execute()
    {
        try{
            $params = $this->params;

            if($params){
                list($accountId,$itemId) = explode('|',$params);
                $account = $this->cacheDriver->getId($accountId);
                if($account){
                    $response = ShopeeApiService::GetItemDetail($account,$itemId);
                    if(isset($response['item']) && $response['item']){

                        $item = ShopeeApiService::managerItemDetailData($response['item']);

                        if(isset($item['product']) && $item['product'] && isset($item['info']) && isset($item['variants'])){
                            ShopeeApiService::saveItemData($accountId,$item);
                        }
                    }
                }
            }
        }catch (Exception $exp){
            throw new Exception("{$exp->getMessage()}");
        }
    }

}