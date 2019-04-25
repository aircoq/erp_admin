<?php
namespace app\publish\controller;

use app\common\exception\JsonErrorException;
use app\common\model\amazon\AmazonCategory;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\publish\service\AmazonCategoryHelper;
use think\Request;
use think\Response;
use think\Cache;
use think\Db;
use think\Exception;
use think\Validate;
use app\publish\service\AmazonAttriubteService;
use app\common\controller\Base;
use app\publish\service\ExpressHelper;
use app\goods\service\GoodsHelp;
use app\publish\service\AmazonAddListingService;
use app\common\service\Common;
use app\publish\service\AmazonXsdToXmlService;

/**
 * @module 刊登系统
 * @title Amazon刊登记录
 * @author hzy
 * @url /publish/amazon-publish
 * Class AmazonPublishListing
 * @package app\publish\controller
 */
class AmazonPublishListing extends Base
{

    protected $lang = 'zh';

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        //erp的语言设置，默认是中文，目前可能的值是en:英文；
        $this->lang = $request->header('Lang', 'zh');

    }

    /**
     * @title 获取仓库列表
     * @method get
     * @url /publish/amazon-publish/warehouses
     * @return 返回一个JSON数组
     */
    public function getWarehouses()
    {
        try {
            $ExpressHelper = new ExpressHelper();
            return json($ExpressHelper->GetWareHouse());
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 获取Xsd分类
     * @method get
     * @url /publish/amazon-publish/xsd-category
     * @return 返回一个JSON数组
     */
//    public function getXsdCategory()
//    {
//        try {
//            $categoryHelper = new AmazonCategoryHelper();
//            return json($categoryHelper->getCategoryTreeList());
//        } catch (\Exception $e) {
//            return json($e->getMessage(), 400);
//        }
//    }

    /**
     * @title 获取站点列表
     * @method get
     * @url /publish/amazon-publish/sites
     * @return 返回一个JSON数组
     */
    public function getSiteList()
    {
        try {
            $categoryHelper = new AmazonCategoryHelper();
            return json($categoryHelper->sitePairs());
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 获取类目绑定的普通属性
     * @method get
     * @url /publish/amazon-publish/common-attribute
     * @return 返回一个JSON数组
     */
    public function getAttributeByCategoryId(Request $request)
    {
        try {
            $get = $request->instance()->param();
            $params = array(
                'site' => isset($get['site']) ? $get['site'] : '',
                'category_id' => isset($get['second_category_id']) && $get['second_category_id'] ? $get['second_category_id'] : $get['first_category_id']
            );
            $categoryHelper = new AmazonCategoryHelper();
            $categoryHelper->checkParams($params, 'search');
            return json($categoryHelper->getAttributeByCategoryId($params['category_id'], $params['site']));
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 获取分类树
     * @method get
     * @url /publish/amazon-publish/category
     * @return 返回一个JSON数组
     */
    public function getCategoryByParentId(Request $request)
    {
        try {
            $params = $request->instance()->param();
            $categoryHelper = new AmazonCategoryHelper();
            return json($categoryHelper->getCategoriesByParentId($params['category_id'], $params['site']));
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 亚马逊分类搜索
     * @method GET
     * @url /publish/amazon-publish/search-categories
     * @return 返回一个JSON数组
     */
    public function getSearchCategory(Request $request)
    {
        try {
            $params = $request->param('keywords', '');
            $page = $request->param('page', 1);
            $pageSize = $request->param('pageSize', 30);
            $site = $request->param('site', '');
            $categoryHelper = new AmazonCategoryHelper();
            return json($categoryHelper->getSearchCategory($params, $site, $page, $pageSize));
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 查询产品列表
     * @method GET
     * @url /publish/amazon-publish/get-listing
     * @apiFilter app\publish\filter\AmazonFilter
     * @apiFilter app\publish\filter\AmazonDepartmentFilter
     * @apiRelate app\goods\controller\Category::index
     * @author 冬
     * @return 返回一个JSON数组
     */
    public function getPublishListing(Request $request)
    {
        $publishService = new AmazonAddListingService();
        $publishService->setLang($this->lang);
        try {
            $params = $request->param();
            $page = $request->param('page', 1);
            $pageSize = $request->param('pageSize', 50);
            return json($publishService->getProductList($params, $page, $pageSize));
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage(). '|'. $e->getLine(). '|'. $e->getFile()], 400);
        }
    }


    /**
     * @title 产品列表刊登状态刷新
     * @method GET
     * @url /publish/amazon-publish/refresh_status
     * @author 冬
     * @return 返回一个JSON数组
     */
    public function getPublishStatus(Request $request)
    {
        $id = $request->get('id', 0);
        if ($id <= 0) {
            if ($this->lang == 'zh') {
                return json(['message' => '缺少刊登记录自增ID'], 400);
            } else {
                return json(['message' => 'The parameter id is empty!'], 400);
            }
        }
        try {
            $publishService = new AmazonAddListingService();
            $result  = $publishService->refresh_status($id);
            return json(['data' => $result]);
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 获取一个产品的信息
     * @method GET
     * @url /publish/amazon-publish/get-one
     * @return 返回一个JSON数组
     */
    public function getByProductId(Request $request)
    {
        $params = $request->instance()->param();
        $productId = $request->param('product_id', '');
        $publishService = new AmazonAddListingService();
        try {
            return json($publishService->getByProductId($productId));
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 删除或批量删除刊登记录
     * @method GET
     * @url /publish/amazon-publish/delete-listing
     * @author 冬
     * @return 返回一个JSON数组
     */
    public function deleteByProductId(Request $request)
    {
        $ids = $request->get('ids', '');
        $id = $request->get('id', '');

        $ids = explode(',', $ids);
        $ids[] = $id;
        $ids = array_filter($ids);
        if(empty($ids)) {
            if ($this->lang == 'zh') {
                return json(['message' => '删除参数id为空！'], 400);
            } else {
                return json(['message' => 'The delete parameter id is empty!'], 400);
            }
        }

        $publishService = new AmazonAddListingService();
        $publishService->setLang($this->lang);
        try {
            $publishService->deleteByProductId($ids);
            if ($this->lang == 'zh') {
                return json(['message' => '删除刊登记录成功']);
            } else {
                return json(['message' => 'Deleted successfully']);
            }
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 已更改价格
     * @method POST
     * @url /publish/amazon-publish/adjusted-price
     * @author 冬
     * @return 返回一个JSON数组
     */
    public function adjustedPrice(Request $request)
    {
        $ids = $request->post('ids', '');

        $ids = explode(',', $ids);
        $ids = array_merge(array_filter($ids));
        if(empty($ids)) {
            if ($this->lang == 'zh') {
                return json(['message' => '参数ids为空！'], 400);
            } else {
                return json(['message' => 'The parameter ids is empty！'], 400);
            }
        }

        $detailModel = new AmazonPublishProductDetail();
        try {
            $details = $detailModel->where(['product_id' => ['in', $ids], 'type' => 1])
                ->where('pre_cost!=current_cost')
                ->field('id,type,current_cost,pre_cost')
                ->select();
            foreach ($details as $detail) {
                $detail->save(['pre_cost' => $detail['current_cost']]);
            }
            if ($this->lang == 'zh') {
                return json(['message' => '已更改价格成功']);
            } else {
                return json(['message' => 'Price change successful']);
            }
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

}