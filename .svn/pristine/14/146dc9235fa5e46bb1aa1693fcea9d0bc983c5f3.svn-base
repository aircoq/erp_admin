<?php

namespace app\goods\controller;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use think\Db;
use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsGallery;
use app\goods\service\GoodsImage as GoodsImageService;
use app\goods\service\GoodsSelfGallery;
use think\Config;
use app\common\service\Common;

/**
 * Class GoodsImage
 * @title 产品图片保存
 * @module 商品系统
 * @author ZhaiBin
 * @package app\goods\controller
 */
class GoodsImage extends Base
{
    /**
     * 保存新建的资源
     * @title 保存产品图片
     * @url /goods-image
     * @method post
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $goods_id = $request->param('id');
        $images = json_decode($request->param('images'), true);
        if (empty($images)) {
            return json(['message' => '无任何修改'], 200);
        }
        try {
            $service = new GoodsImageService();
            $service->save($goods_id, $images);
            return json(['message' => '更新成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => '上传失败 : ' . $ex->getMessage()], 400);
        }
    }

    public function saveResources()
    {
        $param = $this->request->param();
        try {
            if (!isset($param['resource']) || !$param['resource']) {
                throw new Exception('参数不能为空！');
            }
            $resource = json_decode($param['resource'], true);
            if (!$resource) {
                throw new Exception('参数错误');
            }
            $service = new GoodsImageService();
            $result = $service->saveResources($resource);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * 显示指定的资源 单个sku的图片放在主图那
     * @title 查看产品图片
     * @url /goods-image/:id(\d+)
     * @method get
     * @param \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function read(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '信息不正确'], 400);
        }

        $service = new GoodsImageService();
        $lists = $service->getLists($id, Config::get('picture_base_url'), $request->param());
        if (count($lists) == 2) {
            $lists[0]['images'] = array_merge($lists[0]['images'], $lists[1]['images']);
            unset($lists[1]);
        }
        
        return json($lists, 200);
    }

    /**
     * @title 获取相关的资源，支持 goodsid 与 sku_id
     * @url /goods-image/get-thumb
     * @author starzhan <397041849@qq.com>
     */
    public function getThumb()
    {
        $param = $this->request->param();
        try {
            $aGoodsId = [];
            $aSkuId = [];
            isset($param['goods_id']) && $aGoodsId = json_decode($param['goods_id'],true);
            isset($param['sku_id']) && $aSkuId = json_decode($param['sku_id'],true);
            if(!$aGoodsId&&!$aSkuId){
                throw new Exception('缺少必要参数');
            }
            $service = new GoodsImageService();
            $result = $service->getThumbList($aGoodsId, $aSkuId);

            if($result) {

                $data = [];
                foreach ($result as $key =>$val){

                    //如果png图片存在,则过滤
                    if(isset($param['channel']) && $param['channel'] == 4 && strpos($val['thumb'],'.png') !== false) {
                        continue;
                    }
                    $data[$val['unique_code']] = $val;
                }

                $result = [];
                foreach ($data as $val) {
                    $result[] = $val;
                }
            }

            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * 保存新建的资源
     * @title 保存产品图片
     * @url /goods-image/self-image
     * @method post
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function addSelfImage(Request $request)
    {
        $params = $request->param();
        $goods_id = $request->param('goods_id');
        $images = json_decode($request->param('images'), true);
        if (empty($images)) {
            return json(['message' => '缺少图片详情'], 400);
        }
        $channelId = $request->param('channel_id');
        $userInfo = Common::getUserInfo($request);
        $userId = empty($userInfo) ? 0 : $userInfo['user_id'];
        try {
            $goodsImage = new GoodsImageService();
            if (!$goods_id) {
                $goods_id = $goodsImage->getGoodsId($params['channel_sku'], $channelId, $params['account_id']);
            }
            if (!$goods_id) {
                throw new Exception('缺少查询必要信息');
            }
            $service = new GoodsSelfGallery();
            $aPath = $service->save($goods_id, $images, $channelId, $userId);
            $host = Cache::store('configParams')->getConfig('innerPicUrl')['value'] . "/";
            return json(['message' => '上传成功', 'path' => json_encode($aPath),'baseUrl'=>$host], 200);
        } catch (Exception $ex) {
            return json(['message' => '上传失败' . $ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取刊登图片
     * @param Request $request
     * @return \think\response
     * @throws Exception
     * @url /goods-image/listing
     */
    public function listing(Request $request)
    {
        $page = $request->param('page', 1);
        $pageSize = $request->param('pageSize', 200);
        $goods_id = $request->param('goods_id', 0);
        $sku_id = $request->param('sku_id', 0);
        $service = new GoodsImageService();
        $params = $request->param();
        try {
            if (!$goods_id) {
                $goods_id = $service->getGoodsId($params['channel_sku'], $params['channel_id'], $params['account_id']);
            }
            if (!$goods_id) {
                throw new Exception('缺少查询必要信息');
            }
            // $count = $service->countListingImage($goods_id, $sku_id);
            $lists = $service->getListingImage($goods_id, $sku_id, $page, $pageSize);

            return json([
                'count' => count($lists),
                'data' => $lists,
                'page' => $page,
                'pageSize' => $pageSize
            ]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 获取自定义图片
     * @param Request $request
     * @return \think\response
     * @throws Exception
     * @url /goods-image/self-image
     */
    public function getSelfImage(Request $request)
    {
        $page = $request->param('page', 1);
        $pageSize = $request->param('pageSize', 20);
        $service = new GoodsSelfGallery();
        $params = $request->param();
        try {
            /*if (!$goods_id) {
                $goods_id = $service->getGoodsId($params['channel_sku'], $params['channel_id'], $params['account_id']);
            }
            if (!$goods_id) {
                throw new Exception('缺少查询必要信息');
            }*/
            $service->setWhere($params);
            $count = $service->getCount();
            $lists = $service->getList($page, $pageSize);

            return json([
                'count' => $count,
                'data' => $lists,
                'page' => $page,
                'pageSize' => $pageSize
            ]);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 获取产品图片计算路径
     * @method get
     * @url /goods-image/path
     * @apiParam name:id type:int desc:产品ID
     * @param Request $request
     * @return \think\response\Json
     */
    public function getImagePath(Request $request)
    {
        $goods_id = $request->param('id');
        if (empty($goods_id)) {
            return json(['message' => '参数错误'], 400);
        }

        try {
            $data = [];
            $goods_info = Cache::store('goods')->getGoodsInfo($goods_id);
            if (empty($goods_info)) {
                throw new JsonErrorException('产品不存在！');
            }
            //获取分类信息
            $second_category = \app\common\model\Category::where(['id' => $goods_info['category_id']])->field('code,pid')->find();
            if (empty($second_category) || empty($second_category['code'])) {
                throw new JsonErrorException('产品二级分类不存在或code为空！');
            }
            $first_category = \app\common\model\Category::where(['id' => $second_category['pid']])->field('code,pid')->find();
            if (empty($first_category) || empty($first_category['code'])) {
                throw new JsonErrorException('产品一级分类不存在或code为空！');
            }
            if(empty($goods_info['thumb_path'])){
                $data['goods_id'] = $goods_id;
                $data['spu'] = $goods_info['spu'];
                $data['first_category'] = $first_category['code'];
                $data['second_category'] = $second_category['code'];
                $host = config('image_ftp_host');
                $path = "//{$host}/skupic/{$data['first_category']}/{$data['second_category']}/{$data['spu']}";
            }else{
                $path = $goods_info['thumb_path'];
            }
            $path = str_replace('/', '\\', $path);
            return json(['path' => $path, 'status' => $goods_info['sales_status']]);
        } catch (Exception $exception) {
            throw new JsonErrorException($exception->getMessage());
        }
    }
}