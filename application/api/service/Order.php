<?php

namespace app\api\service;

use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\order\queue\DistributionToLocalOrder;
use app\order\service\OrderHelp;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2018/8/16
 * Time: 上午10:15
 */
class Order extends Base
{
    /**
     * 接收订单
     */
    public function receive()
    {
        $orderData = $this->requestData['order'];
        $orderDetail = $this->requestData['order_detail'];
        $shipping = $this->requestData['shipping'];

        $minData = [
            'order' => $orderData,
            'order_detail' => $orderDetail,
            'shipping' => $shipping,
        ];
        //将分销推送订单存入缓存
        Cache::handler()->hSet('hash:distributionReceiveOrder:join_log:' . date('Y-m-d', time()), date('H:i:s',
            time()), json_encode($minData));

        try {
            //加入队列
            //(new UniqueQueuer(DistributionToLocalOrder::class))->push(['order' => $orderData,'orderDetail' => $orderDetail]);
            $orderService = new \app\common\service\Order();

            $code = Cache::store('Channel')->getChannelName($orderData['channel_id']);
            $orderData['order_number'] = $code . '-' . $orderData['channel_order_number'];


            //将分销推送订单存入缓存
            Cache::handler()->hSet('hash:distributionReceiveOrder:log:' . date('Y-m-d', time()), date('H:i:s',
                time()), json_encode(['channel_order_number' => $orderData['channel_order_number']]));
            $orderService->add([['order' => $orderData,'orderDetail' => $orderDetail,'shipping' => $shipping]]);
            $this->retData['message'] = 'success';
        } catch (\Exception $e) {
            $this->retData['status'] = -10;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 取消订单
     */
    public function cancel()
    {
        $channelOrderNumber = $this->requestData['channel_order_number'];
        try {
            $is_ok = (new OrderHelp())->cancel($channelOrderNumber);
            $this->retData['message'] = $is_ok ? 'success' : 'failure';
            $this->retData['status'] = $is_ok ? 1 : -10;
        } catch (\Exception $e) {
            $this->retData['status'] = -10;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 更新物流方式
     */
    public function updateShipping()
    {
        $channelOrderNumber = $this->requestData['channel_order_number'];
        $warehouseCode = $this->requestData['warehouse_code'];
        $shippingCode = $this->requestData['shipping_code'];
        try {
            $is_ok = (new OrderHelp())->updateShippingPL($channelOrderNumber,$warehouseCode,$shippingCode);
            $this->retData['message'] = $is_ok ? 'success' : 'failure';
            $this->retData['status'] = $is_ok ? 1 : -10;
        } catch (\Exception $e) {
            $this->retData['status'] = -10;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 更新收件人信息
     */
    public function updateAddress()
    {
        $channelOrderNumber = $this->requestData['channel_order_number'];
        $address = $this->requestData['address'];
        try {
            $is_ok = (new OrderHelp())->updateAddressPL($channelOrderNumber,$address);
            $this->retData['message'] = $is_ok ? 'success' : 'failure';
            $this->retData['status'] = $is_ok ? 1 : -10;
        } catch (\Exception $e) {
            $this->retData['status'] = -10;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 获取运输
     */
    public function shipping()
    {
        $channelOrderNumber = $this->requestData['channel_order_number'];
        try {
            $shippingInfo = (new OrderHelp())->getShippingInfo($channelOrderNumber);
            $this->retData['data'] = $shippingInfo;
            $this->retData['status'] = 1;
            $this->retData['message'] = 'success';
        } catch (\Exception $e) {
            $this->retData['status'] = -10;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 订单进度信息
     * @return array
     */
    public function speed()
    {
        $channelOrderNumber = $this->requestData['channel_order_number'];
        try {
            $speedInfo = (new OrderHelp())->getOrderSpeed($channelOrderNumber);
            $this->retData['data'] = $speedInfo;
            $this->retData['status'] = 1;
            $this->retData['message'] = 'success';
        } catch (\Exception $e) {
            $this->retData['status'] = -10;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }
}