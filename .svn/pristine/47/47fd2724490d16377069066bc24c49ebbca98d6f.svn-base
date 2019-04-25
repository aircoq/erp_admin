<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/8/1
 * Time: 16:53
 */
namespace app\publish\controller;
use app\common\cache\Cache;
use app\common\controller\Base;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbaySite;
use app\common\service\Common;
use app\index\cache\Role;
use app\publish\service\EbayCtrl as EbayCtrlService;
use app\report\model\ReportExportFiles;
use think\Exception;
use think\Request;


/**
 * @module 重写的Ebay刊登相关接口
 * @title Ebay刊登
 * @author wlw2533
 */
class EbayCtrl extends Base
{
    private $userId;
    private $service;
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $userInfo = Common::getUserInfo();
        $this->userId = empty($userInfo) ? 0 : $userInfo['user_id'];//用户ID
        $this->service = new EbayCtrlService($this->userId);
    }

    /**
     * @title 获取推荐的分类
     * @url /ebay/suggested-categories
     * @method GET
     * @param Request $request
     * @return \think\response\Json
     */
    public function getSuggestedCategories(Request $request)
    {
        try {
            $accountId = $request->param('account_id');
            $site_id = $request->param('site_id');
            $queryString = $request->param('query');
            if (empty($queryString)) {
                throw new Exception('搜索字符不能为空');
            }
            $page = $request->param('page');
            $page = empty($page) ? 1 : $page;
            $size = $request->param('pageSize');
            $size = empty($size) ? 50 : $size;
            $query = [
                'keywords' => $queryString,
                'page' => $page,
                'size' => $size
            ];
            $res = $this->service->getSuggestedCategories($accountId, $site_id, $query);
            return json($res, 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 获取范本/listing店铺分类
     * @url /ebay/dl-store-category/batch
     * @method GET
     * @param Request $request
     * @throws Exception
     */
    public function getDLStoreCategory(Request $request)
    {
        try {
            $ids = json_decode($request->param('ids'), true);
            $res = $this->service->getDLStoreCategory($ids);
            return json($res, 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 获取指定账号指定店铺分类的分类链
     * @url /ebay/store-category-chain/:store_category_id/:account_id
     * @method GET
     * @param $store_category_id
     * @param $account_id
     * @return \think\response\Json
     */
    public function getStoreCategoryChain($store_category_id, $account_id)
    {
        try {
            $data = $this->service->getStoreCategoryChain($store_category_id, $account_id);
            return json(['result'=>true, 'data'=>$data], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 更新listing店铺分类
     * @url /ebay/listing-store-category/batch
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     */
    public function updateListingStoreCategory(Request $request)
    {
        try {
            $data = json_decode($request->param('data'), true);
            $this->service->updateListingStoreCategory($data);
            return json(['result'=>true, 'message'=>'提交成功，稍后自动执行'], 200);

        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 批量修改范本店铺分类
     * @url /ebay/draft-store-category/batch
     * @method PUT
     * @param Request $request
     * @return \think\response\Json
     */
    public function changeDraftStoreCategory(Request $request)
    {
        try {
            $data = json_decode($request->param('data'), true);
            $this->service->changeDraftStoreCategory($data);
            return json(['result'=>true, 'message'=>'操作成功'], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 批量获取listing/范本主图
     * @url /ebay/dl-main-imgs/batch
     * @method GET
     * @param Request $request
     * @return \think\response\Json
     */
    public function getDLMainImgs(Request $request)
    {
        try {
            $ids = json_decode($request->param('ids'), true);
            $data = $this->service->getDLMainImgs($ids);
            return json(['result'=>true, 'data'=>$data], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 批量在线更新listing主图
     * @url /ebay/listing-main-imgs/batch
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     */
    public function updateListingMainImgs(Request $request)
    {
        try {
            $data = json_decode($request->param('data'), true);
            foreach ($data as $datum) {
                $this->service->updateListingMainImgs($datum);
            }
            return json(['result'=>true, 'message'=>'提交成功，稍后自动执行'], 200);

        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 批量切换站点设置账号
     * @url /ebay/change-site/batch
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     */
    public function changeSite(Request $request)
    {
        try {
            $ids = json_decode($request->param('ids'), true);
            $site = $request->param('site');
            $templates = json_decode($request->param('templates'), true);
            $accountId = $request->param('account_id');
            $copy = $request->param('copy');
            $res = $this->service->changeSite($ids, $site, $templates,$copy,$accountId);
            return json($res, $res['result']?200:500);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 批量将listing成本价更改为调整后的价格
     * @url /ebay/adjust-price/batch
     * @method PUT
     * @param Request $request
     * @return \think\response\Json
     */
    public function costPriceToAdjustedPrice(Request $request)
    {
        try {
            $data = json_decode($request->param('data'), true);
            $this->service->costPriceToAdjustedPrice($data);
            return json(['result'=>true, 'message'=>'操作成功'], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 批量修改范本拍卖刊登天数
     * @url /ebay/d-chinese-listing-duration/batch
     * @method PUT
     * @param Request $request
     * @return \think\response\Json
     */
    public function DChineseListingDuration(Request $request)
    {
        try {
            $data = json_decode($request->param('data'), true);
            $this->service->DChineseListingDuration($data);
            return json(['result'=>true, 'message'=>'操作成功'], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 翻译
     * @url /ebay/translate/batch
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     */
    public function translate(Request $request)
    {
        try {
            $data = json_decode($request->param('data'), true);
            $res = $this->service->translate($data);
            return json(['result'=>true, 'data'=>$res], 200);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 获取标题库列表
     * @url /publish/ebay/titles
     * @method get
     * @return \think\response\Json
     */
    public function titles(Request $request)
    {
        $params = $request->param();
        $data = $this->service->titles($params);
        return json($data, $data['result'] ? 200 : 500);
    }

    /**
     * @title 获取指定商品标题库详情
     * @url /publish/ebay/titles/:goods_id
     * @method get
     * @return \think\response\Json
     */
    public function titleDetail($goods_id)
    {
        $data = $this->service->titleDetail($goods_id);
        return json($data, $data['result'] ? 200 : 500);
    }

    /**
     * @title 批量获取商品标题库详情
     * @url /publish/ebay/titles/batch
     * @method get
     * @return \think\response\Json
     */
    public function titleDetails(Request $request)
    {
        $goodsIds = json_decode($request->param('goodsIds'),true);
        if (empty($goodsIds)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->titleDetails($goodsIds);
        return json($res, $res['result'] ? 200 : 500);
    }

    /**
     * @title 保存单条标题库详情
     * @url /publish/ebay/titles/:goods_id
     * @method put
     * @return \think\response\Json
     */
    public function saveTitleDetail(Request $request,$goods_id)
    {
        $id = $request->param('id');
//        $goodsId = $request->param('goodsId');
        $titles = json_decode($request->param('titles'), true);
        try {
            if (empty($titles)|| empty($goods_id)) {
                throw new Exception('参数格式错误');
            }
            $res = $this->service->saveTitleDetail($titles,$goods_id,$id);
            return json($res, $res['result'] ? 200 : 500);
        } catch (Exception $e) {
            return json(['result'=>true, 'message'=>$e->getMessage()], 500);
        }

    }

    /**
     * @title 批量保存商品标题库详情
     * @url /publish/ebay/titles/batch
     * @method put
     * @return \think\response\Json
     */
    public function saveTitleDetails(Request $request)
    {
        try {
            $data = json_decode($request->param('data'),true);
            if (empty($data)) {
                throw new Exception('参数错误');
            }
            $res = $this->service->saveTitleDetails($data);
            return json($res, $res['result'] ? 200 : 500);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 对范本标题随机排序
     * @url /publish/ebay/draft-title/random
     * @method put
     * @param Request $request
     * @return \think\response\Json
     */
    public function randomDraftTitle(Request $request)
    {
        try {
            $data = json_decode($request->param('data'),true);
            if (empty($data)) {
                throw new Exception('参数错误');
            }
            $res = $this->service->randomDraftTitle($data);
            return json($res, $res['result'] ? 200 : 500);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }


    /******************************************************************************************************************/

    /**
     * @title 复制listing并更改账号
     * @url /publish/ebay/copy-listing
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function cpListings(Request $request)
    {
        try {
            $ids = json_decode($request->param('ids'),true);
            $accountId = $request->param('account_id');
            $paypal = $request->param('paypal');
            if (empty($ids) || empty($accountId) || empty($paypal)) {
                throw new Exception('参数有误');
            }
            $res = $this->service->cpListings($ids,$accountId,$paypal);
            return json($res, $res['result'] ? 200 : 500);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 批量检测刊登
     * @url /publish-ebay/check-publish/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function checkPublishFee(Request $request)
    {
        set_time_limit(0);
        try {
            $ids = json_decode($request->param('ids'),true);
            $data = json_decode($request->param('data'),true);
            if (empty($ids) && empty($data)) {
                throw new Exception('参数错误');
            }
            $res = $this->service->checkPublishFee($ids,$data);
            return json($res, $res['result'] ? 200 : 500);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 批量删除
     * @url /publish-ebay/delete-listing/batch
     * @method delete
     * @param Request $request
     * @return \think\response\Json
     */
    public function delListings(Request $request)
    {
        try {
            $ids = json_decode($request->param('ids'),true);
            if (empty($ids)) {
                throw new Exception('参数错误');
            }
            $res = $this->service->delListings($ids);
            return json($res, $res['result'] ? 200 : 500);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 一键展开变体
     * @url /publish-ebay/spread-variants/batch
     * @method get
     * @param Request $request
     * @return array
     */
    public function spreadVariants(Request $request)
    {
        $ids = json_decode($request->param('ids'),true);
        if (empty($ids)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->spreadVariants($ids);
        return json($res, $res['result'] ? 200 : 500);
    }

    /**
     * @title 队列刊登
     * @url /publish-ebay/publish-queue/batch
     * @method post
     * @param Request $request
     * @return array|\think\response\Json
     */
    public function addPublishQueue(Request $request)
    {
        $ids = json_decode($request->param('ids'),true);
        $data = json_decode($request->param('data'),true);
        if (empty($ids) && empty($data)) {
            return ['result'=>false,'message'=>'参数错误'];
        }
        $res = $this->service->addPublishQueue($ids,$data);
        return json($res, $res['result'] ? 200 : 500);
    }

    /**
     * @title 批量设置账号
     * @url /publish-ebay/listing-account/batch
     * @method post
     * @param Request $request
     * @return array|\think\response\Json
     */
    public function setAccount(Request $request)
    {
        try {
            $ids = json_decode($request->param('ids'),true);
            $accountId = $request->param('account_id');
            $paypal = $request->param('paypal');
            $copy = $request->param('copy',0);
            if (empty($ids) || empty($accountId) || empty($paypal)) {
                throw new Exception('参数错误');
            }
            $res = $this->service->setAccount($ids,$accountId,$paypal,$copy);
            return json($res, $res['result'] ? 200 : 500);
        } catch (Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 批量修改一口价及可售量
     * @url /publish-ebay/fixed-price-qty/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function setFixedPriceQty(Request $request)
    {
        $data = json_decode($request->param('data'),true);
        if (empty($data)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->setFixedPriceQty($data);
        return json($res, $res['result'] ? 200 : 500);
    }

    /**
     * @title 批量修改拍卖价
     * @url /publish-ebay/chinese-price/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function setChinesePrice(Request $request)
    {
        $data = json_decode($request->param('data'),true);
        if (empty($data)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->setChinesePrice($data);
        return json($res, $res['result'] ? 200 : 500);
    }

    /**
     * @title 批量修改标题
     * @url /publish-ebay/listing-title/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function setTitle(Request $request)
    {
        $data = json_decode($request->param('data'),true);
        if (empty($data)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->setTitle($data);
        return json($res, $res['result'] ? 200 : 500);
    }

    /**
     * @title 批量修改商店分类
     * @url /publish-ebay/listing-store-category/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function setStoreCategory(Request $request)
    {
        $data = json_decode($request->param('data'),true);
        if (empty($data)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->setStoreCategory($data);
        return json($res, $res['result'] ? 200 : 500);
    }

    /**
     * @title 批量获取刊登图
     * @url /publish-ebay/publish-imgs/batch
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function getPublishImgs(Request $request)
    {
        $ids = json_decode($request->param('ids'),true);
        if (empty($ids)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->getPublishImgs($ids);
        return json($res, $res['result'] ? 200 : 500);
    }

    /**
     * @title 批量设置刊登图
     * @url /publish-ebay/publish-imgs/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function setPublishImgs(Request $request)
    {
        $data = json_decode($request->param('data'),true);
        if (empty($data)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->setPublishImgs($data);
        return json($res, $res['result'] ? 200 : 500);
    }

    /**
     * @title 批量设置平台分类属性
     * @url /publish-ebay/specifics/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function setSpecifics(Request $request)
    {
        $data = json_decode($request->param('data'),true);
        if (empty($data)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->setSpecifics($data);
        return json($res, $res['result'] ? 200 : 500);
    }


    /**
     * @title 批量设置一口价刊登天数
     * @url /publish-ebay/listing-duration/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function setListingDuration(Request $request)
    {
        $data = json_decode($request->param('data'),true);
        if (empty($data)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->setListingDuration($data);
        return json($res, $res['result'] ? 200 : 500);
    }
//
//    /**
//     * @title 批量设置拍卖刊登天数
//     * @url /publish-ebay/chinese-listing-duration/batch
//     * @method post
//     * @param Request $request
//     * @return \think\response\Json
//     */
//    public function setChineseListingDuration(Request $request)
//    {
//        $data = json_decode($request->param('data'),true);
//        if (empty($data)) {
//            return json(['result'=>false,'message'=>'参数错误'],500);
//        }
//        $res = $this->service->setChineseListingDuration($data);
//        return json($res, $res['result'] ? 200 : 500);
//    }

    /**
     * @title 批量应用公共模块
     * @url /publish-ebay/apply-common-module/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function applyCommonModule(Request $request)
    {
        $ids = json_decode($request->param('ids'),true);
        $modules = json_decode($request->param('modules'),true);
        if (empty($ids) || empty($modules)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->applyCommonModule($ids,$modules);
        return json($res, $res['result'] ? 200 : 500);
    }

    /**
     * @title 立即刊登保存
     * @url /publish-ebay/publish-immediately-save
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function publishImmediatelySave(Request $request)
    {
        try {
            $data = json_decode($request->param('data'),true);
            if (empty($data)) {
                throw new Exception('参数错误');
            }
            $res = $this->service->saveListing($data);
            if ($res['result'] === true && $res['data']) {
                $caches = [];
                $listings = $res['data'];//保存成功后的数据
                foreach ($listings as $k => $listing) {
                    $list = $data[$k]['list'];//使用保存前传递的数据
                    $siteInfo = EbaySite::where('siteid',$list['site'])->field('name,symbol,country')->find();
                    $returnData['id'] = $listing['id'];
                    $returnData['listing_id'] = $listing['id'];
                    $returnData['spu'] = $list['spu'];
                    $returnData['title'] = $list['title'];
                    $returnData['site'] = $list['site'];
                    $returnData['site_name'] = $siteInfo['name'];
                    $returnData['site_code'] = $siteInfo['country'];
                    $returnData['account_id'] = $list['account_id'];
                    $returnData['account_code'] = EbayAccount::where('id',$list['account_id'])->value('code');
                    Cache::store('EbayListingReponseCache')->setReponseCache($listing['id'],$returnData);
                    $caches[] = $returnData;
                }
                return json(['result'=>true,'data'=>$caches],200);
            }
            return json($res, 500);
        } catch (\Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 立即刊登
     * @url /publish-ebay/publish-immediately
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function publishImmediately(Request $request)
    {
        set_time_limit(0);
        $ids = explode(',', $request->param('ids'));
//        $data = json_decode($request->param('data'),true);
        if (empty($ids)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        try {
            foreach ($ids as $id) {
                $res = $this->service->publishImmediately($id);
            }
            return json($res, $res['result'] ? 200 : 500);
        } catch (\Exception $e) {
            return json(['result'=>false,'message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 立即刊登结果查询
     * @url /publish-ebay/publish-immediately-result
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function publishImmediatelyResult(Request $request)
    {
        $ids = json_decode($request->param(['ids']),true);
        if (empty($ids)) {
            return json(['result'=>false,'message'=>'参数错误'],500);
        }
        $res = $this->service->publishImmediatelyResult($ids);
        return json($res, $res['result'] ? 200 : 500);
    }
    
    /******************************************************************************************************************/

    /**
     * @title 批量设置自动补货
     * @url /publish-ebay/replenish/batch
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function replenish(Request $request)
    {
        try {
            $ids = json_decode($request->param('ids'),true);
            $replenish = $request->param('replensih',0);
            if (empty($ids)) {
                throw new Exception('参数错误');
            }
            $res = $this->service->replenish($ids,$replenish);
            return json($res, $res['result'] ? 200 : 500);
        } catch (\Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }


    /**
     * @title 获取标题库关键词库
     * @url /title/suggest-word
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function getSuggestWord(Request $request)
    {
        try {
            $query = $request->param('query');
            $res = $this->service->getSuggestWord($query);
            return json($res,$res['result']?200:500);
        } catch (\Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 通过导入方式在线更新listing
     * @url /publish-ebay/update-listing/import
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function updateListingImport(Request $request)
    {
        try {
            set_time_limit(0);
            $file_extension = $request->param('extension');
            $file_content = $request->param('content');
//            $file_name = $request->param('name');
            //post的数据里面，加号会被替换为空格，需要重新替换回来，如果不是POST的数据，则屏蔽下面一行
            $base64file = str_replace(' ', '+', $file_content);
            $file_content = substr($base64file, strpos($base64file, 'base64,') + 7);
            $savePath = './upload/';
            $file_name = date('YmdHis').rand(1000000,9999999) . '.' . $file_extension;//重命名
            $filePath = $savePath.$file_name;
            if (file_put_contents($savePath . $file_name, base64_decode($file_content))) {//可以成功保存
                $res = $this->service->updateListingImport($filePath);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return json($res, 200);
            } else {//保存失败
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return json(['message' => '上传的文件无法保存，导入失败'], 500);
            }
        } catch (\Exception $e) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 拉取指定item id的listing
     * @url /publish-ebay/pull-listing
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function pullListingByItemId(Request $request)
    {
        try {
            $itemId = $request->param('item_id');
            if (empty($itemId)) {
                throw new Exception('参数错误');
            }
            $res = $this->service->pullListingByItemId($itemId);
            return json($res,$res['result']?200:500);
        } catch (\Exception $e) {
            return json(['result'=>false, 'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 设置虚拟仓发货
     * @url /publish-ebay/virtual-send
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function setIsVirtualSend(Request $request)
    {
        try {
            $ids = explode(',', $request->param('ids'));
            $isVirtualSend = $request->param('is_virtual_send');
            if (empty($ids) || !in_array($isVirtualSend, [0, 1])) {
                throw new Exception('参数错误');
            }
            $this->service->setIsVirtualSend($ids, $isVirtualSend);
            return json(['message' => '操作成功']);
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 500);
        }
    }
    /**
     * @title 在线listing数据导出
     * @url /publish-ebay/online-export
     * @method get
     * @param Request $request
     * @return array
     */
    public function onlineExport(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->service->onlineExport($params);
            return json($res);
        } catch (\Exception $e) {
            return json(['message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()], 500);
        }
    }

    /**
     * @title 取消定时或队列刊登
     * @url /publish-ebay/cancel-queue-publish
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function cancelQueuePublish(Request $request)
    {
        try {
            $ids = explode(',', $request->param('ids'));
            if (!$ids) {
                throw new Exception('参数错误');
            }
            $this->service->cancelQueuePublish($ids);
            return json(['message' => '操作成功']);
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @title 修改在线数据导出
     * @url /publish-ebay/online-export-modify
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function onlineExportModify(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->service->onlineExportModify($params);
            return json($res);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 获取范本信息
     * @url /publish-ebay/draft
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function getSiteDraftInfo(Request $request)
    {
        try {
            $params = $request->param();
            if (!$params) {
                throw new Exception('参数错误');
            }
            $res = $this->service->getSiteDraftInfo($params);
            return json(['data'=>$res]);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 设置范本
     * @url /publish-ebay/draft
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function setDraft(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->service->setDraft($params);
            return json($res);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 范本列表
     * @url /publish-ebay/drafts
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function drafts(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->service->drafts($params);
            return json($res);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title ebay测试
     * @url /ebay/test
     * @method POST
     */
    public function test(Request $request)
    {
        set_time_limit(0);
        try {
            $param = $request->param();
            $res = $this->service->test($param);
            return json(['result'=>true, 'message'=>$res], 200);
        } catch (Exception $e) {
            return json(['result'=>true, 'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 复制范本转站点账号
     * @url /publish-ebay/change-site-from-draft/batch
     * @method POST
     * @param Request $request
     * @return \think\response\Json
     */
    public function changeSiteFromDraft(Request $request)
    {
        try {
            $listingIds = explode(',', $request->param('listing_ids'));
            $siteId = $request->param('site_id');
            $templates = json_decode($request->param('templates'), true);
            $accountId = $request->param('account_id');
            $res = $this->service->changeSiteFromDraft($listingIds, $siteId, $templates,$accountId);
            return json($res);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 在线spu统计导出
     * @url /publish-ebay/online-spu/export
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function onlineSpuStatisticExport(Request $request)
    {
        try {
            $params = $request->param();
            $res = $this->service->onlineSpuStatisticExport($params);
            return json($res);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }

}