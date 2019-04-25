<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2018/11/19
 * Time: 21:00
 */

namespace app\publish\task;


use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use app\index\service\AbsTasker;

class EbayBackWriteMaxMinPrice extends AbsTasker
{
    public function getName()
    {
        return "ebay listing最高最低价格";
    }

    public function getDesc()
    {
        return "ebay listing最高最低价格";
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
        set_time_limit(0);
        $wh['variation'] = 0;
        $wh['min_price'] =['neq',0.00];
        $wh['max_price'] =['neq',0.00];

        EbayListing::field('id,start_price,max_price,min_price')->where($wh)->chunk(1000,function($models) {
            foreach ($models as &$model) {
                $model = $model->toArray();
                $model['max_price'] = $model['start_price'];
                $model['min_price'] = $model['start_price'];
            }
            (new EbayListing())->saveAll($models);
        });
        EbayListingVariation::field('max(v_price) max_price,min(v_price) min_price,listing_id id')->group('listing_id')
            ->chunk(1000,function($models) {
                $models = collection($models)->toArray();
                (new EbayListing())->saveAll($models);
            });

    }

}