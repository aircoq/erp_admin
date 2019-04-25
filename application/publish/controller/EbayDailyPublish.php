<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/3/22
 * Time: 17:02
 */

namespace app\publish\controller;

use app\publish\service\EbayDailyPublishService;
use app\publish\validate\EbayDailyPublishValidate;
use think\Request;

/**
 * @module 刊登系统
 * @title Ebay每日刊登
 * @author wlw2533
 */

class EbayDailyPublish
{
    protected $validate;
    protected $service;

    public function __construct()
    {
        $this->validate = new EbayDailyPublishValidate();
        $this->service = new EbayDailyPublishService();
    }

    /**
     * @title 获取每日刊登列表
     * @url /publish-ebay/daily-publish
     * @method get
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $params = $request->param();
        $res = $this->validate->myCheck($params,'list');
        if (!$res) {
            return json(['message'=>$this->validate->getError()],500);
        }
        $res = $this->service->lists($params);
        return json($res);
    }

    /**
     * @title 批量转接
     * @url /publish-ebay/daily-publish/seller
     * @method post
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function setSeller(Request $request)
    {
        $params = $request->param('data');
        $params = json_decode($params,true);
        if (!$params) {
            return json('参数格式错误',500);
        }
        $res = $this->validate->myCheck($params,'ss',true);
        if (!$res) {
            return json(['message'=>$this->validate->getError()],500);
        }
        $this->service->setSeller($params);
        return json(['message'=>'操作成功']);
    }

    /**
     * @title 导出
     * @url /publish-ebay/daily-publish/export
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        $params = $request->param();
        $res = $this->validate->myCheck($params,'list');
        if (!$res) {
            return json(['message'=>$this->validate->getError()],500);
        }
        $res = $this->service->export($params);
        return json($res);
    }
    

}