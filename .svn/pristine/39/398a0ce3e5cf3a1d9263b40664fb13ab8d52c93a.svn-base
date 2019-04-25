<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/8/9
 * Time: 9:30
 */

namespace app\publish\queue;


use app\common\model\ebay\EbayListingVariation;
use app\common\model\GoodsSku;
use app\common\service\SwooleQueueJob;
use app\common\model\ebay\EbayListing;
use think\Exception;

class EbayAdjustCostPrice extends SwooleQueueJob
{
    public function getName():string
    {
        return 'eBay处理已上架SKU调价';
    }

    public function getDesc():string
    {
        return 'eBay处理已上架SKU调价';
    }

    public function getAuthor():string
    {
        return 'wlw2533';
    }

    public function execute()
    {
        try {
            $params = $this->params;
            $goodsId = GoodsSku::where(['id'=>$params['sku_id']])->value('goods_id');

            $wh['item_id'] = ['neq', 0];
            $wh['draft'] = 0;
            $wh['application'] = 1;
            $wh['goods_id'] = $goodsId;

            $listings = EbayListing::field('id,variation')->where($wh)->select();
            if (!$listings) {
                return;
            }
            $ids = [];
            $listingIds = [];
            foreach ($listings as $listing) {
                if ($listing['variation']) {
                    $listingIds[] = $listing['id'];
                } else {
                    $ids[] = $listing['id'];
                }
            }
            //更新单属性
            if ($ids) {
                EbayListing::update(['adjusted_cost_price'=>$params['sku_cost'],'cost_price'=>$params['sku_pre_cost'],['id'=>['in',$ids]]]);
            }
            //更新多属性
            if ($listingIds) {
                $map = [
                    'sku_id' => $params['sku_id'],
                    'listing_id' => ['in', $listingIds]
                ];
                EbayListingVariation::update(['adjusted_cost_price'=>$params['sku_cost'],'cost_price'=>$params['sku_pre_cost']], $map);
            }
        }catch (\Exception $e){
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

}