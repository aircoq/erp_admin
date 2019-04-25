<?php

namespace app\common\model\ebay;

use think\Exception;
use think\Model;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\Loader;
use think\db\Query;
use think\Db;
use app\common\service\Twitter;
use app\common\cache\driver\EbayRsyncListing;
use app\common\service\Common;

class EbayListing extends ErpModel
{
    use ModelFilter;
    public function scopeEbayListing(Query $query,$params)
    {
        if(!empty($params)){
            $query->where('__TABLE__.account_id','in',$params);
//            $query->where(function($q) use ($params){
//                $whOr['__TABLE__.account_id'] = ['in',$params[0]];
////                $whOr['__TABLE__.shared_userid'] = ['neq', 0];
//                $q->whereOr($whOr);
//            });
        }
    }

    public function scopeDepart(Query $query,$params)
    {
        if(!empty($params)){
            $query->where('__TABLE__.account_id','in',$params);
        }
    }


    /**
    * 初始化
    * @return [type] [description]
    */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

//    public  function setListingStatusAttr($v)
//    {
//        if($v=='Completed')
//        {
//            $v=11;
//        }elseif($v=='Active'){
//            $v=3;
//        }elseif($v=='Ended'){
//            $v=11;
//        }
//        return $v;
//    }
    //============关联模型开始===================
    //账号
    public  function account()
    {
        return $this->hasOne(EbayAccount::class, 'id', 'account_id');
    }
    //商品
    public  function goods()
    {
        return $this->hasOne(\app\common\model\Goods::class, 'id', 'goods_id');
    }
    //变体
    public  function variant()
    {
        return $this->hasMany(EbayListingVariation::class, 'listing_id', 'id');
    }

    //图片
    public  function images()
    {
        return $this->hasMany(EbayListingImage::class, 'listing_id', 'id');
    }

    //站点
    public function siteInfo()
    {
        return $this->hasOne(EbaySite::class,'siteid','site',[],'LEFT')->bind('siteid');
    }

    //定时规则
    public function timingInfo()
    {
        return $this->hasOne(EbayListingTiming::class,'id','rule_id',[],'LEFT');
    }

    //设置
    public  function setting()
    {
        return $this->hasOne(EbayListingSetting::class, 'id', 'id');
    }

    //属性
    public  function specifics()
    {
         return $this->hasMany(EbayListingSpecifics::class, 'listing_id', 'id');
    }

    //国际物流
    public  function internationalShipping()
    {
         return $this->hasMany(EbayListingTransport::class, 'listing_id', 'id');
    }

    //国内物流
    public  function shipping()
    {
         return $this->hasMany(EbayListingTransportIn::class, 'listing_id', 'id');
    }

    //退货政策
    public function returnPolicy()
    {
         return $this->hasOne(EbayCommonReturn::class, 'id', 'mod_return',[],'LEFT');
    }
    //风格模板
    public function template()
    {
         return $this->hasOne(EbayCommonTemplate::class, 'id', 'mod_style',[],'LEFT');
    }
    //销售说明
    public function salenote()
    {
         return $this->hasOne(EbayModelSale::class, 'id', 'mod_sale',[],'LEFT');
    }
    //物流
    public function transport()
    {
         return $this->hasOne(EbayCommonTrans::class, 'id', 'mod_trans',[],'LEFT');
    }
    //物流详情
    public  function transportDetail()
    {
        return $this->hasManyThrough(EbayCommonTransDetail::class,EbayCommonTrans::class,'mod_trans','id','trans_id');
    }
    //不送到地区
    public function exclude()
    {
         return $this->hasOne(EbayCommonExclude::class, 'id', 'mod_exclude',[],'LEFT');
    }
    //物品所在地
    public function location()
    {
         return $this->hasOne(EbayCommonLocation::class, 'id', 'mod_location',[],'LEFT');
    }
    //买家限制
    public function refuse()
    {
         return $this->hasOne(EbayCommonRefuseBuyer::class, 'id', 'mod_refuse',[],'LEFT');
    }
    //收款
    public function receive()
    {
         return $this->hasOne(EbayCommonRefuseBuyer::class, 'id', 'mod_receivables',[],'LEFT');
    }
    //组合模板
    public function combine()
    {
         return $this->hasOne(EbayModelComb::class, 'id', 'comb_model_id',[],'LEFT');
    }

