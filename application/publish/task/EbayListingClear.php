<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/3/5
 * Time: 15:20
 */

namespace app\publish\task;


use app\common\model\ebay\EbayListing;
use app\index\service\AbsTasker;
use app\publish\helper\ebay\EbayPublish;
use function foo\func;

class EbayListingClear extends AbsTasker
{
    public function getName()
    {
        return "ebay清理过期listing";
    }

    public function getDesc()
    {
        return "ebay清理过期listing";
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
        //删除之前旧的范本
        $wh['draft'] = 1;
        $ids = EbayListing::where($wh)->limit(500)->column('id');
        if ($ids) {
            (new EbayPublish())->delListings($ids);
        }
        //删除不是erp刊登的，不在线的
//        $wh = [
//            'draft' => 0,
//            'application' => 0,//不是erp刊登的
//            'listing_status' => ['in',[0,1,2,4,11]],
//            'create_date' => ['<',time()-90*86400],
//            'end_date' => ['<',time()-90*86400],//下架时间也在三个月前
//        ];
//        $ids = EbayListing::where($wh)->limit(500)->column('id');
//        if ($ids) {
//            (new EbayPublish)->delListings($ids);
//        }
        //删除三个月前未刊登过的
        $wh = [
            'draft' => 0,
//            'item_id' => 0,
            'listing_status' => ['in',[0,1,2,4,11]],//不在线
            'create_date' => ['<',time()-90*86400],//三个月前的
            'end_date' => ['<',time()-90*86400],//下架时间也在三个月前
        ];
        $ids = EbayListing::where($wh)->limit(500)->column('id');
        if ($ids) {
            (new EbayPublish)->delListings($ids);
        }
    }

}
