<?php

namespace app\publish\controller;

use app\common\controller\Base;
use think\Request;
use think\Db;
use app\publish\service\EbayListingService;
use think\Exception;
use app\common\service\Common;
use app\publish\task\EbayRelistItem;
use app\common\cache\Cache;
use app\common\model\ebay\EbayListingImage;
use app\publish\task\EbayUploadImgs;
use app\goods\service\GoodsImage;
use app\publish\task\EbaySetPromotionalSaleListings;
use app\common\model\ebay\EbayModelPromotion;
use app\common\cache\driver\EbayListingReponseCache;


/**
 * @module 刊登系统
 * @title Ebay刊登-Listing管理
 * @author zengshaohui - wlw2533
 * @url /Publish
 */

class EbayListing extends Base
{
    private $service;
    private $userId;

    protected function init()
    {
        $userInfo = Common::getUserInfo();
        $this->userId = empty($userInfo) ? 0 : $userInfo['user_id'];//用户ID
        $this->service = new EbayListingService($this->userId);
    }

    /**
     * @title Listing新增页面
     * @url EbayListing/addListing
     * @apiRelate app\goods\controller\Goods::goodsToSpu
     * @apiRelate app\publish\controller\Wish::uploadImages
     * @apiRelate app\publish\controller\Wish::createNetImage
     * @apiRelate app\publish\controller\Express::Getpublishimage
     * @apiRelate app\order\controller\Order::getGoods
     * @apiRelate app\index\controller\MemberShip::publish
     * @apiRelate app\publish\controller\PricingRule::calculate
     * @method POST
     *
     * @apiParam name:id type:int require:1  desc:产品ID
     * @param Request $request
     * @return \think\response\Json
     */
    public function addListing(Request $request)
    {
        try{
            $goodsId = $request->param("goods_id");
            $siteId = $request->param('site_id');
            if (empty($goodsId)) {
                throw new Exception('商品id有误');
            }
            $siteId = (empty($siteId) && $siteId!=0) ? 0 : $siteId;
            $res = $this->service->addListingService($goodsId,$siteId);
            return json(['result'=>true, 'data'=>$res],200);
        }catch(Exception $e){
            return json($e->getMessage(),500);
        }
    }

