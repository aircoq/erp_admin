<?php
namespace app\api\service;

use app\customerservice\service\OrderSaleService;
/**
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2018/12/26
 * Time: 19:47
 */
class OrderSale extends Base
{
    /**
     * 新增退货售后单
     */
    public function returnAfterSale()
    {
        //接收参数
        // channel_order_number remark warehouse_id buyer_return_carrier buyer_return_tracking_num goods
        $data = [];
        $data['order_sale'] = json_decode($this->requestData['order_sale'], true);
//        $data['goods'] = json_decode($this->requestData['goods'], true);
        try {
            $orderSaleService = new OrderSaleService();
            $orderSaleService->returnAfterSale($data);
            $this->retData['message'] = '添加成功';
        } catch (Exception $e) {
            $this->retData['message'] = '添加失败，失败原因:'.$e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 取消退货售后单
     */
    public function cancelAfterSale()
    {
        $channel_order_number = $this->requestData['channel_order_number'];
        try {
            $orderSaleService = new OrderSaleService();
            $orderSaleService->cancelAfterSale($channel_order_number);
            $this->retData['message'] = '取消成功';
        } catch (Exception $e) {
            $this->retData['message'] = '取消失败，失败原因:'.$e->getMessage();
        }
        return $this->retData;
    }
}