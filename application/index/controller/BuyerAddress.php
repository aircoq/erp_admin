<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\BuyerAddressService;
use think\Request;
use app\common\service\Common as CommonService;

/**
 * @title 买家地址列表
 * @module 基础设置
 * @author phill
 * @url buyer-addresses
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/4
 * Time: 19:47
 */
class BuyerAddress extends Base
{
    protected $buyerAddressService;

    protected function init()
    {
        if (is_null($this->buyerAddressService)) {
            $this->buyerAddressService = new BuyerAddressService();
        }
    }

    /**
     * @title 买家地址列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $channel_buyer_id = $request->get('id');
        $result = $this->buyerAddressService->addressList($channel_buyer_id);
        return json($result, 200);
    }

    /**
     * @title 保存买家地址信息
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $address = $request->post('address', '');
        $channel_buyer_id = $request->post('channel_buyer_id', '');
        if (!is_json($address)) {
            return json(['message' => '参数格式错误'], 400);
        }
        if(empty($channel_buyer_id) || !is_numeric($channel_buyer_id)){
            return json(['message' => '参数错误'], 400);
        }
        $address = json_decode($address, true);
        $user = CommonService::getUserInfo();
        if (!empty($user)) {
            $basic['creator_id'] = $user['user_id'];
            $basic['create_time'] = time();
        }
        $address['channel_buyer_id'] = $channel_buyer_id;
        $address_id = $this->buyerAddressService->add($address);
        return json(['message' => '保存成功','id' => $address_id], 200);
    }

    /**
     * @title 更新买家地址信息
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function update(Request $request, $id)
    {
        $address = $request->put('address', '');
        if (!is_json($address) || !is_numeric($id)) {
            return json(['message' => '参数格式错误'], 400);
        }
        $address = json_decode($address, true);
        $user = CommonService::getUserInfo();
        if (!empty($user)) {
            $basic['updater_id'] = $user['user_id'];
            $basic['update_time'] = time();
        }
        $this->buyerAddressService->update($address, $id);
        return json(['message' => '更新成功'], 200);
    }

    /**
     * @title 删除买家地址
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        if(!is_numeric($id)){
            return json(['message' => '参数值错误'],400);
        }
        $this->buyerAddressService->delete($id);
        return json(['message' => '删除成功'], 200);
    }

    /**
     * @title 设置默认地址
     * @url default
     * @method post
     * @return \think\response\Json
     */
    public function defaultAddress()
    {
        $request = Request::instance();
        $id = $request->post('id', 0);
        $channel_buyer_id = $request->post('channel_buyer_id', 0);
        if (empty($id) || empty($channel_buyer_id) || !is_numeric($id) || !is_numeric($channel_buyer_id)) {
            return json(['message' => '参数值错误'], 400);
        }
        $this->buyerAddressService->defaultAddress($id, $channel_buyer_id);
        return json(['message' => '设置成功'], 200);
    }
}