<?php

/**
 * Description of EbayModelComb
 * @datetime 2017-6-27  14:07:54
 * @author joy
 */

namespace app\common\model\ebay;
use app\common\model\AutoCompleteModel;
use think\Model;
class EbayModelComb extends AutoCompleteModel {
    protected $autoWriteTimestamp = true;
    protected $createId = 'creator_id';
    protected $updateId = 'updator_id';
    public function initialize() {
        parent::initialize();
    }
    
    //============关联模型开始===================
    //账号
    public  function account()
    {
        return $this->hasOne(EbayAccount::class, 'id', 'ebay_account');
    }
    //商品
    public  function site()
    {
        return $this->hasOne(EbaySite::class, 'siteid', 'site');
    }
    //促销
    public  function promotion()
    {
        return $this->hasOne(EbayModelPromotion::class, 'id', 'promotion');   
    }
     //风格模板
    public function template()
    {
         return $this->hasOne(EbayCommonTemplate::class, 'id', 'style',[],'LEFT');
    }
    //销售说明
    public function salenote()
    {
         return $this->hasOne(EbayCommonSale::class, 'id', 'sale',[],'LEFT');
    }
    //物流
    public function transport()
    {
         return $this->hasOne(EbayCommonTrans::class, 'id', 'trans',[],'LEFT');
    }
    //不送到地区
    public function exclude()
    {
         return $this->hasOne(EbayCommonExclude::class, 'id', 'exclude',[],'LEFT');
    }
    //备货周期
    public  function dispatch(){
        return $this->hasOne(EbayCommonChoice::class, 'id', 'choice',[],'LEFT');
    }
    //自取
    public function  pickup()
    {
        return $this->hasOne(EbayCommonPickup::class, 'id', 'pickup',[],'LEFT');
    }
    //物品所在地
    public function location()
    {
         return $this->hasOne(EbayCommonLocation::class, 'id', 'location',[],'LEFT');
    }
    //物品所在地
    public function gallery()
    {
         return $this->hasOne(EbayCommonGallery::class, 'id', 'gallery',[],'LEFT');
    }
    //点击计数器
    public  function counter()
    {
         return $this->hasOne(EbayCommonCounter::class, 'id', 'counter',[],'LEFT');
    }
    //是否私人物品
    public  function privateListing()
    {
         return $this->hasOne(EbayCommonIndividual::class, 'id', 'individual',[],'LEFT');
    }
    //买家限制
    public function refuse()
    {
         return $this->hasOne(EbayCommonRefuseBuyer::class, 'id', 'refuse',[],'LEFT');
    }
    
    //收款
    public function receive()
    {
         return $this->hasOne(EbayCommonReceivables::class, 'id', 'receivables',[],'LEFT');
    }
    //退货政策
    public function returnPolicy()
    {
         return $this->hasOne(EbayCommonReturn::class, 'id', 'returngoods',[],'LEFT');
    }
    //退货政策
    public function bargaining()
    {
         return $this->hasOne(EbayCommonBargaining::class, 'id', 'bargaining',[],'LEFT');
    }
    
    //可售数量
    public function quantity()
    {
         return $this->hasOne(EbayCommonQuantity::class, 'id', 'quantity',[],'LEFT');
    }

    //============关联模型结束==================
}