    //促销
    public function promotion()
    {
         return $this->hasOne(EbayModelPromotion::class, 'id', 'promotion_id',[],'LEFT');
    }

    //风格模板
    public function style()
    {
         return $this->hasOne(EbayCommonTemplate::class, 'id', 'mod_style',[],'LEFT');
    }
    //备货期
    public function dispatch()
    {
         return $this->hasOne(EbayCommonChoice::class, 'id', 'mod_choice',[],'LEFT');
    }
    //备货期
    public function pickup()
    {
         return $this->hasOne(EbayCommonPickup::class, 'id', 'mod_pickup',[],'LEFT');
    }
    //橱窗
    public function shopwindow()
    {
         return $this->hasOne(EbayCommonGallery::class, 'id', 'mod_galley',[],'LEFT');
    }
    //私人物品
    public function privateListing()
    {
         return $this->hasOne(EbayCommonIndividual::class, 'id', 'mod_individual',[],'LEFT');
    }
    //接收还价
    public function bargaining()
    {
         return $this->hasOne(EbayCommonBargaining::class, 'id', 'mod_bargaining',[],'LEFT');
    }
    //库存
    public function commonQuantity()
    {
         return $this->hasOne(EbayCommonQuantity::class, 'id', 'mod_quantity',[],'LEFT');
    }

    //============关联模型结束==================



    /**
     * 用于同步listing数据
     * @return [type] [description]
    **/
    public function syncListing(array $data){
        $ebayCache = new EbayRsyncListing();
        if($data['item_id']){#同步线上listing
            $itemId = $data['item_id'];
            $info = $ebayCache->getProductCache($data['account_id'],$data['item_id']);
            if($info){
                #直接更新内容
                $this->save($data,['id'=>$info['id']]);
                $id = $info['id'];
            }else{
                $rows=$this->get(["item_id"=>$data['item_id']]);
                if($rows){#刚更新
                    unset($data['item_id']);
                    unset($data['description']);
                    $wh['id']=$rows['id'];
                    $this->save($data,$wh);
                    $id = $rows['id'];
                }else{#添加
                    $id = $this->insertGetId($data);
                }
            }
            $ebayCache->setProductCache($data['account_id'],$itemId,['update_date'=>time(),'id'=>$id]);
            return $id;
        }

        // if($data['item_id']){#同步线上listing
        //     $itemId = $data['item_id'];
        //     $info = $ebayCache->getProductCache($data['account_id'],$data['item_id']);
        //     if($info){
        //         #直接更新内容
        //         $this->save($data,['id'=>$info['id']]);
        //         $id = $info['id'];
        //     }else{
        //         $rows=$this->get(["item_id"=>$data['item_id']]);
        //         if($rows){#更新
        //             unset($data['item_id']);
        //             unset($data['description']);
        //             $wh['id']=$rows['id'];
        //             $this->save($data,$wh);
        //             $id = $rows['id'];
        //         }else{#添加
        //             $id = Twitter::instance()->nextId(1, $data['account_id']);
        //             $data['id'] = abs($id);
        //             time_partition(\app\common\model\ebay\EbayListing::class, time(),'create_date');
        //             $this->save($data);
        //             $id = $this->getAttr("id");
        //         }
        //         #将更新内容插入缓存

        //         $ebayCache->setProductCache($data['account_id'],$itemId,['update_date'=>time(),'id'=>$id]);
        //     }
        //     return $id;
        // }else{
        //     $rows=$this->get(["item_id"=>$data['item_id']]);
        //     if($rows){#更新
        //         unset($data['item_id']);
        //         unset($data['description']);
        //         $wh['id']=$rows['id'];
        //         $this->save($data,$wh);
        //         return $rows['id'];
        //     }else{#添加
        //         $id = Twitter::instance()->nextId(1, $data['account_id']);
        //         $data['id'] = abs($id);
        //         time_partition(\app\common\model\ebay\EbayListing::class, time(),'create_date');
        //         $this->save($data);
        //         return $this->getAttr("id");
        //     }
        // }
    }

    /**
     * 用于保存listing数据
     * @param $data
     * @return int
     * @throws Exception
     */
    public function saveEbayListing($data)
    {
        $id = isset($data['id']) ? $data['id'] : 0;
        try {
            if (intval($id) != 0) {#更新
                $this->save($data, ['id' => $id]);
            } else {#添加
                $id = $this->insertGetId($data);
            }
            return $id;
        }catch (Exception $e){
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
}