    /**
     * @title 保存Listing
     * @url EbayListing/saveListing
     * @apiRelate app\goods\controller\Goods::goodsToSpu
     * @apiRelate app\publish\controller\Wish::uploadImages
     * @apiRelate app\publish\controller\Wish::createNetImage
     * @apiRelate app\publish\controller\Express::Getpublishimage
     * @apiRelate app\order\controller\Order::getGoods
     * @method POST
     *
     * @apiParam name:id type:int require:1  desc:ListingID
     * @param Request $request
     * @return \think\response\Json
     */
    public function saveListing(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);#listing信息
            if (empty($data)) {
                throw new Exception('传递的信息有误');
            }
            $res = (new \app\publish\service\EbayCtrl($this->userId))->saveListing($data);
            return json($res,$res['result']?200:500);
        }catch(Exception $e){
            return json($e->getMessage(),500);
        }
    }

    /**
     * @title 在线listing更新
     * @url EbayListing/updateListing
     * @apiRelate app\goods\controller\Goods::goodsToSpu
     * @apiRelate app\publish\controller\Wish::uploadImages
     * @apiRelate app\publish\controller\Wish::createNetImage
     * @apiRelate app\publish\controller\Express::Getpublishimage
     * @apiRelate app\order\controller\Order::getGoods
     * @method POST
     *
     * @apiParam name:id type:int require:1  desc:ListingID
     */
    public function updateListing(Request $request)
    {
        try {
            $data = json_decode($request->param("data"),true);#listing信息
            if (empty($data)) {
                throw new Exception('传递信息有误');
            }
            foreach ($data as $val) {
//                $this->service->checkListingInfo($val);
                $this->service->updateListing($val);
            }
            return json(["result"=>true, "message"=>"提交成功！"], 200);
        } catch(Exception $e) {
            return json($e->getMessage(),500);
        }
    }

    /**
     * @title 编辑Listing
     * @url EbayListing/editListing
     * @method GET
     * @apiRelate app\goods\controller\Goods::goodsToSpu
     * @apiRelate app\publish\controller\Wish::uploadImages
     * @apiRelate app\publish\controller\Wish::createNetImage
     * @apiRelate app\publish\controller\Express::Getpublishimage
     * @apiRelate app\order\controller\Order::getGoods
     * @apiRelate app\index\controller\MemberShip::publish
     * @apiParam name:id type:int require:1  desc:ListingID
     */
    public function editListing(Request $request)
    {
        try {
            $listingId = $request->get("id");
            if (empty($listingId)) {
                throw new Exception("ID不能为空！");
            }
            $res = $this->service->getListingInfo($listingId);
            return json($res,200);
        } catch(Exception $e) {
            return json(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取子产品列表
     * @url ebay-listing/variations
     * @method GET
     * @apiParam name:ids type:int require:1  desc:ListingID
     */
    public function variations(Request $request)
    {
        try{
            $listingIds = $request->get("ids");
            if(intval($listingIds)==0){
                throw new Exception("ID不能为空！");
            }
            $res = $this->service->listVariations($listingIds);
            return json(['message'=>'获取成功！！','result'=>true,'data'=>$res],200);
        }catch(Exception $e){
            return json(['message'=>$e->getFile()."|".$e->getLine()."|".$e->getMessage()],500);
        }
    }

    /**
     * @title listing管理列表
     * @url EbayListing/listingManagement
     * @method POST
     *
     * @apiParam name:id type:int require:1  desc:ListingID
     * @apiFilter app\publish\filter\EbayListingFilter
     * @apiFilter app\publish\filter\DepartFilter
     */
    public function listingManagement(Request $request)
    {
        try{
            $params = $request->param();
			$res = (new \app\publish\service\EbayCtrl())->listings($params);
            return json($res,$res['result']?200:500);

        }catch(Exception $e){
            return json(['message'=>$e->getFile()."|".$e->getLine()."|".$e->getMessage()],500);
        }
    }


    /**
     * @title 批量修改状态
     * @url EbayListing/updateListingStatus
     * @method POST
     * 
     * @apiParam name:ids type:string require:1  desc:listing标识
     * @apiParam name:action type:string require:1 desc:需要执行的操作
     */
    public function updateListingStatus(Request $request)
    {
        try{
            $ids = (string)$request->param("ids");#待修改的
            $action = $request->param("action");

            if(intval($ids)==0){#执行的操作
                throw new Exception("请选择要操作的记录！");
            }
            $this->service->upListingsStatus($ids,$action);
            return json(['result'=>true, 'message'=>'操作成功'], 200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 批量重上
     * @url EbayListing/bulkHeavyListing
     * @method GET
     * 
     * @apiParam name:ids type:string require:1  desc:listing标识
     * @apiParam name:action type:string require:1 desc:需要执行的操作
     */
    public function bulkHeavyListing(Request $request)
    {
        try{
            $ids = $request->param("ids");#待修改的
            $replen = $request->param("replen");#重上 1立即重上 2定时重上
            if(intval($ids)==0){#执行的操作
                throw new Exception("请选择要操作的记录！");
            }
            $timing = $request->param("timing");#定时
            $separated = $request->param("separated");#间隔时段
            $this->service->bulkHeavyListing($ids,$replen,$timing,$separated);
            return json(['result'=>true, 'message'=>'操作成功'], 200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取范本列表
     * @url EbayListing/getDraftList
     * @apiRelate app\publish\controller\EbayListing::shareDraft
     * @apiParam name:data type:string require:1  desc:listing标识
     * @apiFilter app\publish\filter\EbayListingFilter
     * @apiFilter app\publish\filter\DepartFilter
     */
    public function getDraftList(Request $request)
    {
        try{
            $params = $request->param();
            $res = $this->service->getListingOrDraftList($params, 1);
            return json(['data'=>$res['rows'],'count'=>$res['count']],200);
        }catch(Exception $e){
            return json(['message'=>$e->getFile()."|".$e->getLine()."|".$e->getMessage()],500);
        }
    }

    /**
     * @title 复制范本创建listing
     * @url EbayListing/cListingByDraft
     * 
     * @apiParam name:id type:int require:1  desc:draft标识
     */
    public function cListingByDraft(Request $request)
    {
        try{
            $ids = $request->get("ids",0);
            if($ids==0){
                throw new Exception("请选择范本！");
            }
            $draftIds = explode(",",$ids);
            $i=0;
            foreach($draftIds as $k => $v){
                $res = $this->service->cListing($v,0,0,'');
                if($res['result']){
                    $this->service->pushImageQueuer($res['id']);
                    $i++;
                }
            }
            return json(['message'=>'操作成功','count'=>$i,'result'=>true],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 批量复制范本
     * @url EbayListing/cDraftByDraft
     * 
     * @apiParam name:id type:int require:1  desc:draft标识
     */
    public function cDraftByDraft(Request $request)
    {
        try{
            $ids = $request->get("ids",0);
            $account_id = $request->param('account_id');
            $paypal = $request->param('paypal');
            if($ids==0){
                throw new Exception("请选择范本！");
            }
            $draftIds = explode(",",$ids);
            $i=0;
//            $data = [];
            $newDraftIds = [];
            foreach($draftIds as $k => $v){
            $res = $this->service->cListing($v,1,$account_id,$paypal);
                if($res['result']){
                    $i++;
//                    $data[]=$res;
                    $newDraftIds[] = $res['id'];
                }
            }
            $data = $this->service->getListingOrDraftList(['ids'=>json_encode($newDraftIds)], 1);

            return json(['message'=>'操作成功','count'=>$i,'result'=>true,'data'=>$data],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

     /**
     * @title 修改范本分类
     * @url EbayListing/upDraftCate
     * @method POST
     * @apiParam name:id type:int require:1  desc:draft标识
     */
    public function upDraftCate(Request $request)
    {
        try{
            $ids = $request->param("ids",0);
            if($ids==0){
                throw new Exception("请选择范本！");
            }
            $cateName = $request->param("name","");
            $idsArr = explode(",",$ids);
            foreach($idsArr as $k => $v){
                $this->service->upDraftCate($v,$cateName,$this->userId);
            }
            return json(['message'=>"修改成功！","result"=>true],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 保存定时规则
     * @url EbayListing/saveTimingRule
     * @method POST
     * @apiParam name:id type:int require:1  desc:draft标识
     */
    public function saveTimingRule(Request $request)
    {
        try{
            set_time_limit(0);
            $params = $request->param();
            $res = $this->service->syncTimingRule($params);
            return json($res,200);
        }catch(Exception $e){
            return json(['message'=>$e->getFile()."|".$e->getLine()."|".$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 获取定时规则
     * @url EbayListing/getTimingRuleList
     * @method GET
     * @apiParam name:id type:int require:1  desc:draft标识
     */
    public function getTimingRuleList(Request $request)
    {
        try{
            $ids = $request->get("ids",0);
            // if($ids==0){
            // 	throw new Exception("请选择定时规则！");
            // }
            $param['name'] = $request->get("name","");
            $param['page'] = $request->get("page",1);
            $param['size'] = $request->get("size",20);

            $rows = $this->service->timingRuleList($ids,$param);
            return json(['data'=>$rows,'result'=>true],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 删除定时规则
     * @url EbayListing/removeTimingRuleList
     * @method GET
     * @apiParam name:id type:int require:1  desc:draft标识
     */
    public function removeTimingRuleList(Request $request)
    {
        try{
            $ids = $request->get("ids",0);
            if($ids==0){
                throw new Exception("请选择定时规则！");
            }
            $this->service->rmTimingRule($ids);
            return json(['message'=>"删除成功！",'result'=>true],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 获取范本主图片
     * @url EbayListing/getDraftImgs
     * @method GET
     * @apiParam name:ids type:string require:1  desc:draft标识
     */
    public function getDraftImgs(Request $request)
    {
        try{
            $ids = $request->get("ids");
            if($ids==0){
               throw new Exception("请选择范本！");
            }else{
                $idsArr = explode(",",$ids);
            }
            $imgs=$this->service->getDraftImages($idsArr);
            return json(['result'=>true,'data'=>$imgs],200);
            }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 修改范本主图
     * @url EbayListing/upDraftImgs
     * @method POST
     * @apiParam name:data type:array require:1  desc:修改的图片信息
     */
    public function upDraftImgs(Request $request)
    {
        try{
            $params = json_decode($request->param('data'), true);
            $this->service->upDraftImages($params);
            return json(['result'=>true,'message'=>'修改成功！']);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 修改在线listing价格和数量
     * @url EbayListing/upPriceQty
     * @method POST
     * @apiParam name:data type:array require:1  desc:修改在线listing价格和数量
     */
    public function upPriceQty(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            $this->service->updatePriceQty($data);
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 修改在线listing销售天数
     * @url ebay-listing/up-listing-duration
     * @method POST
     * @apiParam name:data type:array require:1  desc:修改在线listing销售天数
     */
    public function upListingDuration(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            foreach($data as $da){
                $this->service->updateListingDuration($da);
            }
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 修改在线listing拍卖价格
     * @url EbayListing/upChinesePrice
     * @method POST
     * @apiParam name:data type:array require:1  desc:修改在线listing拍卖价格
     */
    public function upChinesePrice(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            foreach($data as $da){
                $this->service->updateChinesePrice($da);
            }
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 修改在线listing刊登标题
     * @url EbayListing/upTitle
     * @method POST
     * @apiParam name:data type:array require:1  desc:修改在线listing刊登标题
     */
    public function upTitle(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            foreach($data as $da){
                $this->service->updateTitle($da);
            }
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 修改在线listing店铺分类
     * @url EbayListing/upStore
     * @method POST
     * @apiParam name:data type:array require:1  desc:修改在线listing店铺分类
     */
    public function upStore(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            foreach($data as $da){
                $upDa['item_id'] = $da['item_id'];
                $upDa['listing_sku'] = $da['listing_sku'];
                $upDa['account_id'] = $da['account_id'];
                $upDa['site'] = $da['site'];
                $upDa['cron_time'] = strtotime($da['cron_time']);
                $upDa['remark'] = $da['remark'];
                $oldVal['store'] = $da['old_store'];
                $oldVal['second_store'] = $da['old_second_store'];
                $newVal['store'] = $da['store'];
                $newVal['second_store'] = $da['second_store'];
                #修改店铺分类 store
                $upDa['new_val'] = json_encode(['store'=>['store'=>$newVal]]);
                $upDa['old_val'] = json_encode(['store'=>['store'=>$oldVal]]);
                $upDa['api_type'] = 1;
                $this->service->insertUpdata($upDa,$this->userId);
            }
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 修改在线listing公共模块
     * @url EbayListing/upConmonMod
     * @method POST
     * @apiParam name:data type:array require:1  desc:修改在线listing公共模块
     */
    public function upConmonMod(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            foreach($data as $da){
                $this->service->updateListingCommonMod($da);
            }
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }


    /**
     * @title 修改在线listing橱窗图片
     * @url EbayListing/upImages
     * @method POST
     * @apiParam name:data type:array require:1  desc:修改在线listing橱窗图片
     */
    public function upImages(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            if (empty($data)) {
                throw new Exception('参数错误');
            }
            $res = $this->service->updateListingImages($data);
            return json($res,$res['result']?200:500);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 批量下架
     * @url EbayListing/endItems
     * @method POST
     * @apiParam name:data type:array require:1  desc:批量下架
     */
    public function endItems(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            foreach($data as $da){
                $upDa['item_id'] = isset($da['item_id'])?$da['item_id']:0;
                $upDa['listing_sku'] = isset($da['listing_sku'])?$da['listing_sku']:"";
                $upDa['account_id'] = $da['account_id'];
                $upDa['site'] = $da['site'];
                $upDa['remark'] = $da['remark'];
                $upDa['cron_time'] = strtotime($da['cron_time']);
                $upDa['new_val'] = json_encode(['end_items'=>$da['productIds']]);
                #$upDa['old_val'] = json_encode($oldVal);
                $upDa['api_type'] = 1;#批量下架
                $this->service->insertUpdata($upDa,$this->userId);
            }
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 批量修改账号
     * @url EbayListing/up-accounts
     * @method put
     * @apiParam name:data type:array require:1  desc:批量修改账号
     */
    public function upAccount(Request $request)
    {
        try{
            $ids = $request->param("ids");
            $newAccountId = $request->param("account_id");
            $paypal = $request->param("paypal");
            $this->service->upListingAccounts($ids,$newAccountId,$paypal,$this->userId);
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 获取待修改多属性范本
     * @url ebay-listing/drfspecifics
     * @method get
     * @apiParam name:data type:array require:1  desc:获取待修改多属性范本
     */
    public function getDrfSpecifics(Request $request)
    {
        try{
            $idsArr = explode(",",$request->param("ids"));
            foreach($idsArr as $id){
                $data[]=$this->service->getDraftSpecifics($id);
            }
            return json(['result'=>true,'message'=>'获取成功！','data'=>$data],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 批量修改范本多属性
     * @url EbayListing/up-specifics
     * @method put
     * @apiParam name:data type:array require:1  desc:批量修改范本多属性
     */
    public function upSpecifics(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            foreach($data as $da){
                $this->service->upListingSpecifics($da,$this->userId);
            }
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 批量修改范本标题
     * @url EbayListing/up-draftitle
     * @method put
     * @apiParam name:data type:array require:1  desc:批量修改范本多属性
     */
    public function upDraftitle(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            foreach($data as $da){
                $this->service->upDraftitle($da,$this->userId);
            }
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 批量修改范本名称前，返回修改前的信息用于前端展示
     * @url EbayListing/preUpDraftname
     * @method get
     * @apiParam name:data type:array require:1  desc:批量修改范本名称前，获取信息展示     *
     */
    public function preUpDraftname(Request $request){
        try{
            $ids = explode(',',$request->param('ids'));
            if(!is_array($ids)){
                $wh['id'] = ['in',$request->param('ids')];
            }else{
                $wh['id'] = ['in',$ids];
            }
            $data = $this->service->preUpDraftname($wh);
            $desc = Db::query('desc ebay_listing');
            foreach($desc as $field){
                if($field['Field']=='draft_name'){
                    $nameType = $field['Type'];
                    break;
                }
            }
            return json(['result'=>true,'data'=>$data,'nameType'=>$nameType],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }
    /**
     * @title 批量修改范本名称
     * @url EbayListing/upDraftname
     * @method put
     * @apiParam name:data type:array require:1  desc:批量修改范本名称
     */
    public function upDraftname(Request $request){
        try{
            $data = json_decode($request->param('data'),true);
            foreach($data as $dt){
                $this->service->upDraftname($dt,$this->userId);
            }
            return json(['result'=>true,'message'=>'修改成功'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 批量修改范本出售方式
     * @url ebay-listing/draf-listingtype
     * @method put
     * @apiParam name:data type:array require:1  desc:批量修改范本出售方式
     */
    public function upDraftListingType(Request $request)
    {
        try{
            $listingType = $request->param("listing_type");#出售方式 Chinese/FixedPriceItem
            $varions = $request->param("varions");#是否多属性产品
            $listingDuration = $request->param("listing_duration");#上架时间
            $data = json_decode($request->param("data"),true);
            foreach($data as $da){
                $this->service->upDraftListingTypeService($da,$listingType,$varions,$listingDuration);
            }
            return json(['result'=>true,'message'=>'修改成功！'],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

     /**
     * @title 获取修改记录信息
     * @url EbayListing/getActionLogs
     * @method GET
     * @apiParam name:data type:array require:1  desc:获取修改记录信息
     */
     public function getActionLogs(Request $request)
     {
        try{
            $param['item_id'] = $request->get("item_id","");
            $param['size'] = $request->get("size",20);
            $param['page'] = $request->get("page",1);
            $param['create_id'] = $this->userId;
            $rows = $this->service->getActionLogs($param);
            return json(['data'=>$rows['rows'],'count'=>$rows['count'],'result'=>true],200);
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 关联本地产品信息
     * @url EbayListing/relatedProduc
     * @method POST
     * @apiParam name:data type:array require:1  desc:关联本地产品信息
     */
    public function relatedProduc(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);
            $rows = isset($data[0])?$data[0]:$data;
            $res = $this->service->relatedProductInformation($rows,$this->userId);
            if($res['result']){
                return json(['message'=>$res['message'],'result'=>true],200);
            }else{
                return json(['message'=>$res['message'],'result'=>true],500);
            }
        }catch(Exception $e){
            return json(['message'=>$e->getMessage(),'result'=>false],500);
        }
    }

    /**
     * @title 获取刊登费用
     * @url EbayListing/getListingFee
     * @method POST
     * 
     * @apiParam name:data type:string require:1  desc:listing标识
     */
    public function getListingFee(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);#listing信息
            if(!is_array($data)) $data = [];
            foreach($data as $val){
                $res = $this->service->testPublishFee($val);
                $newReturnData['insertion_fee'] = isset($res['fees_info']['InsertionFee'])?$res['fees_info']['InsertionFee']:0;
                $newReturnData['listing_fee'] = isset($res['fees_info']['ListingFee'])?$res['fees_info']['ListingFee']:0;
                return json(['result'=>true,'data'=>$newReturnData],200);
//                $listingId = $this->service->saveListingPublish($val);
                #$res=['result'=>true,'listing_id'=>3582];
//                if(!empty($listingId)){
////                    $listingId = $res['listing_id'];#listingID
//                    set_time_limit(0);
//                    $ebayVerif = new EbayVerifyAddFixedPriceItem();
//                    #获取刊登数据
//                    $rows = $ebayVerif->getPublishRows($listingId);
//                    #$ebayVerif->relateCard($rows);die;
//                    // echo "<pre>";print_r($rows);die;
//                    if($rows['list']['listing_type']==1){#固定价格
//                        $verb = "VerifyAddFixedPriceItem";
//                    }else if($rows['list']['listing_type']==2){#拍卖
//                        $verb = "VerifyAddItem";
//                    }
//                    $ebayApi = $ebayVerif->createApi($rows['list']['account_id'],$verb,$rows['list']['site']);
//                    $xml = $ebayVerif->createXml($rows);#echo $xml;die;
//                    $resText = $ebayApi->createHeaders()->__set("requesBody",$xml)->sendHttpRequest2();
//                    Cache::handler()->set('ebay:verify:response:'.$rows['list']['spu'], $resText);
//                    #echo "<pre>";
//                    #print_r($resText);#die;
//                    if($rows['list']['listing_type']==1){#固定价格
//                        $reponse = isset($resText['VerifyAddFixedPriceItemResponse'])?$resText['VerifyAddFixedPriceItemResponse']:[];
//                    }else if($rows['list']['listing_type']==2){#拍卖
//                        $reponse = isset($resText['VerifyAddItemResponse'])?$resText['VerifyAddItemResponse']:[];
//                    }
//                    $siteInfo = (new EbaySite())->where(['siteid'=>$rows['list']['site']])->find();
//                    if(!empty($reponse)){#处理返回结果
//                        $resUp = $ebayVerif->processingReponse($reponse,$rows,$xml);
//                        $up = $resUp['list'];
//                        $upSet = $resUp['set'];
//                    }else{#请求结果出错
//                        $up['listing_status']=4;
//                        $upSet['message'] = "未获取到请求结果！";
//                        $upSet['send_content'] = $xml;
//                    }
//                    $this->service->deleteListing($listingId);
//                    if($up['listing_status']==4){#获取刊登费用失败
//                        return json(['result'=>false,'message'=>"刊登失败！".$upSet['message']],500);
//                    }else{#获取刊登费用成功
//                        $newReturnData['insertion_fee'] = isset($up['insertion_fee'])?$up['insertion_fee']:0;
//                        $newReturnData['listing_fee'] = isset($up['listing_fee'])?$up['listing_fee']:0;
//                        return json(['result'=>true,'data'=>$newReturnData],200);
//                    }
//                }
            }
//            set_time_limit(0);
//            $data = json_decode($request->param("data"),true);#listing信息
//            if(!is_array($data)) $data = [];
//            foreach($data as $val){
//                $res = $this->service->testPublishFee($val);
//                return json($res, 200);
//                $res = $this->service->saveListingPublish($val,$this->userId);
//                #$res=['result'=>true,'listing_id'=>3582];
//                if($res['result']){
//                    $listingId = $res['listing_id'];#listingID
//                    set_time_limit(0);
//                    $ebayVerif = new EbayVerifyAddFixedPriceItem();
//                    #获取刊登数据
//                    $rows = $ebayVerif->getPublishRows($listingId);
//                    #$ebayVerif->relateCard($rows);die;
//                    // echo "<pre>";print_r($rows);die;
//                    if($rows['list']['listing_type']==1){#固定价格
//                        $verb = "VerifyAddFixedPriceItem";
//                    }else if($rows['list']['listing_type']==2){#拍卖
//                        $verb = "VerifyAddItem";
//                    }
//
//                    $ebayApi = $ebayVerif->createApi($rows['list']['account_id'],$verb,$rows['list']['site']);
//                    $xml = $ebayVerif->createXml($rows);#echo $xml;die;
//                    $resText = $ebayApi->createHeaders()->__set("requesBody",$xml)->sendHttpRequest2();
//                    #echo "<pre>";
//                    #print_r($resText);#die;
//                    if($rows['list']['listing_type']==1){#固定价格
//                        $reponse = isset($resText['VerifyAddFixedPriceItemResponse'])?$resText['VerifyAddFixedPriceItemResponse']:[];
//                    }else if($rows['list']['listing_type']==2){#拍卖
//                        $reponse = isset($resText['VerifyAddItemResponse'])?$resText['VerifyAddItemResponse']:[];
//                    }
//                    $siteInfo = (new EbaySite())->where(['siteid'=>$rows['list']['site']])->find();
//                    if(!empty($reponse)){#处理返回结果
//                        $resUp = $ebayVerif->processingReponse($reponse,$rows,$xml);
//                        $up = $resUp['list'];
//                        $upSet = $resUp['set'];
//                    }else{#请求结果出错
//                        $up['listing_status']=4;
//                        $upSet['message'] = "未获取到请求结果！";
//                        $upSet['send_content'] = $xml;
//                    }
//                    $this->service->deleteListing($listingId);
//                    if($up['listing_status']==4){#获取刊登费用失败
//                        return json(['result'=>false,'message'=>"刊登失败！".$upSet['message']],500);
//                    }else{#获取刊登费用成功
//                        $newReturnData['insertion_fee'] = isset($up['insertion_fee'])?$up['insertion_fee']:0;
//                        $newReturnData['listing_fee'] = isset($up['listing_fee'])?$up['listing_fee']:0;
//                        return json(['result'=>true,'data'=>$newReturnData],200);
//                    }
//                }
//            }
        }catch(Exception $e){
            return json(['message'=>$e->getFile().$e->getLine().$e->getMessage()],500);
        }
    }

    /**
     * @title 立即刊登->提交数据
     * @url ebay-listing/publish-immediately-save
     * @method POST
     * @apiParam name:data type:array require:1  desc:立即刊登->提交数据
     */
    public function publishImmediatelySave(Request $request)
    {
        try{
            $data = json_decode($request->param("data"),true);#listing信息
            $resData = [];
            $reponseCache = new EbayListingReponseCache();
            foreach($data as $val){
//                $res = $this->service->saveAndSetCache($val);
                $listingId = $this->service->saveListingPublish($val);
                if ($listingId == 0) {
                    throw new Exception('已存在一条相同的在线listing,无法再进行创建');
                }
                $resDa = (new \app\common\model\ebay\EbayListing())->field(true)->where(['id'=>$listingId])->find();
                $temp['res_data'] = $resDa;
                $temp['listing_id'] = $listingId;
                $temp['result'] = true;
                $rDa = $temp['res_data'];
                unset($temp['res_data']);
                $temp['spu'] = $rDa['spu'];
                $temp['site'] = $rDa['site'];
                $siteInfo = Cache::store('ebaySite')->getSiteInfoBySiteId($rDa['site']);
                $temp['symbol'] = $siteInfo['symbol'];
                $temp['currency'] = $siteInfo['currency'];
                $temp['site_code'] = $siteInfo['country'];
                $temp['site_name'] = $siteInfo['name'];
                $temp['account_id'] = $rDa['account_id'];
                $acInfo = Cache::store('EbayAccount')->getTableRecord($rDa['account_id']);
                $temp['account_code'] = $acInfo['code'];
                $temp['title'] = $rDa['title'];
                $temp['insertion_fee'] = $rDa['insertion_fee'];
                $resData[] = $temp;
                $cache = $temp;
                $cache['id'] = $temp['listing_id'];
                unset($cache['listing_id']);
                #id,listing_status,insertion_fee,listing_fee,item_id,spu,site,account_id,title
                $reponseCache->setReponseCache($cache['id'],$cache);
            }
//            $res = array("result"=>true,"message"=>"提交成功！","data"=>$resData);
            return json(['data'=>$resData, 'result'=>true, 'message'=>'提交成功'],200);
        }catch(Exception $e){
            return json(["result"=>false,"message"=>$e->getMessage()],500);
        }
    }

    /**
     * @title 立即刊登->查看结果
     * @url ebay-listing/publish-immediately-results
     * @method GET
     * @apiParam name:ids type:array require:1  desc:立即刊登->查看结果
     */
    public function publishImmediatelyResults(Request $request)
    {
        try{
            $ids = $request->get("ids",0);#listing信息
            if($ids==0){
                throw new Exception("请输入要查看的ID！");
            }
            $resData = [];
            $resData = $this->service->publishImmediatelyResultsService($ids);
            $res = ["result"=>true,"message"=>"请求成功！","data"=>$resData,"count"=>count($resData)];
            return json($res,200);
        }catch(Exception $e){
            return json([$e->getMessage()],500);
        }
    }

    /**
     * @title 立即刊登
     * @url ebay-listing/publish-immediately
     * @method GET
     * @apiParam name:data type:array require:1  desc:立即刊登
     */
    public function publishImmediately(Request $request)
    {
        set_time_limit(0);
        try{
//            $reponseCache = new EbayListingReponseCache();
            $ids = $request->get("ids",0);
            if($ids==0){
                return json(["message"=>"提交的刊登数据不能为空！"],500);
            }
            $data = explode(",",$ids);#listing信息
            foreach($data as $val){
                $this->service->publishImmediately($val);
//                $listingId = $val;#listingID
//                #$listingId = 3503;#listingID
//                $ebayVerif = new EbayPublishItem();
//                #获取刊登数据
//                $rows = $ebayVerif->getPublishRows($listingId);
//                #$ebayVerif->relateCard($rows);die;
//                if($rows['list']['listing_type']==1){#固定价格
//                    $verb = "AddFixedPriceItem";
//                }else if($rows['list']['listing_type']==2){#拍卖
//                    $verb = "AddItem";
//                }
//                $ebayApi = $ebayVerif->createApi($rows['list']['account_id'],$verb,$rows['list']['site']);
//                $xml = $ebayVerif->createXml($rows);#echo $xml;die;
//                $resText = $ebayApi->createHeaders()->__set("requesBody",$xml)->sendHttpRequest2();
//                #echo "<pre>";
//                #print_r($resText);#die;
//                if($rows['list']['listing_type']==1){#固定价格
//                    $reponse = isset($resText['AddFixedPriceItemResponse'])?$resText['AddFixedPriceItemResponse']:[];
//                }else if($rows['list']['listing_type']==2){#拍卖
//                    $reponse = isset($resText['AddItemResponse'])?$resText['AddItemResponse']:[];
//                }
//                if(!empty($reponse)){#处理返回结果
//                    $resUp = $ebayVerif->processingReponse($reponse,$rows,$xml);
//                    $up = $resUp['list'];
//                    $upSet = $resUp['set'];
//                }else{#请求结果出错
//                    $up['listing_status']=4;
//                    $upSet['message'] = "未获取到请求结果！";
//                    $upSet['send_content'] = $xml;
//                }
//
//                #更新缓存
//                $cache = $reponseCache->getReponseCache($val);
//                $cache['listing_status'] = isset($up['listing_status'])?$up['listing_status']:0;
//                $cache['item_id'] = isset($up['item_id'])?$up['item_id']:0;
//                $cache['insertion_fee'] = isset($up['insertion_fee'])?$up['insertion_fee']:0;
//                $cache['listing_fee'] = isset($up['listing_fee'])?$up['listing_fee']:0;
//                $cache['message'] = isset($upSet['message'])?$upSet['message']:"";
//                $reponseCache->setReponseCache($val,$cache);
//
//                (new EbayListingMod())->where(['id'=>$listingId])->update($up);
//                (new EbayListingSetMod())->where(['id'=>$listingId])->update($upSet);
            }
        }catch(Exception $e){
            return json(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 立即重上
     * @url ebay-listing/relist-itm
     * @method GET
     * @apiParam name:data type:array require:1  desc:立即刊登
     */
    public function relistItm(Request $request)
    {
        set_time_limit(0);
        try{
            $ids = $request->get("ids",1136);
            if($ids==0){
                return json(["message"=>"提交的重上数据不能为空！"],500);
            }
            $data = explode(",",$ids);#listing信息
            $ebayRelist = new EbayRelistItem();
            $resData = [];
            foreach($data as $id){ 
                $listingId = $id;#listingID
                $resData = $ebayRelist->ebayRelistItem($id);
                if($resData['result']){#重上成功
                    return json(['message'=>$resData['rows']['message']],200);
                }else{#重上失败
                    return json(['message'=>$resData['rows']['message']],500);
                }
            }
            #return json(['data'=>$resData],200);
        }catch(Exception $e){
            return json(['message'=>$e->getFile()."|".$e->getLine()."|".$e->getMessage()],500);
        }
    }

    /**
     * @title 促销设置
     * @url ebay-listing/promotion-listings
     * @method GET
     * @apiParam name:data type:array require:1  desc:立即刊登
     */
    public function promotionListings(Request $request)
    {
        set_time_limit(0);
        try{
            $site = $request->get("site",0);
            $accountId = $request->get("account_id",19);
            $promotionId = $request->get("promotion_id",55);
            $itemIds = $request->get("item_ids","1234561231");
            if($accountId==0 || $promotionId==0 || $itemIds == ""){
                return json(["message"=>"账号，站点，促销策略都不能为空！"],500);
            }
            $proMod = new EbayModelPromotion();
            $promo = $proMod->where(['id'=>$promotionId])->find();
            if($promo['status']==2){#已失效
                return json(["message"=>"促销策略已失效！"],500);
            }else if($promo['promotional_sale_id']==0){#未同步
                return json(["message"=>"促销策略未同步到EBAY！"],500);
            }
            $itemIdsArr = explode(",",$itemIds);#listing信息
            $setProm = new EbaySetPromotionalSaleListings();
            $res = $setProm->SetPromotionalSaleListings($promo['promotional_sale_id'],$accountId,$site,$itemIdsArr);
            return json(['data'=>$res],200);
        }catch(Exception $e){
            return json(['message'=>$e->getFile()."|".$e->getLine()."|".$e->getMessage()],500);
        }
    }
    /**
     * @title 批量导出范本
     * @url EbayListing/exportDraftInfo
     * @method GET
     * @apiParam name:ids type:string require:1  desc:批量导出范本
     */
    public function exportDraftInfo(Request $request)
    {
        try {
            set_time_limit(0);
            $ids = $request->param('ids');//范本id
            $type = $request->param('type');//导出类型，0：标准格式，1：小平台格式，
            if (empty($ids)) {
                throw new Exception("ids 不能为空");
            }
            $res = $this->service->exportDraftInfo($ids, $type);
            return json($res,$res['result']===true?200:500);
        } catch (Exception $e) {
            return json(['message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()],500);
        } catch (\Exception $e) {
            return json(['message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()],500);
        }
    }

    /**
     * @title 批量导入范本
     * @url EbayListing/importDraftInfo
     * @method POST
     * @apiParam name:ids type:string require:1  desc:批量导出范本
     */
    public function importDraftInfo(Request $request)
    {
        $filePath = '';
        try {
            set_time_limit(0);
            $file_extension = $request->param('extension');
            $file_content = $request->param('content');
            $file_name = $request->param('name');
            //post的数据里面，加号会被替换为空格，需要重新替换回来，如果不是POST的数据，则屏蔽下面一行
            $base64file = str_replace(' ', '+', $file_content);
            $file_content = substr($base64file, strpos($base64file, 'base64,') + 7);
            $savePath = './upload/';
            $file_name = date('YmdHis').rand(1000000,9999999) . '.' . $file_extension;//重命名
            $filePath = $savePath.$file_name;
            if (file_put_contents($savePath . $file_name, base64_decode($file_content))) {
                $res = $this->service->importDraftInfo($filePath);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return json(['result' => true, 'message' => $res['message']], 200);
            } else {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return json(['message' => '上传的文件无法保存，导入失败'], 500);
            }
        } catch (Exception $e) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return json(['result'=>false, 'message'=>[['0'=>$e->getMessage()]], 'count'=>0], 500);
        }
    }
    /**
     * @title 批量分享范本
     * @url EbayListing/shareDraft
     * @method POST
     * @apiParam name:ids type:string require:1  desc:范本id
     * @apiParam name:option type:string require:1  desc:要执行的操作，分享或取消分享
     */
    public function shareDraft(Request $request)
    {
        try {
            $ids = $request->param('ids');
            $option = $request->param('share');
            $res = $this->service->shareDraft($ids,$this->userId,$option);
            return json($res,$res['result']===true?200:500);
        }catch (Exception $e){
            return json(['message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage(),'result'=>false],500);
        }

    }

    /**
     * @title 同步ebay官网特定站点物流方式
     * @url trans/sync
     * @method POST
     * @apiParam name:site_id type:int require:1  desc:站点id
     * @apiParam name:account_id type:int require:1 desc:账号id
     * @param Request $request
     * @return \think\response\Json
     */
    public function syncTrans(Request $request)
    {
        try {
            set_time_limit(0);
            $siteId = $request->param('site_id');
            $accountId = $request->param('account_id');
            if ((empty($siteId) && $siteId != 0) || empty($accountId)) {
                throw new Exception('站点和账号不能为空');
            }
            $res = $this->service->syncTrans($siteId, $accountId);
            if ($res) {
                return json(['result' => true, 'message' => '同步成功'], 200);
            } else {
                return json(['result'=>false, 'message'=>'同步失败，请重试'], 500);
            }
        } catch(Exception $e) {
            return json(['result'=>false,'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 批量检测刊登费用
     * @url testfees/batch
     * @method GET
     * @apiParam name:draft_ids type:string require:1  desc:范本id,多个','隔开
     * @apiParam name:account_ids type:string require:1 desc:账号id,多个','隔开
     */
    public function testListingFees(Request $request)
    {
        try {
            $draftIdStr = $request->param('draft_ids');
            $accountIdStr = $request->param('account_ids');
            if (empty($draftIdStr)|| empty($accountIdStr)) {
                throw new Exception('范本和账号不能为空');
            }
            $res = $this->service->testListingFees($draftIdStr, $accountIdStr);
            return json(['result'=>true,'data'=>$res], 200);
        } catch(Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

}

