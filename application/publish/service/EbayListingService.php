<?php
namespace app\publish\service;

use app\common\model\ChannelUserAccountMap;
use app\common\model\ebay\EbayCategory;
use app\common\model\ebay\EbayCategorySpecific;
use app\common\model\ebay\EbayCustomCategory;
use app\common\model\ebay\EbayDraft;
use app\common\model\ebay\EbayModelComb;
use app\common\model\ebay\EbayModelSale;
use app\common\model\ebay\EbayModelStyle;
use app\common\model\ebay\EbayTitle;
use app\common\model\ebay\EbayTrans;
use app\common\model\LogExportDownloadFiles;
use app\common\model\paypal\PaypalAccount;
use app\publish\helper\ebay\EbayPublish as EbayPublishHelper;
use app\publish\helper\ebay\EbayPublish;
use app\publish\queue\ebayProductStatusQueue;
use app\publish\queue\EbayPublishItemQueuer;
use app\publish\queue\EbayRelistQueuer;
use app\publish\queue\EbaySettingMove2Mongo;
use app\publish\queue\EbayUpdateOnlineListing;
use think\Db;
use think\Exception;
use app\publish\service\EbayHelper;
use app\common\cache\driver\Goods;
use app\common\cache\driver\EbayCategoryCache;
use app\index\service\MemberShipService;
use app\goods\service\GoodsSkuMapService;
use app\publish\service\EbayService;
use app\common\service\Twitter;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\mongodb\ebay\EbayListingSetting as ELSMongo;
use app\common\model\ebay\EbayListingImage;
use app\common\model\ebay\EbayListingSpecifics;
use app\common\model\ebay\EbayListingTransport;
use app\common\model\ebay\EbayListingTransportIn;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\ebay\EbayListingMappingSpecifics;
use app\common\model\ebay\EbayCommonBargaining;
use app\common\model\ebay\EbayCommonChoice;
use app\common\model\ebay\EbayCommonCounter;
use app\common\model\ebay\EbayCommonExclude;
use app\common\model\ebay\EbayCommonGallery;
use app\common\model\ebay\EbayCommonLocation;
use app\common\model\ebay\EbayCommonPickup;
use app\common\model\ebay\EbayCommonQuantity;
use app\common\model\ebay\EbayCommonReceivables;
use app\common\model\ebay\EbayCommonRefuseBuyer;
use app\common\model\ebay\EbayCommonIndividual;
use app\common\model\ebay\EbayCommonSale;
use app\common\model\ebay\EbayCommonTemplate;
use app\common\model\ebay\EbayCommonTrans;
use app\common\model\ebay\EbayCommonTransDetail;
use app\common\model\ebay\EbayCommonReturn;
use app\goods\service\GoodsImage;
use app\publish\queue\EbayImgQueuer;
use app\publish\queue\EbayQueuer;
use app\publish\queue\EbayTimingQueuer;
use app\goods\controller\ChannelCategory;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\publish\queue\EbayUpdateItemJob;
use app\common\model\Goods as ModelGoods;
use app\common\model\Category;
use app\common\model\GoodsAttribute;
use app\common\model\AttributeValue;
use app\common\model\GoodsLang;
use app\common\model\Currency;
use app\common\model\GoodsSku;
use app\common\model\Channel;
use app\common\model\GoodsCategoryMap;
use app\common\model\Attribute;
use app\common\model\ebay\EbaySite;
use app\common\model\GoodsGallery;
use app\common\model\ebay\EbayHistoryCategory;
use app\common\model\ebay\EbayListingSerialNumber;
use app\common\model\ebay\EbayActionLog;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayListingTiming;
use app\common\model\GoodsSkuMap;
use app\common\cache\Cache;
use app\goods\service\GoodsPublishMapService;
use app\publish\task\EbayUploadImgs;
use app\common\cache\driver\EbayListingReponseCache;
use app\common\service\Common;
use app\common\model\User;
use app\common\service\ImportExport;
//use app\common\service\ImportExportNew;
use app\common\traits\User as UserTraits;
use app\publish\service\CommonService;
use think\Request;
use app\publish\service\EbayConstants as Constants;


/** Ebay Listing管理
 * User: zengshaohui
 * Date: 2017/6/12
 * Time: 11:04
 */
class EbayListingService
{
    use UserTraits;
    private $userId;
    private $queuer;
    private $cacheSite;
    private $helper;
    private $publishHelper;


    protected function init()
    {
        // $this->imgQueuer = new \app\publish\queue\EbayImgQueuer();
        // $this->queuer = new \app\publish\queue\EbayQueuer();
        // $this->cacheSite = new \app\goods\controller\ChannelCategory();
    }

    public function __construct(int $userId=0)
    {
        $this->userId = $userId;
        $this->queuer = new \app\publish\queue\EbayQueuer();
        $this->cacheSite = new \app\goods\controller\ChannelCategory();
        $this->categoryCache = new \app\common\cache\driver\EbayCategoryCache();
        $this->helper = new EbayListingCommonHelper();
        $this->publishHelper = new EbayPublishHelper();
    }

