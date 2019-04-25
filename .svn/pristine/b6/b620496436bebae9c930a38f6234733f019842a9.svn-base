<?php
namespace app\index\controller;

use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use app\index\service\WishAccountHealthService;
use think\Db;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\common\service\Common as CommonService;
use app\index\service\WishShippingRateService;
use app\common\model\wish\WishAccount as WishAccountModel;

/**
 * @module 账号管理
 * @title wish占比与物流设置
 * @author libaimin
 * @url /wish-shipping-rate
 * Class Wish
 * @package app\goods\controller
 */
class WishShippingRate extends Base
{
    protected $wishShippingRateServer;

    protected function init()
    {
        if (is_null($this->wishShippingRateServer)) {
            $this->wishShippingRateServer = new WishShippingRateService();
        }
    }

    /**
     * @title 显示资源列表
     * @return \think\Response
     */
    public function index()
    {
        $request = Request::instance();
        $params = $request->param();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $result = $this->wishShippingRateServer->lists($params, $page, $pageSize);
        return json($result, 200);
    }

    /**
     * @title 显示指定的资源
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $result = $this->wishShippingRateServer->read($id,false);
        return json($result, 200);
    }

    /**
     * @title 显示编辑资源表单页.
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $result = $result = $this->wishShippingRateServer->read($id);
        return json($result, 200);
    }

    /**
     * @title 保存更新的资源
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data['warehouse_id'] = trim($request->put('warehouse_id', 2));
        $data['general_surface_shipping'] = trim($request->put('general_surface_shipping', '[]'));
        $data['general_registration_shipping'] = trim($request->put('general_registration_shipping', '[]'));
        $data['special_surface_shipping'] = trim($request->put('special_surface_shipping', '[]'));
        $data['special_registration_shipping'] = trim($request->put('special_registration_shipping', '[]'));
        //获取操作人信息
//        $user = CommonService::getUserInfo($request);
//        if (!empty($user)) {
//            $data['updater_id'] = $user['user_id'];
//        }
        $data['updated_time'] = time();
        $this->wishShippingRateServer->update($id, $data);
        $result = $this->wishShippingRateServer->read($id);
        return json(['message' => '更改成功','data' => $result]);
    }


    /**
     * @title 计算订单占比
     * @url order-rate
     * @method post
     */
    public function orderRate()
    {
        $request = Request::instance();
        $date_s = $request->post('date_s', date('Y-m'));
        $date_e = $request->post('date_e', date('Y-m'));
        $date_s .= '-01 0:0:0';
        $date_e = date('Y-m-t',strtotime($date_e));
        $date_e .= ' 23:59:59';
        $result = $this->wishShippingRateServer->orderRate($date_s,$date_e);
        return json(['message' => '计算成功','data' => $result]);
    }

    /**
     * @title 计算重量运费
     * @url shipping-charge
     * @method post
     */
    public function addShippingCharge()
    {
        $request = Request::instance();
        $date_s = $request->post('weight_start', 1);
        $date_e = $request->post('weight_end', 4000);
        $result = $this->wishShippingRateServer->addShippingCharge($date_s,$date_e);
        return json($result);
    }

    /**
     * @title wish重量与费用列表
     * @url weight-list
     * @method get
     */
    public function weightList()
    {
        $request = Request::instance();
        $params = $request->param();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $result = $this->wishShippingRateServer->weightList($params, $page, $pageSize);
        return json($result, 200);
    }


}
