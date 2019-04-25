<?php

namespace app\publish\controller;
use app\common\controller\Base;
use think\Request;
use think\Db;
use think\Exception;

use app\publish\service\EbayHelper;
use app\goods\service\GoodsHelp;

use app\publish\task\EbayGetCategorys;
use app\publish\task\EbayGetListings;
use app\publish\task\EbayGetCategoryFeatures;
use app\publish\task\EbayGetCategorySpecifics;
use app\publish\task\EbayGetStore;
use app\publish\task\EbayGeteBayDetails;
use app\publish\task\EbayGetMyEbaySelling;
use app\publish\task\EbayAddFixedPriceItem;
use app\publish\task\EbayVerifyAddFixedPriceItem;
use app\publish\task\EbayPublishItem;
use app\publish\task\EbayUploadImgs;
use app\publish\task\EbayListening;
use app\publish\task\EbayGetListingsQueuer;
use app\publish\service\EbayService;
use app\common\service\Common;
use app\publish\queue\EbayQueuer;
use app\publish\queue\EbayRelistQueuer;
use app\publish\queue\EbayTimingQueuer;
use app\publish\queue\EbayImgQueuer;
use app\publish\queue\EbayGetListQueuer;
use app\publish\queue\EbayUpdateItemJob;
use app\publish\queue\EbayPublishItemQueuer;
use app\publish\queue\EbayGetSellerEventsQueuer;
use app\index\service\MemberShipService;
use app\publish\queue\EbayGetPromotionListQueuer;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbayListing;
use app\publish\queue\EbayGetItemQueue;
use app\common\cache\Cache;
use service\ebay\EbayApi;

/**
 * @module 刊登系统
 * @title Ebay刊登-基础信息
 * @author zengshaohui
 * @url /Publish
 */

class Ebay extends Base
{
    private $service;
    private $siteInfo = [
        0=>'US',3=>'UK',2=>'Canada',15=>'Australia',16=>'Austria',23=>'Belgium_French',71=>'France',
        77=>'Germany',100=>'eBay Motors',101=>'Italy',123=>'Belgium_Dutch',146=>'Netherlands',186=>'Spain',
        193=>'Switzerland',201=>'HongKong',203=>'India',
        205=>'Ireland',207=>'Malaysia',210=>'CanadaFrench',211=>'Philippines',212=>'Poland',
        215=>'Russia',216=>'Singapore'
    ];
    protected function init()
    {
        $this->service = new \app\publish\service\EbayService();
        $this->queuer = new \app\publish\queue\EbayQueuer();
    }

