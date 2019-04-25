<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/4/18
 * Time: 10:36
 */

namespace app\index\controller;


use app\index\service\EbayAccountPerformanceService;
use think\Request;


/**
 * @module 账号监控
 * @title ebay账号表现
 * @author wlw2533
 */
class EbayAccountPerformance
{
    private $service;
    public function __construct()
    {
        $this->service = new EbayAccountPerformanceService();
    }

    /**
     * @title 获取eBay账号表现整体状态
     * @url /ebay/account-performance
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $params = $request->param();//参数太少，不使用验证机制
        $res = $this->service->globalStatusList($params);
        return json($res);
    }


    /**
     * @title 批量同步账号表现数据
     * @url /ebay/account-performance/sync/batch
     * @method post
     * @return \think\response\Json
     */
    public function sync(Request $request)
    {
        $accountIds = explode(',',$request->param('accountIds'));
        $this->service->sync($accountIds);
        return json(['成功加入同步队列']);
    }


    /**
     * @title 获取综合表现状态
     * @url /ebay/account-performance/:account_id/ltnp
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function ltnp($account_id)
    {
        $res = $this->service->ltnp($account_id);
        return json($res);
    }

    /**
     * @title 获取非货运表现状态
     * @url /ebay/account-performance/:account_id/tci
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function tci($account_id)
    {
        $res = $this->service->tci($account_id);
        return json($res);
    }


    /**
     * @title 获取导致非货运表现问题刊登列表
     * @url /ebay/account-performance/:account_id/nonshipping-defect
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function nonShippingDefect($account_id,Request $request)
    {
        $params = $request->param();
        $params['account_id'] = $account_id;
        $res = $this->service->nonShippingDefect($params);
        return json($res);
    }



    /**
     * @title 获取货运表现状态
     * @url /ebay/account-performance/:account_id/ship
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function ship($account_id)
    {
        $res = $this->service->ship($account_id);
        return json($res);
    }

    /**
     * @title 获取货运问题刊登（1-8周）列表
     * @url /ebay/account-performance/:account_id/ship-defect1-8
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function shipDefect1to8($account_id,Request $request)
    {
        $params = $request->param();
        $params['account_id'] = $account_id;
        $res = $this->service->shipDefect1to8($params);
        return json($res);
    }

    /**
     * @title 获取货运问题刊登（5-12周）列表
     * @url /ebay/account-performance/:account_id/ship-defect5-12
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function shipDefect5to12($account_id,Request $request)
    {
        $params = $request->param();
        $params['account_id'] = $account_id;
        $res = $this->service->shipDefect5to12($params);
        return json($res);
    }

    /**
     * @title 获取物流标准政策
     * @url /ebay/account-performance/:account_id/shipping-policy
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function shippingPolicy($account_id)
    {
        $res = $this->service->shippingPolicy($account_id);
        return json($res);
    }


    /**
     * @title 获取SpeedPAK物流表现状态
     * @url /ebay/account-performance/:account_id/speed-pak
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function speedPak($account_id)
    {
        $res = $this->service->speedPak($account_id);
        return json($res);
    }

    /**
     * @title 下载SpeedPAK 物流管理方案及其他符合政策要求的物流服务使用状态相关交易
     * @url /ebay/account-performance/:account_id/speed-pak-list/download
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function speedPakListDownload($account_id)
    {
        $res = $this->service->speedPakListDownload($account_id);
        return json($res);
    }

    /**
     * @title 下载卖家设置SpeedPAK物流选项与实际使用物流服务不符表现相关交易
     * @url /ebay/account-performance/:account_id/speed-pak-misuse/download
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function speedPakMisuseDownload($account_id)
    {
        $res = $this->service->speedPakMisuseDownload($account_id);
        return json($res);
    }



    /**
     * @title 获取海外仓标准
     * @url /ebay/account-performance/:account_id/acct-list
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function acctList($account_id)
    {
        $res = $this->service->acctList($account_id);
        return json($res);
    }

    /**
     * @title 下载海外仓服务标准政策相关交易
     * @url /ebay/account-performance/:account_id/warehouse/download
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function warehouseDownload($account_id)
    {
        $res = $this->service->warehouseDownload($account_id);
        return json($res);
    }

    /**
     * @title 获取商业计划追踪表现状态
     * @url /ebay/account-performance/:account_id/pgc-tracking
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function pgcTracking($account_id)
    {
        $res = $this->service->pgcTracking($account_id);
        return json($res);
    }

    /**
     * @title 获取待处理刊登列表
     * @url /ebay/account-performance/:account_id/qclist
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function qclist($account_id,Request $request)
    {
        $params = $request->param();
        $params['account_id'] = $account_id;
        $res = $this->service->qclist($params);
        return json($res);
    }

    /**
     * @title 获取买家未收到物品提醒列表
     * @url /ebay/account-performance/:account_id/seller-inr
     * @method get
     * @param $account_id
     * @return \think\response\Json
     */
    public function sellerInr($account_id,Request $request)
    {
        $params = $request->param();
        $params['account_id'] = $account_id;
        $res = $this->service->sellerInr($params);
        return json($res);
    }



}