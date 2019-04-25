<?php

namespace app\publish\controller;

use app\common\controller\Base;
use app\common\model\User;
use app\publish\validate\EbayCommonValidate;
use think\Request;
use app\publish\service\EbayCommonService;
use app\common\service\Common;
use think\Exception;

/**
 * @module 刊登系统
 * @title Ebay刊登公用模板
 * @author wlw2533
 * @url /Publish
 */

class EbayCommon extends Base
{
    private $service;
    private $validate;
    //初始化服务类
    protected function init()
    {
        $this->service = new EbayCommonService();
        $this->validate = new EbayCommonValidate();
    }

    /**
     * @title 获取公共模块列表
     * @url /publish-ebay/modules
     * @method get
     */
    public function getCommonModeList(Request $request)
    {
        try{
            $param = $request->param();
            $result = $this->validate->myCheck($param,'list');
            if (!$result) {
                return json(['message'=>$this->validate->getError()],500);
            }
            $res = $this->service->getModelList($param);
            return json($res);
        }catch(\Exception $e){
            return json(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 获取刊登风格列表
     * @url /publish-ebay/styles
     * @method get
     * 为什么要和上面的重复，是因为前端的限制，页面上公共模块，组合模块，促销设置，刊登风格，自定义分类是分成不同的
     * 目录的，路由不能相同
     */
    public function getModelStyles(Request $request)
    {
        $param = $request->param();
        $result = $this->validate->myCheck($param,'list');
        if (!$result) {
            return json(['message'=>$this->validate->getError()],500);
        }
        $res = $this->service->getModelList($param);
        return json($res);
    }

    /**
     * @title 获取模块组合列表
     * @url /publish-ebay/combs
     * @method get
     * 为什么要和上面的重复，是因为前端的限制，页面上公共模块，组合模块，促销设置，刊登风格，自定义分类是分成不同的
     * 目录的，路由不能相同
     */
    public function getModelCombs(Request $request)
    {
        $param = $request->param();
        $result = $this->validate->myCheck($param,'list');
        if (!$result) {
            return json(['message'=>$this->validate->getError()],500);
        }
        $res = $this->service->getModelList($param);
        return json($res);
    }

    /**
     * @title 获取促销设置列表
     * @url /publish-ebay/promotions
     * @method get
     * 为什么要和上面的重复，是因为前端的限制，页面上公共模块，组合模块，促销设置，刊登风格，自定义分类是分成不同的
     * 目录的，路由不能相同
     */
    public function getModelPromotions(Request $request)
    {
        $param = $request->param();
        $result = $this->validate->myCheck($param,'list');
        if (!$result) {
            return json(['message'=>$this->validate->getError()],500);
        }
        $res = $this->service->getModelList($param);
        return json($res);
    }

    /**
     * @title 获取自定义分类列表
     * @url /publish-ebay/cates
     * @method get
     * 为什么要和上面的重复，是因为前端的限制，页面上公共模块，组合模块，促销设置，刊登风格，自定义分类是分成不同的
     * 目录的，路由不能相同
     */
    public function getCates(Request $request)
    {
        $param = $request->param();
        $result = $this->validate->myCheck($param,'list');
        if (!$result) {
            return json(['message'=>$this->validate->getError()],500);
        }
        $res = $this->service->getModelList($param);
        return json($res);
    }

    /**
     * @title 获取待编辑模块信息
     * @url /publish-ebay/module
     * @method get
     */
    public function editCommonMode(Request $request)
    {
        try{
            $param = $request->param();
            $result = $this->validate->myCheck($param,'edit');
            if (!$result) {
                return json(['message'=>$this->validate->getError()],500);
            }
            $res = $this->service->getModeInfo($param);
            return json($res);
        }catch(\Exception $e){
            return json(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @title 保存公共模块
     * @url /publish-ebay/module
     * @method POST
     */
    public function saveCommonModel(Request $request)
    {
        try{
            $params = $request->param();
            $result = $this->validate->saveCheck($params);
            if (!$result) {
                return json(['message'=>$this->validate->getError()],500);
            }
            $this->service->saveModel($params);
            return json(['message'=>'操作成功']);
        }catch(Exception $e){
            return json($e->getFile()."|".$e->getLine()."|".$e->getMessage(),500);
        }
    }

    /**
     * @title 删除模块
     * @url /publish-ebay/module
     * @method delete
     * @param Request $request
     * @return \think\response\Json
     */
    public function deleteModule(Request $request)
    {
        try {
            $params = $request->param();
            $result = $this->validate->myCheck($params,'edit');
            if (!$result) {
                return json(['message'=>$this->validate->getError()],500);
            }
            $res = $this->service->deleteModule($params);
            return json($res);
        } catch (\Exception $e) {
            return json(['message'=>$e->getMessage()], 500);
        }
    }

    /**
     * @title 上传风格图片到EPS获取https地址
     * @url ebay-common/upload-style-imgs
     * @method POST
     *
     * @apiParam name:imgs type:json require:1 desc:图片完整路径

     */
    public function uploadStyleImgs(Request $request)
    {
        try {
            $imgs = json_decode($request->param('imgs'), true);
            $res = $this->service->uploadStyleImgs($imgs);
            return json($res, $res['result'] ? 200 : 500);
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
    /**
     * @title 获取ebay销售员姓名
     * @url EbayCommon/getSellers
     * @method GET
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
//    public function getSellers(Request $request)
//    {
//        try {
//            $userNames = $this->service->getSellers();
//            return json(['result'=>true, 'data'=>$userNames]);
//        } catch(Exception $e) {
//            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
//        }
//    }
}
