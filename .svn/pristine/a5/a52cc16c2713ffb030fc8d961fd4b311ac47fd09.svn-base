<?php

/**
 * Description of WishStat
 * @datetime 2017-5-15  17:08:12
 * @author joy
 */

namespace app\publish\interfaces;

use app\common\interfaces\DashboradPublish;
use app\publish\service\WishHelper;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\model\GoodsPublishMap;
class WishStat implements DashboradPublish {
    //获取未刊登数量
    public function getNotyetPublish(){
        //$helper = new WishHelper;
        $where['status']=['eq',0];   
        $where['channel']=['eq',3];  
        $where['platform_sale']=['eq',1];
        //$where['publish_status']=['eq',0];
        $total = GoodsPublishMap::where($where)->count('id');
        return $total;
    }

    //获取刊登中数量
    public function getListingIn(){
        $helper = new WishHelper;
        $post['status']=0;
        $wheres = $helper->getWhere($post);
        $total = $helper->getListCount($wheres);
        return $total;
    }
    
    //获取刊登异常数量
    public function getExceptionListing(){
        $helper = new WishHelper;
        $post['status']=3;
        $wheres = $helper->getWhere($post);
        $total = $helper->getListCount($wheres);
        return $total;
    }
    

    //获取停售待下架数量
    public function getStopSellWaitRelisting(){
        //$model = new \app\common\model\Goods;
        //$total = $model->alias('g')->join('wish_wait_upload_product p','g.spu=p.parent_sku','LEFT')->where(['sales_status'=>2])->count();
        $model = new WishWaitUploadProductVariant;
        $total =  $model->alias('a')->join('goods_sku b','a.sku_id=b.id','LEFT')->where(['a.status'=>1,'b.status'=>2])->count();       
        return $total;
    }
}
