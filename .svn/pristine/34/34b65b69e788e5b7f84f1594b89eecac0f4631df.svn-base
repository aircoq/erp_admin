<?php

namespace app\api\service;

use app\warehouse\service\Warehouse;
use app\warehouse\service\ShippingMethod;
use app\warehouse\service\WarehouseGoodsList;
use app\warehouse\service\StockOut;
use think\Exception;
use app\common\cache\Cache;
use app\order\service\OrderHelp;

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2018/08/18
 * Time: 14:44
 */
class Shipping extends Base
{
    /**
     * 获取仓库列表
     */
    public function getWarehouse()
    {

        try {
            $warehouseService = new Warehouse();
            $lists = $warehouseService->getWarehouseByTypes(Warehouse::TYPE_LOCAL);
            $data = [];
            foreach ($lists as $value) {
                if ($value['id'] == 218) { //中山滞销仓
                    continue;
                }
                $data[] = [
                    'name' => $value['name'],
                    'code' => $warehouseService->getCodeForDistribution($value['id'], $value['code']),
                    'country' => $value['country_code'],
                ];
            }
            $this->retData['data'] = $data;
            $this->retData['message'] = 'success';
        } catch (Exception $e) {
            $this->retData['status'] = $e->getCode() == -1 ? -1 : -2;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 获取物流商列表包裹物流渠道
     */
    public function getCarrier()
    {
        try {
            $data = (new ShippingMethod())->getInfoForDistribution(false, true);
            $this->retData['data'] = $data;
            $this->retData['message'] = 'success';
        } catch (Exception $e) {
            $this->retData['status'] = $e->getCode() == -1 ? -1 : -2;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 获取仓库物流渠道
     */
    public function getWarehouseShipping()
    {
        $params = $this->requestData;
        try {
            /*if(!param($params, 'warehouse_id')){
                throw new Exception('缺少仓库', -1);
            }*/
            if (isset($params['warehouse_code']) && $params['warehouse_code']) {
                $params['warehouse_id'] = (new Warehouse())->getIdByDistributionCode($params['warehouse_code']);
//                $params['warehouse_id'] = $code_id_info['id'];
            } else {
                throw new Exception('缺少仓库code', -1);
            }
            $data = (new ShippingMethod())->getInfoForDistribution($params['warehouse_id'], false);
            $this->retData['data']['shipping_code_arr'] = array_column($data, 'code');
            $this->retData['message'] = 'success';
        } catch (Exception $e) {
            $this->retData['status'] = $e->getCode() == -1 ? -1 : -2;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 获取仓库物流渠道
     */
    public function trial()
    {
        $params = $this->requestData;
        try {
            /*if(!param($params, 'warehouse_code')){
                throw new Exception('缺少仓库', -1);
            }*/
            if (isset($params['warehouse_code']) && $params['warehouse_code']) {
                $params['warehouse_id'] = (new Warehouse())->getIdByDistributionCode($params['warehouse_code']);
//                $params['warehouse_id'] = $code_id_info['id'];
            } else {
                throw new Exception('缺少仓库code', -1);
            }
            if (!param($params, 'country_code')) {
                throw new Exception('缺少国家code', -1);
            }
            $shipping_code_arr = [];
            if (isset($params['shipping_code_arr']) && $params['shipping_code_arr']) {
                $shipping_code_arr = json_decode($params['shipping_code_arr'], true);
            }

            if (!$shipping_code_arr) {
                $data['shipping_methods'] = (new ShippingMethod())->getInfoForDistribution(false, false);
                $shipping_code_arr = array_column($data['shipping_methods'], 'code');
            }
            $shippingMethodService = new ShippingMethod();
            $shipping_methods = [];
            foreach ($shipping_code_arr as $val) {
                $shipping_methods[] = $shippingMethodService->getIdByDistributionCode($val);
//                if(!$code_id_info){
//                   continue;
//                }
//                $shipping_methods[] = $code_id_info['id'];
            }
            if (empty($shipping_methods)) {
                $this->retData['data'] = [];
            } else {
                $params['shipping_methods'] = json_encode($shipping_methods);
                switch ($params['search_type']) {
                    case 1://按重量
                        $params['volume'] = 0;
                        if (isset($params['length']) && isset($params['width']) && isset($params['height'])) {
                            $params['volume'] = $params['length'] * $params['width'] * $params['height'] * 1000;
                        }
                        if (isset($params['property'])) {//物流属性
                            $properties = json_decode($params['property'], true);
                            $params['property'] = 0;
                            foreach ($properties as $item) {
                                $params['property'] += $item;
                            }
                        }
                        break;
                    case 2;//按sku重量
                        if (!isset($params['skus'])) {
                            throw new Exception('sku信息不能为空', -1);
                        }
                        $sku_arr = json_decode($params['skus'], true);
                        if (empty($sku_arr)) {
                            throw new Exception('sku信息不能为空', -1);
                        }
                        $property = 0;
                        $weight = 0;
                        $sku_ids = [];
                        foreach ($sku_arr as $item) {
                            if (empty($item['sku'])) {
                                throw new Exception('sku不能为空', -1);
                            }
                            $sku_id = \app\goods\service\GoodsHelp::sku2id($item['sku']);
                            if (!$sku_id) {
                                throw new Exception($item['sku'] . '不存在', -1);
                            }
                            if (!$item['num']) {
                                throw new Exception($item['sku'] . '数量不能为空');
                            }

                            $skuInfo = Cache::store('goods')->getSkuInfo($sku_id);
                            $goodsInfo = Cache::store('goods')->getGoodsInfo($skuInfo['goods_id']);

                            if (isset($goodsInfo['transport_property'])) {
                                if (empty($property)) {
                                    $property = $goodsInfo['transport_property'];
                                } else {
                                    $property = $property | $goodsInfo['transport_property'];
                                }
                            }
                            $weight += $skuInfo['weight'] * $item['num'];
                            $sku_ids[] = $sku_id;
                        }
                        $params['property'] = $property; //物流属性
                        $params['weight'] = $weight;//重量
                        $params['volume'] = (new \app\common\service\Order())->getVolume($sku_ids);//体积
                        break;
                    default:
                        throw new Exception("类型错误！");
                }
                $data = $shippingMethodService->trial($params);
                foreach ($data as &$item) {
                    $item['cny_amount'] = sprintf("%.2f", $item['cny_amount']);
                    $item['after_discount_amount'] = sprintf("%.2f", $item['after_discount_amount']);
                    $item['shipping_fee_discount'] = sprintf("%.2f", $item['shipping_fee_discount']);
                    $item['amount'] = sprintf("%.2f", $item['amount']);
                    $item['before_amount'] = sprintf("%.2f", $item['before_amount']);
                    $item['registered_fee'] = sprintf("%.2f", $item['registered_fee']);
                    $item['oli_additional_fee'] = sprintf("%.2f", $item['oli_additional_fee']);
                    $item['shipping_code'] = $shippingMethodService->getCodeForDistribution($item['shipping_id'], $item['shipping_code']);
                    $item['shipping_name'] = $item['shipping_method_name'];
                    unset($item['shipping_id']);
                    unset($item['carrier_name']);
                    unset($item['carrier_code']);
                    unset($item['carrier_id']);
                    unset($item['shipping_method_name']);
                    $item['handle_fee'] = 0;
                    if ($params['weight'] < 0.5) {
                        $item['handle_fee'] = 0.5;
                    } elseif ($params['weight'] >= 100 && $params['weight'] < 500) {
                        $item['handle_fee'] = 0.55;
                    } elseif ($params['weight'] >= 500 && $params['weight'] < 1000) {
                        $item['handle_fee'] = 0.6;
                    } else {
                        $item['handle_fee'] = 0.7;
                    }
                    $item['handle_fee'] = sprintf("%.2f", $item['handle_fee']);
                }
                $this->retData['data'] = $data;
            }
            $this->retData['message'] = 'success';
        } catch (Exception $e) {
            $this->retData['status'] = $e->getCode() == -1 ? -1 : -2;
            $this->retData['message'] = $e->getFile() . $e->getLine() . $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 获取仓库库存
     */
    public function getInventory()
    {
        $params = $this->requestData;
        try {
            if (isset($params['warehouse_code']) && $params['warehouse_code']) {
                $params['warehouse_id'] = (new Warehouse())->getIdByDistributionCode($params['warehouse_code']);
//                $params['warehouse_id'] = $code_id_info['id'];
            } else {
                $warehouseService = new Warehouse();
                $warehouse_info = $warehouseService->getWarehouseByTypes(Warehouse::TYPE_LOCAL);
                $params['warehouse_id_arr'] = array_column($warehouse_info, 'id');
            }
            if (param($params, 'sku')) {
                $params['snType'] = 'sku';
                $params['snText'] = $params['sku'];
            }
            $page = param($params, 'page', 1);
            $pageSize = param($params, 'pageSize', 10);
            $listServer = new WarehouseGoodsList();
            $listServer->getWhere($params);
            $listServer->page($page, $pageSize);
            $field = 'sku_id, if(updated_time=0,created_time, updated_time) as updated_time, warehouse_id, goods_id, alert_quantity, sku,instransit_quantity,quantity-waiting_shipping_quantity as available_quantity,quantity,waiting_shipping_quantity,defects_quantity,allocating_quantity';
            $data = $listServer->getInventory([], $field, 'updated_time desc');
            $this->retData['data']['lists'] = $data;
            $this->retData['data']['count'] = intval($listServer->count());
            $this->retData['data']['page'] = intval($page);
            $this->retData['data']['pageSize'] = intval($pageSize);
            $this->retData['data']['sync_time'] = time();
            $this->retData['message'] = 'success';
        } catch (Exception $e) {
            $this->retData['status'] = $e->getCode() == -1 ? -1 : -2;
            $this->retData['message'] = $e->getFile() . $e->getLine() . $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 获取跟踪号/标记发货
     */
    public function writeBackTracking()
    {
        $params = $this->requestData;
        try {
            $this->retData['data'] = [];
            if (!param($params, 'source_order_no')) {
                throw new Exception('缺少erp单号', -1);
            }
            $number = explode('-', $params['source_order_no']);
            $packageService = new \app\order\service\PackageService();
            //验证包裹是否存在
            $package_info = $packageService->getByPackageNumber($number[0]);
            if (empty($package_info)) {
                throw new Exception($params['source_order_no'] . 'erp找不到对应的包裹信息', -1);
            }
            //后期调整不是api获取
            if (param($params, 'tracking_number')) {
                (new \app\order\service\PackageService())->getTrackingNumber($package_info['id']);
            }
            if (param($params, 'has_send') == true && isset($params['actual_weight'])) {
                (new StockOut())->distributionOut($package_info['id'], $params['actual_weight']);
            }
            $this->retData['message'] = 'success';
        } catch (Exception $e) {
            $this->retData['status'] = $e->getCode() == -1 ? -1 : -2;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }

    /**
     * 取消物流
     */
    public function cancelLogistics()
    {
        $params = $this->requestData;
        try {
            $this->retData['data'] = [];
            if (!param($params, 'source_order_no')) {
                throw new Exception('缺少erp单号', -1);
            }
            $number = explode('-', $params['source_order_no']);
            $packageService = new \app\order\service\PackageService();
            $warehouse = new Warehouse();
            //验证包裹是否存在
            $package_info = $packageService->getByPackageNumber($number[0]);
            if (empty($package_info)) {
                throw new Exception('erp找不到对应的包裹信息', -1);
            }
            //仓库验证
            if (!$warehouse->isAppointType($package_info['warehouse_id'], Warehouse::TYPE_FENXIAO)) {
                throw new Exception("erp仓库信息有误", -1);
            }
            $order_ids = $packageService->getOrderIdsByPackageId($package_info['id']);
            $result = $packageService->cancelLogistics($order_ids, [$package_info['id']]);
            if ($result['success']) {
                $this->retData['message'] = 'success';
            } else {
                throw new Exception('取消失败', -1);
            }
        } catch (Exception $e) {
            $this->retData['status'] = $e->getCode() == -1 ? -1 : -2;
            $this->retData['message'] = $e->getMessage();
        }
        return $this->retData;
    }


}