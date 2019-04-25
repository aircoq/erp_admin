<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2018/12/26
 * Time: 19:47
 */

namespace app\publish\queue;


use app\common\model\ebay\EbayActionLog;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\GoodsSku;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\listing\queue\EbayEndItemQueue;
use app\publish\helper\ebay\EbayPublish;
use app\publish\service\EbayListingService;
use think\Exception;

class EbaySkuLocalStatusChange extends SwooleQueueJob
{
    protected $maxFailPushCount=3;

    public function getName():string
    {
        return 'ebay SKU本地状态变化处理';
    }

    public function getDesc():string
    {
        return 'ebay SKU本地状态变化处理';
    }

    public function getAuthor():string
    {
        return 'wlw2533';
    }

    public  function execute()
    {
        set_time_limit(0);
        try {
            $params = $this->params;
            $skuId = $params['sku_id'];
            $field = 'goods_id,sku,id';
            $sku = GoodsSku::where('id', $skuId)->field($field)->find();
            if (empty($sku)) {
                throw new Exception('获取sku信息失败');
            }
            $field = 'id,variation,account_id,item_id,listing_sku,site,local_sku';
            $wh = [
                'draft' => 0,
                'item_id' => ['<>',0],
                'listing_status' => ['in',EbayPublish::OL_PUBLISH_STATUS],
                'goods_id' => $sku['goods_id'],
            ];
            $listingItemIds = EbayListing::where($wh)->field($field)->column($field,'id');

            $sListingItemIds = [];//记录单属性信息
            $varListingItemIds = [];//记录多属性信息
            foreach ($listingItemIds as $lid => $listingItemId) {
                if ($listingItemId['variation']) {//多属性
                    $varListingItemIds[$lid] = $listingItemId;
                } else {//单属性
                    $sListingItemIds[$lid] = $listingItemId;
                }
            }
            $listingItemIds = $sListingItemIds;//单属性的下架
            //多属性的库存调0
            $varWh = [
                'sku_id' => $skuId,
                'listing_id' => ['in',array_keys($varListingItemIds)],
            ];
            $variants = EbayListingVariation::where($varWh)->column('id,channel_map_code,listing_id','id');
            $data = [];
            foreach ($variants as $variant) {
                $data[] = [
                    'item_id' => $varListingItemIds[$variant['listing_id']]['item_id']??0,
                    'listing_sku' => $variant['channel_map_code'],
                    'account_id' => $varListingItemIds[$variant['listing_id']]['account_id']??0,
                    'site' => $varListingItemIds[$variant['listing_id']]['site']??0,
                    'remark' => '本地SKU状态变化调0',
                    'quantity' => 0,
                ];
            }
            if ($data) {
                (new EbayListingService($params['operator_id']))->updatePriceQty($data);
            }
            $log = [
                'new_val' => [
                    'end_type' => 3,
                ],
                'remark' => '本地SKU状态变化下架',
                'old_val' => '',
                'api_type' => 5,//本地SKU状态变化推送下架
                'create_id' => $params['operator_id'],
            ];
            foreach ($listingItemIds as $listing) {
                if ($listing['local_sku'] != $sku['sku']) {//多属性按单属性上架时，如果sku不对应，不下架
                    continue;
                }
                //下架走日志
                $log['item_id'] = $listing['item_id'];
                $log['account_id'] = $listing['account_id'];
                $log['listing_sku'] = $listing['listing_sku'];
                $log['new_val'] = json_encode($log['new_val']);
                EbayActionLog::create($log);
                $logId = (new EbayActionLog())->getLastInsID();
                EbayListing::update(['listing_status'=>EbayPublish::PUBLISH_STATUS['inEndQueue']],['item_id'=>$listing['item_id']]);
                (new UniqueQueuer(EbayEndItemQueue::class))->push($logId);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
