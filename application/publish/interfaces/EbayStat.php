<?php

/**
 * Description of EbayStat
 * @datetime 2017-6-24  17:00:12
 * @author joy
 */

namespace app\publish\interfaces;

use app\common\interfaces\DashboradPublish;
use app\publish\service\EbayPublishService;
use app\common\model\GoodsPublishMap;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
class EbayStat implements DashboradPublish {
    //获取未刊登数量
    public function getNotyetPublish(){
        $where['status']=['eq',0];   
        $where['channel']=['eq',1];  
        $where['platform_sale']=['eq',1];
        //$where['publish_status']=['eq',0];
        $total = GoodsPublishMap::where($where)->count('id');
        return $total;
    }

    //获取刊登中数量
    public function getListingIn(){
        $model = new  EbayListing;
        $total = $model->where(['listing_status'=>2])->count();
        return $total;
    }
    
    //获取刊登异常数量
    public function getExceptionListing(){
        $model = new  EbayListing;
        $total = $model->where(['listing_status'=>4])->count();
        return $total;
    }
    

    //获取停售待下架数量
    public function getStopSellWaitRelisting(){
        $model = new  EbayListingVariation;
        $total = $model->alias('a')->join('goods_sku b','a.sku_id=b.id','LEFT')->where(['b.status'=>2])->count();
        return $total;
    }
}