    /**
     * @title 从商品添加listing时，获取相关信息
     * @param int $goodsId 商品ID
     * @return array
     * @throws Exception
     */
    public function addListingService(int $goodsId, $siteId=0):array
    {
        try{
            $data = $this->publishHelper->getGoods($goodsId, $siteId);
            //范本信息
            $wh['goods_id'] = $goodsId;
            $wh['site_id'] = $siteId;
            //先查对应站点的
            $listingId = EbayDraft::where($wh)->value('listing_id');
            if ($listingId) {//如果存在，获取详情
                $data['draft'] = $this->getListingInfo($listingId);
                $list = $data['draft']['list'];
                unset($list['id']);
                $list['listing_sku']='';
                unset($list['sold_quantity']);
                unset($list['hit_count']);
                unset($list['create_date']);
                unset($list['start_date']);
                unset($list['end_date']);
                unset($list['update_date']);
                unset($list['listing_status']);
                unset($list['insertion_fee']);
                unset($list['listing_fee']);
                unset($list['listing_cate']);
                unset($list['timing']);
                unset($list['rule_id']);
                unset($list['user_id']);
                unset($list['realname']);
                unset($list['manual_end_time']);

                $data['draft']['list'] = $list;


                unset($data['draft']['set']['id']);

//                unset($data['draft']['imgs']);
//                unset($data['draft']['detail_imgs']);
                foreach ($data['draft']['varians'] as &$varian) {
                    unset($varian['id']);
                    unset($varian['channel_map_code']);
                }
            }
            //始终获取所有站点信息
            $siteIds = EbayDraft::where('goods_id',$goodsId)->column('site_id');
            $data['draft_site'] = $siteIds;
//            $field = 'l.id,primary_categoryid,site,variation,specifics,variation_image';
//            $log = EbayListing::alias('l')->field($field)->where($wh)
//                ->join('ebay_listing_setting s','l.id=s.id','LEFT')->find();
//
//            if (!empty($log)) {
//                $log['specifics'] = json_decode($log['specifics'],true);
//                $log['primary_category_pahtname'] = $this->publishHelper->getEbayCategoryChain($log['primary_categoryid'], $log['site']);
////                $log['second_category_name'] = $this->publishHelper->getEbayCategoryChain($log['second_categoryid'], $log['site']);
//                $data['history']['listing'] = $log;
//                if ($log['variation']) {//多属性
//                    $badFlag = 0;
////                    $fieldVar = 'sku,variation';
//                    $variants = EbayListingVariation::where('listing_id',$log['id'])->column('variation','v_sku');
//                    foreach ($variants as $k => &$variant) {
//                        if (empty($k)) {
//                            $badFlag = 1;
//                            break;
//                        }
//                        $variant = json_decode($variant,true);
//                        empty($tmpKeys) && $tmpKeys = array_keys($variant??[]);
//                    }
//                    $data['history']['variants'] = $variants;
//                    //产品本地属性与平台属性映射
//                    $oldMappingspec = EbayListingMappingSpecifics::where(['listing_id'=>$log['id'],'is_check'=>1])->column('combine_spec','channel_spec');
//                    $mappingspec = [];
//                    if ($oldMappingspec && isset($tmpKeys)) {
//                        foreach ($tmpKeys as $k => $tmpKey) {
//                            $mappingspec[$k]['is_check'] = true;
//                            $mappingspec[$k]['channel_spec'] = $tmpKey;
//                            $mappingspec[$k]['combine_spec'] = $oldMappingspec[$tmpKey]??'';
//                        }
//                    }
//                    $data['history']['mappingspec'] = $mappingspec;
//                    if ($badFlag) {
//                        unset($data['history']);
//                    }
//
//                }
//
//            }
            //获取平台销售账号
//            $channelId = Cache::store('channel')->getChannelId('ebay');
            $data['accounts'] = (new MemberShipService)->memberByPublish(0,
                1,
                'sales',
                $data['goodsInfo']['spu']);
            return $data;
        }catch(Exception $e){
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }


    /**
     *
     * @param Request $request
     * @param int $isDraft
     * @return array
     * @throws Exception
     */
    public function getListingOrDraftList($params, int $isDraft) : array
    {
        try {
            $wh= [];
            $whOr = [];
            $order = "";
            $page = 1;
            $size = 20;
            $published = 2;//全部
            foreach ($params as $k => $v) {
                if (empty($v) && $v !== 0 && $v != '0') {
                    continue;
                }
                switch ($k) {
                    case 'ids':
                        $ids = json_decode($v, true);
                        if (is_null($ids)) {//认为传递的是字符串
                            $wh['l.id'] = $v;
                        } else if (is_array($ids) && count($ids)>0) {
                            $wh['l.id'] = ['in', $ids];
                        }
                        break;
                    case 'item_id':
                    case 'draft_name':
                        $ar = json_decode($v, true);
                        if (is_null($ar)) {
                            $wh['l.'.$k] = $v;
                        } else if (is_array($ar) && count($ar)>0) {
                            $wh['l.'.$k] = ['in',$ar];
                        }
                        break;
                    case 'spu':
                        $spuAr = json_decode($v, true);
                        if (is_null($spuAr)) {
                            $spuAr = [$v];
                        }
                        if (!empty($spuAr)) {
                            $goodsIds = \app\common\model\Goods::where(['spu' => ['in', $spuAr]])->column('id');
                            $wh['l.goods_id'] = ['in', $goodsIds];
                        }
                        break;
//                    case 'sub_title':
                    case 'title'://标题支持批量，支持模糊搜索
                        $title = json_decode($v,true);
                        if (is_null($title)) {
                            $wh['l.'.$k] = ['like','%'.$v.'%'];
                        } else if (is_array($title)) {
                            $wh['l.'.$k] = count($title)==1 ? ['like','%'.$title[0].'%'] : ['in',$title];
                        }
                        break;
                    case 'listing_sku':
                        $ar = json_decode($v, true);
                        if (is_null($ar) && !empty($v)) {
                            $wh['l.listing_sku'] = ['like', $v.'%'];
                            $whOr['channel_map_code'] = ['like', $v.'%'];
                        } else if (is_array($ar) && count($ar)==1) {
                            $wh['l.listing_sku'] = ['like', $ar[0].'%'];
                            $whOr['channel_map_code'] = ['like', $ar[0].'%'];
                        } else if (is_array($ar) && count($ar)>1) {
                            $wh['l.listing_sku'] = ['in', $ar];
                            $whOr['channel_map_code'] = ['in', $ar];
                        }
                        break;
                    case 'sku':
                        $ar = json_decode($v, true);
                        if (empty($ar)) {
                            break;
                        }
                        $wh['l.local_sku'] = ['in',$ar];
                        $whOr['v_sku'] = ['in', $ar];
                        break;
                    case 'account_code':
                        $codes = json_decode($v, true);
                        if (is_null($codes)) {
                            break;
                        } else if (is_array($codes) && count($codes)>0) {
                            $accountIds = EbayAccount::where(['code' => ['in', $codes]])->column('id');
                            $wh['l.account_id'] = ['in', $accountIds];
                        }
                        break;
                    case 'application':
                    case 'account_id':
                    case 'site':
                    case 'model_cate':
                    case 'listing_type':
                    case 'listing_duration':
                    case 'replen':
                    case 'goods_type':
                    case 'restart':
//                    case 'promotion_id':
                    case 'paypal_emailaddress':
                    case 'location':
                    case 'best_offer':
                    case 'return_time':
                    case 'rule_id':
                    case 'listing_cate':
                    case 'variation':
                        $wh['l.'.$k] = $v;
                        break;
                    case 'promotion_id'://促销折扣
                        if ($v == 0) {//无折扣
                            $wh['is_promotion'] = 0;
                        } else {
                            $wh['promotion_id'] = $v;
                        }
                        break;
//                    case 'adjust_type':
//                        if ($v == -1) {
//                            $wh['l.cost_price'] = ['exp', '>adjusted_cost_price'];
//                            $whOr['cost_price'] = ['exp', '>adjusted_cost_price'];
//                        } else if ($v == 1) {
//                            $wh['l.cost_price'] = ['exp', '<adjusted_cost_price'];
//                            $whOr['cost_price'] = ['exp', '<adjusted_cost_price'];
//                        }
//                        break;
                    case 'adjust_range':
                        if ($v > 0) {//涨价
                            $wh['l.adjusted_cost_price'] = ['exp', '>l.cost_price+'.$v];
                            $whOr['adjusted_cost_price'] = ['exp', '>cost_price+'.$v];
                        } else if ($v < 0) {//降价
                            $wh['l.adjusted_cost_price'] = ['exp', '>l.cost_price'.$v];
                            $whOr['adjusted_cost_price'] = ['exp', '>cost_price'.$v];
                        } else if ($v == 0) {
                            $wh['l.adjusted_cost_price'] = ['exp', '=l.cost_price'];
                            $whOr['adjusted_cost_price'] = ['exp', '=cost_price'];
                        }
                        break;
                    case 'work_off'://销售状态
                        if ($v == 1) {
                            $wh['l.sold_quantity'] = ['eq', 0];
                        } else if ($v == 2) {
                            $wh['l.sold_quantity'] = ['neq', 0];
                        }
                        break;
                    case 'sales_status':
                        $wh['g.sales_status'] = $v;
                        break;
                    case 'category':
                        $wh['l.primary_categoryid'] = $v;
                        break;
                    case 'picture_gallery':
                        $wh['l.picture_gallery'] = Constants::LISTVAR_RESERVE_EN['pictureGallery'][$v];
                        break;
                    case 'quantity':
                        $wh['l.quantity'] = $v==1 ? 1 : ['neq',1];
                        break;
                    case 'sub_title':
                        $wh['l.sub_title'] = ['=', 'not null'];
                        break;
                    case 'choice_date':
                        $wh['l.dispatch_max_time'] = $v;
                        break;
                    case 'realname':
                        $map['realname'] = ['like', trim($v).'%'];//创建者
                        $wh['l.realname'] = User::where($map)->value('id');
                        break;
                    case 'update_realname'://更新人
                        $map['realname'] = ['like', trim($v)];
                        $wh['l.user_id'] = User::where($map)->value('id');
                        break;
                    case 'listing_status':
                        if ($v == 3) {
                            $wh['l.listing_status'] = ['in', EbayPublish::OL_PUBLISH_STATUS];//在线状态
                        } else if ($v == 1) {
//                            $wh['l.listing_status'] = ['in', [0,1]];
                            $wh['l.listing_status'] = 1;//刊登队列中
                        } else if ($v == 4) {
                            $wh['l.listing_status'] = 4;//刊登异常
                        } else if ($v == '9,11') {
                            $wh['l.listing_status'] = ['in',$v];//下架状态
                        }
                        break;
                    case 'name':
                        $startDate = $params['start_date'];
                        $endDate = $params["end_date"];
                        if (empty($startDate) && empty($endDate)) break;

                        $startDate = explode(' ', $startDate);
                        $endDate = explode(' ', $endDate);

                        $startDate = empty($startDate[0]) ? 0 : strtotime($startDate[0].' '.'00:00:00');
                        $endDate = empty($endDate[0]) ? time() : strtotime($endDate[0].' '.'23:59:59');
                        if ($startDate !== false || $endDate !== false) {
                            if ($startDate == $endDate) {
                                $endDate = $startDate + 86400;
                            }
                            if ($v == 'start') {
                                $wh['l.start_date'] = array("between",[(string)$startDate,(string)$endDate]);
                            } else if ($v == 'end') {
                                $wh['l.end_date'] = array("between",[(string)$startDate,(string)$endDate]);
                            } else if ($v == 'create') {
                                $wh['l.create_date'] = array("between",[(string)$startDate,(string)$endDate]);
                            } else if ($v == 'update') {
                                $wh['l.update_date'] = array("between",[(string)$startDate,(string)$endDate]);
                            }
                        }
                        break;
                    case 'share':
                        switch($v){
                            case 7://默认不限制
                                break;
                            case 1://未共享
                                $wh['l.shared_userid'] = 0;
                                break;
                            case 2://已共享
                                $wh['l.shared_userid'] = $this->userId;
                                break;
                            case 3://未共享+已共享
                                $wh['l.shared_userid'] = ['in', [0,$this->userId]];
                                break;
                            case 4://他人共享
                                $wh['l.shared_userid'] = ['not in', ['0',$this->userId]];
                                break;
                            case 5://未共享+他人共享
                                $wh['l.shared_userid'] = ['neq', $this->userId];
                                break;
                            case 6://已共享+他人共享
                                $wh['l.shared_userid'] = ['neq', 0];
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'order_sold_quantity':
                        $order .= 'l.sold_quantity '.$v.',';
                        break;
                    case 'order_price':
                        $order .= 'l.start_price '.$v.',';
                        break;
                    case 'order_publish_date':
                        $order .= 'l.start_date '.$v.',';
                        break;
                    case 'order_start_date':
                        $order .= 'l.create_date '.$v.',';
                        break;
                    case 'published':
                        $published = $v;
                        break;
                    case 'page':
                        $page = $v ? $v : 1;
                        break;
                    case 'size':
                        $size = $v ? $v : 20;
                        break;
                    default:
                        break;
                }
            }
            if (empty($order)) {
                $order = "l.id desc,";
            }
            if (!empty($whOr)) {
                $listingIds = EbayListingVariation::distinct(true)->where($whOr)->column('listing_id');
                if (isset($wh['l.adjusted_cost_price'])) {
                    $mainListingIds = EbayListing::where(['adjusted_cost_price'=>$wh['l.adjusted_cost_price']])->column('id');
                    $listingIds = array_merge($listingIds,$mainListingIds);
                }
                if (!empty($listingIds)) {
                    $wh['id'] = ['in',$listingIds];
                }
            }
            $order = substr($order, 0, -1);
            $wh['l.draft'] = $isDraft ? 1 : 0;
            $start = ($page-1)*$size;
            $map = [
                'wh' => $wh,
//                'whOr' => $whOr,
                'start' => $start,
                'size' => $size,
                'order' => $order,
                'published' => $published,
            ];
            $res = $this->getListings($map);
            return $res;
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @title 创建子产品对应图片信息
     * @param $data 图片信息
     * @param listingId
     */
    public function cVarianImages($data,$thumbs,$listingId,$sku,$accountId,$atrName,$atrVal)
    {
        $res = (new EbayListing())->field("listing_status")->where(['id'=>$listingId])->find();
        #$acInfo = Cache::store('account')->ebayAccount($accountId);
        $acInfo = Cache::store('EbayAccount')->getTableRecord($accountId);
        if($res['listing_status']<2 || $res['listing_status']==4){#未提交
            #图片去重
            $nImgs=[];
            $imgTemp=[];
            $insertImgs=[];
            $updateImgs=[];
            $delIds = [];
            $i=0;
            foreach($data as $kimg => $da){
                if(!in_array($da['path'],$imgTemp)){
                    $imgTemp[$i]=$da['path'];
                    $nImgs[$i++]=$da;
                }
            }

            $oImgs = (new EbayListingImage())->where(['listing_id'=>$listingId,'value'=>$atrVal])->select();
            foreach($oImgs as $komg => $vomg){
                if(in_array($vomg['path'],$imgTemp)){#已存在,更新
                    $sort = array_search($vomg['path'],$imgTemp);
                    $updateImgTemp = $vomg->toArray();
                    $updateImgTemp['sort'] = $sort;
                    $updateImgTemp['main_de'] = 1;
                    $updateImgTemp['name'] = $atrName;
                    $updateImgTemp['value'] = $atrVal;
                    $updateImgTemp['sku'] = $sku;
                    $updateImgs[]=$updateImgTemp;
                    unset($nImgs[$sort]);
                    unset($imgTemp[$sort]);
                }else{#不存在,删除
                    if($vomg['main']==0 && $vomg['main_de']==1 && $vomg['value']==$atrVal){
                        $delIds[]=$vomg['id'];
                    }
                }
            }
            #var_dump($updateImgs);
            #var_dump($delIds);
            if($updateImgs){#更新
                (new EbayListingImage())->saveAll($updateImgs);
            }
            if($delIds){#删除
                (new EbayListingImage())->where(['id'=>['in',$delIds]])->delete();
            }
            #新增
            foreach($nImgs as $k => $v){
                $insertImg['listing_id'] = $listingId;
                $insertImg['sku'] = $sku;
                if(stripos($v['path'],'i.ebayimg.com')){
                    $insertImg['eps_path'] = $v['path'];
                    $insertImg['status'] = 3;
                }else{
                    $insertImg['ser_path'] = GoodsImage::getThumbPath($v['path'],0,0,$acInfo['code'],true);
                }

                $insertImg['path'] = $v['path'];#相对路径
                $insertImg['thumb'] = $v['path'];#绝对路径
                $insertImg['base_url'] = $v['base_url'];#图片服务器前缀地址
                $insertImg['main_de'] = 1;
                $insertImg['name'] = $atrName;
                $insertImg['value'] = $atrVal;
                $insertImg['sort'] = $k;
                (new EbayListingImage())->insertGetId($insertImg);
            }
        }
    }

    public function updateListingCommonMod(array $data)
    {
        try {
            $newVal = [];
            $oldVal = [];
            $field = 'id,title,mod_style,mod_sale,country,site,location,paypal_emailaddress,item_id,listing_sku,account_id';
            $oList = EbayListing::field($field)->where('item_id',$data['item_id'])->find();
            if (empty($oList)) {
                throw new Exception('获取listing信息失败');
            }
            $fieldSet = 'description,international_shipping,shipping,exclude_location,postal_code';
            $oSet = EbayListingSetting::field($fieldSet)->where('id',$oList['id'])->find();
            $mongoSet = ELSMongo::get(['id'=>(int)$oList['id']]);
            if ($mongoSet) {
                $oSet['description'] = $mongoSet['description'];
            } else {
                (new UniqueQueuer(EbaySettingMove2Mongo::class))->push($oList['id']);
            }
            if (empty($oSet)) {
                throw new Exception('获取listing 设置信息失败');
            }
            //风格，销售说明
            if (!empty($data['mod_style']) || !empty($data['mod_sale'])) {
                $newVal['style']['title'] = $oList['title'];
                $newVal['style']['description'] = $oSet['description'];
                $newVal['style']['mod_style'] = empty($data['mod_style']) ? $oList['mod_style'] : $data['mod_style'];
                $newVal['style']['mod_sale'] = empty($data['mod_sale']) ? $oList['mod_sale'] : $data['mod_sale'];
                $res = $this->publishHelper->listingImgVersionO2N($oList['id']);
                if ($res['result'] === false) {
                    throw new Exception($res['message']);
                }
                $imgs = $res['data'];
                unset($imgs['sku']);
                $newVal['style']['imgs'] = $imgs;
                $oldVal['style']['mod_style'] = $oList['mod_style'];
                $oldVal['style']['mod_sale'] = $oList['mod_sale'];
            }
            //物流，不运送地区
            if (!empty($data['mod_trans']) || !empty($data['mod_exclude'])) {
                !empty($data['mod_trans']) && $trans = $this->helper->parseTransCommon($data['mod_trans']);
                $newVal['international_shipping'] = isset($trans['internationalShipping']) ? $trans['internationalShipping']
                    : json_decode($oSet['international_shipping'], true);
                $newVal['shipping'] = isset($trans['shipping']) ? $trans['shipping'] : json_decode($oSet['shipping'], true);
                !empty($data['mod_exclude']) && $exclude = $this->helper->parseExcludeCommon($data['mod_exclude']);
                $newVal['exclude_location'] = isset($exclude['exclude']) ? json_decode($exclude['exclude'], true) : json_decode($oSet['exclude_location'], true);

            }

            //商品所在地
            if (!empty($data['mod_location'])) {
                $location = $this->helper->parseLocationCommon($data['mod_location']);
                $newVal['country'] = $location['country'];
                $newVal['location'] = $location['location'];
                $newVal['postal_code'] = $location['post_code'];
                $oldVal['country'] = $oList['country'];
                $oldVal['location'] = $oList['location'];
                $oldVal['postal_code'] = $oSet['postal_code'];
            }

            //退货政策
            if (!empty($data['mod_return'])) {
                $return = $this->helper->parseReturnCommon($data['mod_return']);
                $newVal['return'] = $return;
            }
            //买家限制
            if (!empty($data['mod_refuse'])) {
                $refuse = $this->helper->parseRefuseCommon($data['mod_refuse']);
                $newVal['buyer_requirement'] = $refuse['detail'];
                $newVal['disable_buyer'] = $refuse['refuse'];
            }
            //收款
            if (!empty($data['mod_receivables'])) {
                $receivables = $this->helper->parseReceivablesCommon($data['mod_receivables']);
                $newVal['paypal'] = $oList['paypal_emailaddress'];
                $newVal['payMethod'] = json_decode($receivables['pay_method'], true);
                $newVal['payInstructions'] = $receivables['payment_instructions'];
            }
            //备货时间
            if (!empty($data['mod_choice'])) {
                $choice = $this->helper->parseChoiceCommon($data['mod_choice']);
                $newVal['dispatch_max_time'] = $choice['choice_date'];
            }
            //自提
            if (!empty($data['mod_pickup'])) {
                $pickup = $this->helper->parsePickupCommon($data['mod_pickup']);
                $newVal['pick_up'] = $pickup['local_pickup'];
            }
            //gallery
            if (!empty($data['mod_galley'])) {
                $gallery = $this->helper->parseGalleryCommon($data['mod_galley']);
                $newVal['picture_gallery'] = $gallery['picture_gallery'];

            }
            //议价
            if (!empty($data['mod_bargaining'])) {
                $bargaining = $this->helper->parseBargainingCommon($data['mod_bargaining']);
                $newVal['best_offer'] = $bargaining['best_offer'];
                $newVal['auto_accept_price'] = $bargaining['accept_lowest_price'];
                $newVal['minimum_accept_price'] = $bargaining['reject_lowest_price'];
            }
            //可售量
            if (!empty($data['mod_quantity'])) {
                $quantity = $this->helper->parseQuantityCommon($data['mod_quantity']);
                $newVal['quantity'] = $quantity['quantity'];
            }
            if (!empty($newVal)) {
                $acLog['item_id'] = $oList['item_id'];
                $acLog['listing_sku'] = $oList['listing_sku'];
                $acLog['api_type'] = 3;
                $acLog['site'] = $oList['site'];
                $acLog['old_val'] = json_encode($oldVal);
                $acLog['new_val'] = json_encode($newVal);
                $acLog['account_id'] = $oList['account_id'];
                $acLog['cron_time'] = strtotime($data['cron_time']) ?: 0;
                $acLog['remark'] = $data['remark'];
                $this->insertUpdata($acLog);
            }
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
    /**
     * @param $data
     * @throws Exception
     */
    public function updateListing($data)
    {
        $newVal = [];
        $oldVal = [];
        try {
            $list = $data['list'];
            $set = $data['set'];
            $detailImgs = $data['detail_imgs'];
            $imgs = $data['imgs'];

            EbayPublish::optionImgHost($imgs,'del');
            EbayPublish::optionImgHost($detailImgs,'del');

            $oList = (new EbayListing())->field(true)->where(['id'=>$list['id']])->find();
            $oSet = (new EbayListingSetting())->field(true)->where(['id'=>$list['id']])->find();
            $mongoSet = ELSMongo::get(['id'=>(int)$list['id']]);
            if ($mongoSet) {
                $oSet['description'] = $mongoSet['description'];
            } else {
                (new UniqueQueuer(EbaySettingMove2Mongo::class))->push($list['id']);
            }

            $res = $this->publishHelper->listingImgVersionO2N($oList['id']);
            if ($res['result'] === false) {
                throw new Exception($res['message']);
            }
            $oImgs = $res['data'];
            $oImgs = EbayPublish::seperateImgs($oImgs);

            $accountCode = EbayAccount::where('id',$list['account_id'])->value('code');
            $imgs = ['publishImgs'=>$imgs,'detailImgs'=>$detailImgs];
            $res = EbayPublish::formatImgs($imgs,$accountCode,$list['id']);
            if ($res['result']===false) {
                throw new Exception($res['message']);
            }

            $nImgs = $res['data']['update'];//所有新图片
            $imgs = EbayPublish::seperateImgs($nImgs);

            //刊登图
            $publishImgChangeFlag = 0;
            if (json_encode($imgs['publishImgs']) != json_encode($oImgs['publishImgs'])) {
                $newVal['imgs'] = $nImgs;
                $newVal['publishImgs'] = 1;
                $publishImgChangeFlag = 1;
            }

            //标题，描述，描述图，风格，销售说明
            $desc['title'] = $list['title'];
            $desc['description'] = $set['description'];
            $desc['mod_style'] = $list['mod_style'];
            $desc['mod_sale'] = $list['mod_sale'];
            $desc['detail_imgs'] = $imgs['detailImgs'];

            $oldDesc['title'] = $oList['title'];
            $oldDesc['description'] = $oSet['description'];
            $oldDesc['mod_style'] = $oList['mod_style'];
            $oldDesc['mod_sale'] = $oList['mod_sale'];
            $oldDesc['detail_imgs'] = $oImgs['detailImgs'];

            $styleDetail = EbayModelStyle::where('id',$list['mod_style'])->value('style_detail');
            if ((empty($styleDetail) || $oList['application'] != 1) && $desc['description']!=$oldDesc['description']){//非erp刊登
//                $newVal['style']['description'] = $desc['description'];
//                $oldVal['style']['description'] = $oldDesc['description'];
//                $newVal['style']['application'] = 0;
            } else {
                if (json_encode($desc) != json_encode($oldDesc) || ($publishImgChangeFlag && strpos('[IMG', $styleDetail) !== false)) {
                    unset($desc['detail_imgs']);
                    unset($oldDesc['detail_imgs']);
                    $desc['imgs'] = $nImgs;
                    $oldDesc['imgs'] = $oImgs;
                    $newVal['style'] = $desc;
                    $oldVal['style'] = $oldDesc;
                }
            }

            //副标题
            if ($list['sub_title'] != $oList['sub_title']) {
                $newVal['subTitle'] = $list['sub_title'];
                $oldVal['subTitle'] = $oList['sub_title'];
            }

            //分类属性
            $newSpecifics = [];
            foreach ($set['specifics'] as $k => $specific) {
                $newSpecifics[$k]['attr_name'] = $specific['attr_name'];
                $newSpecifics[$k]['attr_value'] = $specific['attr_value'];
            }
            $oldSpecifics = [];
            foreach (json_decode($oSet['specifics'], true) as $k => $specific) {
                $oldSpecifics[$k]['attr_name'] = $specific['attr_name'];
                $oldSpecifics[$k]['attr_value'] = $specific['attr_value'];
            }
            if (json_encode($newSpecifics) != json_encode($oldSpecifics)) {
                $newVal['specifics'] = $newSpecifics;
                $oldVal['specifics'] = $oldSpecifics;
            }


            //单属性 价格,库存
            if ($list['variation'] == 0) {
                $price = number_format(floatval($list['start_price']), 2);
                $oldPrice = number_format(floatval($oList['start_price']), 2);
                if ($price != $oldPrice) {
                    $newVal['start_price'] = $price;
                    $oldVal['start_price'] = $oldPrice;
                }
                if ($oList['listing_type'] == 2) {//拍卖
                    $reservePrice = number_format(floatval($list['reserve_price']), 2);
                    $oldReservePrice = number_format(floatval($oList['reserve_price']), 2);
                    if ($reservePrice != $oldReservePrice) {
                        $newVal['reserve_price'] = $reservePrice;
                        $oldVal['reserve_price'] = $oldReservePrice;
                    }

                    $buyItNowPrice = number_format(floatval($list['buy_it_nowprice']), 2);
                    $oldBuyItNowPrice = number_format(floatval($oList['buy_it_nowprice']), 2);
                    if ($buyItNowPrice != $oldBuyItNowPrice) {
                        $newVal['buy_it_nowprice'] = $buyItNowPrice;
                        $oldVal['buy_it_nowprice'] = $oldBuyItNowPrice;
                    }
                }
                $quantity = intval($list['quantity']);
                $oldQuantity = intval($oList['quantity']);
                if ($quantity != $oldQuantity) {
                    $newVal['quantity'] = $quantity;
                    $oldVal['quantity'] = $oldQuantity;
                }
//                if (empty($list['listing_sku'])) {//如果为空，重新生成，并写入产品映射表
//                    $map['sku_code'] = $list['local_sku'];
//                    $map['account_id'] = $list['account_id'];
//                    $map['combine_sku'] = $list['sku'];
//                    $newVal['listing_sku'] = (new GoodsSkuMapService())->createSku($list['local_sku']);
//                    $map['channel_sku'] = $newVal['listing_sku'];
//                    $this->publishHelper->maintainTableGoodsSkuMap($map, $this->userId, $list['assoc_order'] ? false : true);
//                    $oldVal['listing_sku'] = '';
//                }
            }
            //国家
            if ($list['country'] != $oList['country']) {
                $newVal['country'] = $list['country'];
                $oldVal['country'] = $oList['country'];
            }
            //location
            if ($list['location'] != $oList['location']) {
                $newVal['location'] = $list['location'];
                $oldVal['location'] = $oList['location'];
            }
            //邮编
            if ($set['postal_code'] != $oSet['postal_code']) {
                $newVal['postal_code'] = $set['postal_code'];
                $oldVal['postal_code'] = $oSet['postal_code'];
            }
            //退货
            $return = [
                'return_description' => $set['return_description'],
                'return_type' => $set['return_type'],
                'return_policy' => $set['return_policy'],
                'return_time' => $list['return_time'],
                'return_shipping_option' => $set['return_shipping_option']
            ];
            $oldReturn = [
                'return_description' => $oSet['return_description'],
                'return_type' => $oSet['return_type'],
                'return_policy' => $oSet['return_policy'],
                'return_time' => $oList['return_time'],
                'return_shipping_option' => $oSet['return_shipping_option']
            ];
            if (json_encode($return) != json_encode($oldReturn)) {
                $newVal['return'] = $return;
                $oldVal['return'] = $oldReturn;
            }
            //买家限制
            if ($list['disable_buyer'] != $oList['disable_buyer']) {
                $newVal['disable_buyer'] = $list['disable_buyer'];
                $oldVal['disable_buyer'] = $oList['disable_buyer'];
            }
            if ($list['disable_buyer']) {
                $buyerRequirementDetails = isset($set['buyer_requirment_details'][0]) ? $set['buyer_requirment_details'][0] : $set['buyer_requirment_details'];
            } else {
                $buyerRequirementDetails = [];
            }
            $oldBuyerRequirementDetails = json_decode($oSet['buyer_requirment_details'], true)??[];
            $oldBuyerRequirementDetails = isset($oldBuyerRequirementDetails[0]) ? $oldBuyerRequirementDetails[0] : $oldBuyerRequirementDetails;
            ksort($buyerRequirementDetails);
            ksort($oldBuyerRequirementDetails);
            if ($buyerRequirementDetails != $oldBuyerRequirementDetails) {
                $newVal['buyer_requirement'] = $buyerRequirementDetails;
                $oldVal['buyer_requirement'] = $oldBuyerRequirementDetails;
            }
            //备货时间
            if ($list['dispatch_max_time'] != $oList['dispatch_max_time']) {
                $newVal['dispatch_max_time'] = $list['dispatch_max_time'];
                $oldVal['dispatch_max_time'] = $oList['dispatch_max_time'];
            }
            //自提
            if ($set['local_pickup'] != $oSet['local_pickup']) {
                $newVal['local_pickup'] = $set['local_pickup'];
                $oldVal['local_pickup'] = $oSet['local_pickup'];
            }
            //议价
            if ($list['best_offer'] != $oList['best_offer']) {
                $newVal['best_offer'] = $list['best_offer'];
                $oldVal['best_offer'] = $oList['best_offer'];
            }
            $autoAcceptPrice = number_format(floatval($set['auto_accept_price']), 2);
            $oldAutoAcceptPrice = number_format(floatval($oSet['auto_accept_price']), 2);
            if ($autoAcceptPrice != $oldAutoAcceptPrice) {
                $newVal['auto_accept_price'] = $autoAcceptPrice;
                $oldVal['auto_accept_price'] = $oldAutoAcceptPrice;
            }
            $minimumAcceptPrice = number_format(floatval($set['minimum_accept_price']), 2);
            $oldMinimumAcceptPrice = number_format(floatval($oSet['minimum_accept_price']), 2);
            if ($minimumAcceptPrice != $oldMinimumAcceptPrice) {
                $newVal['minimum_accept_price'] = $minimumAcceptPrice;
                $oldVal['minimum_accept_price'] = $oldMinimumAcceptPrice;
            }



            //物流 不运送地区
            //国际物流
            $interantionalShippings = $set['international_shipping'];
            $oldInterantionalShippings = json_decode($oSet['international_shipping'], true);
            $interantionalShipping = [];
            foreach ($interantionalShippings as $k => $v) {
                ksort($v);//排序
                $interantionalShipping[] = $v;
            }
            $oldInterantionalShipping = [];
            foreach ($oldInterantionalShippings as $k => $v) {
                ksort($v);
                $oldInterantionalShipping[] = $v;
            }

            //国内物流
            $shippings = $set['shipping'];
            $oldShipping = json_decode($oSet['shipping'], true);
            $shipping = [];
            foreach ($shippings as $k => $v) {
                ksort($v);
                $shipping[$k] = $v;
            }
            foreach ($oldShipping as $k => &$v) {
                ksort($v);
                $oldShipping[$k] = $v;
            }
            //不送达地区 $set['exclude_location']是以中文 ，分割的字符串
            $excludeLocation = explode('，',$set['exclude_location']);
            $oldExcludeLocation = json_decode($oSet['exclude_location'], true);
            empty($excludeLocation) && $excludeLocation = [];
            empty($oldExcludeLocation) && $oldExcludeLocation = [];
            if (json_encode($interantionalShipping) != json_encode($oldInterantionalShipping)
                || json_encode($shipping) != json_encode($oldShipping)
                || json_encode($excludeLocation) != json_encode($oldExcludeLocation)) {
                $newVal['international_shipping'] = $interantionalShipping;
                $newVal['shipping'] = $shipping;
                $newVal['exclude_location'] = $excludeLocation;
                $oldVal['international_shipping'] = $oldInterantionalShipping;
                $oldVal['shipping'] = $oldShipping;
                $oldVal['exclude_location'] = $oldExcludeLocation;
            }

            //店铺分类
            $storeCategoryId = $list['store_category_id'];
            $storeCategory2Id = $list['store_category2_id'];
            $oldStoreCategoryId = $oList['store_category_id'];
            $oldStoreCategory2Id = $oList['store_category2_id'];
            if ($storeCategoryId != $oldStoreCategoryId) {
                $newVal['storeCategoryId'] = $storeCategoryId;
                $oldVal['storeCategoryId'] = $oldStoreCategoryId;
            }
            if ($storeCategory2Id != $oldStoreCategory2Id) {
                $newVal['storeCategory2Id'] = $storeCategory2Id;
                $oldVal['storeCategory2Id'] = $oldStoreCategory2Id;
            }
            //订单关联
            $assocOrderChange = 0;
            if ($list['assoc_order'] != $oList['assoc_order']) {
                if ($list['variation'] == 0) {//单属性
                    $this->publishHelper->maintainTableGoodsSkuMap($list,$list['account_id'],$this->userId,$list['assoc_order']?false:true);
                    if (strpos('ebay',$list['listing_sku']) && !$list['assoc_order']) {
                        $newVal['listing_sku'] = 'ebay' . $list['listing_sku'];
                        $oldVal['listing_sku'] = $oList['listing_sku'];
                    }
                }
                EbayListing::update(['assoc_order'=>$list['assoc_order']],['id'=>$list['id']]);//直接写入
                $assocOrderChange = 1;
            }
            if ($list['is_virtual_send'] != $oList['is_virtual_send']) {
                $newVal['is_virtual_send'] = $list['is_virtual_send'];
            }

            //付款选项
            $paypal = $list['paypal_emailaddress'];
            $oldPaypal = Ebaylisting::where(['id'=>$list['id']])->value('paypal_emailaddress');
            $payMethod = $set['payment_method'];
            $oldPayMethod = json_decode(EbayListingSetting::where(['id'=>$list['id']])->value('payment_method'), true);
            $payInstructions = $set['payment_instructions'];
            $oldPayInstructions = EbayListingSetting::where(['id'=>$list['id']])->value('payment_instructions');
            if ($paypal != $oldPaypal || json_encode($payMethod) != json_encode($oldPayMethod) || $payInstructions != $oldPayInstructions) {
                $newVal['paypal'] = $paypal;
                $newVal['payMethod'] = $payMethod;
                $newVal['payInstructions'] = $payInstructions;
                $oldVal['paypal'] = $oldPaypal;
                $oldVal['payMethod'] = $oldPayMethod;
                $oldVal['payInstructions'] = $oldPayInstructions;
            }
            //立即付款
            if ($list['autopay'] != $oList['autopay']) {
                $newVal['autopay'] = $list['autopay'];
                $oldVal['autopay'] = $oList['autopay'];
            }

            //多属性
//            if ($list['variation']) {
//                $field = 'id,v_sku,v_price,v_qty,variation,path,combine_sku,channel_map_code';
//                $oVars = EbayListingVariation::where('listing_id', $list['id'])->column($field,'id');
//                $vars = $data['varians'];
//                $reserveVarIds = [];
//                $skuImgs = [];
//                $oVarNameList = [];
//                $nVarNameList = [];
//                foreach ($vars as &$var) {
//                    $tmpVariation = $var['variation'];
//                    $value = $tmpVariation[$set['variation_image']];
//                    $skuImgs = array_merge_recursive($skuImgs, [$value => $var['path']]);
//                    if (isset($var['id'])) {//已存在的变体
//                        if ($assocOrderChange || $var['combine_sku']!=$oVars[$var['id']]['combine_sku']) {//订单关联有改变或捆绑有改变需要维护映射表
//                            $this->publishHelper->maintainTableGoodsSkuMap($var, $list['acccount_id'], $this->userId, $list['assoc_order'] ? false : true);
//                            if (strpos('ebay', $var['channel_map_code']) && !$list['assoc_order']) {
//                                $var['channel_map_code'] = 'ebay' . $var['channel_map_code'];
//                            }
//                        }
////                        empty($oVarNameList) && $oVarNameList = array_keys(json_decode($oVars[$var['id']]['variation'],true));
////                        empty($nVarNameList) && $nVarNameList = array_keys($var['variation']);
//                        $reserveVarIds[] = $var['id'];
//                    } else {//新增的
//                        $tmpSku = (new GoodsSkuMapService())->createSku($var['v_sku']);
//                        $var['channel_map_code'] = $tmpSku;
//                        if ($list['assoc_order']) {
//                            $this->publishHelper->maintainTableGoodsSkuMap($var, $list['acccount_id'], $this->userId, $list['assoc_order'] ? false : true);
//                        } else {
//                            $var['channel_map_code'] = 'ebay' . $tmpSku;
//                        }
//                    }
//                }
//
//                $delVars = [];
//                foreach ($oVars as $id => $oVar) {
//                    if (!in_array($id, $reserveVarIds)) {
//                        $delVars[] = $oVar;
//                    }
//                }
//                if (!empty($delVars)) {
//                    $newVal['delVars'] = $delVars;
//                }
//                $newVal['variants'] = $vars;
//                $newVal['variation_image'] = $set['variation_image'];
//                //对比变体图片是否有改变
//                EbayPublish::optionImgHost($skuImgs,'del');
//                $nImgs['skuImgs'] = $skuImgs;
//                $newVal['imgs'] = $nImgs;
//                isset($newVal['style']['imgs']) && $newVal['style']['imgs'] = $nImgs;
//            }
            if (!empty($newVal)) {
                $acLog['item_id'] = $list['item_id'];
                $acLog['listing_sku'] = $list['listing_sku'];
                $acLog['api_type'] = 2;
                $acLog['site'] = $list['site'];
                $acLog['old_val'] = json_encode($oldVal);
                $acLog['new_val'] = json_encode($newVal);
                $acLog['account_id'] = $list['account_id'];
                $acLog['cron_time'] = strtotime($list['timing'])?strtotime($list['timing']):0;
                $acLog['remark'] = "";
                $this->insertUpdata($acLog);
            }
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @title listing管理列表
     * @param array $map
     * @return array
     * @throws Exception
     */
    public function getListings(array $map) : array
    {
        try {
            $wh = $map['wh'];
//            $whOr = $map['whOr'];
            $order = $map['order'];
            $start = $map['start'];
            $size = $map['size'];
            $published = $map['published'];
//            $subWhor = [];
//            if ($whOr) {//多属性
//                $listingIdsArr = EbayListingVariation::distinct(true)->where($whOr)->column('listing_id');
//                $subWh = $wh;
//                $subWh['id'] = ['in', $listingIdsArr];
//                $ids = (new EbayListing())->alias('l')->where($subWh)->column('id');
//                $subWhor['id'] = ['in', $ids];
//            }

            if (isset($wh['g.sales_status'])) {
                $rows = (new EbayListing())->alias('l')->field('l.*, g.sales_status')
                    ->join('goods g', 'g.id = l.goods_id', 'LEFT')
                    ->where($wh)->order($order)->limit($start, $size)->select();
                $count = (new EbayListing())->alias('l')->field('l.*, g.sales_status')
                    ->join('goods g', 'g.id = l.goods_id', 'LEFT')
                    ->where($wh)->count();
            } else {
                $ebayModel = new EbayListing();
                $rows = $ebayModel->alias('l')
                    ->field(true)->where($wh)->order($order)
                    ->limit($start, $size)->select();
                $count = (new EbayListing())->alias('l')->field(true)->where($wh)->count();
            }

            $publishedDrafts = [];
            $unpublishedDrafts = [];
            foreach ($rows as $k0=>$row) {
                $varArr = $row->variant;//使用的关联
                $price = [];
                foreach ($varArr as $k1=>$var) {
                    $varNameValue = json_decode($var['variation'], true);
                    $varArr[$k1]['variation'] = $varNameValue;
                    $price[] = $var['v_price'];
                }
//                $rows[$k0]['varians'] = $varArr;
                $rows[$k0]['v_varkey'] = $varArr ? array_keys($varArr[0]['variation']) : [];
                if (!empty($price)) {
                    $rows[$k0]['rval_price'] = (min($price) == max($price))?min($price):min($price).'-'.max($price);
                    unset($price);
                }
                $accountInfo = Cache::store('EbayAccount')->getTableRecord($row['account_id']);
                $rows[$k0]['account_code'] = empty($accountInfo['code']) ? '共享范本' : $accountInfo['code'];
                $siteInfo = Cache::store('EbaySite')->getSiteInfoBySiteId($row['site']);
                $rows[$k0]['symbol'] = $siteInfo['symbol'];
                if ($wh['l.draft'] == 0) {
                    $timing = $row['timing'] + $siteInfo['time_zone'];
                    $rows[$k0]['site_timing'] = $row['timing'] == 0 ? 0 : $timing;
                    $setting = $row->setting;
                    $rows[$k0]['message'] = $setting['message'];
                    $row->timingInfo && $rows[$k0]['timing_rule_name'] = $row->timingInfo['timing_rule_name'];
                } else {
                    $map = [
                        'goods_id' => $row['goods_id'],
                        'site' => $row['site'],
                        'account_id' => $row['account_id'],
                        'title' => $row['title'],
                        'draft' => 0
                    ];
                    $map['listing_status'] = ['in',[3,5,6,7]];
                    $rows[$k0]['draft_count'] = (new EbayListing())->where($map)->count();

                    $map['listing_status'] = 1;
                    $rows[$k0]['timing_count'] = (new EbayListing())->where($map)->count();
                    if($rows[$k0]['shared_userid']==0){
                        $rows[$k0]['share_type'] = 0;//未共享
                    }else if($rows[$k0]['shared_userid'] == $this->userId){
                        $rows[$k0]['share_type'] = 1;//自己共享
                    }else{
                        $rows[$k0]['share_type'] = 2;//他人共享
                    }
                    $goodsInfo = Cache::store('Goods')->getGoodsInfo($row['goods_id']);
                    $goodsInfo && $rows[$k0]['sales_status'] = $goodsInfo['sales_status'];
                    $rows[$k0]['update_realname'] = User::where(['id' => $row['user_id']])->value('realname');
                    $createuser = User::where(['id' => $row['realname']])->value('realname');
                    $rows[$k0]['realname'] = $createuser ? $createuser : '--';
                    if ($published == 1 && $rows[$k0]['draft_count'] < 1) {
                        $unpublishedDrafts[] = $rows[$k0];
                    } else if ($published == 0 && $rows[$k0]['draft_count'] > 0) {
                        $publishedDrafts[] = $rows[$k0];
                    }
                }
            }
            if ($wh['l.draft'] == 1) {
                if ($published == 1) {
                    $rows = $unpublishedDrafts;
                    $count = count($unpublishedDrafts);
                } else if ($published == 0) {
                    $rows = $publishedDrafts;
                    $count = count($publishedDrafts);
                }
            }
            $desc = strpos($order, 'desc');
            if (strpos($order, 'start_price') !== false && is_array($rows)) {
                usort($rows, function($a, $b) use ($order, $desc) {
                    $num1 = isset($a['rval_price']) ? floatval($a['rval_price']) : floatval($a['start_price']);
                    $num2 = isset($b['rval_price']) ? floatval($b['rval_price']) : floatval($b['start_price']);
                    if ($num1 == $num2) return 0;
                    if ($desc !== false) {//降序排列
                        return ($num1 < $num2 ? 1 : -1);
                    } else {
                        return ($num1 < $num2 ? -1 : 1);
                    }
                });
            }
            $res['rows'] = array_values($rows);
            $res['count'] = $count;
            return $res;
        } catch (Exception $e) {
            throw new Exception($e->getFile()."|".$e->getLine()."|".$e->getMessage());
        }
    }

    /**
     * 立即刊登时，先保存listing并写入缓存
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function saveAndSetCache(array $data)
    {
        try {
            $listingId = $this->saveListingPublish($data);
            $listInfo = (new EbayListing())->field(true)->where(['id'=>$listingId])->find();
            $cache['id'] = $listingId;
            $cahe['spu'] = $listInfo['spu'];
            $cahe['site'] = $listInfo['site'];
            $siteInfo = Cache::store('ebaySite')->getSiteInfoBySiteId($listInfo['site']);
            $cahe['symbol'] = $siteInfo['symbol'];
            $cahe['currency'] = $siteInfo['currency'];
            $cahce['site_code'] = $siteInfo['country'];
            $cahce['site_name'] = $siteInfo['name'];
            $cache['account_id'] = $listInfo['account_id'];
            $accountInfo = Cache::store('EbayAccount')->getTableRecord($listInfo['account_id']);
            $cache['account_code'] = $accountInfo['code'];
            $cache['title'] = $listInfo['title'];
            $cache['insertion_fee'] = $listInfo['insertion_fee'];
            Cache::store('EbayListingReponseCache')->setReponseCache($listingId, $cache);
            return $cache;
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @title 查看立即刊登结果
     * @param $ids
     */
    public function publishImmediatelyResultsService($ids)
    {
        $reponseCache = new EbayListingReponseCache();#先查询缓存
        $mList = new EbayListing();
        $idsArr = explode(",",$ids);
        $rowsCache = [];
        $dbIds = [];
        foreach($idsArr as $k => $id){
            $cache = $reponseCache->getReponseCache($id);
            if($cache){
                $rowsCache[$k] = $cache;
            }else{
                $dbIds[] = $id;
            }
        }

        $rows = [];
        if($dbIds){
            $wh['id'] = ['in',$dbIds];
            $mList = new EbayListing();
            $rowsTempArr = $mList->field("id,listing_status,insertion_fee,listing_fee,item_id,spu,site,account_id,title")
                ->where($wh)->select();
            foreach($rowsTempArr as $k => $v){
                $rows[$k] = $v->toArray();
                $settingInfo = $v->setting;
                $siteInfo = Cache::store('ebaySite')->getSiteInfoBySiteId($v['site']);
                $rows[$k]['message'] = $settingInfo['message'];
                $rows[$k]['symbol'] = $siteInfo['symbol'];
                $rows[$k]['currency'] = $siteInfo['currency'];
                $rows[$k]['site_code'] = $siteInfo['country'];
                $rows[$k]['site_name'] = $siteInfo['name'];
                $acInfo = Cache::store('EbayAccount')->getTableRecord($v['account_id']);
                $rows[$k]['account_code'] = $acInfo['code'];
            }
        }
        return array_merge($rowsCache,$rows);
    }

    /**
     * @param int $id
     * @throws Exception
     */
    public function publishImmediately(int $id)
    {
        set_time_limit(0);
        try {
            $list = EbayListing::get($id);
            if (empty($list)) {
                sleep(2);//等待2s,避免因为数据库同步延迟的原因导致获取信息失败
                $list = EbayListing::get($id);
                if (empty($list)) {
                    throw new Exception('获取listing信息失败');
                }
            }
            EbayPublish::updateListingStatusWithErrMsg('publishing',$list['id']);

            $list = $list->toArray();
            $verb = $list['listing_type'] == 2 ? 'AddItem' : 'AddFixedPriceItem';

            $accountInfo = EbayAccount::get($list['account_id']);
            if (empty($accountInfo)) {
                EbayPublish::updateListingStatusWithErrMsg('publishFail',$list['id'],[],'账号信息获取失败');
                throw new Exception('获取账号信息失败');
            }
            $accountInfo = $accountInfo->toArray();

            $ebayApi = new EbayPackApi();

            try {
                $ebayApi->uploadImgToEPS($list['id'], $accountInfo);
            } catch (Exception $e) {
                EbayPublish::updateListingStatusWithErrMsg('publishFail',$list['id'],[],$e->getMessage());
                throw new Exception('图片上传失败');
            }

            $listInfo = $this->publishHelper->getListInfo($id);

            $ebayDealRes = new EbayDealApiInformation();
            $apiObj = $ebayApi->createApi($accountInfo, $verb, $listInfo['list']['site']);
            $requestBody = $ebayApi->createXml($listInfo);
            $resText = $apiObj->createHeaders()->__set('requesBody', $requestBody)->sendHttpRequest2();
            if (empty($resText)) {
                EbayPublish::updateListingStatusWithErrMsg('publishFail',$list['id'],[],'curl返回为空');
                throw new Exception('curl返回为空');
            }
            $res = $ebayDealRes->dealWithApiResponse($verb, $resText,$listInfo);

            $up = $res['updateList'];
            $upSet = $res['updateSet'];
            //更新缓存
            $reponseCache = new EbayListingReponseCache();
            $cache = $reponseCache->getReponseCache($id);
            empty($cache) && $cache = [];
            $cache['listing_status'] = isset($up['listing_status'])?$up['listing_status']:0;
            $cache['item_id'] = isset($up['item_id'])?$up['item_id']:0;
            $cache['insertion_fee'] = isset($up['insertion_fee'])?$up['insertion_fee']:0;
            $cache['listing_fee'] = isset($up['listing_fee'])?$up['listing_fee']:0;
            $cache['message'] = isset($upSet['message'])?$upSet['message']:"";
            $cache['id'] = $id;
            $cache['result'] = true;
            $reponseCache->setReponseCache($id,$cache);
            (new EbayListing())->where(['id'=>$id])->update($up);
            (new EbayListingSetting())->where(['id'=>$id])->update($upSet);
        } catch(Exception $e) {
            $cache['id'] = $id;
            $cache['result'] = true;
//            $cache['listing_status'] = 4;
            $cache['message'] = $e->getMessage();
            (new EbayListingReponseCache())->setReponseCache($id, $cache);
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        } catch (\Exception $e) {
//            $cache['listing_status'] = 4;
            $cache['message'] = $e->getMessage();
            (new EbayListingReponseCache())->setReponseCache($id, $cache);
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @title Draft管理列表
     * @param $data #draft管理列表
     * @param DraftDetail
     */
    public function getDrafts($wh,$whOr,$start,$size,$order=[],$userId)
    {
        $mList = new EbayListing();
        $modImg = new EbayListingImage();
        $wh['draft'] = ['eq',1];
        $mVarion = new EbayListingVariation();
        if(isset($wh['g.sales_status'])){
            if($whOr){
                $listingIdsArr = $mVarion->alias("va")->distinct("listing_id")
                    ->where($whOr)->select();
                $listingIds = [];
                foreach($listingIdsArr as $listingId){
                    $listingIds[] = $listingId['listing_id'];
                }
                $rows = $mList->alias("l")
                    ->field("l.*,g.sales_status,u.realname as update_realname")
                    ->join("goods g","g.id = l.goods_id","LEFT")
                    ->join('user u','u.id=l.user_id','LEFT')
                    ->whereOr(function($query) use ($wh,$listingIds){
                        $query->where($wh)->whereOr(['l.id'=>['in',$listingIds]]);
                    })
                    ->order($order)->limit($start,$size)->select();
            }else{
                $rows = $mList->alias("l")
                    ->field("l.*,g.sales_status,u.realname as update_realname")
                    ->join("goods g","g.id = l.goods_id","LEFT")
                    ->join('user u','u.id=l.user_id','LEFT')
                    ->where($wh)->order($order)->limit($start,$size)->select();
            }
        }else{
            if($whOr){
                $listingIdsArr = $mVarion->alias("va")->distinct("listing_id")
                    ->where($whOr)->select();
                $listingIds = [];
                foreach($listingIdsArr as $listingId){
                    $listingIds[] = $listingId['listing_id'];
                }
                $rows = $mList->alias("l")
                    ->field("l.*,u.realname as update_realname")
                    ->join('user u','u.id=l.user_id','LEFT')
                    ->whereOr(function($query) use ($wh,$listingIds){
                        $query->where($wh)->whereOr(['l.id'=>['in',$listingIds]]);
                    })
                    ->order($order)->limit($start,$size)->select();
            }else{
                $rows = $mList->alias("l")
                    ->field("l.*,u.realname as update_realname")
                    ->join('user u','u.id=l.user_id','LEFT')
                    ->where($wh)->order($order)->limit($start,$size)->select();
            }
        }
        $price = [];
        foreach($rows as $k => $v){
            $var = $v->variant;
            $mainImg = $modImg->where(['listing_id'=>$v['id'],'main'=>1])
                ->order('sort')->find();
            if(trim($mainImg['path'])){
                if(stripos($mainImg['path'],'i.ebayimg.com')){
                    $rows[$k]['img'] = $mainImg['path'];
                }else{
                    $rows[$k]['img'] = "https://img.rondaful.com/".$mainImg['path'];
                }
            }else{
                $rows[$k]['img'] = $mainImg['eps_path'];
            }

            foreach($var as $kv => $vv){
                $tm1 = json_decode($vv['variation'],true);
                $tmArr = array();
                foreach($tm1 as $ktm1 => $vtm1){
                    $tmArr[$ktm1] = $vtm1;
                }
                $var[$kv]['variation'] = $tmArr;
                $price[] = $vv['v_price'];
            }
            $rows[$k]['varians'] = $var;
            if(!empty($price)){
                if(min($price)==max($price)){
                    $rows[$k]['rval_price'] = min($price);
                }else{
                    $rows[$k]['rval_price'] = min($price)."~".max($price);
                }
                unset($price);
            }
//            $rows[$k]['draft_count'] = (new EbayListing())->where(['draft_id'=>$v['id'],'listing_status'=>3])->field("id")->count();
            $map = [
                'account_id' => $vv['account_id'],
                'site' => $vv['site'],
                'listing_status' => ['in', [3,5,6,7]],
                'spu' => $vv['spu'],
                'draft' => 0
            ];
            $rows[$k]['draft_count'] = (new EbayListing())->where($map)->field("id")->count();
            $map['listing_status'] = 1;
            $rows[$k]['timing_count'] = (new EbayListing())->where($map)->field("id")->count();;
            $acInfo = Cache::store('EbayAccount')->getTableRecord($v['account_id']);
            $rows[$k]['account_code'] = $acInfo['code'];
            if($rows[$k]['shared_userid']==0){
                $rows[$k]['share_type'] = 0;//未共享
            }else if($rows[$k]['shared_userid']==$userId){
                $rows[$k]['share_type'] = 1;//自己共享
            }else{
                $rows[$k]['share_type'] = 2;//他人共享
            }
            $createuser = User::get($rows[$k]['realname']);
            $rows[$k]['realname'] = empty($createuser)?'--':$createuser->realname;//创建人真实姓名
            if($var){
                $varKey = array_keys($var[0]['variation']);
                $rows[$k]['v_varkey'] = $varKey;
            }else{
                $rows[$k]['v_varkey'] = [];
            }
            $goodsInfo = Cache::store('Goods')->getGoodsInfo($v['goods_id']);
            if($goodsInfo) $rows[$k]['sales_status'] = $goodsInfo['sales_status'];
            $siteInfo = Cache::store('ebaySite')->getSiteInfoBySiteId($v['site']);
            $rows[$k]['symbol'] = $siteInfo['symbol'];
        }

        #总记录数量
        $count = $mList->alias("l")
            ->where($wh)->count();
        $res['rows'] = $rows;
        $res['count'] = $count;
        return $res;
    }


    /**
     * @title listing获取单条记录
     * @param int $listingId
     * @return mixed
     * @throws Exception
     */
    public function getListingInfo(int $id) : array
    {
        try {
            $listingId = intval($id);
            //主表
            $list = (new EbayListing())->field(true)->where(['id'=>$listingId])->find();
            if(!$list){
                throw new Exception("编辑的数据不存在！");
            }
            $list['account_name'] = EbayAccount::where(['id'=>$list['account_id']])->value('code');
            $list['time_zone'] = EbaySite::where(['siteid'=>$list['site']])->value('time_zone');
            $list['primary_category_pahtname'] = $this->publishHelper->getEbayCategoryChain($list['primary_categoryid'], $list['site']);
            if (!empty($list['second_categoryid'])) {
                $list['second_category_name'] = $this->publishHelper->getEbayCategoryChain($list['second_categoryid'], $list['site']);
            }
            if (!empty($list['store_category_id'])) {
                $list['store_name'] = $this->publishHelper->getStoreCategoryChain($list['store_category_id'], $list['account_id']);
            }
            if (!empty($list['store_category2_id'])) {
                $list['second_store_name'] = $this->publishHelper->getStoreCategoryChain($list['store_category2_id'], $list['account_id']);
            }
            $row['list'] = $list;
            //setting表
            $mongoSet = ELSMongo::where(['id'=>(int)$listingId])->find();
            $set = (new EbayListingSetting())->field(true)->where(['id'=>$listingId])->find();
            if ($mongoSet) {
                $set['description'] = $mongoSet['description'];
            } else {
                (new UniqueQueuer(EbaySettingMove2Mongo::class))->push($listingId);
            }
            $tmpExcludeLocation = json_decode($set['exclude_location'], true);
            $set['exclude_location'] = empty($tmpExcludeLocation) ? '' : implode('，', $tmpExcludeLocation);
            $tmpShipLocation = json_decode($set['ship_location'], true) ?? [];
            $set['ship_location'] = is_array($tmpShipLocation) ? $tmpShipLocation : [$tmpShipLocation];
            $tmpInternationalShipping = json_decode($set['international_shipping'], true)??[];
            foreach ($tmpInternationalShipping as &$tis) {
                if (!is_array($tis['shiptolocation'])) {
                    $tis['shiptolocation'] = [$tis['shiptolocation']];
                }
            }
            $set['international_shipping'] = empty($tmpInternationalShipping) ? [] : $tmpInternationalShipping;

            $tmpShipping = json_decode($set['shipping'], true);
            $set['shipping'] = empty($tmpShipping) ? [] : $tmpShipping;
            $tmpPaymentMethod = json_decode($set['payment_method'], true);
            $set['payment_method'] = empty($tmpPaymentMethod) ? ['PayPal'] : $tmpPaymentMethod;
            $set['buyer_requirment_details'] = $this->publishHelper->buyerRequirementJsonToArray($set['buyer_requirment_details']);
            $set['compatibility'] = $this->publishHelper->compatibilityJsonToArray($set['compatibility']);
            $set['specifics'] = json_decode($set['specifics'], true);
            $row['set'] = $set;
            //图片
            $version = EbayListingImage::where('listing_id',$id)->value('spu');
            if ($version == 1) {
                $imgs = EbayListingImage::where('listing_id',$id)->find();
                $publishImgs = json_decode($imgs['path'],true)??[];
                $detailImgs = json_decode($imgs['thumb'],true)??[];
            } else {
                $publishImgs = EbayListingImage::where(['listing_id'=>$id,'main'=>1])->order('sort')->column('path');
                $detailImgs = EbayListingImage::where(['listing_id'=>$id,'detail'=>1])->order('sort')->column('path');
            }
            EbayPublish::optionImgHost($publishImgs,'add');
            EbayPublish::optionImgHost($detailImgs,'add');
            $row['imgs'] = $publishImgs;
            $row['detail_imgs'] = $detailImgs;

            //变体表
            $row['varians'] = [];
            if ($list['variation']) {
                $varians = (new EbayListingVariation())->where(['listing_id'=>$listingId])->select();
                if (!$varians) {
                    throw new Exception('id为'.$id.'的listing获取变体信息失败，请取消勾选此条后再操作');
                }
                $row['list']['v_varkey'] = array_keys(json_decode($varians[0]['variation'], true));
                foreach ($varians as $k => $varian) {
                    $path = json_decode($varian['path'], true) ?? [];
                    if (is_array($path)) {
                        foreach ($path as &$p) {
                            if (isset($p['base_url']) && isset($p['path']) && strpos($p['path'], 'http') === false) {
                                $p = $p['base_url'] . $p['path'];
                            } else {
                                if (is_array($p)) {
                                    $p = array_values($p)[0];
                                }
                                if (strpos($p, 'http') === false) {
                                    $p = 'https://img.rondaful.com/' . $p;
                                }
                            }
                        }
                    }
                    is_string($path) && $path = [$path];
                    $varians[$k]['path'] = empty($path) ? [] : $path;
                    $thumb = json_decode($varian['thumb'], true)??[];
                    foreach ($thumb as &$p) {
                        if (isset($p['base_url']) && isset($p['path']) && strpos($p['path'],'http') === false) {
                            $p = $p['base_url'].$p['path'];
                        } else {
                            if (is_array($p)) {
                                $p = array_values($p)[0];
                            }
                            if (strpos($p,'http') === false) {
                                $p = 'https://img.rondaful.com/' . $p;
                            }
                        }
                    }
                    $varians[$k]['thumb'] = empty($thumb) ? [] : $thumb;
                    $mapSku = json_decode($varian['map_sku'], true);
                    $varians[$k]['map_sku'] = empty($mapSku) ? [] : $mapSku;
                }
                $row['varians'] = $varians;
            }
            $row['mappingspec'] = (new EbayListingMappingSpecifics())->where(['listing_id'=>$listingId])->select();
            $goods = $this->publishHelper->getGoods($list['goods_id']);
            $row['goods_info'] = empty($goods['goodsInfo']) ? [] : $goods['goodsInfo'];
            $row['goodsSku'] = empty($goods['goodsSku']) ? [] : $goods['goodsSku'];
            $row['attrInfo'] = empty($goods['attrInfo']) ? [] : $goods['attrInfo'];
            $row['code'] = $list['account_name'];
            return $row;
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @title 获取子产品列表
     * @param $listingIds #删除单条listing记录
     */
    public function listVariations($listingIds){
        try {
            $ids = explode(",", $listingIds);
            $data = [];
            foreach ($ids as $k => $id) {
                $varians = (new EbayListingVariation())->field(true)->where(['listing_id' => $id])->select();
                $varKey = [];
                //获取变体图片
                foreach ($varians as $key => $varian) {
                    $varNV = $this->publishHelper->variationJsonToArray($varian['variation']);
                    if (empty($varKey)) {
                        $varKey = array_keys($varNV);
                    }
                    $path = json_decode($varian['path'], true) ?? [];
//                    foreach ($path as &$p) {
//                        if (isset($p['base_url']) && isset($p['path'])) {
//                            $p = $p['base_url'].$p['path'];
//                        }
//                    }
                    $varians['mainde_path'] = $path;
//                    $variationImage = EbayListingSetting::where(['id'=>$id])->value('variation_image');
//                    if (!empty($variationImage)) {
//                        $wh['listing_id'] = $id;
//                        $wh['main_de'] = 1;
//                        $wh['name'] = $variationImage;
//                        $wh['value'] = $varNV[$variationImage];
//                        $img = (new EbayListingImage())->where($wh)->order('sort')->find();
//                        $varian['mainde_path'] = empty($img['path']) ? $img['eps_path'] : $img['path'];
//                        $varian['base_url'] = empty($img['base_url']) ? 'http://14.118.130.19' : $img['base_url'];
//                    }
                }
                $data[$k]['varians'] = $varians;
                $data[$k]['v_varkey'] = $varKey;
                $data[$k]['listing_id'] = $id;
            }
            return array_values($data);
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @title
     * @param $ids 批量操作的记录
     * @param $action 批量操作的动作
     * #listing状态,0提交1待刊登2刊登中3在线4失败5待更新6更新中7待下架8下架中9已下架11已结束，12伪删除，13重上
     */
    public function upListingsStatus($ids,$action)
    {
        try{
            if($action =="del"){
                $delIds = explode(",",$ids);
                $this->publishHelper->delListings($delIds);
            }
        }catch(Exception $e){
            throw new Exception($e->getFile().$e->getLine().$e->getMessage());
        }
    }

    /**
     * @title 批量修改账号
     * @param $ids            待修改的Listing ID
     * @param $newAccountId
     * @param $paypal             类目名称
     */
    public function upListingAccounts($ids,$newAccountId,$paypal,$userId)
    {
        $update_date = time();
        $wh['id'] = ['in',$ids];
        $wh['draft'] = 1;
        $res = (new EbayListing())->where($wh)->update(['account_id'=>$newAccountId,'paypal_emailaddress'=>$paypal,'user_id'=>$userId,'update_date'=>$update_date]);
    }

    /**
     * @title 同步历史类目
     * @param $categoryId 类目ID
     * @param $site       站点ID
     * @param $cateName   类目名称
     * @param $accountId  平台账号ID
     * @param $userId     用户ID
     */
    public function syncHistoryCategory($categoryId,$site,$cateName,$accountId,$userId,$cus=0)
    {
        $wh['category_id'] = $categoryId;
        $wh['site'] = $site;
        $wh['account_id'] = $accountId;
        $wh['user_id'] = $userId;
        $res = (new EbayHistoryCategory())->where($wh)->find();
        if($res){#更新
            (new EbayHistoryCategory())->where($wh)->update(['update_date'=>time()]);
        }else{#新增
            $da = $wh;
            $da['category_name'] = $cateName;
            $da['update_date'] = time();
            $id=(new EbayHistoryCategory())->insertGetId($da);
        }
    }

    /**
     * @title 批量重上
     * @param $ids 类目ID
     * @param $replen   重上
     * @param $timing   定时
     * @param $separated  间隔时段
     */
    public function bulkHeavyListing($ids,$replen,$timing,$separated)
    {
        try{
            $ids = explode(",",$ids);
            EbayPublish::updateListingStatusWithErrMsg('relistQueue',0,['id'=>['in',$ids]]);
            if(intval($replen)==1){#立即重上
                foreach ($ids as $id) {
                    (new UniqueQueuer(EbayRelistQueuer::class))->push($id);
                }
            }else if(intval($replen)==2){#定时重上
                $t = date("Y-m-d",time());
                $tn = strtotime($t." ".$timing);
                if ($tn < time()) {
                    $tn += 86400;
                }

                foreach($ids as  $k => $v){
                    $time = $tn + $k*$separated*60;
                    (new UniqueQueuer(EbayRelistQueuer::class))->push($v,$time);
                }
            }
        }catch(Exception $e){
            throw new Exception($e->getFile().$e->getLine().$e->getMessage());
        }
    }

    /**
     * @title 基于范本创建lsting
     * @param $draftId 范本ID
     */
    public function cListing($draftId,$draft=0,$account_id=0,$paypal='')
    {
        try{
            //主表
            $list = (new EbayListing())->field(true)->where(['id'=>$draftId])->find();
            $list = json_decode(json_encode($list), true);
            $list['id'] = 0;
            $list['item_id'] = 0;
            $list['application'] = 1;
            $list['listing_sku'] = '';
            !empty($account_id) && $list['account_id'] = $account_id;
            !empty($paypal) && $list['paypal_emailaddress'] = $paypal;
            $list['listing_status'] = 0;
            if ($draft) {//范本
                $list['draft'] = 1;
                $list['draft_id'] = 0;
            } else {//listing
                $list['draft'] = 0;
                $list['draft_id'] = $draftId;
            }
            $price = $list['start_price'];
            //setting表
            $set = (new EbayListingSetting())->field(true)->where(['id'=>$draftId])->find();
            $set = json_decode(json_encode($set), true);
            $set['message'] = '';//清空错误信息
            /****************格式化一些json数据，与前端传递的保持一致**************************************************/
            //不运送地区
            $excludeLocation = $set['exclude_location'];
            if (empty($excludeLocation)) {
                $set['exclude_location'] = '';
            } else {
                $tmp = json_decode($excludeLocation, true);
                if (is_array($tmp) && count($tmp)>1) {
                    $set['exclude_location'] = implode('，', $tmp);
                } else {
                    $set['exclude_location'] = '';
                }
            }
            //运送地区
            if (empty($set['ship_location'])) {
                $set['ship_location'] = [];
            } else if (preg_match('/^[a-zA-Z]$/', $set['ship_location'])) {
                $set['ship_location'] = [$set['ship_location']];
            } else {
                $tmp = json_decode($set['ship_location'], true);
                if (is_array($tmp)) {
                    $set['ship_location'] = $tmp;
                } else {
                    $set['ship_location'] = [];
                }
            }
            //国际物流
            $internationalShipping = json_decode($set['international_shipping'], true);
            if (is_array($internationalShipping) && isset($internationalShipping[0])) {
                $set['international_shipping'] = $internationalShipping;
            } else {
                $set['international_shipping'] = [];
            }
            //国内物流
            $shipping = json_decode($set['shipping'], true);
            if (is_array($shipping) && isset($shipping[0])) {
                $set['shipping'] = $shipping;
            } else {
                $set['shipping'] = [];
            }
            //付款方式
            $paymentMethod = json_decode($set['payment_method'], true);
            if (is_array($paymentMethod) && count($paymentMethod)>1) {
                $set['payment_method'] = $paymentMethod;
            } else {
                $set['payment_method'] = ['PayPal'];
            }
            //买家限制
            $set['buyer_requirment_details'] = $this->publishHelper->buyerRequirementJsonToArray($set['buyer_requirment_details']);
            //兼容
            $set['compatibility'] = $this->publishHelper->compatibilityJsonToArray($set['compatibility']);
            //specifics
            $specifics = json_decode($set['specifics'], true);
            if (empty($specifics)) {
                $set['specifics'] = [];
            } else {
                $set['specifics'] = $specifics;
            }
            /**********************************************************************************************************/
            //变体表
            if ($list['variation']) {
                $price = 0;
                $varians = (new EbayListingVariation())->field(true)->where(['listing_id'=>$draftId])->select();
                $varians = json_decode(json_encode($varians), true);//转数组
                foreach ($varians as &$varian) {
                    $varian['variation'] = json_decode($varian['variation'], true);
                    $varian['path'] = json_decode($varian['path'], true);
                    $varian['thumb'] = json_decode($varian['thumb'], true);
                    $varian['map_sku'] = json_decode($varian['map_sku'], true);
                    $price = (empty($price) || $varian['v_price'] < $price) ? $varian['v_price'] : $price;
                }

            }
            !empty($account_id) && $list['paypal_emailaddress'] = $this->publishHelper->autoAdaptPaypal($account_id,$list['site'],$price,$this->userId);
            //图片表
            $imgs = (new EbayListingImage())->field(true)->where(['listing_id'=>$draftId, 'main'=>1])->order('sort')->select();
            $imgs = json_decode(json_encode($imgs), true);//转数组
            $detail_imgs = (new EbayListingImage())->field(true)->where(['listing_id'=>$draftId, 'detail'=>1])->order('de_sort')->select();
            $detail_imgs = json_decode(json_encode($detail_imgs), true);//转数组

            $data = [
                'list' => $list,
                'set' => $set,
                'imgs' => $imgs,
                'detail_imgs' => $detail_imgs
            ];
            if ($list['variation']) {
                $data['varians'] = $varians;
            }
            $listingId = $this->saveListingPublish($data);
            if ($listingId == 0) {
                return ['result'=>false, 'message'=>'已存在一条相同的在线listing，无法再进行创建'];
            }
            $nList =EbayListing::get($listingId)->toArray();

            return ['message'=>"复制成功！","result"=>true,"id"=>intval($listingId),"site"=>$list['site'],"data"=>$nList];
        }catch(Exception $e){
            throw new Exception($e->getFile() . "|" . $e->getLine() . "|" . $e->getMessage());
        }
    }


    /**
     * @title 加入队列
     * @param $listId ID
     */
    public function pushImageQueuer($listId){
        $queuer = new UniqueQueuer(EbayPublishItemQueuer::class);
        $queuer->push($listId);
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function testPublishFee(array $data)
    {
        set_time_limit(0);
        try {
            $listingId = $this->saveListingPublish($data, true);
            if (empty($listingId)) {
                throw new Exception('保存listing失败,无法测试刊登');
            }
            $accountId = EbayListing::where(['id'=>$listingId])->value('account_id');
            $accountInfo = Cache::store('EbayAccount')->getTableRecord($accountId);

            $ebayApi = new EbayPackApi();
            $ebayDealRes = new EbayDealApiInformation();

            //检测时仅上传一张图片
            $listInfo = $this->publishHelper->getListInfo($listingId);
            $tmpImg = isset($listInfo['imgs'][0]) ? [$listInfo['imgs'][0]] : [];
            $tmpDetailImg = isset($listInfo['detail_imgs'][0]) ? [$listInfo['imgs'][0]] : [];
            !empty($tmpImg) && $ebayApi->updateListingUploadImages($tmpImg, $accountInfo);
            !empty($tmpDetailImg) && $ebayApi->updateListingUploadImages($tmpDetailImg, $accountInfo);
            $listInfo['imgs'] = $tmpImg;
            $listInfo['detail_imgs'] = $tmpDetailImg;
            $listInfo['sku_imgs'] = [];

            $verb = 'VerifyAddFixedPriceItem';
            if ($listInfo['list']['listing_type'] == 2) {
                $verb = 'VerifyAddItem';
            }

            $apiObj = $ebayApi->createApi($accountInfo, $verb, $listInfo['list']['site']);
            $xml = $ebayApi->createXml($listInfo);
            $response = $apiObj->createHeaders()->__set('requesBody', $xml)->sendHttpRequest2();
            $res = $ebayDealRes->dealWithApiResponse($verb, $response);
            $this->publishHelper->delListings([$listingId]);
            if ($res['updateList']['listing_status'] == 4) {
                throw new Exception('测试刊登失败，失败信息：'.$res['updateSet']['message']);
            } else {
                $returnData['fees_info'] = $res['updateList']['fees_info'];
                isset($res['updateSet']['message']) && $returnData['message'] = $res['updateSet']['message'];
                return $returnData;
            }
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
    /**
     * @title 修改范本分类
     * @param $draftId 范本ID
     * @param $cateName
     */
    public function upDraftCate($draftId,$cateName,$userId)
    {
        try{
            $update_date = time();
            (new EbayListing())->where(['id'=>$draftId])->update(['model_cate'=>$cateName,'user_id'=>$userId,'update_date'=>$update_date]);
            return ['message'=>"修改成功！","result"=>true];
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @title 保存定时规则
     * @param $data
     * @param $userId
     */
    public function syncTimingRule($data)
    {
        try{
            $message = '';
            $startTime = 0;
            unset($data['id']);//定时规则不再有编辑功能，只能新增
            $timing = [
                'count' => $data['count'],
                'count_interval' => $data['count_interval'],
                'count_val' => $data['count_val'],
                'detail' => $data['detail'],
                'draft_ids' => $data['draft_ids'],
                'end_date' => $data['end_date'],
                'end_validity' => $data['end_validity'],
                'remark' => $data['remark'],
                'start_count' => $data['start_count'],
                'start_date' => $data['start_date'],
                'start_interval' => $data['start_interval'],
                'start_validity' => $data['start_validity'],
                'timing_fre' => $data['timing_fre'],
                'timing_rule_name' => $data['timing_rule_name'],
                'total_count' => $data['total_count'],
                'validity' => $data['validity']
            ];
            $fre = $timing['timing_fre'];//刊登频率类型，1按分段时间 2按间隔时间
            $validity = $timing["validity"];//刊登规则有效期 1 按刊登数量 2 按时间范围
            if ($fre == 1 && $validity == 1) {
                $startTime = strtotime($timing['start_count'].' '.$timing['start_date']);
                $endTime = strtotime($timing['start_count'].' '.$timing['end_date']);
            } else if ($fre == 2 && $validity == 1) {
                $startTime = strtotime($timing['start_count'].' '.$timing['start_interval']);
            } else if ($fre == 1 && $validity == 2) {
                $startTime = strtotime($timing['start_validity'].' '.$timing['start_date']);
                $endTime = strtotime($timing['start_validity'].' '.$timing['end_date']);
            } else if ($fre == 2 && $validity == 2) {
                $startTime = strtotime($timing['start_validity'].' '.$timing['start_interval']);
            }
            $timing['user_id'] = $this->userId;
            //验证名称是否重复
            $wh['timing_rule_name'] = $timing['timing_rule_name'];
            $wh['user_id'] = $this->userId;
            if ($data['id']??0) {
                $wh['id'] = ['<>',$data['id']];
            }
            $oldInfo = EbayListingTiming::get($wh);
            if (!empty($oldInfo)) {
                throw new Exception("规则名称".$oldInfo->timing_rule_name."重复！");
            }
            //由范本创建listing
            $draft_ids = explode(',', $timing['draft_ids']);


            $draftCount = count($draft_ids);
            if (($fre == 1 && $timing['count'] < $draftCount) || ($fre == 2 && $timing['total_count'] < $draftCount)) {
                throw new Exception('均时刊登或最多刊登数量不能小于添加的范本数量');
            }

            !empty($timing['start_count']) && $timing['start_count'] = strtotime($timing['start_count']);
            !empty($timing['start_date']) && $timing['start_date'] = strtotime($timing['start_date']);
            !empty($timing['end_date']) && $timing['end_date'] = strtotime($timing['end_date']);
            !empty($timing['start_interval']) && $timing['start_interval'] = strtotime($timing['start_interval']);
            !empty($timing['start_validity']) && $timing['start_validity'] = strtotime($timing['start_validity']);
            !empty($timing['end_validity']) && $timing['end_validity'] = strtotime($timing['end_validity']);

            $listingIds = $draft_ids;
            if (isset($data['id'])) {
                EbayListingTiming::update($timing,['id'=>$data['id']]);
                $timingId = $data['id'];
            } else {
                $timingId = (new EbayListingTiming())->allowField(true)->insertGetId($timing);
            }
            EbayListing::update(['rule_id'=>$timingId], ['id'=>['in', $listingIds]]);//更新listing的定时规则
            //应用规则
            $listingCount = count($listingIds);//listing数量
            if ($fre == 1){//1按分段时间
                $time = $endTime - $startTime;
                $c = $listingCount;//直接取范本数量
                $intervalTime = $time/$c;
            } else if ($fre == 2) {//2按间隔时间
                $intervalTime = $timing['count_interval'] * 60;//间隔时间
            }

            $listInfo = EbayListing::whereIn('id',$listingIds)->column('site,spu','id');

            $listInQueue = [];
            foreach ($listingIds as $k => $listingId) {
                $siteTimezone = EbaySite::where(['siteid'=>$listInfo[$listingId]['site']])->value('time_zone');//站点时区
                $t = $startTime + $k*$intervalTime - $siteTimezone;//转化为本地时间
                $now = time();
                if ($t < $now) {
                    $message .= 'spu为【'.$listInfo[$listingId]['spu'].'】的listing定时的时间是过去的时间,未加入队列；';
                    continue;
                }
                EbayListing::update(['timing'=>$t], ['id'=>$listingId]);
                $queuer = new UniqueQueuer(EbayPublishItemQueuer::class);
                $queuer->push($listingId, $t);//加入队列
                //判断是否需要提前上传图片
                if ($t-time()>86400) {//超过了1天
                    (new UniqueQueuer(EbayImgQueuer::class))->push($listingId,strtotime(date('Y-m-d'),$t));//当天0点开始上传
                } elseif ($t-time()>3600) {//超过1h
                    $hi = date('H:i',$t-3600);//定时时分
                    if ($hi>'22:00' || ($hi>'00:00' && $hi<'09:00') || ($hi>'12:00' && $hi<'13:30')) {
                        (new UniqueQueuer(EbayImgQueuer::class))->push($listingId,$t-3600);
                    }
                }
                $listInQueue[] = $listingId;
            }
            EbayListing::update(['listing_status'=>1], ['id'=>['in', $listInQueue]]);
            return ['message'=>empty($message) ? '定时成功':$message,'result'=>true];
        }catch(Exception $e){
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @title 获取定时规则
     * @param $ids
     * @param $userId
     */
    public function timingRuleList($ids,$param)
    {
        try{
            if($ids!=0){
                $wh['id'] = ['in',$ids];
            }
            $wh['user_id'] = $this->userId;
            if(trim($param['name'])){
                $wh['timing_rule_name'] = ['like',"%".$param['name']."%"];
            }
            $start = ($param['page']-1)*$param['size'];
            $rows =  (new EbayListingTiming())->where($wh)->limit($start,$param['size'])->select();
            foreach($rows as $k => $v){
                $rows[$k]['queue_count']=(new EbayListing())
                    ->where(['rule_id'=>$v['id'],'listing_status'=>1])->count();
                $rows[$k]['draft_count']=(new EbayListing())->distinct(true)
                    ->where(['rule_id'=>$v['id']])->count();
            }
            $res['rows'] = $rows;
            $res['count'] = (new EbayListingTiming())->where($wh)->count();
            return $res;
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @title 删除定时规则
     * @param $ids
     */
    public function rmTimingRule($ids)
    {
        try{
            $listIds = (new EbayListingTiming())->field('draft_ids')->where(['id'=>['in', $ids]])->select();
//            foreach ($listIds as $listId) {
//                EbayListing::destroy(['id'=>['in', $listId], 'listing_status'=>['in', [0,1]]]);
//            }
            EbayListingTiming::destroy(['id'=>['in', $ids]]);
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @title 获取范本主图
     * @param $ids
     */
    public function getDraftImages($ids)
    {
        try{
            $imgs = [];
            foreach($ids as $id){
                $imgs[$id]['imgs']=(new EbayListingImage())->alias('img')->field('img.*, l.spu as listing_spu')
                    ->join('ebay_listing l', 'l.id=img.listing_id', 'LEFT')
                    ->where(['img.listing_id'=>$id,'img.main'=>1])->order('sort')->select();
            }
            return $imgs;
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @title 更新范本主图
     * @param $ids
     */
    public function upDraftImages($params)
    {
        try{
            $listingIds = [];
            Db::startTrans();
            foreach ($params as $param) {
                $listingId = $param['listing_id'];
                $accountId = $param['account_id'];
                $listingIds[] = $listingId;
                $accountCode = EbayAccount::where(['id'=>$accountId])->value('code');
                $imgs = $param['imgs'];

                $listImg = $imgs[0]['base_url'].$imgs['0']['path'];//第一张做主图
                EbayListing::update(['img'=>$listImg], ['id'=>$listingId]);

                //获取旧的主图，详情图
                $oldImgs = (new EbayListingImage())->field(true)->where(['listing_id'=>$listingId,'main_de'=>0])
                    ->order('id')->select();
                $oldImgPath = EbayListingImage::where(['listing_id'=>$listingId,'main_de'=>0])->order('id')->column('path');
                $oldImgCombine = array_combine($oldImgPath, $oldImgs);
                $updateImgs = [];
                $updatePath = [];
                $insertImgs = [];
                foreach ($imgs as $k => $img) {
                    if (in_array($img['path'], $oldImgPath)) {
                        $oldImgCombine[$img['path']]['main'] = 1;
                        $oldImgCombine[$img['path']]['sort'] = $k;
                        $updateImgs[] = $oldImgCombine[$img['path']]->toArray();
                        $updatePath[] = $img['path'];
                    } else {
                        $insertImgs[$k] = $img;//需要新增的
                    }
                }
                //处理新图片中没有而旧图片中有的
                $delIds = [];
                $diffPath = array_diff($oldImgPath, $updatePath);
                foreach ($diffPath as $path) {
                    if ($oldImgCombine[$path]['detail'] == 0) {//不是详情图，删掉
                        $delIds[] = $oldImgCombine[$path]['id'];
                    } else {//是详情图，去除主图标志，加入更新图片数组
                        $oldImgCombine[$path]['main'] = 0;
                        $updateImgs[] = $oldImgCombine[$path]->toArray();
                    }
                }
                //处理新增图片
                $i = 0;
                foreach ($insertImgs as $k => $insertImg) {
                    $insertImgs[$i]['path'] = $insertImg['path'];
                    $insertImgs[$i]['thumb'] = $insertImg['path'];
                    $insertImgs[$i]['base_url'] = isset($insertImg['baseUrl']) ? $insertImg['baseUrl'] : $insertImg['base_url'];
                    $insertImgs[$i]['ser_path'] = GoodsImage::getThumbPath($insertImg['path'], 0, 0, $accountCode, true);
                    $insertImgs[$i]['sort'] = $k;
                    $insertImgs[$i]['main'] = 1;
                    $insertImgs[$i]['listing_id'] = $listingId;
                    $insertImgs[$i]['update_time'] = time();
                }
                !empty($updateImgs) && (new EbayListingImage())->saveAll($updateImgs);
                !empty($insertImgs) && (new EbayListingImage())->isUpdate(false)->saveAll($insertImgs);
                !empty($delIds) && EbayListingImage::destroy($delIds);
            }
            $listingUpdate['update_date'] = time();//更新时间
            $listingUpdate['user_id'] = $this->userId;//更新人
            EbayListing::update($listingUpdate, ['id'=>['in', $listingIds]]);
            Db::commit();
        }catch(Exception $e){
            Db::rollback();
            throw new Exception($e->getMessage());
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    public function updateListingImages($params)
    {
        try {
            $ids = array_column($params,'id');
            $field = 'id,item_id,listing_sku,account_id,site,mod_style,mod_sale,title,application';
            $lists = EbayListing::whereIn('id',$ids)->column($field,'id');
            if (!$lists) {
                throw new Exception('获取listing信息失败');
            }
            $lists = collection($lists)->toArray();
//            $accountIds = array_column(array_values($lists),'account_id');
//            $accountIds = array_values(array_unique($accountIds));
//            $accountCodes = EbayAccount::whereIn('id',$accountIds)->column('code','id');


            foreach ($params as $param) {


                $imgs = $param['imgs'];//新图片
                EbayPublish::optionImgHost($imgs,'del');

                $res = $this->publishHelper->listingImgVersionO2N($param['id']);
                if ($res['result'] === false) {
                    throw new Exception($res['message']);
                }
                $oImgs = $res['data'];
                $res = EbayPublish::seperateImgs($oImgs);
                if (isset($res['result'])) {
                    throw new Exception($res['message']);
                }
                $oldPublishImgs = [];
                $oldPaths = [];
                foreach ($res['publishImgs'] as $k1 => $publishImg) {
                    $index = $publishImg['path']?:$publishImg['thumb'];
                    $oldPublishImgs[$index] = $publishImg;
                    $oldPaths[] = $index;
                }
                $i = 0;
                foreach ($imgs as $k2 => $path) {
                    $tmpImg = [];
                    if (in_array($path,$oldPaths) && isset($oldPublishImgs[$path]['id'])) {//旧图中有
                        $tmpImg = $oldPublishImgs[$path];
                        $usedIds[] = $tmpImg['id'];
                        $tmpImg['path'] = $path;
                        $tmpImg['thumb'] = $path;
                        $tmpImg['sort'] = $k2;
                        $newImgs[$i] = $tmpImg;
                        $i++;
                    } else {//旧图中没有
                        $tmpImg['listing_id'] = $param['id'];
                        $tmpImg['path'] = $path;
                        $tmpImg['thumb'] = $path;
                        $tmpImg['ser_path'] = '';
                        $tmpImg['sort'] = $k2;
                        $tmpImg['main'] = 1;
                        $newImgs[$i] = $tmpImg;
                        $i++;
                    }
                }
                $allImgs = array_merge($newImgs,$res['detailImgs']);
                if ($lists[$param['id']]['application']) {//erp刊登的
                    $styleDetail = EbayModelStyle::where('id',$lists[$param['id']]['mod_style'])->value('style_detail');
                    $styleDetail = $styleDetail?:'a';
                    if (strpos('[IMG', $styleDetail) !== false) {
                        $newVal['style']['title'] = $lists[$param['id']]['title'];
                        $newVal['style']['description'] = EbayListingSetting::where('id', $param['id'])->value('description');
                        $newVal['style']['mod_style'] = $lists[$param['id']]['mod_style'];
                        $newVal['style']['mod_sale'] = $lists[$param['id']]['mod_sale'];
                        $newVal['style']['imgs'] = $allImgs;
                    }
                }

                $newVal['imgs'] = $allImgs;//始终传递所有图片
                $newVal['publishImgs'] = 1;//刊登图更新标志

                $upDa[] = [
                    'item_id' => $lists[$param['id']]['item_id'],
                    'listing_sku' => $lists[$param['id']]['listing_sku'],
                    'account_id' => $lists[$param['id']]['account_id'],
                    'site' => $lists[$param['id']]['site'],
                    'remark' => $param['remark'],
                    'cron_time' => empty($param['cron_time']) ? 0 : strtotime($param['cron_time']),
                    'new_val' => json_encode($newVal),
                    'old_val' => json_encode(['publishImgs'=>$res['publishImgs']]),
                    'api_type' => 2,
                ];
            }
            foreach ($upDa as $item) {
                $this->insertUpdata($item);
            }
            return ['result'=>true,'message'=>'成功加入更新队列'];

        } catch (\Exception $e) {
            return ['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()];
        }
    }

    /**
     * 批量更新一口价价格和库存
     * @param $data
     * @throws Exception
     */
    public function updatePriceQty($data)
    {
        try {
            $updateData = [];
            $item_ids = [];
            foreach ($data as $k => $datum) {//以item_id为单位进行打包
                if (!in_array($datum['item_id'], $item_ids)) {
                    $updateData[$datum['item_id']]['item_id'] = $datum['item_id'];
                    $updateData[$datum['item_id']]['listing_sku'] = $datum['listing_sku'];
                    $updateData[$datum['item_id']]['account_id'] = $datum['account_id'];
                    $updateData[$datum['item_id']]['site'] = $datum['site'];
                    $updateData[$datum['item_id']]['cron_time'] = empty($datum['cron_time']) ? 0 : strtotime($datum['cron_time']);
                    $updateData[$datum['item_id']]['remark'] = $datum['remark'];
                    $updateData[$datum['item_id']]['api_type'] = 1;
                    $item_ids[] = $datum['item_id'];
                }
                $tmp = [];
                isset($datum['start_price']) && $tmp['price'] = $datum['start_price'];
                isset($datum['quantity']) && $tmp['quantity'] = $datum['quantity'];
                $tmp['listing_sku'] = $datum['listing_sku'];
                $updateData[$datum['item_id']]['new_val'][] = $tmp;
                $updateData[$datum['item_id']]['old_val'][] = [
                    'price'=>$datum['old_start_price']??0,
                    'quantity'=>$datum['old_quantity']??0,
                    'listing_sku'=>$datum['listing_sku']??'',
                ];
            }
            foreach ($updateData as $updateDatum) {
                $count = ceil(count($updateDatum['new_val'])/4);
                $tmpUpdate = $updateDatum;
                for ($i=0;$i<$count;$i++) {
                    $newVal = array_slice($updateDatum['new_val'],$i*4,4);
                    $tmpUpdate['new_val'] = json_encode($newVal);
                    $oldVal = array_slice($updateDatum['old_val'],$i*4,4);
                    $tmpUpdate['old_val'] = json_encode($oldVal);
                    $this->insertUpdata($tmpUpdate);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     *批量修改刊登周期
     * @param $data
     * @throws Exception
     */
    public function updateListingDuration($data)
    {
        try {
            $updateData['item_id'] = $data['item_id'];
            $updateData['listing_sku'] = $data['listing_sku'];
            $updateData['account_id'] = $data['account_id'];
            $updateData['site'] = $data['site'];
            $updateData['cron_time'] = empty($data['cron_time']) ? 0 : strtotime($data['cron_time']);
            $updateData['remark'] = $data['remark'];
            $updateData['api_type'] = 2;
            $updateData['new_val'] = json_encode(['listing_duration'=>$data['listing_duration']]);
            $updateData['old_val'] = json_encode(['listing_duration'=>$data['old_listing_duration']]);
            $this->insertUpdata($updateData);
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * 批量修改拍卖价
     * @param $data
     * @throws Exception
     */
    public function updateChinesePrice($data)
    {
        try {
            $upDa['item_id'] = $data['item_id'];
            $upDa['listing_sku'] = $data['listing_sku'];
            $upDa['account_id'] = $data['account_id'];
            $upDa['site'] = $data['site'];
            $upDa['remark'] = $data['remark'];
            $upDa['cron_time'] = empty($data['cron_time']) ? 0 : strtotime($data['cron_time']);

            $newVal['start_price'] = $data['start_price'];
            $oldVal['start_price'] = $data['old_start_price'];
            $newVal['reserve_price'] = $data['reserve_price'];
            $oldVal['reserve_price'] = $data['old_reserve_price'];
            $newVal['buy_it_nowprice'] = $data['buy_it_nowprice'];
            $oldVal['buy_it_nowprice'] = $data['old_buy_it_nowprice'];
            $upDa['new_val'] = json_encode($newVal);
            $upDa['old_val'] = json_encode($oldVal);
            $upDa['api_type'] = 2;
            $this->insertUpdata($upDa);
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * 批量修改在线标题
     * @param $da
     * @throws Exception
     */
    public function updateTitle($da)
    {
        try {
            $upDa['item_id'] = $da['item_id'];
            $upDa['listing_sku'] = $da['listing_sku'];
            $upDa['account_id'] = $da['account_id'];
            $upDa['site'] = $da['site'];
            $upDa['cron_time'] = empty($da['cron_time']) ? 0 : strtotime($da['cron_time']);
            $upDa['remark'] = $da['remark'];

            //修改标题时，同时修改描述里面的标题
            $field = 'id,mod_style,mod_sale';
            $list = EbayListing::field($field)->where(['item_id'=>$da['item_id']])->find();
            $description = EbayListingSetting::where('id',$list['id'])->value('description');
            $res = $this->publishHelper->listingImgVersionO2N($list['id']);
            if ($res['result'] === false) {
                throw new Exception($res['message']);
            }
            $imgs = $res['data'];
            unset($imgs['sku']);
            //标题，描述，描述图，风格，销售说明
            $desc['title'] = $da['title'];//新标题
            $desc['description'] = $description;
            $desc['mod_style'] = $list['mod_style'];
            $desc['mod_sale'] = $list['mod_sale'];
            $desc['imgs'] = $imgs;
            $newVal['style'] = $desc;
            $upDa['old_val'] = json_encode(['title'=>$da['old_title']]);
            $upDa['new_val'] = json_encode($newVal);
            $upDa['api_type'] = 2;
            $this->insertUpdata($upDa);
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @title
     * @param $param 修改的参数
     * @param $userId 用户ID
     */
    public function insertUpdata($param)
    {
        try{
            $param['create_id'] = $this->userId;
            $param['status'] = 0;
            $param['create_time'] = time();
            if (!$param['item_id']) {//如果item id为空，不进行更新
                return;
            }
            //添加更新日志
            $id = (new EbayActionLog())->insertGetId($param);
            EbayListing::update(['listing_status'=>EbayPublish::PUBLISH_STATUS['inUpdateQueue']],['item_id'=>$param['item_id']]);//更新状态
            //加入更新队列
            $queuer = new UniqueQueuer(EbayUpdateOnlineListing::class);
            if($param['cron_time']){
                $queuer->push($id,$param['cron_time']);
            }else{
                $queuer->push($id, time());
            };
            return $id;
        }catch(Exception $e){
            throw new Exception($e->getFile()."|".$e->getLine()."|".$e->getMessage());
        }
    }


    /**
     * @title 修改listing的类目属性
     * @param $data 修改的参数
     */
    public function upListingSpecifics($data,$userId)
    {
        // foreach($data['specifics'] as &$sp){
        //     $sp['listing_id'] = $data['id'];
        // }
        #(new EbayListingSpecifics())->upListingSpecifics($data['specifics'],$data['id']);
        (new EbayListing())->where(['id'=>$data['id']])->update(['user_id'=>$userId,'update_date'=>time()]);
        (new EbayListingSetting())->where(['id'=>$data['id']])->update(['specifics'=>json_encode($data['specifics'])]);
    }

    /**
     * @title 修改listing的类目属性
     * @param $data 修改的参数
     */
    public function upDraftitle($data,$userId)
    {
        $update_date = time();
        (new EbayListing())->where(['id'=>$data['id']])->update(['title'=>$data['title'],'user_id'=>$userId,'update_date'=>$update_date]);
    }

    /**
     * 获取要修改名称的范本信息
     * @param $wh
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function preUpDraftname($wh){
        return (new EbayListing)->field('id,spu,draft_name')->where($wh)->select();
    }
    /**
     * 修改范本名称
     * @param $data
     */
    public function upDraftname($data,$userId){
        $update_date = time();
        (new EbayListing())->where(['id'=>$data['id']])->update(['draft_name'=>$data['draft_name'],'user_id'=>$userId,'update_date'=>$update_date]);
    }

    /**
     * @title 修改listing的类目属性
     * @param $data 修改的参数
     */
    public function getDraftSpecifics($id)
    {
        $rows['id'] = $id;
        $setting = (new EbayListingSetting())->where(['id'=>$id])->find()->toArray();
        $list = (new EbayListing())->field('primary_categoryid,site')->where(['id'=>$id])->find()->toArray();
        $rows['specifics'] = json_decode($setting['specifics'],true);
        $specificsArr = (new EbayService())->getEbaySpecifics($list['primary_categoryid'],$list['site']);
        $specifics = [];
        foreach($specificsArr as $sp){
            $temp = $sp->toArray();
            $temp['custom'] = 0;
            $specifics[] = $temp;
        }
        $rows['ebay_specifics'] = $specifics;
        return $rows;
    }

    /**
     * @title 批量修改范本出售方式
     * @param $da  修改的范本数据
     * @param $listingType 出售方式
     * @param $accountId 销售账号ID
     * @param $varians 是否多属性 0单属性 1多属性
     * @param $listingDuration 上架时间
     * @param $userId  当前系统用户ID
     */
    public function upDraftListingTypeService($da,$listingType,$varions,$listingDuration)
    {
        $draftId = $da['id'];#范本ID
        if($listingType==2){#拍卖
            $list['local_sku'] = $da['local_sku'];
            $list['sku'] = $da['sku'];
            $list['quantity']=1;
            $list['listing_duration']=2;
            $list['start_price'] = $da['start_price'];#拍卖起始价
            $list['reserve_price'] = $da['reserve_price'];#拍卖保留价
            $list['buy_it_nowprice'] = $da['buy_it_nowprice'];#立即成交价
            #插入映射sku
            $map['sku_code'] = $da['local_sku'];
            $map['channel_id'] = 1;
            $map['account_id'] = $da['account_id'];
            $map['combine_sku'] = $list['sku'];
            $list['listing_sku'] = (new GoodsSkuMapService())->createSku($da['local_sku']);
        }else if($listingType==1){#一口价
            if(!$varions){#单属性产品
                $list['local_sku'] = $da['local_sku'];
                $list['sku'] = $da['sku'];
                $list['quantity']=$da['quantity'];
                $list['start_price'] = $da['start_price'];#价格
                $list['listing_duration'] = $listingDuration;
                #插入映射sku
                $map['sku_code'] = $da['local_sku'];
                $map['channel_id'] = 1;
                $map['account_id'] = $da['account_id'];
                $map['combine_sku'] = $list['sku'];
                $list['listing_sku'] = (new GoodsSkuMapService())->createSku($da['local_sku']);
            }else{#多属性产品
                #$list['sku'] = $da['spu'];#sku
                #生成产品唯一标识
                #$acInfo = (new EbayAccount())->where(['id'=>$da['account_id']])->find();
                #$list['listing_sku'] = $this->createOnlyListingSku($list['sku'],1,$da['account_id'],$acInfo['code']);
            }
        }
        $list['listing_type'] = $listingType;
        $list['variation'] = $varions;
        $list['user_id'] = $this->userId;
        $list['update_date'] = time();
        (new EbayListing())->where(['id'=>$draftId])->update($list);
    }

    /**
     * @title 获取单个listing的修改记录
     * @param $param 修改的参数
     */
    public function getActionLogs($param)
    {
        try{
            $size = $param['size'];
            $page = $param['page'];
            $start = ($page-1)*$size;
            $rows = (new EbayActionLog())->alias('lg')
                ->join("user u","lg.create_id = u.id","LEFT")
                ->field("u.realname,lg.*")
                ->where(['lg.item_id'=>$param['item_id']])
                ->order("lg.create_time desc")->limit($start,$size)->select();
            $count = (new EbayActionLog())->alias('lg')
                ->join("user u","lg.create_id = u.id","LEFT")
                ->field("u.realname,lg.*")
                ->where(['lg.item_id'=>$param['item_id']])
                ->order("lg.create_time")->count();
            foreach($rows as $k => $row){
                #title,price,quantity,description,trans,transin,location,refuse,pay,imgs,varians
                $newVal = json_decode($row['new_val'],true);
                $oldVal = json_decode($row['old_val'],true);
                $msg = $row['listing_sku']."<br>";

                if(isset($newVal['title'])){
                    $msg.='标题由 '.$oldVal['title']['title'].' 变更为 '.$newVal['title']['title']."<br>";
                }
                if(isset($newVal['chinese_price'])){
                    $msg.='起始价由'.$oldVal['chinese_price']['start_price'].'变更为'.$newVal['chinese_price']['start_price']."<br>";
                    $msg.='保留价由'.$oldVal['chinese_price']['reserve_price'].'变更为'.$newVal['chinese_price']['reserve_price']."<br>";
                    $msg.='一口价由'.$oldVal['chinese_price']['buy_it_nowprice'].'变更为'.$newVal['chinese_price']['buy_it_nowprice']."<br>";
                }
                if(isset($newVal['price']) && $row['api_type']==1){
                    $msg.='价格由'.$oldVal['price'].'变更为'.$newVal['price']."<br>";
                }
                if(isset($newVal['quantity']) && $row['api_type']==1){
                    $msg.='库存由'.$oldVal['quantity'].'变更为'.$newVal['quantity']."<br>";
                }
                if(isset($newVal['description'])){
                    $msg.='描述由'.$oldVal['description']['description'].'变更为'.$newVal['description']['description']."<br>";
                }
                if(isset($newVal['common_mod']) && $row['api_type']==1){#应用公共模块
                    // $assoc = array_diff_assoc($newVal['common_mod'],$oldVal['common_mod']);
                    // if(isset($assoc['mod_trans'])){#物流
                    //     $trans = Db::name('ebay_common_trans')->where(['id'=>$assoc['mod_trans']])->find();
                    //     $msg.='物流模块变更为'.$trans['model_name']."<br>";
                    // }
                    // if(isset($assoc['mod_exclude'])){#不送达地区
                    //     $exclude = (new EbayCommonTrans())->where(['id'=>$assoc['mod_exclude']])->find();
                    //     $msg.='不送达地区模块变更为'.$exclude['model_name']."<br>";
                    // }
                    // if(isset($assoc['mod_location'])){#发货地
                    //     $location = (new EbayCommonLocation())->where(['id'=>$assoc['mod_location']])->find();
                    //     $msg.='发货地模块变更为'.$location['model_name']."<br>";
                    // }
                    // if(isset($assoc['mod_return'])){#退货
                    //     $return = (new EbayCommonReturn())->where(['id'=>$assoc['mod_return']])->find();
                    //     $msg.='退货政策模块变更为'.$return['model_name']."<br>";
                    // }
                    // if(isset($assoc['mod_refuse'])){#买家限制
                    //     $refuse = (new EbayCommonRefuseBuyer())->where(['id'=>$assoc['mod_refuse']])->find();
                    //     $msg.='买家限制模块变更为'.$refuse['model_name']."<br>";
                    // }
                    // if(isset($assoc['mod_receivables'])){#收付款
                    //     $rece = (new EbayCommonReceivables())->where(['id'=>$assoc['mod_receivables']])->find();
                    //     $msg.='收付款模块变更为'.$rece['model_name']."<br>";
                    // }
                }
                if(isset($newVal['images']) && $row['api_type']==1){#图片信息
                    $images = explode(';',$newVal['images']);
                    $msg.='图片改变为<br>';
                    foreach($images as $img){
                        $msg.=$img."<br>";
                    }
                }

                // if(isset($newVal['location'])){
                //     $msg.='发货地Location'.$oldVal['location']['location'].'变更为'.$newVal['location']['location']."<br>";
                //     $msg.='邮编PostCode'.$oldVal['location']['post_code'].'变更为'.$newVal['location']['post_code']."<br>";
                //     $msg.='国家Country'.$oldVal['location']['country'].'变更为'.$newVal['location']['country']."<br>";
                // }
                // if(isset($newVal['trans'])){
                //     $msg.='国际物流方式'.$oldVal['trans']['shipping_service'].'变更为'.$newVal['trans']
                //     ['shipping_service']."<br>";
                //     $msg.='首件运费'.$oldVal['trans']['shipping_service_cost'].'变更为'.$newVal['trans']
                //     ['shipping_service_cost']."<br>";
                //     $msg.='续件费'.$oldVal['trans']['shipping_service_additional_cost'].'变更为'.$newVal['trans']
                //     ['shipping_service_additional_cost']."<br>";
                //     $msg.='送达地区'.$oldVal['trans']['shiptolocation'].'变更为'.$newVal['trans']['shiptolocation']."<br>";
                // }
                // if(isset($newVal['transin'])){
                //     $msg.='国内物流方式'.$oldVal['transin']['shipping_service'].'变更为'.$newVal['transin']
                //     ['shipping_service']."<br>";
                //     $msg.='首件运费'.$oldVal['transin']['shipping_service_cost'].'变更为'.$newVal['transin']
                //     ['shipping_service_cost']."<br>";
                //     $msg.='续件费'.$oldVal['transin']['shipping_service_additional_cost'].'变更为'.$newVal['transin']
                //     ['shipping_service_additional_cost']."<br>";
                //     $msg.='额外收费'.$oldVal['transin']['extra_cost'].'变更为'.$newVal['transin']['extra_cost']."<br>";
                // }
                // if(isset($newVal['refuse'])){
                //     $msg.='买家限制由'.$oldVal['refuse']['refuse']?'是':'否'.'变更为'.$newVal['refuse']['refuse']?'是':'否';
                //     $msg.='paypal限制由'.$oldVal['refuse']['link_paypal']?'是':'否'.'变更为'.$newVal['refuse']['link_paypal']?'是':'否';
                //     $msg.='运送范围限制由'.$oldVal['refuse']['registration']?'是':'否'.'变更为'.$newVal['refuse']['registration']?'是':'否';
                //     $msg.='违反相关政策限制由'.$oldVal['refuse']['violations']?'是':'否'.'变更为'.$newVal['refuse']
                //     ['violations']?'是':'否';
                //     $msg.='违反次数由'.$oldVal['refuse']['violations_count'].'变更为'.$newVal['refuse']['violations_count'];
                //     $msg.='违反周期由'.$oldVal['refuse']['violations_period'].'变更为'.$newVal['refuse']['violations_period'];
                //     $msg.='未付款限制由'.$oldVal['refuse']['strikes']?'是':'否'.'变更为'.$newVal['refuse']
                //     ['strikes']?'是':'否';
                //     $msg.='未付款次数由'.$oldVal['refuse']['strikes_count'].'变更为'.$newVal['refuse']['strikes_count'];
                //     $msg.='未付款周期由'.$oldVal['refuse']['strikes_period'].'变更为'.$newVal['refuse']['strikes_period'];
                //     $msg.='信用限制由'.$oldVal['refuse']['credit']?'是':'否'.'变更为'.$newVal['refuse']
                //     ['credit']?'是':'否';
                //     $msg.='信用等级由'.$oldVal['refuse']['requirements_feedback_score'].'变更为'.
                //     $newVal['refuse']['requirements_feedback_score'];
                //     $msg.='次数限制由'.$oldVal['refuse']['requirements']?'是':'否'.'变更为'.$newVal['refuse']
                //     ['requirements']?'是':'否';
                //     $msg.='次数由'.$oldVal['refuse']['requirements_max_count'].'变更为'.
                //     $newVal['refuse']['requirements_max_count'];
                //     $msg.='次数由'.$oldVal['refuse']['requirements_max_count'].'变更为'.
                //     $msg.='评分限制由'.$oldVal['refuse']['minimum_feedback']?'是':'否'.'变更为'.$newVal['refuse']
                //     ['minimum_feedback']?'是':'否';
                //     $msg.='评分由'.$oldVal['refuse']['minimum_feedback_score'].'变更为'.
                //     $newVal['refuse']['minimum_feedback_score'];
                // }
                // if(isset($newVal['pay'])){
                //     $msg.='付款方式由'.$oldVal['pay']['pay_method'].'变更为'.$newVal['pay']['pay_method'];
                //     $msg.='自动付款'.$oldVal['pay']['autopay'].'变更为'.$newVal['pay']['autopay'];
                //     $msg.='付款说明'.$oldVal['pay']['payment_instructions'].'变更为'.$newVal['pay']['payment_instructions'];
                // }
                // if(isset($newVal['varians'])){
                //     foreach($newVal['varians'] as $k2 => $var){
                //         $msg.='子产品'.$var['v_sku'].$oldVal['varians'][$k2]['v_qty'].'变更为'.$var['v_qty'];
                //         $msg.='子产品'.$var['v_sku'].$oldVal['varians'][$k2]['v_price'].'变更为'.$var['v_qty'];
                //     }
                // }
                $rows[$k]['contents'] = $msg;
            }
            $result['rows'] = $rows;
            $result['count'] = $count;
            return $result;
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @title 关联本地产品信息
     * @param $param
     */
    public function relatedProductInformation($param,$userId)
    {
        try{
            $listType = $param['listing_type'];
            $listingId = $param['id'];
            $goodsId = $param['goods_id'];
            #关联map记录
            #GoodsPublishMapService::update($channel,$spu,$account_id,$status=1,$site_id=0)
            $vars = [];
            if($listType==2){
                $list['local_sku'] = $param['local_sku'];
                $skuId = (new GoodsSku())->field("id")->where(['sku'=>$list['local_sku']])->find();
                $list['sku'] = $param['sku'];
                $list['spu'] = $param['spu'];

                $skuCode[$skuId['id']]['sku_id'] = $skuId['id'];
                $skuCode[$skuId['id']]['quantity'] =1;
                $skuCode[$skuId['id']]['sku_code'] =$list['local_sku'];
                $this->insertRelatedCord($skuId['id'],$list['local_sku'],1,$param['account_id'],
                    $param['listing_sku'],1,$userId,json_encode($skuCode));
                GoodsPublishMapService::update(1,$param['spu'],$param['account_id']);

            }else if($listType==1){

                $list['spu'] = $param['spu'];
                if(isset($param['local_sku'])){
                    $list['local_sku'] = $param['local_sku'];
                    $skuId = (new GoodsSku())->field("id")->where(['sku'=>$list['local_sku']])->find();
                    $list['sku'] = $param['sku'];
                    $list['spu'] = $param['spu'];
                }
                $vars = isset($param['vars'])?$param['vars']:[];
                GoodsPublishMapService::update(1,$param['spu'],$param['account_id']);
            }
            $list['goods_id'] = $goodsId;
            (new EbayListing())->where(['id'=>$listingId])->update($list);
            foreach($vars as $k => $v){
                $temp['goods_id'] = $goodsId;
                $temp['sku_id'] = $v['sku_id'];
                #子产品sku
                $sku = (new GoodsSku())->where(['id'=>$temp['sku_id']])->find();
                $temp['v_sku'] = $sku['sku'];
                $temp['combine_sku'] = $v['combine_sku'];
                $temp['map_sku'] = json_encode($v['map_sku']);
                if($k==0){
                    // $skuCode[$sku['id']]['sku_id'] = $temp['sku_id'];
                    // $skuCode[$sku['id']]['quantity'] =1;
                    // $skuCode[$sku['id']]['sku_code'] =$sku['sku'];
                    $skuCode[$v['sku_id']] = $v['map_sku'];
                    $vInfo = (new EbayListingVariation())->where(['id'=>$v['id']])->find();
                    $this->insertRelatedCord($temp['sku_id'],$sku['sku'],1,$param['account_id'],
                        $vInfo['channel_map_code'],1,$userId,json_encode($skuCode));
                }
                #插入映射sku
                (new EbayListingVariation())->where(['id'=>$v['id']])->update($temp);
            }

            return ['message'=>'关联成功！','result'=>true];
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @title 添加关联记录
     * @param $param
     */
    public function insertRelatedCord($skuId,$sku,$chId,$account,$chanlSku,$quantity,$createId,$skuCode)
    {
        $wh['sku_id'] = $skuId;
        $wh['sku_code'] = $sku;
        $wh['channel_id'] = $chId;
        $wh['account_id'] = $account;
        $wh['channel_sku'] = $chanlSku;
        $res = (new GoodsSkuMap())->where($wh)->find();
        if(!$res){
            $map = $wh;
            $map['quantity'] = $quantity;
            $map['sku_code_quantity'] = $skuCode;
            $map['creator_id'] = $createId;
            $map['create_time'] = time();
            (new GoodsSkuMap())->insertGetId($map);
        }
    }

    /**
     * @title 应用公共模块
     * @param $param
     */
    public function syncCommonModel($old,$new,$itemId){
        try{
            $modListing = new EbayListing();
            $modSetting = new EbayListingSetting();
            $list = $modListing->where(['item_id'=>$itemId])->find();
            $set = $modSetting->where(['id'=>$list['id']])->find();
            $newVal = [];
            $oldVal = [];
            $upCommondModel = [];
            if($old['mod_trans']!=$new['mod_trans']){#物流模块
                $upCommondModel['mod_trans'] = $new['mod_trans'];
                $comTrans = (new EbayCommonTransDetail())->where(['trans_id'=>$new['mod_trans']])->find();
                $shipping = [];#国内物流
                $interShipping = [];#国际物流
                $i=0;$k=0;
                foreach($comTrans as $trans){
                    if($trans['inter']==1){#国际物流
                        $interShipping[$i]['shiptolocation'] = $trans['location']=="Worldwide"?"Worldwide":
                            json_decode($trans['location'],true);
                        $interShipping[$i]['shipping_service'] = $trans['trans_code'];
                        $interShipping[$i]['shipping_service_cost'] = $trans['cost'];
                        $interShipping[$i]['shipping_service_additional_cost'] = $trans['add_cost'];
                        $i++;
                    }else{#国内物流
                        $shipping[$k]['extra_cost'] = $trans['extra_cost'];
                        $shipping[$k]['shipping_service'] = $trans['trans_code'];
                        $shipping[$k]['shipping_service_cost'] = $trans['cost'];
                        $shipping[$k]['shipping_service_additional_cost'] = $trans['add_cost'];
                        $k++;
                    }
                }
                $newVal['trans'] = $shipping;
                $newVal['transin'] = $interShipping;
            }

            if($old['mod_exclude']!=$new['mod_exclude']){#不送达地区
                $upCommondModel['mod_exclude'] = $new['mod_exclude'];
                $exclude = (new EbayCommonExclude())->where(['id'=>$new['mod_exclude']])->find();
                if($exclude){
                    $newVal['exclude_location'] = $exclude['exclude'];
                }
            }

            if($old['mod_location']!=$new['mod_location']){#商品所在地
                $upCommondModel['mod_location'] = $new['mod_location'];
                $locationTemp = (new EbayCommonLocation())->where(['id'=>$new['mod_location']])->find();
                if($locationTemp){
                    $location['location'] = $locationTemp['location'];
                    $location['country'] = $locationTemp['country'];
                    $location['postal_code'] = $locationTemp['post_code'];
                    $newVal['location'] = $location;
                }
            }

            if($old['mod_return']!=$new['mod_return']){#退货
                $upCommondModel['mod_return'] = $new['mod_return'];
                $returnTemp = (new EbayCommonReturn())->where(['id'=>$new['mod_return']])->find();
                if($returnTemp){
                    $return['return_policy'] = $returnTemp['return_policy'];
                    $return['return_type'] = $returnTemp['return_type'];
                    $return['return_time'] = $returnTemp['return_time'];
                    $return['extended_holiday'] = $returnTemp['extension'];
                    $return['return_shipping_option'] = $returnTemp['return_shipping_option'];
                    $return['restocking_fee_code'] = $returnTemp['restocking_fee_code'];
                    $return['return_description'] = $returnTemp['return_description'];
                    $newVal['return'] = $return;
                }
            }

            if($old['mod_refuse']!=$new['mod_refuse']){#买家限制
                $upCommondModel['mod_refuse'] = $new['mod_refuse'];
                $refuse = (new EbayCommonRefuseBuyer())->where(['id'=>$new['mod_refuse']])->find();
                if($refuse){
                    $buyerRequirment['credit'] = $refuse['credit'];#信用限制
                    $buyerRequirment['strikes'] = $refuse['strikes'];#未付款限制
                    $buyerRequirment['violations'] = $refuse['violations'];#违反政策相关
                    $buyerRequirment['link_paypal'] = $refuse['link_paypal'];#paypal限制
                    $buyerRequirment['registration'] = $refuse['registration'];#是否限制运送范围
                    $buyerRequirment['requirements'] = $refuse['requirements'];
                    $buyerRequirment['strikes_count'] = $refuse['strikes_count'];
                    $buyerRequirment['strikes_period'] = $refuse['strikes_period'];
                    $buyerRequirment['minimum_feedback'] = $refuse['minimum_feedback'];
                    $buyerRequirment['violations_count'] = $refuse['violations_count'];
                    $buyerRequirment['violations_period'] = $refuse['violations_period'];
                    $buyerRequirment['minimum_feedback_score'] = $refuse['minimum_feedback'];
                    $buyerRequirment['requirements_max_count'] = $refuse['requirements_max_count'];
                    $buyerRequirment['requirements_feedback_score'] = $refuse['requirements_feedback_score'];
                    $newVal['buyer_requirment'] = $buyerRequirment;
                }
            }

            if($old['mod_receivables']!=$new['mod_receivables']){#收款选项
                $upCommondModel['mod_receivables'] = $new['mod_receivables'];
                $receivables = (new EbayCommonReceivables())->where(['id'=>$new['mod_receivables']])->find();
                if($receivables){
                    $pay['payment_method'] = $receivables['pay_method'];#付款方式
                    $pay['payment_instructions'] = $receivables['payment_instructions'];#支付说明
                    $pay['autopay'] = $receivables['auto_pay'];#自动付款
                    $newVal['pay'] = $pay;
                }
            }

            if($old['mod_choice']!=$new['mod_choice']){#备货期
                $upCommondModel['mod_choice'] = $new['mod_choice'];
                $choice = (new EbayCommonChoice())->where(['id'=>$new['mod_choice']])->find();
                if($choice){
                    $newVal['dispatch_max_time'] = $choice['choice_date'];
                }
            }

            if($old['mod_galley']!=$new['mod_galley']){#橱窗展示
                $upCommondModel['mod_galley'] = $new['mod_galley'];
                $galley = (new EbayCommonGallery())->where(['id'=>$new['mod_galley']])->find();
                if($galley){
                    $newVal['picture_gallery'] = $galley['picture_gallery'];
                }
            }

            if($old['mod_individual']!=$new['mod_individual']){#私人物品
                $upCommondModel['mod_individual'] = $new['mod_individual'];
                $individual = (new EbayCommonIndividual())->where(['id'=>$new['mod_individual']])->find();
                if($individual){
                    $newVal['private_listing'] = $individual['individual_listing'];
                }
            }

            if($old['mod_bargaining']!=$new['mod_bargaining']){#买家还价
                $upCommondModel['mod_bargaining'] = $new['mod_bargaining'];
                $bargaining = (new ebayCommonBargaining())->where(['id'=>$new['mod_bargaining']])->find();
                if($bargaining){
                    $bestOffer['best_offer'] = $bargaining['best_offer'];
                    $bestOffer['auto_accept_price'] = $bargaining['accept_lowest_price'];
                    $bestOffer['minimum_accept_price'] = $bargaining['reject_lowest_price'];
                    $newVal['best_offer'] = $bestOffer;
                }
            }

            if($old['mod_quantity']!=$new['mod_quantity']){#库存
                $upCommondModel['mod_quantity'] = $new['mod_quantity'];
                $quantity = (new EbayCommonQuantity())->where(['id'=>$new['mod_quantity']])->find();
                if($quantity){
                    $newVal['mod_quantity'] = $quantity['quantity'];
                }
            }

            #风格模板
            if($old['mod_style']!=$new['mod_style'] || $old['mod_sale']!=$new['mod_sale']){
                $upCommondModel['mod_style'] = $new['mod_style'];
                $upCommondModel['mod_sale'] = $new['mod_sale'];
                $style['style_id'] = $new['mod_style'];
                $style['desc'] = $set['description'];
                $style['title'] = $list['title'];
                $style['sale_id'] = $new['mod_sale'];
                $style['images'] = (new EbayListingImage())->where(['listing_id'=>$list['id']])->select();
                $newVal['style'] = $style;
            }
            $modListing->where(['id'=>$list['id']])->update($upCommondModel);
            return json_encode($newVal);
        }catch(Exception $e){
            throw new Exception($e->getFile()."|".$e->getLine()."|".$e->getMessage());
        }
    }

    /**
     * @param $ids
     * @param $type
     * @return array
     * @throws Exception
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function exportDraftInfo(string $ids, int $type)
    {
        try {
            $data = [];
            $idArr = explode(',', $ids);
            $listingVar_cn = Constants::LISTVAR_CN;
            if ($type == 0) {
                //定义一级标题
                $masterTitle = ['一般信息', '标题与价格', 'SKU设置', '图片与描述', '物流设置',
                    '不运送地区', '商品所在地', '退货', '买家限制', '补充与重上规则', '收款选项', '其他'];
                //定义二级标题'UPC','数量',
                $subTitle['base'] = ['范本id','本地SPU', '范本名称', '账号简称', '平台站点', '出售方式', 'sku属性', '引用模块组合',
                    '刊登分类1', '刊登分类2', '商店分类1', '商店分类2', 'UPC', 'EAN', '订单商品是否关联', '物品状况',
                    '物品状态描述', '分类属性1', '分类属性2', '分类属性3', '分类属性4', '分类属性5', '分类属性6',
                    '分类属性7', '分类属性8', '分类属性9', '分类属性10', '分类属性11', '分类属性12', '分类属性13',
                    '分类属性14', '分类属性15', '分类属性16', '分类属性17', '分类属性18', '分类属性19', '分类属性20',
                    '分类属性21', '分类属性22', '分类属性23', '分类属性24', '分类属性25','分类属性26', '分类属性27',
                    '分类属性28', '分类属性29', '分类属性30','分类属性31','分类属性32','分类属性33','分类属性34','分类属性35','分类属性36',
                    '分类属性37','分类属性38','分类属性39','分类属性40','分类属性41','分类属性42','分类属性43','分类属性44','分类属性45',
                    '分类属性46','分类属性47','分类属性48','分类属性49','分类属性50'];
                $subTitle['titlePrice'] = ['刊登标题', '刊登副标题',  '上架时间', 'VAT税率(%)', '是否接受买家还价',
                    '自动交易价格', '自动拒绝价格', '是否是私人物品'];
                $subTitle['skuSet'] = ['本地SKU', '捆绑/打包销售', '展图(最多12张)','图片关联', '变体属性1', '变体属性2',
                    '变体属性3', '变体属性4', '变体属性5', '变体属性6', '变体属性7', '变体属性8', '变体属性9',
                    '变体属性10','拍卖底价', '保底拍卖价', '一口价/销售价', '可售数', 'UPC', 'EAN'];
                $subTitle['img'] = ['刊登风格', '销售说明模板', '刊登图片', '详情描述图片', '移动端描述', '详情描述',
                    '橱窗展示(Gallery)图片', '样式'];
                $subTitle['shipSet'] = ['引用模板', '国内运输方式1', '首件运费', '续件运费', 'AK,HI,PR额外收费',
                    '国内运输方式2', '首件运费', '续件运费', 'AK,HI,PR额外收费', '国内运输方式3', '首件运费',
                    '续件运费', 'AK,HI,PR额外收费', '国内运输方式4', '首件运费', '续件运费', 'AK,HI,PR额外收费',
                    '国际运输方式1', '首件运费', '续件运费', '可运至的国家', '国际运输方式2', '首件运费', '续件运费',
                    '可运至的国家', '国际运输方式3', '首件运费', '续件运费', '可运至的国家', '国际运输方式4',
                    '首件运费', '续件运费', '可运至的国家', '国际运输方式5', '首件运费', '续件运费', '可运至的国家',
                    '备货时间', '销售税', '运费是否包含销售税', '是否上门提货'];
                $subTitle['noShip'] = ['引用模板', '不运送地区'];
                $subTitle['placeOfGoods'] = ['引用模板', '商品所在地', '国家', '邮编'];
                $subTitle['returnGoods'] = ['引用模板', '接受退货', '接受退货方式', '接受退货周期', '节假日是否延期退货',
                    '退货邮费承担', '折旧费', '退货政策详情说明'];
                $subTitle['buyerLimit'] = ['引用模板', '是否开启买家限制', '买家具体限制'];
                $subTitle['restart'] = ['是否开启自动补货', '是否开启自动重上', '重上规则', '重上时间'];
                $subTitle['receivePay'] = ['引用模板', 'Paypal收款账号', '收款方式', '立即付款', '付款说明'];
                $subTitle['others'] = ['范本分类', 'listing分类'];

                foreach ($idArr as $k => $v) {
                    $i = 0;
                    $draftInfo = $this->getListingInfo(intval($v));
                    $list = $draftInfo['list'];
                    $set = $draftInfo['set'];
                    $list['variation'] && $varians = $draftInfo['varians'];
                    $imgs = $draftInfo['imgs'];
                    $detail_imgs = $draftInfo['detail_imgs'];
                    //一般信息
                    $data[$k][$i++] = $list['id'];//范本id
                    $data[$k][$i++] = $list['spu'];//spu
                    $data[$k][$i++] = $list['draft_name'];//范本名称
                    $data[$k][$i++] = EbayAccount::where(['id'=>$list['account_id']])->value('code');//账号简称
                    $siteInfo = Cache::store('EbaySite')->getSiteInfoBySiteId($list['site']);
                    $data[$k][$i++] = $siteInfo['name'];//站点名称
                    $data[$k][$i++] = $listingVar_cn['listingType'][$list['listing_type']];//出售方式
                    $data[$k][$i++] = $listingVar_cn['variation'][$list['variation']];//sku属性
                    $data[$k][$i++] = 0;//组合模板
                    $data[$k][$i++] = $list['primary_categoryid'];//第一分类
                    $data[$k][$i++] = $list['second_categoryid'];//第二分类
                    $data[$k][$i++] = $list['store_category_id'];//商店第一分类
                    $data[$k][$i++] = $list['store_category2_id'];//商店第二分类
                    $data[$k][$i++] = $set['upc'];//upc
                    $data[$k][$i++] = $set['ean'];//ean
                    $data[$k][$i++] = $listingVar_cn['boolType'][$list['assoc_order']];//订单商品是否关联
                    $data[$k][$i++] = $listingVar_cn['condition'][$set['condition_id']]??'New Brand New';//物品状态
                    $data[$k][$i++] = $set['condition_description'];//物品状态描述
                    /*********************specifics start *************************************************************
                     * $set['specifics']: array
                     *                    ['custom'=>0, 'attr_name'=>'Model', 'attr_value'=>'']
                     *                    ['custom'=>0, 'attr_name'=>'MPN', 'attr_value'=>'Does not apply']
                     *                    ...
                     * 打包格式：custom<$>attr_name<$>attr_value
                     */
                    $specs = $set['specifics'];
                    $specsCount = 0;
                    if (!empty($specs)) {
                        foreach ($specs as $spec) {
                            $data[$k][$i++] = $spec['custom'] . '<$>' . $spec['attr_name'] . '<$>';
                            if (!empty($spec['attr_value'])) {
                                $data[$k][$i - 1] .= is_array($spec['attr_value']) ? implode('<->',
                                    $spec['attr_value']) : $spec['attr_value'];
                            }
                            $specsCount++;
                        }
                    }
                    for (; $specsCount<50; $specsCount++) { //最多30个
                        $data[$k][$i++] = '';
                    }
                    /**************************specifics end***********************************************************/

                    //标题与价格
                    $data[$k][$i++] = $list['title'];//刊登标题
                    $data[$k][$i++] = $list['sub_title'];//刊登副标题
                    $data[$k][$i++] = $listingVar_cn['listingDuration'][$list['listing_duration']];//上架时间
                    $data[$k][$i++] = $list['vat_percent'];//VAT税率
                    $data[$k][$i++] = $listingVar_cn['boolType'][$list['best_offer']];//是否接受还价
                    $data[$k][$i++] = $set['auto_accept_price'];//自动交易价格
                    $data[$k][$i++] = $set['minimum_accept_price'];
                    $data[$k][$i++] = $listingVar_cn['boolType'][$list['private_listing']];

                    //sku设置
                    if ($list['variation']) {//多属性
                        foreach ($varians as $k1 => $varian) {
                            $data[$k][$i][] = $varian['v_sku'];
                            $data[$k][$i+1][] = $varian['combine_sku'];
                            /*打包属性图片路径。
                             *$varian['path']: array
                             *[['path'=>'/./**.jpg', 'base_url'=>'url'], [], ...]
                             *打包格式：base_urlpath
                             *          base_urlpath
                             */
                            $data[$k][$i+2][$k1] = '';
                            $paths = $varian['path'];
                            if (is_array($paths)) {
                                foreach ($paths as $value) {
                                    if (isset($value['base_url']) && isset($value['path'])) {
                                        $path = $value['base_url'].$value['path'];
                                    } else {
                                        $path = $value;
                                    }
                                    $data[$k][$i+2][$k1] .= $path."\n";
                                }
                            }
                            $data[$k][$i+3] = $set['variation_image'];
                            /**********************打包选中的属性   start**********************************************
                             *$varian['variation']: json
                             *"{"MPN": "Dose Not Apply", "Brand": "Dark Purple", "Bundle Listing": "1", "Country/Region of Manufacture": "Antarctica"}"
                             *打包格式：name-value
                             */
                            $checkedSpecs = json_decode($varian['variation']);
                            $checkedSpecsCount = 0;
                            foreach ($checkedSpecs as $name => $value) {
                                $data[$k][$i+4+$checkedSpecsCount][$k1] = $name.'<$>'.$value;
                                $checkedSpecsCount++;
                            }
                            for (; $checkedSpecsCount<10; $checkedSpecsCount++) {
                                $data[$k][$i+4+$checkedSpecsCount][$k1] = '';
                            }
                            /******************打包选中的属性 end******************************************************/
                            $data[$k][$i+14][] = $list['buy_it_nowprice'];
                            $data[$k][$i+15][] = $list['reserve_price'];
                            $data[$k][$i+16][] = $varian['v_price'];
                            $data[$k][$i+17][] = $varian['v_qty'];
                            $data[$k][$i+18][] = $varian['upc'];
                            $data[$k][$i+19][] = $varian['ean'];
                        }
                        $i += 20;
                    } else { //单属性
                        $data[$k][$i++] = $list['local_sku'];
                        $data[$k][$i++] = $list['sku'];
                        $data[$k][$i++] = '';
                        $data[$k][$i++] = '';
                        for ($j=0; $j<10; $j++) {
                            $data[$k][$i++] = '';
                        }
                        $data[$k][$i++] = $list['buy_it_nowprice'];
                        $data[$k][$i++] = $list['reserve_price'];
                        $data[$k][$i++] = $list['start_price'];
                        $data[$k][$i++] = $list['quantity'];
                        $data[$k][$i++] = '';
                        $data[$k][$i++] = '';
                    }

                    //图片与描述
                    $data[$k][$i++] = empty($list['mod_style']) ? '' : (EbayModelStyle::where(['id'=>$list['mod_style']])->value('model_name'));
                    $data[$k][$i++] = empty($list['mod_sale']) ? '' : (EbayModelSale::where(['id'=>$list['mod_sale']])->value('model_name'));
                    $data[$k][$i++] = '';
                    foreach ($imgs as $img) {
                        $data[$k][$i-1] .= $img."\n";
                    }
                    $data[$k][$i++] = '';
                    foreach ($detail_imgs as $detail_img) {
                        $data[$k][$i-1] .= $detail_img."\n";
                    }
                    $data[$k][$i++] = $set['mobile_desc'];
                    $data[$k][$i++] = $set['description'];
                    $data[$k][$i++] = $listingVar_cn['pictureGallery'][$list['picture_gallery']];
                    $data[$k][$i++] = $list['listing_enhancement'];

                    //物流设置
                    $data[$k][$i++] = 0;//物流模板
                    $shippingCount = 0;
                    if (!empty($set['shipping'])) {
                        $shippings = $set['shipping'];
                        $shippings = empty($shippings) ? [] : $shippings;
                        foreach ($shippings as $shipping) {
                            $data[$k][$i++] = $shipping['shipping_service'];
                            $data[$k][$i++] = $shipping['shipping_service_cost'];
                            $data[$k][$i++] = isset($shipping['shipping_service_additional_cost']) ? $shipping['shipping_service_additional_cost'] : 0;
                            $data[$k][$i++] = isset($shipping['extra_cost']) ? $shipping['extra_cost'] : 0;
                            $shippingCount++;
                        }
//                        $i += 4;
                    }
                    for (; $shippingCount<4; $shippingCount++) {
                        $data[$k][$i++] = '';
                        $data[$k][$i++] = '';
                        $data[$k][$i++] = '';
                        $data[$k][$i++] = '';
                    }
                    $internationalShippingCnt = 0;
                    if (!empty($set['international_shipping'])) {
                        $internationalShippings = $set['international_shipping'];
                        $internationalShippings = empty($internationalShippings) ? [] : $internationalShippings;
                        foreach ($internationalShippings as $internationalShipping) {
                            $data[$k][$i++] = $internationalShipping['shipping_service'];
                            $data[$k][$i++] = $internationalShipping['shipping_service_cost'];
                            $data[$k][$i++] = isset($internationalShipping['shipping_service_additional_cost']) ? $internationalShipping['shipping_service_additional_cost'] : 0;
                            $tmp = $internationalShipping['shiptolocation'];
                            $data[$k][$i++] = is_array($tmp) ? implode(',', $tmp) : $tmp;
                            $internationalShippingCnt++;
                        }
//                        $i += 4;
                    }
                    for (; $internationalShippingCnt<5; $internationalShippingCnt++) {
                        $data[$k][$i++] = '';
                        $data[$k][$i++] = '';
                        $data[$k][$i++] = '';
                        $data[$k][$i++] = '';
                    }
                    $data[$k][$i++] = $list['dispatch_max_time']==0 ? '当天发货' : $list['dispatch_max_time'];
                    $data[$k][$i++] = $list['sales_tax_state'].':'.$list['sales_tax'];
                    $data[$k][$i++] = $listingVar_cn['boolType'][$list['shipping_tax']];
                    $data[$k][$i++] = $listingVar_cn['boolType'][$set['local_pickup']];

                    //不运送地区
                    $data[$k][$i++] = '';
                    $data[$k][$i++] = empty($set['exclude_location']) ? '' : $set['exclude_location'];

                    //商品所在地
                    $data[$k][$i++] =  '';
                    $data[$k][$i++] = $list['location'];
                    $data[$k][$i++] = $list['country'];
                    $data[$k][$i++] = $set['postal_code'];

                    //退货
                    $data[$k][$i++] = '';
                    $data[$k][$i++] = $listingVar_cn['returnPolicy'][$set['return_policy']];
                    $data[$k][$i++] = $set['return_type'];
                    $data[$k][$i++] = $listingVar_cn['returnTime'][$list['return_time']];
                    $data[$k][$i++] = $listingVar_cn['boolType'][$set['extended_holiday']];
                    $data[$k][$i++] = empty($set['return_shipping_option']) ? 'Buyer' :
                        $listingVar_cn['returnShippingOption'][$set['return_shipping_option']];
                    $data[$k][$i++] = empty($set['restocking_fee_code']) ? 'NO' :
                        $listingVar_cn['restockingFeeCode'][$set['restocking_fee_code']];
                    $data[$k][$i++] = $set['return_description'];

                    //买家限制
                    $data[$k][$i++] = '';
                    $data[$k][$i++] = $listingVar_cn['boolType'][$list['disable_buyer']];
                    /*****************************打包买家限制详情 start***********************************************/
                    $buyer_requirment_details = $set['buyer_requirment_details'];
                    if (!empty($buyer_requirment_details)) {
                        $buyer_requirment_details = $buyer_requirment_details[0]??$buyer_requirment_details;
                        $tmpDisableDetails = 'link_paypal:';
                        if (isset($buyer_requirment_details['link_paypal'])) {
                            $tmpDisableDetails .= intval($buyer_requirment_details['link_paypal']);
                        }
                        $tmpDisableDetails .= "\n";
                        $tmpDisableDetails .= 'registration:';
                        if (isset($buyer_requirment_details['registration'])) {
                            $tmpDisableDetails .= intval($buyer_requirment_details['registration']);
                        }
                        $tmpDisableDetails .= "\n";
                        $tmpDisableDetails .= 'strikes:';
                        if (isset($buyer_requirment_details['strikes']) && $buyer_requirment_details['strikes'] == 1) {
                            $tmpDisableDetails .= $buyer_requirment_details['strikes_count'] . ',' . substr($buyer_requirment_details['strikes_period'], 5);
                        }
                        $tmpDisableDetails .= "\n";
                        $tmpDisableDetails .= 'violations:';
                        if (isset($buyer_requirment_details['violations']) && $buyer_requirment_details['violations'] == 1) {
                            $tmpDisableDetails .= $buyer_requirment_details['violations_count'] . ',' . substr($buyer_requirment_details['violations_period'], 5);
                        }
                        $tmpDisableDetails .= "\n";
                        $tmpDisableDetails .= 'credit:';
                        if (isset($buyer_requirment_details['credit']) && $buyer_requirment_details['credit'] == 1) {
                            $tmpDisableDetails .= $buyer_requirment_details['requirements_feedback_score'];
                        }
                        $tmpDisableDetails .= "\n";
                        $tmpDisableDetails .= 'requirements:';
                        if (isset($buyer_requirment_details['requirements']) && $buyer_requirment_details['requirements'] == 1) {
                            $tmpDisableDetails .= $buyer_requirment_details['requirements_max_count'];
                        }
                        $tmpDisableDetails .= "\n";
                        $tmpDisableDetails .= 'minimum_feedback:';
                        if (isset($buyer_requirment_details['minimum_feedback']) && $buyer_requirment_details['minimum_feedback'] == 1) {
                            $tmpDisableDetails .= $buyer_requirment_details['minimum_feedback_score'];
                        }
                    }
                    /**************************************打包买家限制详情 end****************************************/
                    $data[$k][$i++] = empty($tmpDisableDetails) ? '' : $tmpDisableDetails;

                    //补充与重上
                    $data[$k][$i++] = $listingVar_cn['boolType'][$list['replen']];
                    $data[$k][$i++] = $listingVar_cn['boolType'][$list['restart']];
                    $data[$k][$i++] = $set['restart_rule'] == 5 ? '5'.$set['restart_count'] : $set['restart_rule'];
                    $data[$k][$i++] = date('H:i', $set['restart_time']);

                    //收款选项
                    $data[$k][$i++] = '';
                    $data[$k][$i++] = $list['paypal_emailaddress'];
                    $paymentMethod = $set['payment_method'];
                    $data[$k][$i++] = count(empty($paymentMethod) ? ['PayPal'] : $paymentMethod) > 1 ? implode(',', $set['payment_method']) : $paymentMethod[0];
                    $data[$k][$i++] = $listingVar_cn['boolType'][$list['autopay']];
                    $data[$k][$i++] = $set['payment_instructions'];

                    //其他
                    $data[$k][$i++] = $list['model_cate'];
                    $data[$k][$i++] = $list['listing_cate'];
                }
            } else {
                $title = ['Parent Unique ID','*Product Name','Description','*Tags','*Unique ID','Color','Size','*Quantity','*Price','MSRP','*Shipping',
                    'Shipping Time(enter without " ", just the estimated days )','Shipping Weight','Shipping Length','Shipping Width','Shipping Height',
                    'HS Code','*Product Main Image URL','Variant Main Image URL','Extra Image URL','Extra Image URL 1','Extra Image URL 2','Extra Image URL 3',
                    'Extra Image URL 4','Extra Image URL 5','Extra Image URL 6','Extra Image URL 7','Extra Image URL 8','Extra Image URL 9','Extra Image URL 10'];
                $i=0;
                for ($j = 0; $j < count($idArr); $j++) {
                    $draft = EbayListing::get(['item_id'=>$idArr[$j]]);
                    $draftInfo = $this->getListingInfo($draft->id);
                    if(!empty($draftInfo['varians'])) {//多属性
                        foreach($draftInfo['varians'] as $varian) {
                            $data[$i]['puid'] = $draftInfo['list']['listing_sku'];
                            $data[$i]['pName'] = $draftInfo['list']['title'];
                            $data[$i]['description'] = $draftInfo['set']['description'];
                            $data[$i]['tags'] = '';
                            $data[$i]['uid'] = $varian['v_sku'];
                            $vars = json_decode($varian['variation'],true);
                            $data[$i]['color'] = '';
                            foreach($vars as $key=>$value){
                                if($key=='color'|| $key=='colour'){
                                    $data[$i]['color'] = $value;
                                }else{
                                    $data[$i]['size'] = empty($data[$i]['size'])?$value:$data[$i]['size'].'-'.$value;
                                }
                            }
                            $data[$i]['quantity'] = $varian['v_qty'];
                            $data[$i]['price'] = $varian['v_price'];
                            $data[$i]['msrp'] = '';
                            $data[$i]['shipping'] = '';
                            $data[$i]['shipping_time'] = $draftInfo['list']['dispatch_max_time']==0?'当天发货':$draftInfo['list']['dispatch_max_time'];
                            if(isset($draftInfo['goods_info']['weight'])){
                                $data[$i]['shipping_weight'] = $draftInfo['goods_info']['weight'];
                                $data[$i]['shipping_length'] = $draftInfo['goods_info']['depth'];
                                $data[$i]['shipping_width'] = $draftInfo['goods_info']['width'];
                                $data[$i]['shipping_height'] = $draftInfo['goods_info']['height'];
                                $data[$i]['hs_code'] = $draftInfo['goods_info']['hs_code'];
                            }else {

                                $data[$i]['shipping_weight'] = '';
                                $data[$i]['shipping_length'] = '';
                                $data[$i]['shipping_width'] = '';
                                $data[$i]['shipping_height'] = '';
                                $data[$i]['hs_code'] = '';
                            }
                            $data[$i]['main_picture'] = '';
                            if(!empty($draftInfo['imgs'])){
                                $data[$i]['main_picture'] = $draftInfo['imgs'][0]??'';
                            }
                            $data[$i]['var_img'] = '';
                            if(!empty($varian['path'])) {
                                $var_imgs = json_decode($varian['path'], true);
                                $data[$i]['var_img'] = isset($var_imgs[0]['base_url']) ?
                                    ($var_imgs[0]['base_url'].$var_imgs[0]['path']) : $var_imgs[0];
                            }
                            $data[$i]['extra_img_0'] = '';
                            $data[$i]['extra_img_1'] = '';
                            $data[$i]['extra_img_2'] = '';
                            $data[$i]['extra_img_3'] = '';
                            $data[$i]['extra_img_4'] = '';
                            $data[$i]['extra_img_5'] = '';
                            $data[$i]['extra_img_6'] = '';
                            $data[$i]['extra_img_7'] = '';
                            $data[$i]['extra_img_8'] = '';
                            $data[$i]['extra_img_9'] = '';
                            $data[$i]['extra_img_10'] = '';
                            for($m=1;$m<count($draftInfo['imgs']);$m++){
                                $data[$i]['extra_img_'.($m-1)] = $draftInfo['imgs'][$m];
                            }
                            $i++;
                        }
                    }else{//单属性
                        $data[$i]['puid'] = $draftInfo['list']['listing_sku'];
                        $data[$i]['pName'] = $draftInfo['list']['title'];
                        $data[$i]['description'] = $draftInfo['set']['description'];
                        $data[$i]['tags'] = '';
                        $data[$i]['uid'] = $draftInfo['list']['sku'];
                        $data[$i]['color'] = '';
                        $data[$i]['size'] = '';
                        $data[$i]['quantity'] = $draftInfo['list']['quantity'];
                        $data[$i]['price'] = $draftInfo['list']['start_price'];
                        $data[$i]['msrp'] = '';
                        $data[$i]['shipping'] = '';
                        $data[$i]['shipping_time'] = $draftInfo['list']['dispatch_max_time'];
                        if(isset($draftInfo['goods_info']['weight'])){
                            $data[$i]['shipping_weight'] = $draftInfo['goods_info']['weight'];
                            $data[$i]['shipping_length'] = $draftInfo['goods_info']['depth'];
                            $data[$i]['shipping_width'] = $draftInfo['goods_info']['width'];
                            $data[$i]['shipping_height'] = $draftInfo['goods_info']['height'];
                            $data[$i]['hs_code'] = $draftInfo['goods_info']['hs_code'];
                        }else {
                            $data[$i]['shipping_weight'] = '';
                            $data[$i]['shipping_length'] = '';
                            $data[$i]['shipping_width'] = '';
                            $data[$i]['shipping_height'] = '';
                            $data[$i]['hs_code'] = '';
                        }
                        $data[$i]['main_picture'] = '';
                        if(!empty($draftInfo['imgs'])){
                            $data[$i]['main_picture'] = $draftInfo['imgs'][0];
                        }
                        $data[$i]['var_img'] = '';
                        $data[$i]['extra_img_0'] = '';
                        $data[$i]['extra_img_1'] = '';
                        $data[$i]['extra_img_2'] = '';
                        $data[$i]['extra_img_3'] = '';
                        $data[$i]['extra_img_4'] = '';
                        $data[$i]['extra_img_5'] = '';
                        $data[$i]['extra_img_6'] = '';
                        $data[$i]['extra_img_7'] = '';
                        $data[$i]['extra_img_8'] = '';
                        $data[$i]['extra_img_9'] = '';
                        $data[$i]['extra_img_10'] = '';
                        for($m=1;$m<count($draftInfo['imgs']);$m++){
                            $data[$i]['extra_img_'.($m-1)] = $draftInfo['imgs'][$m];
                        }
                        $i++;
                    }
                }
            }
            $log['file_code'] = date('YmdHis');
            $log['created_time'] = time();
            $filename = $log['file_code'];
            $log['download_file_name'] = $filename;
            $log['type'] = 'draft_export';
            $log['file_extionsion'] = 'xls';
            $log['saved_path'] = ROOT_PATH.'public/download/draft_export/'.$filename.'.'.$log['file_extionsion'];
            $filepath = './download/draft_export/'.$filename.'.'.$log['file_extionsion'];
            if($type==0) {
                ImportExport::excelExportWithSet($data, $masterTitle, $subTitle, $filename, $filepath);
            }else{
                ImportExport::excelExportSmallPlat($data, $title, $filename, $filepath);
            }
            (new LogExportDownloadFiles())->allowField(true)->isUpdate(false)->save($log);
            return ['file_code' => $log['file_code'], 'file_name' => $filename,'result'=>true];
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }


    /**
     * @param $filename
     * @return array
     * @throws Exception
     */
    public function importDraftInfo(string $filename) : array
    {
        $count = 0;
        $message = [];
        $variationMsg = [];
        try{
            $data = ImportExport::excelImportNew($filename);
            $fastImport = 0;//默认标准导入格式
            if (!isset($data[0][0])) {
                return ['count'=>0,'message'=>[[
                    'lineNum' =>0,
                    'errorMsg' => '文件解析失败，请检查文件格式是否正确']],
                ];
            }
            if(trim($data[0][0]) == '引用范本的ID'){
                $fastImport = 1;//快速导入格式
            }

            $listVarReverse = Constants::LISTVAR_RESERVE_CN;
            $assocData = [];
            if($fastImport == 0) {//标准导入模式
                //验证格式
                if (count($data[0]) != 172) {
                    return ['count'=>0,'message'=>[[
                        'lineNum' => 1,
                        'errorMsg' => '文件格式错误，正确的应该到FP列，请导出一份对比',
                    ]]];
                }
//                $pTitle = $data[0];

//                $corPTitle = ["一般信息",null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,"标题与价格",null,null,null,null,null,null,null,"SKU设置",null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,"图片与描述",null,null,null,null,null,null,null,"物流设置",null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,"不运送地区",null,"商品所在地",null,null,null,"退货",null,null,null,null,null,null,null,"买家限制",null,null,"补充与重上规则",null,null,null,"收款选项",null,null,null,null,"其他",null];
//                if (array_diff_assoc($pTitle,$corPTitle)) {
//                    return ['count'=>0,'message'=>[
//                        'lineNum' => 1,
//                        'errorMsg' => '文件第一行大标题错误，请检查文件格式是否正确']
//                    ];
//                }
//                $subTitle = $data[1];
//                $corSubTitle = ["范本id","本地SPU","范本名称","账号简称","平台站点","出售方式","sku属性","引用模块组合","刊登分类1","刊登分类2","商店分类1","商店分类2","UPC","EAN","订单商品是否关联","物品状况","物品状态描述","分类属性1","分类属性2","分类属性3","分类属性4","分类属性5","分类属性6","分类属性7","分类属性8","分类属性9","分类属性10","分类属性11","分类属性12","分类属性13","分类属性14","分类属性15","分类属性16","分类属性17","分类属性18","分类属性19","分类属性20","分类属性21","分类属性22","分类属性23","分类属性24","分类属性25","分类属性26","分类属性27","分类属性28","分类属性29","分类属性30","分类属性31","分类属性32","分类属性33","分类属性34","分类属性35","分类属性36","分类属性37","分类属性38","分类属性39","分类属性40","分类属性41","分类属性42","分类属性43","分类属性44","分类属性45","分类属性46","分类属性47","分类属性48","分类属性49","分类属性50","刊登标题","刊登副标题","上架时间","VAT税率(%)","是否接受买家还价","自动交易价格","自动拒绝价格","是否是私人物品","本地SKU","捆绑/打包销售","展图(最多12张)","图片关联","变体属性1","变体属性2","变体属性3","变体属性4","变体属性5","变体属性6","变体属性7","变体属性8","变体属性9","变体属性10","拍卖底价","保底拍卖价","一口价/销售价","可售数","UPC","EAN","刊登风格","销售说明模板","刊登图片","详情描述图片","移动端描述","详情描述","橱窗展示(Gallery)图片","样式","引用模板","国内运输方式1","首件运费","续件运费","AK,HI,PR额外收费","国内运输方式2","首件运费","续件运费","AK,HI,PR额外收费","国内运输方式3","首件运费","续件运费","AK,HI,PR额外收费","国内运输方式4","首件运费","续件运费","AK,HI,PR额外收费","国际运输方式1","首件运费","续件运费","可运至的国家","国际运输方式2","首件运费","续件运费","可运至的国家","国际运输方式3","首件运费","续件运费","可运至的国家","国际运输方式4","首件运费","续件运费","可运至的国家","国际运输方式5","首件运费","续件运费","可运至的国家","备货时间","销售税","运费是否包含销售税","是否上门提货","引用模板","不运送地区","引用模板","商品所在地","国家","邮编","引用模板","接受退货","接受退货方式","接受退货周期","节假日是否延期退货","退货邮费承担","折旧费","退货政策详情说明","引用模板","是否开启买家限制","买家具体限制","是否开启自动补货","是否开启自动重上","重上规则","重上时间","引用模板","Paypal收款账号","收款方式","立即付款","付款说明","范本分类","listing分类",];
//                if (array_diff_assoc($subTitle,$corSubTitle)) {
//                    return ['count'=>0,'message'=>[
//                        'lineNum' => 2,
//                        'errorMsg' => '文件第二行子标题错误，请检查文件格式是否正确']
//                    ];
//                }


                $draftData = [];
                $keys = [
                    //一般信息 'variation','set_upc',
                    'id','spu', 'draft_name', 'account_name', 'site', 'listing_type', 'list_variation', 'comb_model_id', 'primary_categoryid', 'second_categoryid',
                    'store_category_id', 'store_category2_id',  'upc', 'ean', 'assoc_order', 'condition_id', 'condition_description', 'attr_0', 'attr_1', 'attr_2', 'attr_3', 'attr_4', 'attr_5',
                    'attr_6', 'attr_7', 'attr_8', 'attr_9', 'attr_10', 'attr_11', 'attr_12', 'attr_13', 'attr_14', 'attr_15', 'attr_16', 'attr_17', 'attr_18', 'attr_19',
                    'attr_20', 'attr_21', 'attr_22', 'attr_23', 'attr_24', 'attr_25', 'attr_26', 'attr_27', 'attr_28', 'attr_29','attr_30','attr_31','attr_32','attr_33','attr_34',
                    'attr_35','attr_36','attr_37','attr_38','attr_39','attr_40','attr_41','attr_42','attr_43','attr_44','attr_45','attr_46','attr_47','attr_48','attr_49',
                    //标题与价格'quantity',
                    'title', 'sub_title', 'listing_duration', 'vat_percent', 'best_offer', 'auto_accept_price',
                    'minimum_accept_price', 'private_listing',
                    //sku设置
                    'v_sku', 'combine_sku', 'sku_imgs', 'variation_image','sku_attr_0', 'sku_attr_1', 'sku_attr_2', 'sku_attr_3', 'sku_attr_4', 'sku_attr_5', 'sku_attr_6', 'sku_attr_7',
                    'sku_attr_8', 'sku_attr_9','buy_it_nowprice', 'reserve_price', 'start_price', 'v_qty', 'v_upc', 'v_ean',
                    //图片与描述
                    'mod_style', 'mod_sale', 'imgs', 'detail_imgs', 'mobile_desc', 'description', 'picture_gallery', 'listing_enhancement',
                    //物流设置
                    'trans_template', 'shipping_service_0', 'shipping_service_cost_0', 'shipping_service_additional_cost_0', 'extra_cost_0', 'shipping_service_1',
                    'shipping_service_cost_1', 'shipping_service_additional_cost_1', 'extra_cost_1', 'shipping_service_2', 'shipping_service_cost_2', 'shipping_service_additional_cost_2',
                    'extra_cost_2', 'shipping_service_3', 'shipping_service_cost_3', 'shipping_service_additional_cost_3', 'extra_cost_3', 'inter_shipping_service_0',
                    'inter_shipping_service_cost_0', 'inter_shipping_service_additional_cost_0', 'inter_shiptolocation_0', 'inter_shipping_service_1', 'inter_shipping_service_cost_1',
                    'inter_shipping_service_additional_cost_1', 'inter_shiptolocation_1', 'inter_shipping_service_2', 'inter_shipping_service_cost_2', 'inter_shipping_service_additional_cost_2',
                    'inter_shiptolocation_2', 'inter_shipping_service_3', 'inter_shipping_service_cost_3', 'inter_shipping_service_additional_cost_3', 'inter_shiptolocation_3',
                    'inter_shipping_service_4', 'inter_shipping_service_cost_4', 'inter_shipping_service_additional_cost_4', 'inter_shiptolocation_4', 'dispatch_max_time',
                    'sales_tax2', 'shipping_tax', 'local_pickup',
                    //不运送地区
                    'exclude_template', 'exclude_location',
                    //商品所在地
                    'location_template', 'location', 'country', 'postal_code',
                    //退货
                    'return_template', 'return_policy', 'return_type', 'return_time', 'extended_holiday', 'return_shipping_option', 'restocking_fee_code', 'return_description',
                    //买家限制
                    'buyer_limit_template', 'disable_buyer', 'buyer_requirment_details',
                    //补充重上规则
                    'replen', 'restart', 'restart_rule', 'restart_time',
                    //收款选项
                    'pay_template', 'paypal_emailaddress', 'payment_method', 'autopay', 'payment_instructions',
                    //其他
                    'model_cate', 'listing_cate'];
                $skuStart = 0;
                $skuEnd = 0;
                $realIndex = 0;
                foreach ($keys as $key => $value) {
                    if ($value == 'v_sku') {
                        $skuStart = $key;//记录多属性产品偏移量起始位置
                    }
                    if ($value == 'v_ean') {
                        $skuEnd = $key;//记录多属性产品偏移量结束位置
                        break;
                    }
                }
                array_shift($data);
                array_shift($data);//前两行是标题，移除掉
                //将数组转换成关联数组，方便后面处理
                $indexRow = [];//记录条数与行数对应关系
                $listingNum = 0;
                for ($i = 0; $i < count($data); $i++) {//循环行
                    if ($i ==0 && empty($data[$i][1])) {
                        return ['count'=>0,'message'=>[[
                            'lineNum' => $i+3,
                            'errorMsg' => '缺少SPU，请检查文件修复后重试'],
                        ]];
                    }
                    if (empty($data[$i][0]) && empty($data[$i][1]) && empty($data[$i][$skuStart])) {
                        return ['count'=>0,'message'=>[[
                            'lineNum' => $i+3,
                            'errorMsg' => '检测到文件共有'.(count($data)+2).'行，但是第'.($i+3).'行没有SPU，也没有本地SKU，请检查是否有垃圾数据，修改后重试'],
                        ]];
                    }
                    if (empty($data[$i][0]) && empty($data[$i][1])) {
                        //如果为空,说明是多属性产品，直接偏移到对应位置获取，转换成一行
                        for ($j = $skuStart; $j < $skuEnd + 1; $j++) {
                            if (is_null($assocData[$realIndex][$keys[$j]])) {

                                $assocData[$realIndex][$keys[$j]] = is_null($data[$i][$j])? '':$data[$i][$j];
                            } else {

                                $assocData[$realIndex][$keys[$j]] .= is_null($data[$i][$j]) ? '' : '<|>' . $data[$i][$j];
                            }
                        }
                        continue;
                    }
                    $assocData[$i] = array_combine($keys,$data[$i]);
                    $realIndex = $i;//记录此次的键名
                    $indexRow[$listingNum++] = $i;
                }
                //打包处理数据
                $tipIndex = 0;

                foreach ($assocData as $rowIndex => $item) {
                    $rowNumber = $rowIndex+3;//删除了2行标题，索引是从0开始的，所以真实的行数要+3
                    $tipIndex++;
                    $list = [];
                    $set = [];
                    //一般信息*********************************************
                    if (!empty($item['id'])) {
                        $oldDraft = EbayListing::get(intval($item['id']));
                        if (!empty($oldDraft)) {
                            $list['id'] = intval($item['id']);
                            $oldListId = $list['id'];
                        }
                    }
                    $list['spu'] = trim($item['spu']);
                    $spuInfo = ModelGoods::get(['spu'=>$list['spu']]);//本地spu
                    if(empty($spuInfo)){
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '根据SPU获取商品信息失败,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }

                    if (!isset($item['account_name'])) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '账号简称无法获取，请检查文件格式及对应项是否正确',
                        ];
                        continue;
                    }
                    //账号，账号简称区分大小写，会有yunt,YUNT这种形式的账号，要过滤
                    $accounts = (new EbayAccount())->where(['code' => trim($item['account_name'])])->select();
                    foreach ($accounts as $account) {
                        if ($account['code'] == trim($item['account_name'])) {
                            $accountInfo = $account;
                        }
                    }
                    if(empty($accountInfo)){
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '平台账号有误,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $list['account_id'] = $accountInfo->id;//平台账号

                    if (!$this->helper->isAccountBelongsToUser($list['account_id'], $this->userId)) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '用户和平台账号未关联,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    if (isset($list['id']) && $list['account_id'] != $oldDraft->account_id) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '为了防止误更新他人的listing,现在不允许通过导入更新账号，请检查是否带了范本id进行了更新',
                        ];
                        continue;
                    }



                    $site = EbaySite::get(['name' => ['like', trim($item['site'])]]);
                    if(empty($site)){
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '获取站点信息失败,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }

                    $list['site'] = $site->siteid;//站点id
                    $list['currency'] = $site->currency;//站点货币
                    //优先级顺序：表格>单独模板>组合模板
                    //初始化模板
                    $list['comb_model_id'] = 0;//组合模板id
                    $list['mod_promotion'] = 0;//促销策略模板id
                    $list['mod_style'] = 0;//刊登风格模板id
                    $list['mod_sale'] = 0;//刊登说明模板id
                    $list['mod_trans'] = 0;//物流模板id
                    $list['mod_exclude'] = 0;//不送达地区模板id
                    $list['mod_choice'] = 0;//备货模板id
                    $list['mod_pickup'] = 0;//自提模板id
                    $list['mod_location'] = 0;//所在地模板id
                    $list['mod_galley'] = 0;//橱窗模板id
                    $list['mod_individual'] = 0;//私人物品模板id
                    $list['mod_refuse'] = 0;//买家限制模板id
                    $list['mod_receivables'] = 0;//收款模板模板id
                    $list['mod_return'] = 0;//退货模板id
                    $list['mod_bargaining'] = 0;//议价模板id
                    $list['mod_quantity'] = 0;//数量模板id
                    /***********************************模板解析 start*************************************************/
                    $models = ['promotion'=>[], 'trans'=>[], 'exclude'=>[], 'choice'=>[], 'pickup'=>[], 'location'=>[],
                        'gallery'=>[], 'individual'=>[], 'refuse'=>[], 'receivables'=>[], 'return'=>[],
                        'bargaining'=>[], 'quantity'=>[]];
                    //先检测是否使用了组合模板
                    if(!empty($item['comb_model_id'])) {
                        $models = $this->helper->parseCombModel(0, trim($item['comb_model_id']));
                        if (empty($combInfo)) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '填写了组合模块名称，但是获取信息失败,请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $list['comb_model_id'] = $models['comb']['id'];
                        $list['promotion_id'] = $models['comb']['promotion'];//促销策略
                        $list['mod_style'] = $models['comb']['style'];//刊登风格
                        $list['mod_sale'] = $models['comb']['sale'];//刊登说明
                        $list['mod_trans'] = $models['comb']['trans'];//物流
                        $list['mod_exclude'] = $models['comb']['exclude'];//不送达地区
                        $list['mod_choice'] = $models['comb']['choice'];//备货
                        $list['mod_pickup'] = $models['comb']['pickup'];//自提
                        $list['mod_location'] = $models['comb']['location'];//所在地
                        $list['mod_galley'] = $models['comb']['gallery'];//橱窗
                        $list['mod_individual'] = $models['comb']['individual'];//私人物品
                        $list['mod_refuse'] = $models['comb']['refuse'];//买家限制
                        $list['mod_receivables'] = $models['comb']['receivables'];//收款模板
                        $list['mod_return'] = $models['comb']['returngoods'];//退货
                        $list['mod_bargaining'] = $models['comb']['bargaining'];//议价
                        $list['mod_quantity'] = $models['comb']['quantity'];//数量
                    }
                    //物流模板
                    if (!empty($item['trans_template'])) {
                        $models['trans'] = $this->helper->parseTransCommon(0, trim($item['trans_template']));
                        !empty($models['trans']) && $list['mod_trans'] = $models['trans']['id'];//物流模板覆盖组合模板里面的物流设置
                    }

                    //不运送地区模板
                    if (!empty($item['exclude_template'])) {
                        $models['exclude'] = $this->helper->parseExcludeCommon(0, trim($item['exclude_template']));
                        !empty($models['exclude']) && $list['mod_exclude'] = $models['exclude']['id'];
                    }

                    //物品所在地模板
                    if (!empty($item['location_template'])) {
                        $models['location'] = $this->helper->parseLocationCommon(0, trim($item['location_template']));
                        !empty($models['location']) && $list['mod_location'] = $models['location']['id'];
                    }
                    //退货模板
                    if (!empty($item['return_template'])) {
                        $models['return'] = $this->helper->parseReturnCommon(0, trim($item['return_template']));
                        !empty($models['return']) && $list['mod_return'] = $models['return']['id'];
                    }
                    //是否使用了买家限制模板
                    $list['disable_buyer'] =  0;
                    if (!empty($item['buyer_limit_template'])) {
                        $models['refuse'] = $this->helper->parseRefuseCommon(0, trim($item['buyer_limit_template']));
                        !empty($models['refuse']) && $list['mod_refuse'] = $models['refuse']['id'];
                        !empty($models['refuse']) && $list['disable_buyer'] = $models['refuse']['refuse'];
                    }

                    //是否使用了收款模板
                    if (!empty($item['pay_template'])) {
                        $models['receivables'] = $this->helper->parseReceivablesCommon(0, trim($item['pay_template']));
                        !empty($models['receivables']) && $list['mod_receivables'] = $models['receivables']['id'];
                    }
                    /***************************************解析模板  end**********************************************/
                    $list['goods_id'] = $spuInfo->id;
                    $list['goods_type'] = empty($item['combine_sku']) ? 0 : 1;//是否捆绑销售
                    $list['promotion_id'] = 0;//促销策略
                    if(!empty($item['location'])){
                        $list['mod_location'] = 0;
                        $list['location'] = trim($item['location']);
                    }else{
                        $list['location'] = isset($models['location']['location']) ? $models['location']['location'] : '';//物品所在地
                    }

                    if(!empty($item['country'])){
                        $list['mod_location'] = 0;
                        $list['country'] = trim($item['country']);
                    }else{
                        $list['country'] = isset($models['location']['country']) ? $models['location']['country'] : '';//物品所在地国家代码
                    }
                    if (!in_array(trim($item['assoc_order']), ['是', '否'])) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【订单商品是否关联】项填写错误，只能填写是或否,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $list['assoc_order'] = empty($item['assoc_order']) ? 1 : $listVarReverse['boolType'][trim($item['assoc_order'])];
                    if (!isset($listVarReverse['variation'][trim($item['list_variation'])])) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【sku属性】项填写错误,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }

                    $list['variation'] = $listVarReverse['variation'][trim($item['list_variation'])];
                    $variationChanged = 0;

                    $list['primary_categoryid'] = intval(trim($item['primary_categoryid']));//刊登分类1
                    $categorySite = (new EbayCategory())->where(['category_id' => $list['primary_categoryid'], 'site' => $list['site']])->find();
                    if (!empty($list['primary_categoryid'])) {
                        if (empty($categorySite)) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '第一分类与站点不匹配,请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                    }
                    if ($list['variation'] && $categorySite->variations_enabled == 0) {//新站点分类不支持多属性
//                        $list['variation'] = 1;
//                        $skus = explode('<|>', $item['v_sku']);
//                        $list['local_sku'] = $skus[0];//取第一个
//                        $combineSkus = explode('<|>', $item['combine_sku']);
//                        $list['sku'] = $combineSkus[0];//取第一个
//                        $variationChanged = 1;//sku被强制改变了
//                        $variationMsg[] = 'spu:'.$list['spu'].',新站点分类不支持多属性，请注意。';
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '新站点分类不支持多属性。',
                        ];
                        continue;
                    } else {
                        $list['local_sku'] = $list['variation'] ? '' : trim($item['v_sku']);//本地sku不论单属性多属性必填
                        if (strpos($list['local_sku'],'<|>') !== false) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '本地SKU无法被正确解析，请检查是否将【SKU属性】项填写成了单属性或文件格式是否有错'
                            ];
                            continue;
                        }
                        $list['sku'] = $list['variation'] ? '' : trim($item['combine_sku']);
                    }

                    $list['second_categoryid'] = empty($item['second_categoryid']) ? 0 : intval(trim($item['second_categoryid']));
                    if (!empty($list['second_categoryid'])) {
                        $categorySite = (new EbayCategory())->where(['category_id' => $list['second_categoryid'], 'site' => $list['site']])->find();
                        if (empty($categorySite)) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '第二分类与站点不匹配,请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                    }
                    $list['store_category_id'] = empty($item['store_category_id']) ? 0 : intval(trim($item['store_category_id']));
                    $list['store_category2_id'] = empty($item['store_category2_id']) ? 0 : intval(trim($item['store_category2_id']));


                    if (trim($item['paypal_emailaddress']) == '自动识别') {
                        //获取商品价格
                        if ($list['variation']) {//多属性
                            $prices = explode('<|>', $item['start_price']);
                            $price = number_format(floatval(trim($prices[0])), 2);
                        } else {//单属性
                            $price = number_format(floatval(trim($item['start_price'])), 2);
                        }
                        $ppe = $this->publishHelper->autoAdaptPaypal($list['account_id'], $list['site'], $price, $this->userId);
                        if (!$ppe) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => 'paypal账号信息获取失败,请确认账号绑定了paypal，并检查文件格式及对应项是否正确）',
                            ];
                            continue;
                        }
                        $list['paypal_emailaddress'] = $ppe;

                    } else {
                        $list['paypal_emailaddress'] = trim($item['paypal_emailaddress']);//付款账号
                        $paypal = PaypalAccount::get(['account_name'=>$list['paypal_emailaddress']]);
                        if (empty($paypal)) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => 'paypal账号信息获取失败,请检查文件格式及对应项是否正确）'
                            ];
                            continue;
                        }
                        $minPaypals = json_decode($accountInfo->min_paypal_id, true);
                        $maxPaypals = json_decode($accountInfo->max_paypal_id, true);
                        $minPaypals = empty($minPaypals) ? [] : $minPaypals;
                        $maxPaypals = empty($maxPaypals) ? [] : $maxPaypals;
                        $paypalIds = [];
                        foreach ($minPaypals as $minPaypal) {
                            $paypalIds[] = $minPaypal['id'];
                        }
                        foreach ($maxPaypals as $maxPaypal) {
                            $paypalIds[] = $maxPaypal['id'];
                        }
                        if (!in_array($paypal->id, $paypalIds)) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => 'paypal账号与eBay账号不匹配,请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }


                    }



                    //总库存
                    $list['quantity'] = 0;
                    if(!empty($item['v_qty']) && $list['variation']==0){
                        $list['mod_quantity'] = 0;
                        if ($variationChanged) {
                            $qty = explode('<|>', $item['v_qty']);
                            $list['quantity'] = $qty[0];
                        } else {
                            $list['quantity'] = intval(trim($item['v_qty']));
                        }
                    }else if ($list['variation']==0) {
                        $list['quantity'] = isset($models['quantity']['quantity']) ? $models['quantity']['quantity'] : 0;
                    }

                    if (!empty($item['best_offer'])) {
                        $list['mod_bargaining'] = 0;
                        if (!in_array(trim($item['best_offer']), ['是', '否'])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【是否接受买家还价】项填写错误，只能填写是或否,请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $list['best_offer'] = $listVarReverse['boolType'][trim($item['best_offer'])];

                    }else{
                        $list['best_offer'] =  isset($models['bargaining']['best_offer']) ? $models['bargaining']['best_offer'] : 0;//是否接受议价
                    }
                    $list['buy_it_nowprice'] = empty($item['buy_it_nowprice']) ? 0.00 : number_format(floatval(trim($item['buy_it_nowprice'])), 2);//拍卖底价
                    $list['reserve_price'] = empty($item['reserve_price']) ? 0.00 : number_format(floatval(trim($item['reserve_price'])), 2);//保底拍卖价

                    if ($list['variation'] == 0) {
//                        if ($variationChanged) {
//                            $startPirces = explode('<|>', $item['start_price']);
//                            $startPirce = $startPirces[0];
//                        } else {
                        $startPirce = trim($item['start_price']);
//                        }
                        if (preg_match('/^\d+(\.?\d+)?$/',$startPirce)===0) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【一口价/销售价】项填写有误，不能使用表格公式，请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $list['start_price'] = number_format(floatval($startPirce), 2);
                    }

                    if (!in_array(trim($item['listing_type']), ['一口价', '拍卖'])) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【出售方式】项填写错误,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $list['listing_type'] = $listVarReverse['listingType'][trim($item['listing_type'])];//出售方式

                    $tmp_img = explode("\n", $item['imgs']);
                    if (!isset($tmp_img[0])) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【刊登图片】项填写错误,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $list['img'] = $tmp_img[0];//主图第一张
                    $list['title'] = trim($item['title']);//刊登标题
                    if (mb_strlen($list['title']) > 80) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【刊登标题】项超过了80个字符,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $list['sub_title'] = trim($item['sub_title']);//副标题
                    if (mb_strlen($list['sub_title']) > 55) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【刊登副标题】项超过了55个字符,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $list['vat_percent'] = number_format(floatval(trim($item['vat_percent'])),2);//vat税
                    $list['sales_tax'] = '';
                    $list['sales_tax_state'] = '';
                    if (!empty($item['sales_tax2'])) {
                        $sale_ar = explode(':', trim($item['sales_tax2']));
                        if(!isset($sale_ar[0]) || !isset($sale_ar[1])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【销售税】项填写有误，请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $list['sales_tax'] = number_format(floatval($sale_ar[1]),2);
                        $list['sales_tax_state'] = $sale_ar[0];
                    }
                    if (!empty($item['shipping_tax']) && !in_array(trim($item['shipping_tax']), ['是', '否'])) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【运费是否包含销售税】项填写错误，只能填写是或否,请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $list['shipping_tax'] = empty($item['shipping_tax']) ? 0 : $listVarReverse['boolType'][trim($item['shipping_tax'])];//运费是否含税

                    //备货时间
                    if(!empty($item['dispatch_max_time'])){
                        $list['mod_choice'] = 0;
                        $list['dispatch_max_time'] = trim($item['dispatch_max_time'])=='当天发货' ? 0 : intval(trim($item['dispatch_max_time']));
                    }else {
                        $list['dispatch_max_time'] = isset($models['choice']['choice_date']) ? $models['choice']['choice_date'] : 0;
                    }
                    //退货周期
                    if(!empty($item['return_time'])){
                        $list['mod_return'] = 0;
                        if (!isset($listVarReverse['returnTime'][trim($item['return_time'])])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【【接受退货周期】项填写错误,请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $list['return_time'] = $listVarReverse['returnTime'][trim($item['return_time'])];
                    }else{
                        $list['return_time'] = isset($models['return']['return_time']) ?
                            $models['return']['return_time'] : 1;
                    }

                    //私人物品
                    if(!empty($item['private_listing'])){
                        $list['mod_individual'] = 0;
                        if (!in_array(trim($item['private_listing']), ['是', '否'])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【是否是私人物品】项填写错误，只能填写是或否,请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $list['private_listing'] = $listVarReverse['boolType'][trim($item['private_listing'])];
                    }else {
                        $list['private_listing'] = isset($models['individual']['individual_listing']) ?
                            $models['individual']['individual_listing'] : 0;
                    }

                    if (!empty($item['disable_buyer'])) {
                        $list['mod_refuse'] = 0;//取消使用模板
                        if (trim($item['disable_buyer']) == '是') {
                            $list['disable_buyer'] = 1;
                            $models['refuse']['detail'] = $this->helper->packBuyerRequirementDetail(trim($item['buyer_requirment_details']),
                                Constants::DATA_TO_STORE);
                        }
                    }
                    if(!empty($item['autopay'])){
                        $list['mod_receivables'] = 0;
                        if (!in_array(trim($item['autopay']), ['是', '否'])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【立即付款】项填写错误，只能填写是或否,请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $list['autopay'] = $listVarReverse['boolType'][trim($item['autopay'])];
                    }else{
                        $list['autopay'] = isset($models['receivables']['auto_pay']) ? $models['receivables']['auto_pay'] : 0;
                    }
                    if (!isset($listVarReverse['listingDuration'][trim($item['listing_duration'])])) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【上架时间】项填写错误，请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $list['listing_duration'] = $listVarReverse['listingDuration'][trim($item['listing_duration'])];
                    if(!empty($item['picture_gallery'])){
                        $list['mod_galley'] = 0;
                        if (!isset($listVarReverse['pictureGallery'][trim($item['picture_gallery'])])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【橱窗展示(Gallery)图片】项填写错误，请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $list['picture_gallery'] = $listVarReverse['pictureGallery'][trim($item['picture_gallery'])];
                    }else {
                        $list['picture_gallery'] = isset($models['gallery']['picture_gallery']) ? $models['gallery']['picture_gallery'] : 0;
                    }
                    if (!in_array(trim($item['replen']), ['是', '否'])) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【是否开启自动补货】项填写错误，只能填写是或否，请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $list['replen'] = empty($item['replen']) ? 0 : $listVarReverse['boolType'][trim($item['replen'])];
                    if (!in_array(trim($item['restart']), ['是', '否'])) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【是否开启自动重上】项填写错误，只能填写是或否，请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $list['restart'] = empty($item['restart']) ? 0 : $listVarReverse['boolType'][trim($item['restart'])];

                    $list['listing_cate'] = trim($item['listing_cate']);
                    $list['model_cate'] = trim($item['model_cate']);

                    if (!empty($item['mod_style'])) {
                        $modStyle = EbayModelStyle::where(['model_name' => trim($item['mod_style'])])->value('id');
                        $list['mod_style'] = empty($modStyle) ? $list['mod_style'] : $modStyle;
                    }

                    if(!empty($item['mod_sale'])){
                        $modSale = EbayModelSale::where(['model_name' => trim($item['mod_sale'])])->value('id');
                        $list['mod_sale'] = empty($modSale) ? $list['mod_sale'] : $modSale;
                    }

                    $list['draft'] = 1;
                    $list['draft_name'] = trim($item['draft_name']);


                    if(!empty($item['postal_code'])){
                        $list['mod_location'] = 0;
                        $set['postal_code'] = trim($item['postal_code']);
                    }else{
                        $set['postal_code'] = isset($models['location']['post_code']) ? $models['location']['post_code'] : '';//邮编
                    }
                    $set['upc'] = empty($item['upc']) ? 'Does not apply' : trim($item['upc']);
                    $set['ean'] = empty($item['ean']) ? 'Does not apply' : trim($item['ean']);

                    //自提
                    if(!empty($item['local_pickup'])){
                        $list['mod_pickup'] = 0;
                        if (!in_array(trim($item['local_pickup']), ['是', '否'])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【是否上门提货】项填写错误，只能填写是或否，请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $set['local_pickup'] = empty($item['local_pickup']) ? 0 : $listVarReverse['boolType'][trim($item['local_pickup'])];
                    }else {
                        $set['local_pickup'] = isset($models['pickup']['local_pickup']) ? $models['pickup']['local_pickup'] : 0;
                    }

                    if (!empty($list['mod_trans']) || !empty($item['exclude_location'])) {
                        $set['custom_exclude'] = 3;
                    } else if(trim($item['exclude_location']) == '运输至所有国家') {
                        $set['custom_exclude'] = 1;
                    } else {
                        $set['custom_exclude'] = 2;
                    }
                    if(!empty($item['exclude_location'])){
                        $list['mod_exclude'] = 0;
                        $set['exclude_location'] = trim($item['exclude_location']);
                    }else{
                        if (isset($models['exclude']['exclude'])) {
                            $exclude = json_decode($models['exclude']['exclude'], true);
                            if (is_array($exclude) && count($exclude)>1) {
                                $set['exclude_location'] = implode('，', $exclude);
                            } else {
                                $set['exclude_location'] = '';
                            }
                        } else {
                            $set['exclude_location'] = '';
                        }
                    }

                    if(!empty($item['payment_method'])){
                        $list['mod_receivables'] = 0;
                        $set['payment_method'] = explode(',', trim($item['payment_method']));
                    }else{
                        $set['payment_method'] = isset($models['receivables']['pay_method']) ? json_decode($models['receivables']['pay_method'], true) : 0;
                    }

                    if(!empty($item['payment_instructions'])){
                        $list['mod_receivables'] = 0;
                        $set['payment_instructions'] = trim($item['payment_instructions']);
                    }else{
                        $set['payment_instructions'] = isset($models['receivables']['payment_instructions']) ? $models['receivables']['payment_instructions'] : '';
                    }
                    if(!empty($item['return_policy'])){
                        $list['mod_return'] = 0;
                        if (!isset($listVarReverse['returnPolicy'][trim($item['return_policy'])])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【接受退货】项填写错误，请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $set['return_policy'] = $listVarReverse['returnPolicy'][trim($item['return_policy'])];
                    }else {
                        $set['return_policy'] = isset($models['return']['return_policy']) ? $models['return']['return_policy'] : 0;
                    }
                    if(!empty($item['return_type'])){
                        $list['mod_return'] = 0;
                        if (!in_array(trim($item['return_type']), ['MoneyBack','MoneyBackOrExchange','MoneyBackOrReplacement'])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【接受退货方式】项填写错误，请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $set['return_type'] = trim($item['return_type']);
                    }else {
                        $set['return_type'] = isset($models['return']['return_type']) ? $models['return']['return_type'] : 'Money Back';
                    }

                    if(!empty($item['extended_holiday'])){
                        $list['mod_return'] = 0;
                        if (!in_array(trim($item['extended_holiday']), ['是', '否'])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【节假日是否延期退货】项填写错误，只能填写是或否，请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $set['extended_holiday'] = $listVarReverse['boolType'][trim($item['extended_holiday'])];
                    }else {
                        $set['extended_holiday'] = isset($models['return']['extended_holiday']) ? $models['return']['extended_holiday'] : 1;
                    }

                    if(!empty($item['return_shipping_option'])){
                        $list['mod_return'] = 0;
                        if (!isset($listVarReverse['returnShippingOption'][trim($item['return_shipping_option'])])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【退货邮费承担】项填写错误，请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $set['return_shipping_option'] = $listVarReverse['returnShippingOption'][trim($item['return_shipping_option'])];
                    }else {
                        $set['return_shipping_option'] = isset($models['return']['return_shipping_option']) ?
                            $models['return']['return_shipping_option'] : 1;
                    }

                    if(!empty($item['restocking_fee_code'])){
                        $list['mod_return'] = 0;
                        if (!isset($listVarReverse['restockingFeeCode'][trim($item['restocking_fee_code'])])) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '【折旧费】项填写错误，请检查文件格式及对应项是否正确'
                            ];
                            continue;
                        }
                        $set['restocking_fee_code'] = $listVarReverse['restockingFeeCode'][trim($item['restocking_fee_code'])];
                    }else {
                        $set['restocking_fee_code'] = isset($models['return']['restocking_fee_code']) ?
                            $models['return']['restocking_fee_code'] : 0;
                    }

                    if(!empty($item['return_description'])){
                        $list['mod_return'] = 0;
                        $set['return_description'] = trim($item['return_description']);
                    }else {
                        $set['return_description'] = isset($models['return']['return_description']) ?
                            $models['return']['return_description'] : '';
                    }
                    $set['variation_image'] = trim($item['variation_image']);

                    $set['buyer_requirment_details'] = isset($models['refuse']['detail']) ? $models['refuse']['detail'] : [];

                    //属性set.specifics
                    $specIndex = 0;
                    $errorFlag = 0;
                    for ($i = 0; $i < 50; $i++) {
                        if (!empty($item['attr_' . $i])) {
                            $attr_ar = explode('<$>', trim($item['attr_' . $i]));
                            if (!isset($attr_ar[0]) || !isset($attr_ar[1])) {
                                $message[] = [
                                    'lineNum' =>$rowNumber,
                                    'errorMsg' => '【分类属性'.($i+1).'】项填写错误，请检查文件格式及对应项是否正确'
                                ];
                                $errorFlag = 1;
                                break;
                            }
                            $set['specifics'][$specIndex]['custom'] = intval($attr_ar[0]);
                            $set['specifics'][$specIndex]['attr_name'] = $attr_ar[1];
                            $set['specifics'][$specIndex]['attr_value'] = empty($attr_ar[2]) ? '' : (strpos($attr_ar[2],'<->')!==false ? explode("<->",$attr_ar[2]) : $attr_ar[2]);
                            $specIndex++;
                            continue;
                        }
                        break;
                    }
                    if($errorFlag) {
                        continue;
                    }
                    //打包shipping
                    $shipping = [];
                    $shippingIndex = 0;
                    if(!empty($item['shipping_service_0'])){
                        $list['mod_trans'] = 0;
                        for($i=0;$i<4;$i++){
                            if(!empty($item['shipping_service_' . $i])){
                                $shipping[$shippingIndex]['shipping_service'] = trim($item['shipping_service_' . $i]);
                                $shipping[$shippingIndex]['shipping_service_cost'] = number_format(floatval(trim($item['shipping_service_cost_' . $i])),2);
                                $shipping[$shippingIndex]['shipping_service_additional_cost'] = number_format(floatval(trim($item['shipping_service_additional_cost_' . $i])),2);
                                $shipping[$shippingIndex]['extra_cost'] = number_format(floatval(trim($item['extra_cost_' . $i])),2);
                                $shippingIndex++;
                                continue;
                            }
                            break;
                        }
                        $models['trans']['shipping'] = $shipping;
                    }
                    $set['shipping'] = isset($models['trans']['shipping']) ? $models['trans']['shipping'] : [];
                    //检测国内物流合法性
                    $validShippings = EbayTrans::where(['site'=>$list['site'], 'international_service'=>0])->column('shipping_service');
                    $flag = 0;
                    foreach ($set['shipping'] as $v) {
                        if (!in_array($v['shipping_service'], $validShippings)) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '国内物流['.$v['shipping_service'].']与站点不匹配，请检查文件格式及对应项是否正确'
                            ];
                            $flag = 1;
                            break;
                        }
                    }
                    if ($flag) continue;

                    $internal_shipping = [];
                    $interShippingIndex = 0;
                    if(!empty($item['inter_shipping_service_0'])){
                        $list['mod_trans'] = 0;
                        for($i=0;$i<5;$i++){
                            if (!empty($item['inter_shipping_service_' . $i])) {
                                $internal_shipping[$interShippingIndex]['shipping_service'] = trim($item['inter_shipping_service_' . $i]);
                                $internal_shipping[$interShippingIndex]['shipping_service_cost'] = number_format(floatval(trim($item['inter_shipping_service_cost_' . $i])),2);
                                $internal_shipping[$interShippingIndex]['shipping_service_additional_cost'] = number_format(floatval(trim($item['inter_shipping_service_additional_cost_' . $i])),2);
                                if (trim($item['inter_shiptolocation_' . $i]) == 'Worldwide') {
                                    $internal_shipping[$interShippingIndex]['shiptolocation'] = 'Worldwide';
                                } else {
                                    $internal_shipping[$interShippingIndex]['shiptolocation'] = explode(',', trim($item['inter_shiptolocation_' . $i]));
                                }
                                $interShippingIndex++;
                                continue;
                            }
                            break;
                        }
                        $models['trans']['internationalShipping'] = $internal_shipping;
                    }
                    $set['international_shipping'] = isset($models['trans']['internationalShipping']) ? $models['trans']['internationalShipping'] : [];
                    //检测国际物流合法性
                    $validInterShippings = EbayTrans::where(['site'=>$list['site'], 'international_service'=>1])->column('shipping_service');
                    $flag = 0;
                    foreach ($set['international_shipping'] as $v) {
                        if (!in_array($v['shipping_service'], $validInterShippings)) {
                            $message[] = [
                                'lineNum' =>$rowNumber,
                                'errorMsg' => '国际物流['.$v['shipping_service'].']与站点不匹配，请检查文件格式及对应项是否正确'
                            ];
                            $flag = 1;
                            continue;
                        }
                    }
                    if ($flag) continue;


                    if (!empty($item['auto_accept_price'])) {
                        $list['mod_bargaining'] = 0;
                        $set['auto_accept_price'] = number_format(floatval(trim($item['auto_accept_price'])),2);
                    } else {
                        $set['auto_accept_price'] = isset($models['bargaining']['accept_lowest_price']) ?
                            $models['bargaining']['accept_lowest_price'] : 0;
                    }
                    if (!empty($item['minimum_accept_price'])) {
                        $list['mod_bargaining'] = 0;
                        $set['minimum_accept_price'] = number_format(floatval(trim($item['minimum_accept_price'])),2);
                    } else {
                        $set['minimum_accept_price'] = isset($models['bargaining']['reject_lowest_price']) ?
                            $models['bargaining']['reject_lowest_price'] : 0;
                    }

                    if ($list['restart'] == 1) {
                        if (empty($item['restart_rule'])) {
                            $set['restart_rule'] = 1;
                        } else if (strpos(trim($item['restart_rule']), ',')) {
                            $set['restart_rule'] = 5;
                            $set['restart_count'] = substr(trim($item['restart_rule']), 2);
                        } else {
                            $set['restart_rule'] = intval(trim($item['restart_rule']));
                        }
                        if (empty($item['restart_time'])) {
                            $set['restart_way'] = 1;
                        } else {
                            $set['restart_way'] = 2;
                            $set['restart_time'] = strtotime(trim($item['restart_time']));
                        }
                    }

                    if (!isset($listVarReverse['condition'][trim($item['condition_id'])])) {
                        $message[] = [
                            'lineNum' =>$rowNumber,
                            'errorMsg' => '【物品状况】项填写有误，请检查文件格式及对应项是否正确'
                        ];
                        continue;
                    }
                    $set['condition_id'] = $listVarReverse['condition'][trim($item['condition_id'])];
                    $set['condition_description'] = trim($item['condition_description']);
                    $set['description'] = trim($item['description']);
                    $set['mobile_desc'] = trim($item['mobile_desc']);

                    //imgs部分
                    //刊登图片
                    $imgs = explode("\n", $item['imgs']);
                    foreach ($imgs as $kImg => $img) {
                        if (!$img) {
                            unset($imgs[$kImg]);
                        }
                    }
                    //详情图片
                    $detail_imgs = explode("\n", $item['detail_imgs']);
                    foreach ($detail_imgs as $kDimg => $detail_img) {
                        if (!$detail_img) {
                            unset($detail_imgs[$kDimg]);
                        }
                    }


                    $varians = [];
                    if($list['variation']==1) {
                        //varians部分
                        if (!empty($item['v_sku'])) {
                            $sku_ar = explode('<|>', $item['v_sku']);
                            $combine_sku_ar = explode('<|>', $item['combine_sku']);
                            $v_price_ar = explode('<|>', $item['start_price']);
                            $v_qty_ar = explode('<|>', $item['v_qty']);
                            $upc_ar = explode('<|>', $item['v_upc']);
                            $ean_ar = explode('<|>', $item['v_ean']);
                            $sku_imgs_ar = explode('<|>', $item['sku_imgs']);

                            //sku_attr_$i的格式为name-value1,name-value2
                            $sku_attrs = [];
                            for ($i = 0; $i < 10; $i++) {
                                if (!empty($item['sku_attr_' . $i])) {
                                    $sku_attrs[] = explode('<|>', $item['sku_attr_' . $i]);
                                }
                            }
                            $varians = [];
                            $failFlag = 0;
                            //sku_attrs 格式为[[name1-value1,name1-value2],[name2-value1,name2-value2],...]
                            foreach ($sku_ar as $k => $v) {
                                $varians[$k]['v_sku'] = $v;
                                $skuRowNumber = $rowNumber + $k;
                                $varians[$k]['goods_id'] = $list['goods_id'];
                                if (!isset($v_price_ar[$k]) || preg_match('/^\d+(\.?\d+)?$/',$v_price_ar[$k])===0) {
                                    $message[] = [
                                        'lineNum' =>$skuRowNumber,
                                        'errorMsg' => '【一口价/销售价】项填写有误，不能使用表格公式，请检查文件格式及对应项是否正确'
                                    ];
                                    $failFlag = 1;
                                    break;
                                }
                                $varians[$k]['v_price'] = number_format(floatval($v_price_ar[$k]), 2);
                                if (!isset($v_qty_ar[$k]) || $v_qty_ar[$k]==0) {
                                    $message[] = [
                                        'lineNum' =>$skuRowNumber,
                                        'errorMsg' => '【可售数】项填写有误，不能为0，请检查文件格式及对应项是否正确'
                                    ];
                                    $failFlag = 1;
                                    break;
                                }
                                $varians[$k]['v_qty'] = $v_qty_ar[$k];
                                $list['quantity'] += $v_qty_ar[$k];
//                                $varians[$k]['v_qty'] = $v_qty_ar[$k];
                                $varians[$k]['v_pre_qty'] = $v_qty_ar[$k];
                                $varians[$k]['upc'] = empty($upc_ar[$k]) ? 'Does not apply' : $upc_ar[$k];
                                $varians[$k]['ean'] = empty($ean_ar[$k]) ? 'Does not apply' : $ean_ar[$k];
                                $varians[$k]['isbn'] = '';
                                if (!isset($combine_sku_ar[$k]) || 0==preg_match('/^[A-Z]{2}\d{7}\*\d+(,[A-Z]{2}\d{7}\*\d+)*$/',$combine_sku_ar[$k])) {
                                    $message[] = [
                                        'lineNum' =>$skuRowNumber,
                                        'errorMsg' => '【捆绑/打包销售】项填写有误，请检查文件格式及对应项是否正确'
                                    ];
                                    $failFlag = 1;
                                    break;
                                }
                                $varians[$k]['combine_sku'] = $combine_sku_ar[$k];
                                if ($list['assoc_order']) {
                                    $skuInfo = GoodsSku::get(['sku' => $v]);
                                    if (empty($skuInfo)) {
                                        $message[] = [
                                            'lineNum' =>$skuRowNumber,
                                            'errorMsg' => '根据【本地SKU】:'.$v.'获取产品信息失败，请检查文件格式及对应项是否正确'
                                        ];
                                        $failFlag = 1;
                                        break;
                                    }
                                    $varians[$k]['map_sku'][0]['goods_id'] = $list['goods_id'];
                                    $varians[$k]['map_sku'][0]['sku_id'] = $skuInfo->id;
                                    $varians[$k]['map_sku'][0]['sku'] = $combine_sku_ar[$k];
                                } else {
                                    $varians[$k]['map_sku'][0]['goods_id'] = $list['goods_id'];
                                    $varians[$k]['map_sku'][0]['sku_id'] = 0;
                                    $varians[$k]['map_sku'][0]['sku'] = $combine_sku_ar[$k];
                                }
                                //打包属性\
                                foreach ($sku_attrs as $kst => $value) {
                                    if (!isset($value[$k])) {
                                        $message[] = [
                                            'lineNum' =>$skuRowNumber,
                                            'errorMsg' => '【变体属性'.($kst+1).'】项有误，请检查文件格式及对应项是否正确'
                                        ];
                                        $failFlag = 1;
                                        break;
                                    }
                                    $k_v = explode('<$>', $value[$k]);
                                    if (!isset($k_v[0]) || !isset($k_v[1])) {
                                        $message[] = [
                                            'lineNum' =>$skuRowNumber,
                                            'errorMsg' => '【变体属性'.($kst+1).'】项有误，请检查文件格式及对应项是否正确'
                                        ];
                                        $failFlag = 1;
                                        break;
                                    }
                                    $varians[$k]['variation'][trim($k_v[0])] = $k_v[1];
                                }
                                if (!isset($varians[$k]['variation'])) {
                                    $message[] = [
                                        'lineNum' =>$skuRowNumber,
                                        'errorMsg' => '【变体属性】有缺失，例如有listing有4个SKU，但是只有3条设置了属性，请检查该行对应listing的所有SKU属性设置，而不只是本行',
                                    ];
                                    continue;
                                }
                                $skuAttrKeys = array_keys($varians[$k]['variation']);
                                if (!in_array($set['variation_image'], $skuAttrKeys)) {
                                    $message[] = [
                                        'lineNum' =>$skuRowNumber,
                                        'errorMsg' => '【图片关联项】设置的属性值必须在【变体属性】项中存在，请检查文件格式及对应项是否正确'
                                    ];
                                    continue;
                                }

                                //处理图片
                                if (!empty($sku_imgs_ar)) {
                                    if (isset($sku_imgs_ar[$k])) {
                                        $sku_img_ar = explode("\n", $sku_imgs_ar[$k]);
                                        foreach ($sku_img_ar as $kSkuImg => $item) {
                                            if (empty($item)) {
                                                unset($sku_img_ar[$kSkuImg]);
                                            }
                                        }
                                        $varians[$k]['path'] = $sku_img_ar;
//                                        foreach ($sku_img_ar as $img_k => $img_v) {
//                                            if (!empty($img_v)) {
//                                                $img_path_ar = explode('/', $img_v);
//                                                $varians[$k]['path'][$img_k]['base_url'] = $img_path_ar[0] . '//' . $img_path_ar['2'] . '/';
//                                                $varians[$k]['path'][$img_k]['path'] = substr($img_v,
//                                                    strlen($varians[$k]['path'][$img_k]['base_url']));
//                                            }
//                                        }
                                    }
                                    !isset($varians[$k]['path']) && $varians[$k]['path'] = [];
                                    $varians[$k]['thumb'] = $varians[$k]['path'];
                                } else {
                                    $varians[$k]['thumb'] = [];
                                    $varians[$k]['path'] = [];
                                }
                            }
                            if ($failFlag) continue;

                        }
                    }
                    //为了方便删除公用模块，listing里面不再记录使用的模板id
                    $list['comb_model_id'] = 0;//组合模板id
                    $list['mod_promotion'] = 0;//促销策略模板id
//                    $list['mod_style'] = 0;//刊登风格模板id
//                    $list['mod_sale'] = 0;//刊登说明模板id
                    $list['mod_trans'] = 0;//物流模板id
                    $list['mod_exclude'] = 0;//不送达地区模板id
                    $list['mod_choice'] = 0;//备货模板id
                    $list['mod_pickup'] = 0;//自提模板id
                    $list['mod_location'] = 0;//所在地模板id
                    $list['mod_galley'] = 0;//橱窗模板id
                    $list['mod_individual'] = 0;//私人物品模板id
                    $list['mod_refuse'] = 0;//买家限制模板id
                    $list['mod_receivables'] = 0;//收款模板模板id
                    $list['mod_return'] = 0;//退货模板id
                    $list['mod_bargaining'] = 0;//议价模板id
                    $list['mod_quantity'] = 0;//数量模板id
                    $list['listing_cate'] = ($item['listing_cate'])??'';
                    $list['is_virtual_send'] = 0;

                    $draftData[$count] = [
                        'list' => $list,
                        'set' => $set,
                        'imgs' => $imgs,
                        'detail_imgs' => $detail_imgs,
                    ];
                    if ($list['variation']) {
                        $draftData[$count]['varians'] = $varians;
                    }
//                    $this->saveListingPublish($draftData);
//                    Db::startTrans();
//                    $list = $this->helper->packList($list, $this->userId, Constants::DATA_TO_STORE);
//                    $listId = (new EbayListing())->saveEbayListing($list);
//                    $set = $this->helper->packSet($set, Constants::DATA_TO_STORE);
//                    $set['id'] = $listId;
//                    isset($oldListId) ? EbayListingSetting::update($set) : EbayListingSetting::create($set);
//                    $idInfo['oldListId'] = isset($oldListId) ? $oldListId : 0;
//                    $idInfo['account_id'] = $list['account_id'];
//                    $idInfo['newListId'] = $listId;
//                    $idInfo['userId'] = $this->userId;
//                    $imgs = $this->helper->packImgs($imgs, $idInfo, Constants::IMG_TYPE_MAIN);
//                    $this->helper->updateImgs($imgs, $listId);
//                    $detailImgs = $this->helper->packImgs($detail_imgs, $idInfo, Constants::IMG_TYPE_DETAIL);
//                    $this->helper->updateImgs($detailImgs, $listId);
//                    if (!empty($varians) && $list['variation']) {
//                        $varians = $this->helper->packVarians($varians, $idInfo, Constants::DATA_TO_STORE);
//                        $this->helper->updateVarians($varians, $idInfo, $list['assoc_order']);
//                    }
//                    Db::commit();
                    $count++;
                }
                if (!empty($message)) {//有错误
                    return ['count'=>0, 'message'=>$message];
                }
                $errMsg = [];
                $errorFlag = 0;
                Db::startTrans();
                foreach ($draftData as $k => $draftDatum) {
                    $res = (new EbayCtrl($this->userId))->saveListing($draftDatum);
                    if ($res['result'] === false) {
                        $errMsg[] = [
                            'lineNum' => $indexRow[$k],
                            'errorMsg' => $res['message']
                        ];
                        $errorFlag = 1;
                    }
                }
                if ($errorFlag) {
                    Db::rollback();
                    return ['count'=>0,'message'=>$errMsg];
                }
                Db::commit();

            } else {
                $keys = ['oldDraftId','site','primary_categoryid','second_category','account_name','paypal_emailaddress','combine_id','store_category_id',
                    'store_category2_id','draft_name','model_cate'];
                array_shift($data);//移除第一行的标题
                //转化为关联数组，方便后面处理
                for($i=0;$i<count($data);$i++){
                    foreach($keys as $k=>$v){
                        $assocData[$i][$v] = $data[$i][$k];
                    }
                }
                $tipIndex = 0;
                foreach ($assocData as $draftIndex => $item){
                    $tipIndex++;
                    //先获取原范本信息
                    $oldDraftinfo = $this->getListingInfo(trim($item['oldDraftId']));
                    if(empty($oldDraftinfo)){
                        $message .= '第'.$tipIndex.'个范本信息,获取失败,请检查；';
                        continue;
                    }
                    $oldlist = $oldDraftinfo['list'];
                    $oldset = $oldDraftinfo['set'];
                    $oldimgs = $oldDraftinfo['imgs'];
                    $olddetail_imgs = $oldDraftinfo['detail_imgs'];
//                    $mappingspec = $oldDraftinfo['mappingsepc'];
                    $oldvarians = $oldDraftinfo['varians'];
//                    $oldgoodsSku = $oldDraftinfo['goodsSku'];
//                    $oldattrInfo = $oldDraftinfo['attrInfo'];

                    $oldlist['id'] = 0;
                    $site = EbaySite::get(['name' => ['like', trim($item['site']).'%']]);
                    $oldlist['site'] = $site->siteid;
                    $oldlist['currency'] = $site->currency;
                    $oldlist['primary_categoryid'] = intval(trim($item['primary_categoryid']));
                    !empty($item['second_categoryid']) && $oldlist['second_categoryid'] = trim($item['second_categoryid']);
                    $accountInfo = EbayAccount::get(['code'=>trim($item['account_name'])]);
                    !empty($accountInfo) && $oldlist['account_id'] =  $accountInfo->id;
                    //验证此账号是否属于此销售员
                    if (!$this->helper->isAccountBelongsToUser($oldlist['account_id'], $this->userId)) {
                        $message .= '第'.$tipIndex.'个范本信息,用户和账号未关联,请检查；';
                        continue;
                    }
                    !empty($item['paypal_emailaddress']) && $oldlist['paypal_emailaddress'] = trim($item['paypal_emailaddress']);

                    !empty($item['store_category_id']) && $oldlist['store_category_id'] = trim($item['store_category_id']) ;//商店分类1
                    !empty($item['store_category2_id']) && $oldlist['store_category2_id'] = trim($item['store_category2_id']) ;//商店分类2

                    $oldlist['draft_name'] = empty($item['draft_name']) ? $this->createOnlyListingSku($oldlist['draft_name']."-COPY",1,$oldlist['account_id'],""):trim($item['draft_name']);
                    !empty($item['model_cate']) && $oldlist['model_cate'] = trim($item['model_cate']);
                    $oldlist['shared_userid'] = 0;
                    if(!empty($item['comb_model_id'])){
                        $models = $this->helper->parseCombModel(0, trim($item['comb_model_id']));
                        if(empty($models)){
                            $message .= '第'.$tipIndex.'个范本信息,填写了组合模块，但是获取信息失败,请检查；';
                            continue;
                        }
                        $oldlist['comb_model_id'] = $models['comb']['id'];
                        $oldlist['mod_style'] = empty($models['style']) ? 0 : $models['style'];
                        $oldlist['mod_sale'] = empty($models['sale']) ? 0 : $models['sale'];
                        $oldlist['mod_trans'] = isset($models['trans']['id']) ? $models['trans']['id'] : 0;
                        $oldlist['mod_exclude'] = isset($models['exclude']['id']) ? $models['exclude']['id'] : 0;
                        $oldlist['mod_choice'] = isset($models['choice']['id']) ? $models['choice']['id'] : 0;
                        $oldlist['mod_pickup'] = isset($models['pickup']['id']) ? $models['pickup']['id'] : 0;
                        $oldlist['mod_location'] = isset($models['location']['id']) ? $models['location']['id'] : 0;
                        $oldlist['mod_galley'] = isset($models['gallery']['id']) ? $models['gallery']['id'] : 0;
                        $oldlist['mod_individual'] = isset($models['individual']['id']) ? $models['individual']['id'] : 0;
                        $oldlist['mod_refuse'] = isset($models['refuse']['id']) ? $models['refuse']['id'] : 0;
                        $oldlist['mod_receivables'] = isset($models['receivables']['id']) ? $models['receivables']['id'] : 0;
                        $oldlist['mod_return'] = isset($models['return']['id']) ? $models['return']['id'] : 0;

                        //物流
                        if (isset($models['trans']['shipping'])) {
                            $oldset['shipping'] = $models['trans']['shipping'];
                        }
                        if (isset($models['trans']['internationalShipping'])) {
                            $oldset['international_shipping'] = $models['trans']['internationalShipping'];
                        }
                        //不运送地区模板
                        if (!empty($models['exclude'])) {
                            $oldset['exclude_location'] = $models['exclude']['exclude'];
                        }
                        //备货时间
                        if (!empty($models['choice'])) {
                            $oldlist['dispatch_max_time'] = $models['choice']['choice_date'];
                        }
                        //自提
                        if (!empty($models['pickup'])){
                            $oldset['local_pickup'] = $models['pickup']['local_pickup'];
                        }
                        //物品所在地
                        if (!empty($models['location'])) {
                            $oldlist['location'] = $models['location']['location'];
                            $oldlist['country'] = $models['location']['country'];
                            $oldset['postal_code'] = $models['location']['post_code'];
                        }
                        //橱窗
                        if (!empty($models['gallery'])) {
                            $oldlist['picture_gallery'] = $models['gallery']['picture_gallery'];
                        }
                        //私人物品
                        if (!empty($models['individual'])) {
                            $oldlist['private_listing'] = $models['individual']['individual_listing'];
                        }
                        //买家限制
                        if (isset($models['refuse']['detail'])) {
                            $oldlist['disable_buyer'] = $models['refuse']['refuse'];
                            $oldset['buyer_requirment_details'] = $models['refuse']['detail'];
                        }
                        //收款
                        if (!empty($models['receivables'])) {
                            $oldset['payment_method'] = json_decode($models['receivables']['pay_method'], true);
                            $oldlist['autopay'] = $models['receivables']['auto_pay'];
                            $oldset['payment_instructions'] = $models['receivables']['payment_instructions'];
                        }
                        //退货
                        if (!empty($models['return'])) {
                            $oldset['return_policy'] = $models['return']['return_policy'];
                            $oldset['return_type'] = $models['return']['return_type'];
                            $oldlist['return_time'] = $models['return']['return_time'];
                            $oldset['extended_holiday'] = $models['return']['extension'];
                            $oldset['return_shipping_option'] = $models['return']['return_shipping_option'];
                            $oldset['restocking_fee_code'] = $models['return']['restocking_fee_code'];
                            $oldset['return_description'] = $models['return']['return_description'];
                        }
                        //议价
                        if (!empty($models['bargaining'])) {
                            $oldlist['best_offer'] = $models['bargaining']['best_offer'];
                            $oldset['auto_accept_price'] = $models['bargaining']['accept_lowest_price'];
                            $oldset['minimum_accept_price'] = $models['bargaining']['reject_lowest_price'];
                        }
                        //数量
                        if (!empty($models['quantity'])){
                            $oldlist['quantity'] = $models['quantity']['quantity'];
                        }
                    }
                    //根据分类获取属性
                    $specInfo = (new EbayCategorySpecific())->field('category_specific_name')->where(['category_id'=>$oldlist['primary_categoryid']])->select();
                    if(empty($specInfo)){
                        $message .= '第'.$tipIndex.'个范本信息,获取分类属性失败,请检查；';
                        continue;
                    }
                    foreach($specInfo as $spec){
                        $newspecs[] = $spec['category_specific_name'];
                    }
                    foreach($oldset['specifics'] as $k=>$v) {
                        $oldspecs[] = $v['attr_name'];
                    }
                    $needToChange = array_diff($oldspecs,$newspecs);
                    $needToAdd = array_diff($newspecs,$oldspecs);
                    foreach($needToChange as $value){
                        foreach($oldset['specifics'] as $k=>$v) {
                            if($v['attr_name'] == $value){
                                $oldset['specifics'][$k]['custom'] = 1;
                            }
                        }
                    }
                    $cnt = count($oldset['specifics']);
                    foreach($needToAdd as $value){
                        $oldset['specifics'][$cnt]['custom'] = 0;
                        $oldset['specifics'][$cnt]['attr_name'] = $value;
                        $oldset['specifics'][$cnt]['value'] = '';
                        $cnt++;
                    }

                    //更新完毕后，开始写入
                    $list = $this->helper->packList($oldlist, $this->userId, Constants::DATA_TO_STORE);
                    $set = $this->helper->packSet($oldset, Constants::DATA_TO_STORE);
                    Db::startTrans();
                    $listId = (new EbayListing())->saveEbayListing($list);
                    $set['id'] = $listId;
                    EbayListingSetting::create($set);
                    $idInfo['oldListId'] = 0;
                    $idInfo['account_id'] = $oldlist['account_id'];
                    $idInfo['newListId'] = $listId;
                    $idInfo['userId'] = $this->userId;
                    $imgs = $this->helper->packImgs($oldimgs, $idInfo, Constants::IMG_TYPE_MAIN);
                    $this->helper->updateImgs($imgs, $listId);
                    $detailImgs = $this->helper->packImgs($olddetail_imgs, $idInfo, Constants::IMG_TYPE_DETAIL);
                    $this->helper->updateImgs($detailImgs, $listId);
                    if(!empty($oldvarians)) {
                        foreach ($oldvarians as $k=>$v){
                            $oldvarians[$k]['variation'] = json_decode($oldvarians[$k]['variation'],true);
                        }
                        $varians = $this->helper->packVarians($oldvarians);
                        $this->helper->updateVarians($varians, $idInfo, Constants::DATA_TO_STORE);
                    }
                    Db::commit();
                    $count++;
                }
            }
            $warningMsg = '';
            if ($variationMsg) {
                foreach ($variationMsg as $msg) {
                    $warningMsg .= $msg;
                }
            }
            return ['result'=>true,'message'=>'成功导入'.$count.'条。'.$warningMsg];
        }catch (Exception $e){
            Db::rollback();
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage().'|'.'已成功导入 0 条数据');
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage().'|'.'已成功导入 0 条数据');
        }
    }

    /**
     * @param $data
     * @param $userId
     * @param $option
     */
    public function shareDraft($data, $userId, $option){
        try {
            $ids = explode(',', $data);
            if ($option == 1){
                $sharedIds = EbayListing::where(['account_id'=>0])->column('id');//已分享的
                $unsharedIds = array_diff($ids, $sharedIds);//未分享的
                $draftIds = [];
                foreach ($unsharedIds as $unsharedId) {
                    $draft = $this->cListing($unsharedId, 1);
                    $draftIds[] = $draft['id'];
                }
                $update['account_id'] = 0;
                $update['shared_userid'] = $userId;
                EbayListing::update($update, ['id'=>['in', $draftIds]]);
            }else{
                $wh['shared_userid'] = $userId;//只能针对自己分享出去的进行操作
                $wh['id'] = ['in', $ids];
                $draftIds = EbayListing::where($wh)->column('id');
                $this->publishHelper->delListings($draftIds);
            }
            return ['result'=>true,'message'=>'操作成功'];
        }catch (Exception $e){
            return ['message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage(),'result'=>false];
        }
    }


    /**
     * @param int $siteId
     * @param int $accountId
     * @return array|bool
     * @throws Exception
     */
    public function syncTrans(int $siteId, int $accountId)
    {
        try {
            $accountInfo = Cache::store('EbayAccount')->getTableRecord($accountId);
            $ebayPack = new EbayPackApi();
            $ebayDealRes = new EbayDealApiInformation();
            $verb = "GeteBayDetails";
            $xmlInfo['detail_name'] = 'ShippingServiceDetails';
            $xmlInfo['site_id'] = $siteId;
            $res = $ebayPack->sendEbayApiCall($accountInfo, $xmlInfo, $verb, $siteId);
            $result = $ebayDealRes->dealWithApiResponse($verb, $res, $xmlInfo);
            return $result;
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @param string $draftIdStr
     * @param string $accountIdStr
     * @throws Exception
     */
    public function testListingFees(string $draftIdStr, string $accountIdStr)
    {
        try {
            $draftIds = explode(',', $draftIdStr);
            $accountIds = explode(',', $accountIdStr);
            $returnData = [];
            foreach ($draftIds as $k => $draftId) {
                $accountInfo = Cache::store('EbayAccount')->getTableRecord($accountIds[$k]);
                $listInfo = $this->getListingInfo($draftId);
                $verb = 'VerifyAddFixedPriceItem';
                if ($listInfo['list']['listing_type'] == 2) {
                    $verb = 'VerifyAddItem';
                }
                $ebayPackApi = new EbayPackApi();
                $ebayDealRes = new EbayDealApiInformation();
                $response = $ebayPackApi->sendEbayApiCall($accountInfo, $listInfo, $verb, $listInfo['list']['site']);
                $res = $ebayDealRes->dealWithApiResponse($verb, $response);
                $returnData[$k]['id'] = $listInfo['list']['id'];
                $returnData[$k]['spu'] = $listInfo['list']['spu'];
//                $returnData[$k]['code'] = $listInfo['code'];
//                $returnData[$k]['site'] = $listInfo['list']['site'];
//                $returnData[$k]['listing_type'] = $listInfo['list']['listing_type'];
                if ($res['updateList']['listing_status'] == 4) {
                    $returnData[$k]['message'] = $res['updateSet']['message'];
                } else {
                    $returnData[$k]['fees_info'] = $res['updateList']['fees_info'];
                    isset($res['updateSet']['message']) && $returnData[$k]['message'] = $res['updateSet']['message'];
                }
            }
            return $returnData;
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

}
