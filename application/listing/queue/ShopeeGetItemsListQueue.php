<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-25
 * Time: 下午5:36
 */

namespace app\listing\queue;


use app\common\cache\Cache;
use app\common\service\CommonQueuer;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\publish\service\ShopeeApiService;
use service\shopee\ShopeeApi;
use think\Exception;

class ShopeeGetItemsListQueue extends SwooleQueueJob
{
    private $cacheDriver = null;
    private $accountId=0;
    private $config=null;
    public function getName(): string
    {
        return 'shopee获取商品列表队列';
    }

    public function getDesc(): string
    {
        return 'shopee获取商品列表队列';
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
        return 10;
    }

    public function execute()
    {
        try{
            $params = $this->params;
            if($params){
                $this->accountId = $params;
                $account = $this->cacheDriver->getId($params);
                $this->config = $account;
                if($account){
                    $updateTimeFrom = $this->getItemUpdateTime($this->accountId);
                    $this->getItemList($account,$updateTimeFrom);
                }
            }
        }catch (Exception $exp){
            throw new Exception("{$exp->getMessage()}");
        }
    }
    private function getItemUpdateTime(){
        return $update_time = $this->cacheDriver->getListingUpdateTime($this->accountId);
    }
    private function getItemListRequest($config,$params){
        $response = ShopeeApi::instance($config)->loader('Item')->getItemsList($params);
        if(isset($response['items']) && isset($response['more']))
        {
            return ['items'=>$response['items'],'more'=>$response['more']];
        }else{
            return ['items'=>[],'more'=>false];
        }
    }
    private function getRequestParams(){

    }
    private function getItemList($account,$updateTimeFrom='',$page=1){
            $params=[
                'pagination_offset'=>$page,
                'pagination_entries_per_page'=>100,
            ];
            if($updateTimeFrom){
                $params['update_time_from']=(int)$updateTimeFrom;
            }

            $response = $this->getItemListRequest($account,$params);

            if(isset($response['items']) && $response['items'])
            {
                $items = $response['items'];
                foreach ($items as $item){
                    $queue = $this->accountId.'|'.$item['item_id'];

                    (new UniqueQueuer(ShopeeGetItemDetail::class))->push($queue);
                    //$this->saveItemData($item['item_id']);
                }
                if(isset($response['more']) && $response['more']){
                    $page = $page + 1;
                    $this->getItemList($account,$updateTimeFrom,$page);
                }
            }
            $this->cacheDriver->setListingUpdateTime($this->accountId,time());
    }

    private function saveItemData($itemId){
        $response = ShopeeApiService::GetItemDetail($this->config,$itemId);
        if(isset($response['item']) && $response['item']){
            $item = ShopeeApiService::managerItemDetailData($response['item']);
            if(isset($item['product']) && $item['product'] && isset($item['info']) && isset($item['variants'])){
                ShopeeApiService::saveItemData($this->accountId,$item);
            }
        }
    }
}