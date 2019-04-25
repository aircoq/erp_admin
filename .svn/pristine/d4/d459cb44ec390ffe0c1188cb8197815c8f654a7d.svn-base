<?php

namespace app\goods\controller;

use app\common\controller\Base;
use app\common\model\Goods as GoodsModel;
use app\goods\service\GoodsHelp;
use app\goods\service\Goodsdev as goodsdevModel;
use app\goods\service\GoodsDeveloper;
use think\Request;
use think\Exception;
use app\goods\service\GoodsQcItems;
use app\common\service\Common;

/**
 * Class Goodsdev
 * @module 商品系统
 * @title 产品开发
 * @url /goodsdev
 * @author ZhaiBin
 * @package app\goods\controller
 */
class Goodsdev extends Base
{
    /**
     * 显示资源列表
     * @title 产品开发列表
     * @method get
     * @param  \think\Request $request
     * @return \think\Response
     * @apiRelate app\goods\controller\Category::index
     * @apiRelate app\goods\controller\Unit::dictionary
     * @apiRelate app\goods\controller\Tag::dictionary
     * @apiRelate app\system\controller\Lang::dictionary
     * @apiRelate app\goods\controller\Brand::tortDictionary
     * @apiRelate app\warehouse\controller\Delivery::getWarehouseChannel
     * @apiRelate app\goods\controller\Goods::getPlatformSaleStatus
     */
    public function index(Request $request)
    {

        $param = $request->param();
        $userInfo = Common::getUserInfo($request);
        $page = $param['page'] ?? 1;
        $pageSize = $param['page_size'] ?? 50;
        $goodsdev = new goodsdevModel();
        try {
            $result = $goodsdev->index($page, $pageSize, $param, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * 保存新建的资源
     * @title 保存产品开发
     * @method post
     * @apiRelate app\goods\controller\Unit::dictionary
     * @apiRelate app\goods\controller\Brand::tortDictionary
     * @apiRelate app\goods\controller\Category::index
     * @apiRelate app\warehouse\controller\Delivery::getWarehouseChannel
     * @apiRelate app\goods\controller\Goods::getPlatformSaleStatus
     * @apiRelate app\goods\controller\Goods::transportProperty
     * @apiRelate app\purchase\controller\Supplier::info
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $userInfo = Common::getUserInfo($request);
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];

        try {
            $goods = new goodsdevModel();
            $goods->saveGoodsdev($params, $user_id);
            return json(['message' => '添加成功'], 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message], 400);
        }
    }

    /**
     * 保存新建的资源
     * @title 保存产品开发
     * @url save/base-info
     * @method post
     * @apiRelate app\goods\controller\Unit::dictionary
     * @apiRelate app\goods\controller\Brand::tortDictionary
     * @apiRelate app\goods\controller\Category::index
     * @apiRelate app\warehouse\controller\Delivery::getWarehouseChannel
     * @apiRelate app\goods\controller\Goods::getPlatformSaleStatus
     * @apiRelate app\goods\controller\Goods::transportProperty
     * @apiRelate app\purchase\controller\Supplier::info
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function saveBaseInfo(Request $request)
    {
        $params = $request->param();
        $userInfo = Common::getUserInfo($request);
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];
        try {
            $goods = new goodsdevModel();
            $result = $goods->saveBaseInfo($params, $user_id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }

    }

    /**
     * 显示指定的资源
     * @title 编辑产品开发
     * @url :id/edit
     * @method get
     * @match ['id' => '\d+']
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        try {
            $goods = new goodsdevModel();
            $result = $goods->getGoodsdevInfo($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message], 400);
        }
    }

    /**
     * 保存更新的资源
     * @title 更新产品开发
     * @method put
     * @url :id
     * @match ['id' => '\d+']
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        if (empty($id) || empty($params)) {
            return json(['message' => '缺少Id或者参数不能为空'], 400);
        }
        $userInfo = Common::getUserInfo($request);
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];
        try {
            $goods = new goodsdevModel();
            $goods->updateGoodsdev($id, $params, $user_id);
            return json(['message' => '更新成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * 获取产品规格信息
     * @title 查看分类规格参数
     * @url category-specification/:id(\d+)
     * @method get
     * @param int $id
     * @return \think\Response
     */
    public function getCateSpecification($id)
    {
        if (empty($id)) {
            return json(['message' => '产品分类Id不能为空']);
        }
        $goodsHelp = new GoodsHelp();
        $attributes = $goodsHelp->getCategoryAttribute($id, 1);
        foreach ($attributes as &$attribute) {
            if ($attribute['type'] != 2) {
                $attribute['attribute_value'] = array_values($attribute['attribute_value']);
            }
        }
        return json($attributes, 200);
    }

