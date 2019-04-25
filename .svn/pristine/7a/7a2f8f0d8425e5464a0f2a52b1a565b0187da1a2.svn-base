<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/4/11
 * Time: 14:37
 */

namespace app\publish\task;


use app\common\cache\Cache;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\GoodsSku;
use app\index\service\AbsTasker;
use app\publish\helper\ebay\EbayPublish;
use think\Db;

class EbayMergeListingTable extends AbsTasker
{
    public function getName()
    {
        return "ebay单属性多属性表合并";
    }

    public function getDesc()
    {
        return "ebay单属性多属性表合并";
    }

    public function getCreator()
    {
        return "wlw2533";
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        $lastId = Cache::handler()->get('ebay:task:ebay_merge_listing_table:id');
        $lastId = $lastId ?: 0;

        //获取在线单属性
        $field = 'id,goods_id,local_sku,start_price,reserve_price,buy_it_nowprice,quantity,sku,listing_sku,cost_price,
            adjusted_cost_price,sold_quantity';
        $sql = "select $field from ebay_listing where  ((draft=0 and item_id<>0 and listing_status in (3,5,6,7,8,9,10)) 
          or create_date>".(time()-90*86400)." or end_date>".(time()-90*86400).") and variation=0 and id>$lastId limit 1000";
        $listings = Db::query($sql);
        if (!$listings) {
            return;
        }
        $listings = collection($listings)->toArray();
        $localSkus = array_column($listings,'local_sku');

        $skuIds = GoodsSku::whereIn('sku',$localSkus)->column('id','sku');

        //查询变体表中是否已存在，避免重复创建，单属性时主表与变体表时一对一的关系
        $listingIds = array_column($listings,'id');
        $varIds = EbayListingVariation::whereIn('listing_id',$listingIds)->column('id','listing_id');

        $vars = [];
        foreach ($listings as $listing) {
            $tmpVar = [
                'listing_id' => $listing['id'],
                'goods_id' => $listing['goods_id'],
                'v_sku' => $listing['local_sku'],
                'sku_id' => $skuIds[$listing['local_sku']]??0,
                'v_price' => $listing['start_price'],
                'v_qty' => $listing['quantity'],
                'combine_sku' => $listing['sku'],
                'channel_map_code' => $listing['listing_sku'],
                'cost_price' => $listing['cost_price'],
                'adjusted_cost_price' => $listing['adjusted_cost_price'],
                'reserve_price' => $listing['reserve_price'],
                'buy_it_nowprice' => $listing['buy_it_nowprice'],
                'variation' => json_encode([]),
                'v_sold' => $listing['sold_quantity'],
            ];
            if (isset($varIds[$listing['id']])) {
                $tmpVar['id'] = $varIds[$listing['id']];
            }
            $vars[] = $tmpVar;
        }
        (new EbayListingVariation())->saveAll($vars);
        Cache::handler()->set('ebay:task:ebay_merge_listing_table:id',$listing['id']);

    }

}