    /**
     * @title 获取ebay商品类目
     * @url Ebay/getCategorys
     * @method POST
     *
     * @apiParam name:category_id type:int require:1  desc:父级ID,如果为第一级则为0
     * @apiParam name:site_id type:int require:1  desc:站点ID
     * @apiReturn data:ebay商户类目ID@
     * @data category_id:类目ID category_name:类目名称 variations_enabled:是否支持多属性 leaf_category:是否叶类目 auto_pay_enabled 是否要求立即付款 best_offer_enabled:是否支持议价
     */
    public function getCategorys(Request $request)
    {
        try{
            $categoryId = $request->param("category_id")?$request->param("category_id"):0;#父类目
            $siteId = intval($request->param("site"));#站点
            if($siteId!==0 && empty($siteId)){
                return json(['data' => '请选择站点！'],500);
            }
            $res = $this->service->getEbayCategorys($categoryId,$siteId);
            return json(['data'=>$res], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取ebay商品类目属性
     * @url Ebay/getSpecifics
     * @method POST
     *
     * @apiParam name:category_id type:int require:1  desc:类目ID不能为空！
     * @apiParam name:site type:int require:1  desc:站点ID
     * @apiReturn category_id:类目ID
     * @apiReturn category_specific_name:属性名称
     * @apiReturn site:站点
     * @apiReturn min_values:是否必填
     * @apiReturn selection_mode:内容限制
     * @apiReturn variation_specifics:是否支持多属性
     * @apiReturn variation_picture:知否支持多属性图片分类
     * @apiReturn relationship:是否有关联上级
     * @apiReturn detail:属性值设置@
     * @detail category_specific_value:属性值 parent_name:关联的上级属性名 parent_value:关联的上级属性值
     */
    public function getSpecifics(Request $request)
    { 
        try{
            if(!$request->param("category_id") || (!$request->param("site") && $request->param("site")!=0 ) ){
                return json(['message' => '类目与站点不能为空！'],500);
            }
            $categoryId = $request->param("category_id");#获取类目ID
            $site = $request->param("site");#站点
            $res = $this->service->getEbaySpecifics($categoryId,$site);
            return json($res, 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }


    /**
     * @title 获取ebay站点
     * @url Ebay/getSite
     *
     * @apiReturn country:站点国家
     * @apiReturn siteid:站点Id
     * @apiReturn abbreviation:简称
     * @apiReturn currency:币种
     * @apiReturn name:名称
     * @apiReturn symbol:币种符号
     * @apiReturn show:是否显示
     */
    public function getSite()
    {
        try{
            $res = $this->service->getEbaySites();
            return json(['data'=>$res], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }


    /**
     * @title 获取ebay站点对应的增值税选项
     * @apiParam name:site type:int require:1  desc:站点ID
     * @url Ebay/getVatInfo
     *
     * @apiReturn site:站点ID
     * @apiReturn jurisdiction_id:
     * @apiReturn jurisdiction_name:
     * @apiReturn detail_version:
     */
    public function getVatInfo(Request $request)
    {
        try{
            $site = $request->get("site",0);
            $res = $this->service->getEbayVatInfo($site);
            return json(['data'=>$res], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取ebay账号自定义类目
     * @apiParam name:site type:int require:1  desc:账号ID
     * @apiParam name:parent_id type:int require:1  desc:父级ID
     * @url Ebay/getCustomCategory
     * @method POST 
     *
     * @apiReturn category_id:类目ID
     * @apiReturn name:类目名称
     * @apiReturn parent_id:父级ID
     * @apiReturn order:
     */
    public function getCustomCategory(Request $request)
    {
        try{
            $parentId = $request->param("parent_id")?$request->param("parent_id"):0;
            $accountId = $request->param("account")?$request->param("account"):15;#账号ID
            $res = $this->service->getEbayCusCategory($parentId,$accountId);
            return json(['data'=>$res], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }


    /**
     * @title 获取ebay物流方式
     * @apiParam name:site type:int require:1  desc:站点
     * @apiParam name:inter type:int require:1  desc:是否国际运输
     * @url Ebay/getTrans
     * @method POST
     * 
     * @apiReturn data:返回数据@
     * @data site:站点 description:物流名称 shipping_service:物流代码 shipping_service_id: shipping_time_max:最长交付时间 shipping_time_min:最短交付时间 valid_for_selling_flow:是否支持送货 shipping_category:航运类别(航运类别包括: 经济, 标准, 快速, ONE_DAY, 皮卡, 其他, 和没有) dimensions_required:是否支持包裹类型 weight_required:卖家指定必须包类型 shipping_carrier:承运人 international_service:是否国际货运0否1是
     */
    public function getTrans(Request $request)
    {
        try{
            if(!$request->param("site") && $request->param("site")!=0 ){
                return json(['message' => '站点不能为空！'],500); 
            }
            $site = intval($request->param("site"));#获取站点
            $res = $this->service->getTrans($site);
            return json($res,200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取ebay国家代码
     * @url Ebay/getCountrys   
     *
     * @apiReturn data:返回数据@
     * @data countryNameCn:国家名称(中文) countryNameEn:国家名称(英文) continentId: countrySn:国家简码 countrySnThr:  numberSn:  
     */
    public function getCountrys(Request $request)
    {
        try{
            $res = $this->service->getCountrys();
            return json(['data'=>$res], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取locations国家代码
     * @url Ebay/getEbayLocations   
     *
     * @apiReturn data:返回数据@
     * @data countryNameCn:国家名称(中文) countryNameEn:国家名称(英文) continentId: countrySn:国家简码 countrySnThr:  numberSn:  
     */
    public function getEbayLocations(Request $request)
    {
        try{
            $res = $this->service->getLocations();
            return json(['data'=>$res], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取ebay平台销售账号
     * @url Ebay/getAccounts
     * 
     * @apiReturn data:返回数据@
     * @data account_name:账号名称 code:编码
     */
    public function getAccounts(Request $request)
    {
        try{
            $userInfo = Common::getUserInfo();
            $site = $request->get('site','');
            $res = $this->service->getEbayAccount($userInfo['user_id'],$site);
            return json(['data'=>$res], 200);
        }catch(Exception $e){
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 获取历史选择分类
     * @url Ebay/getHistoryCategory
     * @method POST
     * 
     * @apiReturn data:返回数据@
     * @data category_id:类目ID category_name:类目名称
     */
    public function getHistoryCategory(Request $request)
    {
        try{
            $service = new EbayService();
            if(!$request->param("site") && $request->param("site")!=0 ){
                return json(['message' => '站点不能为空！'],500); 
            }

            if(!$request->param("account") && $request->param("account")!=0 ){
                return json(['message' => 'ebay账号不能为空！'],500); 
            }

            $userInfo = Common::getUserInfo($request);
            $param['user_id'] =  empty($userInfo) ? 0 : $userInfo['user_id'];
            $param['site'] = $request->param("site");
            $param['account_id'] = $request->param("account");
            $param['cus'] = $request->param("cus");

            $res = $this->service->getEbayHistoryCategory($param);
            return json(['data'=>$res], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取ebay类目树
     * @url Ebay/getCategoryTree
     * @apiParam name:site type:int require:1  desc:站点 
     * @apiParam name:category_id type:int require:1  desc:类目ID 
     * @method GET
     *
     * @apiReturn data:返回数据@
     * @data category_id:类目ID category_name:类目名称
     */
    public function getCategoryTree(Request $request)
    {
        try{
            $site = $request->get('site',0);
            $categoryId = $request->get("category_id",181834);
            if(!$categoryId){
                return json(['message' => '类目ID不能为空！'],500); 
            }
            $str = $this->service->getEbayCategoryTree($categoryId,"",$site);
            $res['category_name'] = $categoryId." - ".substr($str,3);
            $res['category_id'] = $categoryId;
            return json(['data'=>$res], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 根据关键词查询ebay商户类目
     * @url Ebay/getCategoryByKeyword
     * @apiParam name:site type:int require:1  desc:站点 
     * @apiParam name:key type:string   desc:关键词
     * @method GET
     *
     * @apiReturn data:返回数据@
     * @data category_id:类目ID category_name:类目名称
     */
    public function getCategoryByKeyword(Request $request)
    {
        try{
            $site = $request->get('site',0);
            $key = $request->get("key","279");
            $page = $request->get("page",1);
            $size = $request->get("size",20);
            $start = ($page-1)*$size;
            $res = $this->service->getCategoryByKeyword($key,$site,$start,$size);
            return json(['data'=>$res['rows'],'count'=>$res['count']],200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取ebay店铺自定义类目树
     * @url Ebay/getCustomCategoryTree
     * @apiParam name:account_id type:int require:1  desc:账号ID 
     * @apiParam name:category_id type:int require:1  desc:类目ID 
     * @method GET
     * 
     * @apiReturn data:返回数据@
     * @data category_id:类目ID name:类目名称
     */
    public function getCustomCategoryTree(Request $request)
    {
        try{
            $accountId = $request->get("account_id",40);
            $categoryId = $request->get("category_id",1743869719);
            if(!$accountId){
                return json(['message' => '账号ID不能为0！'],500); 
            }
            $str = $this->service->getCustomCateTree($accountId,$categoryId,"");
            $res['name'] = $categoryId." - ".substr($str,1);
            $res['category_id'] = $categoryId;
            return json(['data'=>$res],200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }


    /**
     * @title 获取ebay备货时间
     * @url Ebay/getDispatchTimeMax
     * @apiParam name:site type:int require:1  desc:站点 
     * @method GET
     *
     * @apiReturn data:返回数据@
     * @data dispatch_time_max:备货时间 description:显示文本
     */
    public function getDispatchTimeMax(Request $request)
    {
        try{
            $site = $request->get("site",0);
            $time = $this->service->getEbayDispatchTimeMax($site);
            return json(['data'=>$time], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 同步店铺自定义类目
     * @url ebay/sync-store
     * @apiParam name:account_id type:int require:1  desc:账号ID 
     * @method GET
     *
     */
    public function syncStore(Request $request)
    {
        $storeTask = new EbayGetStore();
        try{
            $accountId = $request->get("account_id",105);
            if($accountId==0){
                throw new Exception("请输入要同步的账号！");
            }
            $res = $storeTask->getStoreImmediately($accountId);
            if($res['result']){
                return json(['message'=>$res['message']], 200);
            }else{
                return json(['message'=>$res['message']], 500);
            }
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取ebay Paypal账号
     * @url Ebay/getPaypals
     * @apiParam name:account_id type:int require:1  desc:平台销售账号 
     * @method GET
     *  
     * @apiReturn data:返回数据@
     * @data code:商户编号 account_name:PayPal账户(邮箱)
     */
    public function getPaypals(Request $request)
    {
        try{
            $userInfo = Common::getUserInfo($request);
            $userId =  empty($userInfo) ? 0 : $userInfo['user_id'];
            $accountId = $request->get("account_id",3);
            $paypals = $this->service->getEbayPaypals($accountId,$userId);
            return json(['data'=>$paypals], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取ebay 退货时间
     * @url Ebay/getWithin
     * @apiParam name:site type:int require:1  desc:站点ID 
     * @method GET
     *  
     *
     * @apiReturn data:返回数据@
     * @data returns_within_option:退货时间(传值) description:退货时间(显示文本)
     */
    public function getWithin(Request $request)
    {
        try{
            $siteId = $request->get("site",0);
            $withIn = $this->service->getEbayWithIn($siteId);
            return json(['data'=>$withIn], 200);
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 同步listing信息
     * @url Ebay/syncItemInfo
     * 
     */
    public function syncItemInfo(Request $request)
    {
        try{
            $itemId = $request->get('item_id',"99347061");
            $accountId = $request->get('account_id',133);
            $res = $this->service->getItem($itemId,$accountId);
            if($res['result']){
                return json(['data'=>$res,'message'=>'同步成功！','result'=>true],200);
            }else{
                return json(['message'=>'同步失败！','result'=>false],500);
            }
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 通过item_id来获取在线listing信息
     * @url ebay/listing-info-byitemid
     * @method get
     * @apiParam name:item_id type:int require:1  desc:在线listing的唯一标识
     * @apiParam name:account_id type:int require:1  desc:销售账号ID
     */
    public function getItemInfoByItemid(Request $request)
    {
        try{
            $itemId = $request->get('Item_id',"173146002159");
            $accountId = $request->get('Account_id',133);
            $res = $this->service->getItemByItemid($itemId,$accountId);
            if($res['result']){
                return json(['data'=>$res,'message'=>'获取数据成功！','result'=>true],200);
            }else{
                return json(['message'=>'获取数据失败！','result'=>false],500);
            }
        }catch(Exception $e){
            return json(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 通过item_id来获取在线oe
     * @url ebay/oe-sync
     * @method get
     * @apiParam name:item_id type:int require:1  desc:在线listing的唯一标识
     * @apiParam name:oe_id type:int require:1  desc:oe标识
     * @apiParam name:spu type:int require:1  desc:spu
     * @apiParam name:factory_model type:int require:1  desc:工厂型号
     */
    public function syncOeByimtemId(Request $request)
    {
        try{
            $userInfo = Common::getUserInfo();
            $userId = $userInfo['user_id'];
            $oe['item_id'] = $request->get('item_id',"310770968373");
            $oe['number'] = $request->get('number',"testDemo");
            $oe['spu'] = $request->get('spu','K00001');
            $oe['factory_model'] = $request->get('factory_model','DF367');
            if(!$oe['item_id'] || !is_numeric($oe['item_id'])){
                throw new Exception("itemId必填,且必须为数字！");
            }
            if(!$oe['number']){
                throw new Exception("OE Id必填！");
            }
            $res = $this->service->getOeByitemId($oe['item_id']);
            $Compatibility = $res?isset($res[0])?$res:[$res]:[];
            $result = $this->service->syncOeManagement($oe,$Compatibility,$userId);
            if($result['result']){
                return json(['message'=>'同步成功！','result'=>true],200);
            }else{
                return json(['message'=>'同步失败！','result'=>false],500);
            }
        }catch(Exception $e){
            return json(['result'=>false,'message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title oe管理新增
     * @url ebay/oe-save
     * @method POST
     * @apiParam name:item_id type:int require:1  desc:在线listing的唯一标识
     * @apiParam name:number type:int require:1  desc:oe标识
     * @apiParam name:spu type:int require:1  desc:spu
     * @apiParam name:factory_model type:int require:1  desc:工厂型号
     * @apiParam name:oe_vechiles type:array require:0 desc:适配车型
     */
    public function oeSave(Request $request)
    {
        try{
            Db::startTrans();#开启事物
            $userInfo = Common::getUserInfo();
            $userId = $userInfo['user_id'];
            $data = json_decode($request->param('data'),true);
            if(!$data['item_id'] || !is_numeric($data['item_id'])){
                throw new Exception("itemId必填,且必须为数字！");
            }
            if(!$data['number']){
                throw new Exception("OE Id必填！");
            }
            $oe['item_id'] = $data['item_id'];
            $oe['number'] = $data['number'];
            $oe['spu'] = $data['spu'];
            $oe['factory_model'] = $data['factory_model'];
            $Compatibility = $data['oe_vechiles'];
            $result = $this->service->oeSaveService($oe,$Compatibility,$userId);
            Db::commit();#提交事务
            if($result['result']){
                return json(['message'=>'保存成功！','result'=>true,'id'=>$result['id']],200);
            }else{
                return json(['message'=>'保存失败！','result'=>false],500);
            }
        }catch(Exception $e){
            Db::rollback();#回滚事务
            return json(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title oe管理更新
     * @url ebay/oe-update
     * @method POST
     * @apiParam name:item_id type:int require:1  desc:在线listing的唯一标识
     * @apiParam name:oe_id type:int require:1  desc:oe标识
     * @apiParam name:spu type:int require:1  desc:spu
     * @apiParam name:factory_model type:int require:1  desc:工厂型号
     * @apiParam name:oe_vechiles type:array require:0 desc:适配车型
     */
    public function oeUpdate(Request $request)
    {
        try{
            Db::startTrans();#开启事物
            $userInfo = Common::getUserInfo();
            $userId = $userInfo['user_id'];
            $data = json_decode($request->param('data'),true);
            $oe['id'] = $data['id'];
            $oe['spu'] = $data['spu'];
            $oe['factory_model'] = $data['factory_model'];
            $Compatibility = $data['oe_vechiles'];
            $result = $this->service->oeUpdateService($oe,$Compatibility,$userId);
            Db::commit();#提交事务
            if($result['result']){
                return json(['message'=>'更新成功！','result'=>true],200);
            }else{
                return json(['message'=>'更新失败！','result'=>false],500);
            }
        }catch(Exception $e){
            Db::rollback();#回滚事务
            return json(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title oe管理删除
     * @url ebay/oe-remove
     * @method GET
     * @apiParam name:item_id type:int require:1  desc:在线listing的唯一标识
     * @apiParam name:oe_id type:int require:1  desc:oe标识
     * @apiParam name:spu type:int require:1  desc:spu
     * @apiParam name:factory_model type:int require:1  desc:工厂型号
     * @apiParam name:oe_vechiles type:array require:0 desc:适配车型
     */
    public function oeRemove(Request $request)
    {
        try{
            $userInfo = Common::getUserInfo();
            $userId = $userInfo['user_id'];
            $oeIds = $request->get('ids',0);
            if(!$oeIds){
                return json(['message'=>'请选择要删除的记录！','result'=>false],500);
            }
            $result = $this->service->oeRemoveService($oeIds);
            if($result['result']){
                return json(['message'=>'删除成功！','result'=>true],200);
            }else{
                return json(['message'=>'删除失败！','result'=>false],500);
            }
        }catch(Exception $e){
            return json(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title oe管理列表
     * @url ebay/oe-list
     * @method GET
     * @apiParam name:item_id type:int require:1  desc:在线listing的唯一标识
     * @apiParam name:number type:int require:1  desc:oe标识
     * @apiParam name:spu type:int require:1  desc:spu
     * @apiParam name:factory_model type:int require:1  desc:工厂型号
     * @apiParam name:page type:int require:0 desc:页数
     * @apiParam name:size type:int require:0 desc:返回行数
     * @apiFilter app\publish\filter\EbayOeListFilter
     * @apiFilter app\publish\filter\EbayOeDepartFilter
     */
    public function oeList(Request $request)
    {
        try{
            $userInfo = Common::getUserInfo();
            $userId = $userInfo['user_id'];
            $oe['item_id'] = $request->get('item_id',0);
            $oe['number'] = $request->get('number',0);
            $oe['spu'] = $request->get('spu','');
            $oe['factory_model'] = $request->get('factory_model','');
            $page = $request->get('page',1);
            $size = $request->get('size',50);
            $start = ($page-1)*$size;
            $result = $this->service->oeListService($oe,$start,$size,$userId);
            if($result['result']){
                return json(['message'=>'获取成功！','data'=>$result['rows'],'count'=>$result['count'],'result'=>true],200);
            }else{
                return json(['message'=>'获取失败！','data'=>[],'result'=>false],500);
            }
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title oe管理编辑
     * @url ebay/oe-edit
     * @method GET
     * @apiParam name:id type:int require:1  desc:
     */
    public function oeEdit(Request $request)
    {
        try{
            $userInfo = Common::getUserInfo();
            $userId = $userInfo['user_id'];
            $oeId = $request->get('id',2);
            $result = $this->service->oeEditService($oeId,$userId);
            if($result['result']){
                return json(['message'=>'获取成功！','data'=>$result['rows'],'result'=>true],200);
            }else{
                return json(['message'=>'获取失败！','data'=>[],'result'=>false],500);
            }
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title oe模板合并
     * @url ebay/oe-modelmerge
     * @method POST
     * @apiParam name:item_id type:int require:1  desc:在线listing的唯一标识
     * @apiParam name:number type:int require:1  desc:oe标识
     * @apiParam name:spu type:int require:1  desc:spu
     * @apiParam name:factory_model type:int require:1  desc:工厂型号
     */
    public function oeModelMerge(Request $request)
    {
        try{
            $temp = json_decode($request->param('data'),true);
            $data = isset($temp[0])?$temp[0]:$temp;
            if(!$data['item_id']){
                throw new Exception("itemId必填！");
            }
            if(!$data['number']){
                throw new Exception("OE Id必填！");
            }
            $userInfo = Common::getUserInfo();
            $userId = $userInfo['user_id'];
            $oe['item_id'] = $data['item_id'];
            $oe['number'] = $data['number'];
            $oe['spu'] = $data['spu'];
            $oe['factory_model'] = $data['factory_model'];
            $oeIds = explode(",",$data['oe_ids']);#被合并的oe数据
            $result = $this->service->oeModelMergeService($oe,$oeIds,$userId);
            if($result['result']){
                return json(['message'=>'合并成功！','data'=>$result['id'],'result'=>true],200);
            }else{
                return json(['message'=>$result['message'],'data'=>[],'result'=>false],500);
            }
        }catch(Exception $e){
            return json(['error'=>$e->getFile()."|".$e->getLine()."|".$e->getMessage()],500);
        }
    }

    /**
     * @title oe管理编辑
     * @url ebay/oe-edit
     * @method GET
     * @apiParam name:id type:int require:1  desc:
     */

    /**
     * @title oe获取车型信息
     * @url ebay/oe-vechile
     * @method GET
     * @apiParam name:id type:int require:1  desc:
     */
    public function oeVechile(Request $request)
    {
        try{
            $userInfo = Common::getUserInfo();
            $userId = $userInfo['user_id'];
            $wh = [];
            $name = "make";
//            $name = $request->get('name');
//            $value = $request->get('value');
//            $wh[$name] = $value;

            if($request->get('make','')){
                $wh['make'] = $request->get('make','');
            }
            if($request->get('model','')){
                $wh['model'] = $request->get('model','');
                $name = "model";
            }
            if($request->get('year','')){
                $wh['year'] = $request->get('year','');
                $name = "year";
            }
            if($request->get('trim','')){
                $wh['trim'] = $request->get('trim','');
                $name = "trim";
            }
            if($request->get('engine','')){
                $wh['engine'] = $request->get('engine','');
                $name = "engine";
            }
            $result = $this->service->oeVechileService($wh,$userId,$name);
            if($result['result']){
                return json(['message'=>'获取成功！','data'=>$result['rows'],'result'=>true],200);
            }else{
                return json(['message'=>'获取失败！','data'=>[],'result'=>false],500);
            }
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title oe获取车型品牌
     * @url ebay/oe-makes
     * @method GET
     * @apiParam name:id type:int require:1  desc:
     */
    public function oeMakes(Request $request)
    {
        try{
            $userInfo = Common::getUserInfo();
            $userId = $userInfo['user_id'];
            $result = $this->service->oeMakesService($userId);
            if($result['result']){
                return json(['message'=>'获取成功！','data'=>$result['rows'],'result'=>true],200);
            }else{
                return json(['message'=>'获取失败！','data'=>[],'result'=>false],500);
            }
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取ebay在线listing
     * @url Ebay/getListing
     * 
     */
    public function getListing()
    {
        set_time_limit(0);
        try{
            #$task = new EbayGetCategorySpecifics();
            #$task = new EbayGetCategoryFeatures();
            #$task = new EbayGetListings();
            #$task = new EbayGetCategorys();
            #$task = new EbayUploadImgs();
            #$task = new EbayGeteBayDetails();
            #$task = new EbayGetStore();
            #$task = new EbayPublishItem();
            #$task = new EbayListening();
            #$task = new EbayVerifyAddFixedPriceItem();
            #$task = new EbayPublishQueuer();
            #$task = new EbayGetListingsQueuer();
            #$task = new EbayGetListQueuer(EbayGetListQueuer::class);
            #$task = new EbayUpdateItemJob(EbayUpdateItemJob::class);
            #$task = new EbayImgQueuer(EbayImgQueuer::class);
            #$task = new EbayPublishItemQueuer(EbayPublishItemQueuer::class);
            #$task = new EbayGetPromotionListQueuer(EbayGetPromotionListQueuer::class);
            #$task = new EbayGetSellerEventsQueuer(EbayGetSellerEventsQueuer::class);
            #$task = new EbayGetItemQueue(EbayGetItemQueue::class);
            #$task->execute();die;
            $this->testDemo();die;
        }catch(Exception $e){
            return json(['error'=>$e->getMessage()],500);
        }
    }

    public function testDemo()
    {
        #测试图片上传
        $task = new EbayUploadImgs();
        $acInfo = Cache::store('EbayAccount')->getTableRecord(133);
        $tokenArr = json_decode($acInfo['token'],true);
        $token = trim($tokenArr[0])?$tokenArr[0]:$acInfo['token'];
        $config = $task->createConfig($acInfo);
        $ebayApi = new EbayApi($config);
        $xml ="<?xml version='1.0' encoding='utf-8'?>\n";
        $xml.="<UploadSiteHostedPicturesRequest xmlns='urn:ebay:apis:eBLBaseComponents'>\n";
        $xml.="<RequesterCredentials>\n";
        $xml.="<eBayAuthToken>".$token."</eBayAuthToken>\n";#账号token
        $xml.="</RequesterCredentials>\n";
        $imgPath="http://47.88.100.67/Q1I7N8287/QcI0N2795ec59f0a51f32a2cbef9e258427.jpg";
        $xml.="<ExternalPictureURL>".$imgPath."</ExternalPictureURL>\n";
        $xml.="<PictureSet>Supersize</PictureSet>";
        $xml.="<WarningLevel>High</WarningLevel>\n";
        $xml.="</UploadSiteHostedPicturesRequest>\n";
        $resText = $ebayApi->createHeaders()->__set("requesBody",$xml)->sendHttpRequest2();
        echo "<pre>";
        print_r($resText);die;

        // use app\publish\queue\EbayQueuer;
        // use app\publish\queue\EbayRelistQueuer;
        // use app\publish\queue\EbayTimingQueuer;
        // use app\publish\queue\EbayImgQueuer;
        // $que = new EbayImgQueuer();
        // $res = $que->consumption();
        // echo "<pre>";
        // print_r($res);die;

        #更新多属性key
        // $rows = Db::name("ebay_listing")->field("id")->where(['varions'=>1,'v_varkey'=>['eq','']])->select();
        // foreach($rows as $k => $v){
        //     $var = Db::name("ebay_listing_variation")->field("variation")->where(['listing_id'=>$v['id']])->find();
        //     $k = json_decode($var['variation'],true);
        //     if(!empty($k)){
        //         $up['v_varkey'] = json_encode(array_keys(json_decode($var['variation'],true)));
        //         Db::name("ebay_listing")->where(['id'=>$v['id']])->update($up);
        //     }
        // }
        // $rows = Db::name("ebay_listing_setting")->field("exclude,id")->select();
        // foreach($rows as $k => $v){
        //     $ex = json_decode($v['exclude'],true);
        //     if(is_array($ex)){
        //         $tm = implode(",",$ex);
        //         Db::name("ebay_listing_setting")->where(['id'=>$v['id']])->update(['exclude'=>$tm]);
        //     }
        // }

        #清理冗余数据
        // $rows = Db::name("ebay_listing")->field("id")->select();
        // $ids = [];
        // foreach($rows as $k =>$v){
        //     $ids[]=$v['id'];
        // }
        // $idsStr = implode(",",$ids);
        // $wh['listing_id'] = ['not in',$idsStr];
        // $sets = Db::name("ebay_listing_variation")->field("id")->where($wh)->select();
        // echo "<pre>";
        // print_r($sets);
        // $setArr = [];
        // foreach($sets as $sk => $sv){
        //     $setArr[] = $sv['id']; 
        // }
        // $setStr = implode(",",$setArr);
        // Db::name("ebay_listing_variation")->where(['id'=>['in',$setStr]])->delete();
        // die;

        // $rows = Db::name("ebay_listing_image")->field("listing_id")->select();
        // $ids = [];
        // foreach($rows as $k =>$v){
        //     $ids[]=$v['listing_id'];
        // }
        // $idsStr = implode(",",$ids);
        // $wh['id'] = ['not in',$idsStr];
        // $lists = Db::name("ebay_listing")->where($wh)->select();
        // echo "<pre>";
        // print_r($lists);die;
    }

}