    /**
     * 获取产品规格信息
     * @title 查看分类属性
     * @url category-attribute/:id(\d+)
     * @method get
     * @param int $id
     * @return \think\Response
     */
    public function getCateAttribute($id)
    {
        if (empty($id)) {
            return json(['message' => '产品分类Id不能为空']);
        }
        $goodsHelp = new GoodsHelp();
        $attributes = $goodsHelp->matchCateAttribute($id, 0, 1, $goods_attributes = []);

        return json($attributes, 200);
    }

    /**
     * 获取产品开发基础信息
     * @title 查看产品开发基础信息
     * @url :id(\d+)/base-info
     * @method get
     * @param int $id
     * @return \think\Response
     */
    public function getBaseInfo($id)
    {
        if (empty($id)) {
            return json(['message' => '开发产品Id不能为空']);
        }
        try {
            $goodsdev = new goodsdevModel();
            $result = $goodsdev->getBaseInfo($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * 获取产品开发供应商信息
     * @title 查看产品开发供应商信息
     * @url :id(\d+)/supplier
     * @method get
     * @param int $id
     * @return \think\Response
     */
    public function getSupplierInfo($id)
    {
        try {
            $server = new goodsdevModel();
            $result = $server->getSupplierInfo($id);
            return json($result, 200);
        } catch (Exception $ex) {

            return json(['message' => '获取失败'], 400);
        }
    }

    /**
     * @title 保存供应商信息
     * @method put
     * @url :id(\d+)/supplier
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function saveSupplierInfo($id)
    {
        $param = $this->request->param();
        try {
            $server = new goodsdevModel();
            $result = $server->saveSupplierInfo($id, $param);
            return json($result, 200);
        } catch (Exception $ex) {

            return json(['message' => '获取失败'], 400);
        }
    }

    /**
     * 获取开发产品规格
     * @title 查看产品开发规格信息
     * @url :id(\d+)/specification
     * @method get
     * @param int $id
     * @return \think\Response
     */
    public function getSpecification($id)
    {
        try {
            $server = new goodsdevModel();
            $result = $server->getAttributeInfo($id);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => '获取失败'], 400);
        }
    }

    /**
     * 获取开发产品属性信息
     * @title 查看产品开发属性
     * @url :id(\d+)/attribute
     * @method get
     * @param int $id
     * @return \think\Response
     */
    public function getAttribute($id)
    {
        if (empty($id)) {
            return json(['message' => '产品ID不能为空'], 400);
        }
        try {
            $server = new goodsdevModel();
            $result = $server->getAttributeInfo($id);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => '获取失败'], 400);
        }
    }

    /**
     * 获取产品供应商信息
     * @title 查看产品开发描述
     * @url :id(\d+)/description
     * @method get
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function getDescription(Request $request, $id)
    {
        $lang_id = $request->param('lang_id', 0);

        if (empty($id)) {
            return json(['message' => '产品ID不能为空'], 400);
        }
        try {
            $goodsHelp = new GoodsHelp();
            $result = $goodsHelp->getProductDescription($id, $lang_id);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => '获取失败'], 400);
        }
    }

    /**
     * 获取开发产品日志信息
     * @title 查看产品开发日志
     * @url :id(\d+)/logs
     * @method get
     * @param int $id
     * @return \think\Response
     */
    public function getLog($id)
    {
        if (empty($id)) {
            return json(['message' => '产品ID不能为空'], 400);
        }
        try {
            $goodsHelp = new GoodsHelp();
            $result = $goodsHelp->getLog($id, 0);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => '获取失败'], 400);
        }
    }

    /**
     * 添加开发产品备注信息
     * @title 添加产品开发备注
     * @url log/:id(\d+)
     * @method post
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function addLog(Request $request, $id)
    {
        $remark = $request->param('remark');
        if (empty($id)) {
            return json(['message' => '产品ID不能为空'], 400);
        }
        try {
            $goodsHelp = new GoodsHelp();
            $goodsHelp->addLog($id, $remark);
            return json(['message' => '添加成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => '添加失败'], 400);
        }
    }

    /**
     * 更新开发产品描述
     * @title 更新产品开发描述
     * @url description/:id(\d+)
     * @method put
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function updateDescription(Request $request, $id)
    {
        $params = $request->param();
        $goodsHelp = new GoodsHelp();
        $descriptions = $goodsHelp->formatDescription($params['descriptions']);
        if (empty($id) || empty($descriptions)) {
            return json(['message' => '产品ID不能为空或描述不能为空'], 400);
        }
        try {
            $result = $goodsHelp->modifyProductDescription($id, $descriptions);
            return json($result, 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message], 400);
        }
    }

    /**
     * 更新开发产品基础信息
     * @title 更新产品开发基础信息
     * @url base/:id(\d+)
     * @method put
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function updateBaseInfo(Request $request, $id)
    {
        if (empty($id)) {
            return json(['message' => '产品ID不能为空'], 400);
        }
        $params = $request->param();
        $userInfo = Common::getUserInfo($request);
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];
        try {
            $goodsHelp = new GoodsHelp();
            $goodsHelp->updateBaseInfo($id, $params, $user_id);
            return json(['message' => '更新成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * 更新开发产品规格参数
     * @title 更新产品开发规格信息
     * @url specification/:id(\d+)
     * @method put
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function updateSpecification(Request $request, $id)
    {
        $params = $request->param();
        $goodsHelp = new GoodsHelp();
        $attributes = $goodsHelp->formatAttribute($params['attributes']);
        if (empty($id) || empty($attributes)) {
            return json(['message' => '产品ID不能为空或属性不能为空'], 400);
        }
        try {
            $result = $goodsHelp->modifyAttribute($id, $attributes, 1);
            return json($result, 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message], 400);
        }
    }

    /**
     * @title 获取编辑开发产品属性信息
     * @url attribute/:id(\d+)/edit
     * @method get
     * @param int $id
     * @return \think\Response
     */
    public function editAttribute($id)
    {
        if (empty($id)) {
            return json(['message' => '产品ID不能为空'], 400);
        }
        try {
            $goodsHelp = new GoodsHelp();
            $result = $goodsHelp->getAttributeInfo($id, 0);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => '获取失败'], 400);
        }
    }

    /**
     * @title 更新开发产品属性参数
     * @url attribute/:id(\d+)
     * @method put
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function updateAttribute(Request $request, $id)
    {
        $params = $request->param();
        $goodsHelp = new GoodsHelp();
        $attributes = $goodsHelp->formatAttribute($params['attributes']);
        if (empty($id) || empty($attributes)) {
            return json(['message' => '产品ID不能为空或属性不能为空'], 400);
        }
        try {
            $result = $goodsHelp->modifyAttribute($id, $attributes, 0);
            return json($result, 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message], 400);
        }
    }

    /**
     * @title 获取流程按钮组
     * @url processbtn
     * @method get
     * @return \think\Response
     */
    public function getProcessBtn()
    {
        $goodsdev = new GoodsdevModel();
        $result = $goodsdev->getProcessBtn();
        return json($result, 200);
    }

    /**
     * @title 获取流程处理按钮根据ID
     * @url processbtn/:id(\d+)
     * @method get
     * @param int $id
     * @return \think\Response
     */
    public function getProcessBtnById($id)
    {
        $goodsdev = new GoodsdevModel();
        $result = $goodsdev->getProcessBtnById($id);
        return json($result, 200);
    }

    /**
     * @title 产品开发流程操作
     * @url process/:id(\d+)
     * @method put
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function process(Request $request, $id)
    {
        $goodsdev = new GoodsdevModel();
        $params = $request->param();
        $userInfo = Common::getUserInfo($request);
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];
        try {
            $goodsdev->handle($id, $params, $user_id);
            return json(['message' => '操作成功'], 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 获取分类sku
     * @url category-sku
     * @method post
     * @param Request $request
     * @return Response
     */
    public function getCategorySkuLists(Request $request)
    {
        $category_id = $request->param('category_id');
        $attributes = json_decode($request->param('attributes', '[]'), true);
        $weight = $request->param('weight', 0);
        try {
            $goodsdev = new GoodsdevModel();
            $lists = $goodsdev->getCategorySkuLists($category_id, $attributes, $weight);
            return json($lists, 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message], 400);
        }
    }

    /**
     * @title 获取平台销售状态
     * @url platform-sale-status
     * @method get
     * @public
     * @param Request $request
     * @return \think\Request
     */
    public function getPlatformSaleStatus()
    {
        try {
            $goods = new GoodsHelp();
            $result = $goods->getPlatformSaleStatus();
            return json($result, 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message], 400);
        }
    }

    /**
     * @title 获取平台分类
     * @method get
     * @url :id/platform-sale
     * @author starzhan <397041849@qq.com>
     */
    public function getPlatformSale($id)
    {
        try {
            $server = new goodsdevModel();
            $result = $server->getPlatformSale($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 保存平台分类
     * @method put
     * @url :id/platform-sale
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function putPlatformSale($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo($this->request);
        try {
            $server = new goodsdevModel();
            $result = $server->putPlatformSale($id, $param, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 获取编辑sku
     * @url :id(\d+)/sku-list
     * @method get
     * @param int $id
     * @return \think\Request
     */
    public function getSkuList($id)
    {
        $server = new goodsdevModel();
        try {
            $result = $server->getSkuInfo($id);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => '获取失败'], 400);
        }
    }

    /**
     * @title 保存sku列表信息
     * @url :id(\d+)/sku-list
     * @method put
     * @param Request $request
     * @param int $id
     * @return \think\Response
     */
    public function saveSkuLists(Request $request, $id)
    {
        $service = new goodsdevModel();
        $param = $request->param();
        try {
            if (!isset($param['lists']) || !$param['lists']) {
                throw new Exception('lists不能为空');
            }
            $list = json_decode($param['lists'], true);
            if (!$list) {
                throw new Exception('lists不能为空');
            }
            $userInfo = Common::getUserInfo($request);
            $result = $service->saveSkuList($id, $list, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            return json([
                'message' => '保存失败' . ' ' . $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ], 400);
        }
    }

    /**
     * @title 获取编辑产品质检信息
     * @url :id(\d+)/qcitems
     * @method get
     * @param int $id
     * @return \think\Response
     */
    public function editQcItems($id)
    {
        $qcItems = new GoodsQcItems();
        try {
            $result = $qcItems->getGoodsQcItems($id, 0);
            return json($result, 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => $message], 400);
        }
    }

    /**
     * @title 获取产品修图要求
     * @url :id(\d+)/img-requirement
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function getImgRequirement($id)
    {
        try {
            $server = new goodsdevModel();
            $result = $server->getImgRequirement($id);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], $ex->getCode());
        }
    }

    /**
     * @title 保存修图要求
     * @method put
     * @url :id(\d+)/img-requirement
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function saveImgRequirement($id)
    {
        try {
            $param = $this->request->param();
            if (!isset($param['img_requirement']) || !$param['img_requirement']) {
                throw new Exception('修图要求不能为空!');
            }
            $goods = new goodsdevModel();
            $result = $goods->saveImgRequirement($id, $param);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 保存产品质检信息
     * @url :id(\d+)/qcitems
     * @method put
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function saveQcItems(Request $request, $id)
    {
        $qcItems = new GoodsQcItems();
        $lists = json_decode($request->param('lists'), true);

        try {
            $qcItems->saveGoodsQcItems($id, $lists);
            return json(['message' => '保存成功'], 200);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            return json(['message' => '保存失败' . $message], 400);
        }
    }

    /**
     * @title 获取开发产品节点信息
     * @url node/:id(\d+)
     * @method get
     * @public
     * @param int $id
     * @return \think\Response
     */
    public function node($id)
    {
        $goods = new goodsdevModel();
        $result = $goods->getNode($id);
        return json($result, 200);
    }

    /**
     * @title 批量处理流程
     * @url batch/process
     * @method post
     * @param \think\Request
     * @return \think\Response
     */
    public function batchProcess(Request $request)
    {
        $ids = json_decode($request->param('ids'), true);
        $code = $request->param('code');
        $remark = $request->param('remark');
        if (empty($ids) || empty($code)) {
            return json(['message' => '缺少必要信息'], 400);
        }
        $userInfo = Common::getUserInfo($request);
        $user_id = empty($userInfo) ? 0 : $userInfo['user_id'];
        $service = new goodsdevModel();
        try {
            foreach ($ids as $id) {
                $data = [
                    'code' => $code,
                    'remark' => $remark
                ];
                $service->handle($id, $data, $user_id);
            }
            return json(['message' => '操作成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 批量添加开发者矩阵
     * @url /developer/batch/add
     * @method post
     * @title 批量添加开发者
     * @author starzhan <397041849@qq.com>
     */
    public function addDeveloper()
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            if (!isset($param['lists'])) {
                throw new Exception('非法提交，参数错误');
            }
            $lists = json_decode($param['lists'], true);

            if (!is_array($lists)) {
                throw new Exception('非法提交，参数错误');
            }
            $GoodsDeveloper = new GoodsDeveloper();
            $result = $GoodsDeveloper->addDeveloper($lists, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 修改开发员信息
     * @method put
     * @url /developer/:id(\d+)
     * @author starzhan <397041849@qq.com>
     */
    public function developerUpdate($id)
    {
        $param = $this->request->param();
        try {
            $GoodsDeveloper = new GoodsDeveloper();
            $result = $GoodsDeveloper->developerUpdate($param, $id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 开发员矩阵列表
     * @url /developer
     * @method get
     * @author starzhan <397041849@qq.com>
     */
    public function developer()
    {
        $param = $this->request->param();
        try {
            $page = $param['page'] ?? 1;
            $page_size = $param['page_size'] ?? 50;
            $GoodsDeveloper = new GoodsDeveloper();
            $result = $GoodsDeveloper->developer($param, $page, $page_size);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 删除开发员信息
     * @method delete
     * @url /developer/:id(\d+)
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function removeDeveloper($id)
    {
        try {
            $GoodsDeveloper = new GoodsDeveloper();
            $result = $GoodsDeveloper->removeDeveloper($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 获取开发员矩阵详情
     * @url :id/developer
     * @method get
     * @author starzhan <397041849@qq.com>
     */
    public function getDeveloperById($id)
    {
        try {
            $GoodsDeveloper = new GoodsDeveloper();
            $result = $GoodsDeveloper->getDeveloperById($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 生成sku
     * @method get
     * @url :id/generate-sku
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function generateSku($id)
    {
        $param = $this->request->param();
        try {
            $server = new goodsdevModel();
            if (!isset($param['sku_id']) || !$param['sku_id']) {
                throw new Exception('提交参数为空');
            }
            $sku_id = json_decode($param['sku_id'], true);
            $result = $server->generateSkus($id, $sku_id);
            return json($result, 200);

        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 确认生成sku
     * @method put
     * @url :id/generate-sku
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function sureGenerateSku($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $server = new goodsdevModel();
            if (!isset($param['sku_list']) || !$param['sku_list']) {
                throw new Exception('提交参数为空');
            }
            $sku_list = json_decode($param['sku_list'], true);
            $result = $server->sureGenerateSku($id, $sku_list, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @url :id/declare
     * @title 保存报关信息
     * @method put
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function saveDeclare($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $server = new goodsdevModel();
            if (!isset($param['declare_name']) || !$param['declare_name']) {
                throw new Exception('中文报关名是必须的');
            }
            if (!isset($param['hs_code']) || !$param['hs_code']) {
                throw new Exception('海关编码是必须的');
            }
            $data = [];
            isset($param['declare_name']) && $data['declare_name'] = $param['declare_name'];
            isset($param['hs_code']) && $data['hs_code'] = $param['hs_code'];
            isset($param['declare_en_name']) && $data['declare_en_name'] = $param['declare_en_name'];

            $result = $server->saveDeclare($id, $data, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 指定摄影师
     * @method put
     * @url :id/set-grapher
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function setGrapher($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $server = new goodsdevModel();
            if (!isset($param['grapher']) || !$param['grapher']) {
                throw new Exception('摄影师不能为空');
            }
            $result = $server->setGrapher($id, $param['grapher'], $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 开始拍图
     * @method put
     * @url :id/start-photo
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function startPhoto($id)
    {
        $userInfo = Common::getUserInfo();
        try {
            $server = new goodsdevModel();
            $result = $server->startPhoto($id, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 设置原图路径
     * @method put
     * @url :id/set-photo-path
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function setPhotoPath($id)
    {
        $userInfo = Common::getUserInfo();
        $param = $this->request->param();
        try {
            $server = new goodsdevModel();
            if (!isset($param['photo_path']) || !$param['photo_path']) {
                throw new Exception('原图路径不能为空');
            }
            $result = $server->setPhotoPath($id, $param['photo_path'], $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 获取拍图待审核信息
     * @url :id/photo
     * @method get
     * @author starzhan <397041849@qq.com>
     */
    public function getPhotoInfo($id)
    {
        try {
            $server = new goodsdevModel();
            $result = $server->getPhotoInfo($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 分配翻译员
     * @method put
     * @url :id/set-translator
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function setTranslator($id)
    {
        $userInfo = Common::getUserInfo();
        $param = $this->request->param();
        try {
            $server = new goodsdevModel();
            if (!isset($param['translator']) || !$param['translator']) {
                throw new Exception('翻译信息不能为空');
            }
            $aTranslator = json_decode($param['translator'], true);
            $result = $server->setTranslator($id, $aTranslator, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 获取翻译员信息
     * @method get
     * @url :id/translator-info
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function getTranslatorInfo($id)
    {
        $userInfo = Common::getUserInfo();
        try {
            $server = new goodsdevModel();
            $result = $server->getTranslatorInfo($id, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 开始翻译
     * @method put
     * @url :id/translator-starting
     * @author starzhan <397041849@qq.com>
     */
    public function startTranslator($id)
    {
        $userInfo = Common::getUserInfo();
        try {
            $server = new goodsdevModel();
            $result = $server->startTranslator($id, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 翻译中确定
     * @method put
     * @url :id/translator-ing
     * @author starzhan <397041849@qq.com>
     */
    public function translatorIng($id)
    {
        $userInfo = Common::getUserInfo();
        $param = $this->request->param();
        try {
            $server = new goodsdevModel();
            $result = $server->translatorIng($id, $param, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @url :id/:lang_id/translator-submit
     * @method put
     * @title 翻译提交审批
     * @author starzhan <397041849@qq.com>
     */
    public function translatorSubmit($id, $lang_id)
    {
        $userInfo = Common::getUserInfo();
        try {
            $server = new goodsdevModel();
            $result = $server->translatorSubmit($id, $lang_id, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 审核不通过退回语种
     * @method put
     * @url :id/translator-back
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function translatorBack($id)
    {
        $userInfo = Common::getUserInfo();
        $param = $this->request->param();
        try {
            $aLang = [];
            if (isset($param['langs'])) {
                $aLang = json_decode($param['langs'], true);
            }
            if (!$aLang || !is_array($aLang)) {
                throw new Exception('请选择要退回重新翻译的语种');
            }
            if (!isset($param['remark']) || !$param['remark']) {
                throw new Exception('原因不能为空');
            }
            $server = new goodsdevModel();
            $result = $server->translatorBack($id, $aLang, $userInfo['user_id'], $param['remark']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 待分配修图指定美工
     * @url :id/designer-setting
     * @method put
     * @author starzhan <397041849@qq.com>
     */
    public function designerSetting($id)
    {
        $userInfo = Common::getUserInfo();
        $param = $this->request->param();
        try {
            if (!isset($param['designer']) || !$param['designer']) {
                throw new Exception('请选择美工');
            }
            $server = new goodsdevModel();
            $result = $server->designerSetting($id, $param['designer'], $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 开始修图
     * @param $id
     * @method put
     * @url :id/designer-starting
     * @author starzhan <397041849@qq.com>
     */
    public function designerStarting($id)
    {
        $userInfo = Common::getUserInfo();
        try {
            $server = new goodsdevModel();
            $result = $server->designerStarting($id, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 保存修图路径
     * @method put
     * @url :id/ps_img_url
     * @author starzhan <397041849@qq.com>
     */
    public function psImgUrl($id)
    {
        $userInfo = Common::getUserInfo();
        $param = $this->request->param();
        try {
            if (!isset($param['ps_img_url']) || !$param['ps_img_url']) {
                throw new Exception('修图路径不能为空');
            }
            $server = new goodsdevModel();
            $result = $server->psImgUrl($id, $param['ps_img_url'], $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @url :id/final_submit
     * @method put
     * @title 提交终审..
     * @author starzhan <397041849@qq.com>
     */
    public function finalSubmit($id)
    {
        $userInfo = Common::getUserInfo();
        $param = $this->request->param();
        try {
            if (!isset($param['grapher']) || !$param['grapher']) {
                throw new Exception('摄影分数不能为空');
            }
            if (!isset($param['designer']) || !$param['designer']) {
                throw new Exception('美工分数不能为空');
            }
            if (!isset($param['translator']) || !$param['translator']) {
                throw new Exception('修图路径不能为空');
            }
            $server = new goodsdevModel();
            $result = $server->finalSubmit($id, $param['grapher'], $param['designer'], $param['translator'], $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }


    /**
     * @title 获取退回的指定节点
     * @url :id/back-process
     * @method get
     * @author starzhan <397041849@qq.com>
     */
    public function getBackProcess($id)
    {
        try {
            $server = new goodsdevModel();
            $result = $server->getBackProcess($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 退回的指定节点
     * @url :id/back-process
     * @method put
     * @author starzhan <397041849@qq.com>
     */
    public function backProcess($id)
    {
        $userInfo = Common::getUserInfo();
        $param = $this->request->param();
        try {
            if (!isset($param['remark']) || !$param['remark']) {
                throw new Exception('原因不能为空');
            }
            $aProcessId = [];
            if (isset($param['process_ids']) && $param['process_ids']) {
                $aProcessId = json_decode($param['process_ids'], true);
            }
            if (!$aProcessId || !is_array($aProcessId)) {
                throw new Exception('打回的流程不能为空');
            }
            $server = new goodsdevModel();
            $result = $server->backProcess($id, $aProcessId, $param['remark'], $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 发布产品
     * @url :id/release
     * @method put
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function release($id)
    {
        $userInfo = Common::getUserInfo();
        try {
            $server = new goodsdevModel();
            $result = $server->release($id, $userInfo['user_id']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 获取菜单
     * @method get
     * @url :id/menu
     * @author starzhan <397041849@qq.com>
     */
    public function menu($id)
    {

        $server = new goodsdevModel();
        $result = $server->menu($id);
        return json($result, 200);
    }


}