<?php

namespace app\carrier\service;

use app\common\model\OrderPackageDeclare;
use app\common\model\OrderSourceDetail;
use app\common\model\OrderDetail;
use app\common\model\PackageLabel;
use app\order\service\OrderRuleExecuteService;
use app\goods\service\GoodsHelp;
use think\Exception;
use app\common\cache\Cache;
use service\shipping\ShippingApi as ShippingApi;
use app\order\service\PackageService;
use app\warehouse\service\ShippingMethod;
use app\warehouse\service\Carrier;
use app\warehouse\service\SortingCodeService;
use app\common\traits\OrderStatus;
use app\common\service\ChannelAccountConst;


/**
 * Class Shipping
 * 物流服务类
 * @package app\goods\controller
 */
class Shipping
{
    use OrderStatus;

    public function createUploadShippingOrder($packageId, $confirm, $changeCarrier = false)
    {
        set_time_limit(0);
        $packages = PackageService::detail(0, $packageId, 1);
        if (empty($packages)) {
            throw new Exception('包裹不存在');
        }
        $order = [];
        foreach ($packages as $k => $package) {
            $shipping_id = $package['shipping_id'];
            $shippingInfo = Cache::store('shipping')->getShipping($shipping_id);
            $order[$k]['user_code'] = $package['shipping_number'];//物流商跟踪号
            if (!empty($shippingInfo)) {
                switch ($shippingInfo['label_used_number']) {
                    case 2:  //处理号
                        $package['shipping_number'] = $package['process_code'];
                        break;
                    case 3:
                        $package['shipping_number'] = $package['number'];
                        break;
                }
            }
            $order[$k]['shipping_id'] = $package['shipping_id'];//物流商单号
            $order[$k]['process_code'] = $package['process_code'];//物流商单号
            $order[$k]['shipping_time'] = $package['shipping_time'];//发货时间
            $order[$k]['pay_time'] = $package['pay_time'];//付款时间
            $order[$k]['shipping_number'] = $package['shipping_number'];
            $currency_code = $package['currency_code'];
            $detailList = $package['detailList'];
            $order[$k]['number'] = $package['number'];    //包裹号， refNo号
            $order[$k]['package_id'] = $packageId;

            $order[$k]['currency'] = $currency_code; // 币种
            $order[$k]['order_id'] = $package['order_id']; //系统订单id
            $order[$k]['channel_id'] = $package['channel_id']; //订单所属平台
            $order[$k]['channel_account_id'] = $package['channel_account_id']; //订单所属平台
            $order[$k]['warehouse_id'] = $package['warehouse_id']; // 发货仓库
            $order[$k]['shipping_id'] = $package['shipping_id'];  // 邮寄方式
            $order[$k]['estimated_weight'] = $package['estimated_weight']; // 估算重量
            $order[$k]['with_electricity'] = 0; // 是否带电
            //地址信息
            $address = $package['address'];
            $order[$k]['name'] = $address['consignee'];    //收件人名
            $order[$k]['zip'] = $address['zipcode'];      //邮编
            $order[$k]['phone'] = $address['mobile'];       //手机号
            $order[$k]['country_code'] = $address['country_code']; //国家简码
            $order[$k]['province'] = $address['province'];     //省
            $order[$k]['city'] = $address['city'];     //城市
            $country = Cache::store('Country')->getCountry($address['country_code']);
            $order[$k]['country_name'] = isset($country['country_en_name']) ? $country['country_en_name'] : ''; //国家名
            $order[$k]['street'] = $address['address'];         //地址
            $order[$k]['street2'] = $address['address2']; // 地址2
            $order[$k]['email'] = $address['email'];     //邮箱
            $order[$k]['tel'] = $address['tel'];       //电话

            $order[$k]['street_address'] = empty($address['address']) ? $address['address2'] : (!empty($address['address2']) ? $address['address'] . ' ' . $address['address2'] : $address['address']);

            // 产品
            $sku = [];
            $declaredInfo = [];
            //包裹产品的申报价值及单位
            $packageDeclare = OrderPackageDeclare::where(['package_id' => (string)$packageId])
                ->with('skuInfo')
                ->field('goods_name_en,goods_name_cn,quantity,unit_weight,unit_price,declare_currency,goods_id,sku_id,package_id,hs_code,sku_url')
                ->select();
            //没有申报信息直接提示用户去添加申报信息
            if (empty($packageDeclare)) {
//                return [
//                    'number' => $package['number'],
//                    'success' => false,
//                    'error' => [
//                        'error_msg' => '包裹缺少申报信息',
//                        'error_code' => 1
//                    ]];
            }
            foreach ($packageDeclare as $item) {
                $declaredInfo[$item['sku_id']] = [
                    'sku' => $item['skuInfo']['sku'],
                    'declared_name_en' => $item['goods_name_en'],
                    'declared_name_cn' => $item['goods_name_cn'],
                    'qty' => $item['quantity'],
                    'declared_value' => $item['unit_price'],
                    'declared_value_currency' => $item['declare_currency'],
                    'declared_weight' => $item['unit_weight'],
                    'hs_code' => $item['hs_code'],
                    'url' => $item['sku_url']
                ];
            }
            $order[$k]['declared_amount'] = $package['declared_amount'];
            $order[$k]['declared_currency'] = $package['declared_currency_code'];
            $order[$k]['declared_weight'] = $package['declared_weight'];
            $order[$k]['declared_info'] = $declaredInfo;
            foreach ($detailList as $val) {
                $skus = Cache::store('Goods')->getSkuInfo($val['sku_id']);
                if (isset($skus['goods_id'])) {
                    $goods = Cache::store('Goods')->getGoodsInfo($skus['goods_id']);
                    $sku[$val['sku_id']]['title_en'] = $goods['declare_en_name'];
                    $sku[$val['sku_id']]['title_cn'] = $goods['declare_name'];
                    $sku[$val['sku_id']]['weight'] = $skus['weight'] == 0 ? $goods['weight'] : $skus['weight'];
                    $sku[$val['sku_id']]['price'] = $skus['retail_price'];
                    $sku[$val['sku_id']]['hs_code'] = $goods['hs_code'];
                    $sku[$val['sku_id']]['goods_id'] = $goods['id'];
                    $sku[$val['sku_id']]['battery'] = '';
                }
                $sku[$val['sku_id']]['sku'] = $val['sku'];
                $sku[$val['sku_id']]['sku_price'] = $val['sku_price'];
                $sku[$val['sku_id']]['order_source_detail_id'] = $val['order_source_detail_id'];
                $sku[$val['sku_id']]['qty'] = $val['sku_quantity'];
            }
            $order[$k]['product'] = $sku;
            // 运输方式

            $re = Cache::store('shipping')->getShipping($shipping_id);

            $order[$k]['shipping_method_code'] = $re['code'];
            $order[$k]['shortname'] = $re['shortname'];
            $order[$k]['delivery_mode'] = $re['delivery_mode']; //交运方式（0 上门揽收，1 卖家自送)
            //寄件人信息
            $order[$k]['sender'] = $this->getSendAddress($re);
            //揽收地址信息
            $order[$k]['pickup'] = $this->getPickupAddress($re);
            //退货地址信息
            $order[$k]['refund'] = $this->getReturnAddress($re);;

            //包裹长宽高，单位mm
            $order[$k]['length'] = $package['length'];
            $order[$k]['width'] = $package['width'];
            $order[$k]['height'] = $package['height'];

            $order[$k]['is_need_return'] = $re['is_need_return'];
        }
        return $order;

    }

    public function getLabelHtml($packageId, $shipping_id)
    {
        set_time_limit(0);
        $order = $this->createUploadShippingOrder($packageId, false);
        $aShippingInfo = ShippingMethod::getShippingById($shipping_id);
        if (!$aShippingInfo) {
            throw new Exception('不存在的物流方式');
        }
        $Carrier = new Carrier();
        $aCarrier = $Carrier->getCarrier($aShippingInfo['carrier_id']);
        if (!$aCarrier) {
            throw new Exception('不存在的物流方式');
        }
        if (!$order) {
            throw new Exception('面单对应订单不存在');
        }
        $order = reset($order);
        $SortingCodeService = new SortingCodeService();
        $order['sorting_code'] = $SortingCodeService->getSortingCode($aCarrier['index'], $order);
        $order['total_weight'] = 0;
        $order['total_value'] = 0;
        $country = Cache::store('country')->getCountry($order['country_code']);
        $order['country_name'] = $country['country_cn_name'];
        $order['list'] = [];
        foreach ($order['declared_info'] as $k => $v) {
            $order['list'][] = $v;
            $order['total_weight'] += $v['declared_weight'];
            $order['total_value'] += $v['declared_value'] * $v['qty'];
        }
        return ShippingApi::instance()->label($aCarrier['index'], $aShippingInfo['code'], $order);
    }

    /**
     * @title 获取自制面单
     * @param $packageId
     * @param $shipping_id
     * @param $change boolean 是否打印的是转化面单
     * @author starzhan <397041849@qq.com>
     */
    public function getSelfControlLabel($packageId, $shipping_id, $change = false)
    {
        set_time_limit(0);
        $result = [];
        try {
            $order = $this->createUploadShippingOrder($packageId, false);
            $aShippingInfo = ShippingMethod::getShippingById($shipping_id);
            if (!$aShippingInfo) {
                throw new Exception('不存在的物流方式');
            }
            $Carrier = new Carrier();
            $aCarrier = $Carrier->getCarrier($aShippingInfo['carrier_id']);
            if (!$aCarrier) {
                throw new Exception('不存在的物流方式');
            }
            if (!$order) {
                throw new Exception('面单对应订单不存在');
            }
            $order = reset($order);
            if (!$order['shipping_number']) {
                throw new Exception('条码未生成！');
            }
            $SortingCodeService = new SortingCodeService();
            $order['sorting_code'] = $SortingCodeService->getSortingCode($aCarrier['index'], $order);
            $order['total_weight'] = 0;
            $order['total_value'] = 0;
            $order['product_weight'] = 0;
            $order['product_value'] = 0;
            $order['product_qty'] = 0;
            #####
//            $order['country_code'] = 'FR';
//            $aShippingInfo['code'] = 'A1610200';
//            $order['reason'] = '你长得太帅了';
//            $aCarrier['index'] = 'Daiyun';
            #####
            $country = Cache::store('country')->getCountry($order['country_code']);
            $order['country_name'] = $country['country_cn_name'] ?? '';
            $order['country_en_name'] = $country['country_en_name'] ?? '';
            $order['list'] = [];
            $order['product_list'] = [];
            $order['total_qty'] = 0;
            foreach ($order['declared_info'] as $k => $v) {
                $order['list'][] = $v;
                $order['total_weight'] += $v['declared_weight'];
                $order['total_value'] += $v['declared_value'] * $v['qty'];
                $order['total_qty'] += $v['qty'];
            }
            $result['success'] = true;
            $result['file'] = ShippingApi::instance()->createLabel($aCarrier['index'], $aShippingInfo['code'], $order, $change);
            //  halt($result);
            return $result;
        } catch (Exception $ex) {
            $result['success'] = false;
            $result['msg'] = $ex->getMessage();
            return $result;
        }
    }

    public function getIsWaterByShippingId($shipping_id)
    {
        $ShippingMethod = new ShippingMethod();
        $carrieInfo = $ShippingMethod->getCarrierByShippingId($shipping_id);
        if ($carrieInfo) {
            return ShippingApi::instance()->IsOpenWater($carrieInfo['index']);
        }
        return false;
    }

    public function isCanDeclare($shipping_id, $shipping_code)
    {
        $ShippingMethod = new ShippingMethod();
        $carrieInfo = $ShippingMethod->getCarrierByShippingId($shipping_id);
        if ($carrieInfo) {
            return ShippingApi::instance()->isCanDeclare($carrieInfo['index'], $shipping_code);
        }
        return false;
    }


    public function getDefaultLabel($packageId, $reason = '')
    {
        set_time_limit(0);
        $result = [];
        try {
            $order = $this->createUploadShippingOrder($packageId, false);
            $order = reset($order);
            $order['reason'] = $reason;
            $result['file'] = ShippingApi::instance()->createLabel('DefaultLabel', '', $order);
            $result['success'] = true;
            return $result;
        } catch (Exception $ex) {
            $result['success'] = false;
            $result['msg'] = $ex->getMessage();
            return $result;
        }
    }

    /**
     * @desc 获取报关单信息
     * @param array $shipping_method 渠道信息
     * @return array
     */
    public function getDeclareLabel($packageId, $shipping_id)
    {
        set_time_limit(0);
        $result = [];
            try {
                $order = $this->createUploadShippingOrder($packageId, false);
                $aShippingInfo = ShippingMethod::getShippingById($shipping_id);
                if (!$aShippingInfo) {
                    throw new Exception('不存在的物流方式');
                }
                $Carrier = new Carrier();
                $aCarrier = $Carrier->getCarrier($aShippingInfo['carrier_id']);
                if (!$aCarrier) {
                    throw new Exception('不存在的物流方式');
                }
                if (!$order) {
                    throw new Exception('面单对应订单不存在');
                }
                $order = reset($order);
                if (!$order['shipping_number']) {
                    throw new Exception('条码未生成！');
                }
                $SortingCodeService = new SortingCodeService();
                $order['sorting_code'] = $SortingCodeService->getSortingCode($aCarrier['index'], $order);
                $order['total_weight'] = 0;
                $order['total_value'] = 0;
                $order['product_weight'] = 0;
                $order['product_value'] = 0;
                $order['product_qty'] = 0;
                #####
//            $order['country_code'] = 'FR';
//            $aShippingInfo['code'] = 'A1610200';
//            $order['reason'] = '你长得太帅了';
//            $aCarrier['index'] = 'GYang';
                #####
                $country = Cache::store('country')->getCountry($order['country_code']);
                $order['country_name'] = $country['country_cn_name'] ?? '';
                $order['country_en_name'] = $country['country_en_name'] ?? '';
                $order['list'] = [];
                $order['product_list'] = [];
                $order['total_qty'] = 0;
                foreach ($order['declared_info'] as $k => $v) {
                    $order['list'][] = $v;
                    $order['total_weight'] += $v['declared_weight'];
                    $order['total_value'] += $v['declared_value'] * $v['qty'];
                    $order['total_qty'] += $v['qty'];
                }
                $orderRuleExecuteService=new OrderRuleExecuteService();
                $orderSourceDetailModel=new OrderSourceDetail();
                $orderDetailModel=new OrderDetail();
                $orderSourceDate = $orderSourceDetailModel->field('id,channel_sku_quantity,channel_sku_price,channel_sku')->where(['order_id'=>['in',$order['order_id']]])->select();
                foreach ($orderSourceDate as $key=>$detail){
                    $detailList = $orderDetailModel->field('goods_id,sku,sku_id,sku_quantity,order_source_detail_id,sku_price')->where(['order_source_detail_id' => $detail['id']])->find();
                    $orderSource = $detail['channel_sku_price'];
                    $totalChange=$orderRuleExecuteService->convertCurrency($order['currency'], 'EUR', $orderSource);
                    $totalChange=number_format($totalChange,3);
                    $channel_sku_quantity=$detail['channel_sku_quantity'];
                    $skus = Cache::store('Goods')->getSkuInfo($detailList['sku_id']);
                    if (isset($skus['goods_id'])) {
                        $goods = Cache::store('Goods')->getGoodsInfo($skus['goods_id']);
                        $detail['title_en'] =  $goods['declare_en_name'];
                        $detail['title_cn'] =  $goods['declare_name'];
                        $detail['weight'] =  $skus['weight'] == 0 ? $goods['weight'] : $skus['weight'];
                        $detail['hs_code'] = $goods['hs_code'];
                    }
                    $detail['channel_sku_price'] =$totalChange;
                    $detail['channel_sku_quantity'] =$channel_sku_quantity;
                    $detail['channel_sku_quantity'] =$channel_sku_quantity;
                    $order['product_list'][]=$detail;
                    $order['product_weight'] += $detail['weight'] *$channel_sku_quantity;
                    $order['product_value'] += $totalChange*$channel_sku_quantity;
                    $order['product_qty'] +=$channel_sku_quantity;
                }
                if($order['product_weight']>2000){
                    $order['product_weight']=1999;
                }
            
            $result['file'] = ShippingApi::instance()->createLabel('DeclareLabel', '', $order);
            $result['success'] = true;
            return $result;
        } catch (Exception $ex) {
            $result['success'] = false;
            $result['msg'] = $ex->getMessage();
            return $result;
        }
    }


    /**
     * @desc 获取发件人信息
     * @param array $shipping_method 渠道信息
     * @return array
     */
    public function getSendAddress($shipping_method)
    {
        $sender = [];
        if (param($shipping_method, 'sender_address_id')) {
            $address = Cache::store('shippingAddress')->getAddress($shipping_method['sender_address_id']);
            if ($address) {
                $sender['sender_code'] = '';    //原来的这个没有用到
                $sender['sender_name'] = $address['name'];    //'寄件人姓名'
                $sender['sender_country'] = $address['country']; //'发件人国家'
                $sender['sender_state'] = $address['state'];   //'寄件人所在州或者省份'
                $sender['sender_city'] = $address['city'];   //'寄件人城市'
                $sender['sender_district'] = $address['district']; //'寄件人所在地区
                $sender['sender_street'] = $address['street'];  //'寄件人街道地址'
                $sender['sender_zipcode'] = $address['zipcode']; //'寄件人邮编'
                $sender['sender_phone'] = $address['phone'];   //'寄件人电话'
                $sender['sender_mobile'] = $address['mobile'];  //'寄件人电话'
                $sender['sender_company'] = $address['company'];  //'寄件人公司'
                $sender['sender_email'] = $address['email'];  //'寄件人邮箱'
                return $sender;
            }
        }
        $sender['sender_code'] = $shipping_method['sender_code'];    //'寄件地址code'
        $sender['sender_name'] = $shipping_method['sender_name'];    //'寄件人姓名'
        $sender['sender_country'] = $shipping_method['sender_country']; //'发件人国家'
        $sender['sender_state'] = $shipping_method['sender_state'];   //'寄件人所在州或者省份'
        $sender['sender_city'] = $shipping_method['sender_city'];   //'寄件人城市'
        $sender['sender_district'] = $shipping_method['sender_district']; //'寄件人所在州'
        $sender['sender_street'] = $shipping_method['sender_street'];  //'寄件人街道地址'
        $sender['sender_zipcode'] = $shipping_method['sender_zipcode']; //'寄件人邮编'
        $sender['sender_phone'] = $shipping_method['sender_phone'];   //'寄件人电话'
        $sender['sender_mobile'] = $shipping_method['sender_mobile'];  //'寄件人电话'
        $sender['sender_company'] = $shipping_method['sender_company'];  //'寄件人公司'
        $sender['sender_email'] = $shipping_method['sender_email'];  //'寄件人邮箱'
        return $sender;
    }

    /**
     * @desc 获取揽收人信息
     * @param array $shipping_method 渠道信息
     * @return array
     */
    public function getPickupAddress($shipping_method)
    {
        $pickup = [];
        if (param($shipping_method, 'pickup_address_id')) {
            $address = Cache::store('shippingAddress')->getAddress($shipping_method['pickup_address_id']);
            if ($address) {
                $pickup['pickup_name'] = $address['name'];
                $pickup['pickup_country'] = $address['country'];
                $pickup['pickup_state'] = $address['state'];
                $pickup['pickup_city'] = $address['city'];
                $pickup['pickup_district'] = $address['district'];
                $pickup['pickup_street'] = $address['street'];
                $pickup['pickup_zipcode'] = $address['zipcode'];
                $pickup['pickup_email'] = $address['email'];
                $pickup['pickup_mobile'] = $address['mobile'];
                $pickup['pickup_company'] = $address['phone'];
                return $pickup;
            }
        }
        $pickup['pickup_name'] = $shipping_method['pickup_name'];
        $pickup['pickup_country'] = $shipping_method['pickup_country'];
        $pickup['pickup_state'] = $shipping_method['pickup_state'];
        $pickup['pickup_city'] = $shipping_method['pickup_city'];
        $pickup['pickup_district'] = $shipping_method['pickup_district'];
        $pickup['pickup_street'] = $shipping_method['pickup_street'];
        $pickup['pickup_zipcode'] = $shipping_method['pickup_zip_code'];
        $pickup['pickup_email'] = $shipping_method['pickup_email'];
        $pickup['pickup_mobile'] = $shipping_method['pickup_mobile'];
        $pickup['pickup_company'] = $shipping_method['pickup_company'];
        return $pickup;
    }

    /**
     * @desc 获取推荐人信息
     * @param array $shipping_method 渠道信息
     * @return array
     */
    public function getReturnAddress($shipping_method)
    {
        $refund = [];
        if (param($shipping_method, 'return_address_id')) {
            $address = Cache::store('shippingAddress')->getAddress($shipping_method['return_address_id']);
            if ($address) {
                $refund['refund_name'] = $address['name'];
                $refund['refund_country'] = $address['country'];
                $refund['refund_province'] = $address['state'];
                $refund['refund_city'] = $address['city'];
                $refund['refund_district'] = $address['district'];
                $refund['refund_street'] = $address['street'];
                $refund['refund_zipcode'] = $address['zipcode'];
                $refund['refund_email'] = $address['email'];
                $refund['refund_mobile'] = $address['mobile'];
                $refund['refund_company'] = $address['company'];
                return $refund;
            }
        }

        $refund['refund_name'] = $shipping_method['return_name'];
        $refund['refund_country'] = $shipping_method['return_country'];
        $refund['refund_province'] = $shipping_method['return_province'];
        $refund['refund_city'] = $shipping_method['return_city'];
        $refund['refund_district'] = $shipping_method['return_district'];
        $refund['refund_street'] = $shipping_method['return_street'];
        $refund['refund_zipcode'] = $shipping_method['return_postcode'];
        $refund['refund_email'] = $shipping_method['return_email'];
        $refund['refund_mobile'] = $shipping_method['return_mobile'];
        $refund['refund_company'] = $shipping_method['return_company'];
        return $refund;
    }

    /**
     * 上传包裹信息到物流商
     * @params int $packageId 包裹Id
     * @param int $confirm 是否需要交运
     * @param int $changeCarrier $changeCarrier是否需要改变物流方式，默认不需要，如果需要转的话就传入对应的shipping_id
     * @return array
     */
    public function uploadShipping($packageId, $confirm, $changeCarrier = false)
    {
        set_time_limit(0);
        $packages = PackageService::detail(0, $packageId, 1);
        if (empty($packages)) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '包裹不存在 ',
                    'error_code' => 1
                ]];
        }
        if (!$changeCarrier && $packages[0]['package_upload_status'] > 0) {
            return [
                'number' => $packages[0]['number'],
                'success' => false,
                'repeat' => 1,
                'error' => [
                    'error_msg' => '包裹已上传，不能重复操作 ',
                    'error_code' => 1
                ]];
        }
        $order = [];
        foreach ($packages as $k => $package) {
            if (empty($package['distribution_time']) && !$this->isStockOut($package['status'])) {  // 既不是缺货，也不是已配货
                return [
                    'number' => $package['number'],
                    'success' => false,
                    'error' => [
                        'error_msg' => '包裹状态不符合物流商下单条件！',
                        'error_code' => 1
                    ]];
            }
            $shipping_id = $package['shipping_id'];
            $currency_code = $package['currency_code'];
            $detailList = $package['detailList'];
            $order[$k]['number'] = $package['number'];    //包裹号， refNo号
            $order[$k]['order_number'] = $package['number'];    //包裹号， refNo号
            $order[$k]['customer_number'] = $package['customer_number'];
            $order[$k]['package_id'] = $packageId;
            if ($changeCarrier) {
                $order[$k]['shipping_number'] = $package['shipping_number'];
            }
            $order[$k]['currency'] = $currency_code; // 币种
            $order[$k]['order_id'] = $package['order_id']; //系统订单id
            $order[$k]['channel_id'] = $package['channel_id']; //订单所属平台
            $order[$k]['channel_account_id'] = $package['channel_account_id']; //订单所属平台
            $order[$k]['warehouse_id'] = $package['warehouse_id']; // 发货仓库
            $order[$k]['shipping_id'] = $package['shipping_id'];  // 邮寄方式
            $order[$k]['shipping_number'] = $package['shipping_number'];  // 跟踪号（zoodmall平台线上发货需要）
            $order[$k]['estimated_weight'] = $package['estimated_weight']; // 估算重量
            $order[$k]['with_electricity'] = 0; // 是否带电
            //地址信息
            $address = $package['address'];
            $order[$k]['name'] = $address['consignee'];    //收件人名
            $order[$k]['zip'] = $address['zipcode'];      //邮编
            $order[$k]['phone'] = $address['mobile'];       //手机号
            $order[$k]['country_code'] = $address['country_code']; //国家简码
            $order[$k]['province'] = $address['province'];     //省
            $order[$k]['city'] = $address['city'];     //城市
            $country = Cache::store('Country')->getCountry($address['country_code']);
            $order[$k]['country_name'] = isset($country['country_en_name']) ? $country['country_en_name'] : ''; //国家名
            $order[$k]['street'] = $address['address'];         //地址
            $order[$k]['street2'] = $address['address2']; // 地址2
            $order[$k]['email'] = strstr($address['email'], 'Invalid Request') ? '' : $address['email'];     //邮箱
            //所有cd平台邮箱都屏蔽
            if ($order[$k]['channel_id'] == ChannelAccountConst::channel_CD) {
                $order[$k]['email'] = '';
            }
            $order[$k]['tel'] = $address['tel'];       //电话

            $order[$k]['street_address'] = empty($address['address']) ? $address['address2'] : (!empty($address['address2']) ? $address['address'] . ' ' . $address['address2'] : $address['address']);

            // 产品
            $sku = [];
            $declaredInfo = [];
            //包裹产品的申报价值及单位
            $packageDeclare = OrderPackageDeclare::where(['package_id' => (string)$packageId])
                ->with('skuInfo')
                ->field('goods_name_en,goods_name_cn,quantity,unit_weight,unit_price,declare_currency,goods_id,sku_id,package_id,hs_code,sku_url')
                ->select();
            //没有申报信息直接提示用户去添加申报信息
            if (empty($packageDeclare)) {
                return [
                    'number' => $package['number'],
                    'success' => false,
                    'error' => [
                        'error_msg' => '包裹缺少申报信息',
                        'error_code' => 1
                    ]];
            }
            foreach ($packageDeclare as $item) {
                $declaredInfo[$item['sku_id']] = [
                    'sku' => $item['skuInfo']['sku'],
                    'declared_name_en' => $item['goods_name_en'],
                    'declared_name_cn' => $item['goods_name_cn'],
                    'qty' => $item['quantity'],
                    'declared_value' => $item['unit_price'],
                    'declared_value_currency' => $item['declare_currency'],
                    'declared_weight' => $item['unit_weight'],
                    'hs_code' => $item['hs_code'],
                    'url' => $item['sku_url'],
//                    'transport_property' =>GoodsHelp::getPropertyByOrder($item['goods_id']) //物流属性值
                ];
            }

            $order[$k]['declared_amount'] = $package['declared_amount'];
            $order[$k]['declared_currency'] = $package['declared_currency_code'];
            $order[$k]['declared_weight'] = $package['declared_weight'];
            $order[$k]['declared_info'] = $declaredInfo;
            foreach ($detailList as $val) {
                $skus = Cache::store('Goods')->getSkuInfo($val['sku_id']);
                if (isset($skus['goods_id'])) {
                    $goods = Cache::store('Goods')->getGoodsInfo($skus['goods_id']);
                    $sku[$val['sku_id']]['title_en'] = $goods['declare_en_name'];
                    $sku[$val['sku_id']]['title_cn'] = $goods['declare_name'];
                    $sku[$val['sku_id']]['weight'] = $skus['weight'] == 0 ? $goods['weight'] : $skus['weight'];
                    $sku[$val['sku_id']]['price'] = $skus['retail_price'];
                    $sku[$val['sku_id']]['hs_code'] = $goods['hs_code'];
                    $sku[$val['sku_id']]['goods_id'] = $goods['id'];
                    $sku[$val['sku_id']]['battery'] = '';
                    $sku[$val['sku_id']]['sku'] = $val['sku'];
                    $sku[$val['sku_id']]['order_source_detail_id'] = $val['order_source_detail_id'];
                    $sku[$val['sku_id']]['height'] = $skus['height'];
                    $sku[$val['sku_id']]['width'] = $skus['width'];
                    $sku[$val['sku_id']]['length'] = $skus['length'];
                }
                if (isset($sku[$val['sku_id']]) && isset($sku[$val['sku_id']]['qty'])) {
                    $sku[$val['sku_id']]['qty'] += $val['sku_quantity'];
                } else {
                    $sku[$val['sku_id']]['qty'] = $val['sku_quantity'];
                }
            }
            $order[$k]['product'] = $sku;
            // 运输方式
            if ($changeCarrier) {
                //转物流方式、就获取需要转到的物流对应的shipping_id
                $re = Cache::store('shipping')->getShipping($changeCarrier);
            } else {
                $re = Cache::store('shipping')->getShipping($shipping_id);
            }
            $order[$k]['shipping_method_code'] = $re['code'];
            $order[$k]['shipping_id'] = $shipping_id;
            $order[$k]['shortname'] = $re['shortname'];
            $order[$k]['delivery_mode'] = $re['delivery_mode']; //交运方式（0 上门揽收，1 卖家自送)
            //寄件人信息
            $order[$k]['sender'] = $this->getSendAddress($re);

            //揽收地址信息
            $order[$k]['pickup'] = $this->getPickupAddress($re);

            //退货地址信息s
            $order[$k]['refund'] = $this->getReturnAddress($re);

            //包裹长宽高，单位mm
            $order[$k]['length'] = $package['length'];
            $order[$k]['width'] = $package['width'];
            $order[$k]['height'] = $package['height'];

            $order[$k]['is_need_return'] = $re['is_need_return'];
        }
        // 取物流商的密钥
        $carrier = Cache::store('carrier')->getCarrier($re['carrier_id']);

        $token = [];
        $token['client_id'] = $carrier['interface_user_name'];
        $token['client_secret'] = $carrier['interface_user_key'];
        $token['interface_user_password'] = $carrier['interface_user_password'];
        $token['accessToken'] = $carrier['interface_token'];

        $token['customer_code'] = $carrier['customer_code'];
        $token['pickup_account_id'] = $carrier['pickup_account_id'];
        $token['soldto_account_id'] = $carrier['soldto_account_id'];
        $token['has_trackingNumber'] = $re['has_tracking_number']; //是否有跟踪号
        //$token['label_used_number']       = $re['label_used_number'];//面单需要跟踪号还是物流商单号。1 跟踪号，2 物流商单号
        //$token['label_source_type']       = $re['label_source_type'];//面单来源。1 自制面单, 2 API接口获取面单
        $classType = param($carrier, 'index'); // "Starpost"  //物流商的类名

        ###测试测试
        // $classType = 'Shopee'; // "Starpost"  //物流商的类名
        //api调用的密钥
        try {
            $shipping = ShippingApi::instance()->loader($classType);
            $result = $shipping->createOrder($token, $order, $confirm, $changeCarrier);
            return $result;
        } catch (Exception $e) {
            return [
                'number' => $order[0]['number'],
                'success' => false,
                'error' => [
                    'error_msg' => '程序错误，请联系开发人员 ' . $e->getMessage(),
                    'error_code' => 1
                ]];
        }
    }

    /**
     * 包裹交运
     * @param string $packageId 包裹Id
     * @return array
     */
    public function confirm($packageId)
    {
        $packages = PackageService::detail(0, $packageId, 1);
        if (empty($packages)) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '包裹不存在',
                    'error_code' => 1
                ]];
        }
        $order = [];
        foreach ($packages as $package) {
            $order['process_code'] = $package['process_code'];
            $shipping_id = $package['shipping_id'];
            $order['number'] = $package['number'];
            $order['shipping_number'] = $package['shipping_number'];
            $order['warehouse_id'] = $package['warehouse_id'];
            $order['channel_account_id'] = $package['channel_account_id']; //订单所属平台
            $order['channel_id'] = $package['channel_id']; //订单所属平台
            $order['order_id'] = $package['order_id'];
            $order['package_id'] = $packageId;
            $order['estimated_weight'] = $package['estimated_weight'];
        }
        $re = Cache::store('shipping')->getShipping($shipping_id);
        $order['shipping_method_code'] = $re['code'];
        $token = $this->getToken($shipping_id);
        $token['has_trackingNumber'] = $re['has_tracking_number'];
        //api调用的密钥
        try {
            $shipping = ShippingApi::instance()->loader($token['classType']);
            $result = $shipping->confirm($token, $order);
            return $result;
        } catch (Exception $e) {
            return [
                'number' => $order['number'],
                'success' => false,
                'error' => [
                    'error_msg' => '程序错误，请联系开发人员 ' . $e->getMessage(),
                    'error_code' => 1
                ]];
        }
    }

    /**
     * 获取跟踪号
     * @param int $packageId 包裹ID
     * @return array
     */
    public function getTrackingNumber($packageId)
    {
        $packages = PackageService::detail(0, $packageId, 1);
        if (empty($packages)) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '包裹不存在',
                    'error_code' => 1
                ]];
        }
        $order = [];
        foreach ($packages as $k => $package) {
            $order['process_code'] = $package['process_code'];
            $shipping_id = $package['shipping_id'];
            $order['number'] = $package['number'];
            $order['channel_account_id'] = $package['channel_account_id']; //订单所属平台
            $order['order_id'] = $package['order_id']; //系统订单id
            $order['channel_id'] = $package['channel_id']; //系统订单id
            $address = $package['address'];
            $re = Cache::store('shipping')->getShipping($shipping_id);
            $order['shipping_method_code'] = $re['code'];
            $order['country_code'] = $address['country_code'];
            $order['package_upload_status'] = $package['package_upload_status'];
        }

        $token = $this->getToken($shipping_id);
        //api调用的密钥
        try {
            $shipping = ShippingApi::instance()->loader($token['classType']);
            $result = $shipping->getTrackingNumber($token, $order);
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '程序错误，请联系开发人员 ' . $e->getMessage(),
                    'error_code' => 1
                ]];
        }
    }

    /**
     * 取消包裹
     * @param int $packageId 包裹ID
     * @return array
     */
    public function cancel($packageId)
    {
        $packages = PackageService::detail(0, $packageId, 1);
        if (empty($packages)) {
            return json(['message' => '包裹不存在.'], 400);
        }
        $order = [];
        try {
            foreach ($packages as $k => $package) {
                $order['process_code'] = $package['process_code'];
                $shipping_id = $package['shipping_id'];
                $order['number'] = $package['number'];
                $order['shipping_number'] = $package['shipping_number'];
                $order['confirm'] = $package['package_confirm_status']; //0：未交运  1 ：已交运'
                $order['channel_account_id'] = $package['channel_account_id']; //订单所属平台
                $order['channel_id'] = $package['channel_id']; //订单所属平台
                $order['order_id'] = $package['order_id']; //订单所属平台
                $re = Cache::store('shipping')->getShipping($shipping_id);
                $order['shipping_method_code'] = $re['code'];
            }
            $token = $this->getToken($shipping_id);
            //api调用的密钥
            $shipping = ShippingApi::instance()->loader($token['classType']);
            $result = $shipping->cancel($token, $order);
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '程序错误，请联系开发人员 ' . $e->getMessage(),
                    'error_code' => 1
                ]];
        }
    }

    /**
     * 获取包裹标签
     * @param int $packageId 包裹ID
     * @return array
     */
    public function getLabel($packageId)
    {
        $packages = PackageService::detail(0, $packageId, 1);
        if (empty($packages)) {
            return json(['message' => '包裹不存在.'], 400);
        }
        $order = [];
        foreach ($packages as $k => $package) {
            $order['process_code'] = $package['process_code'];
            $shipping_id = $package['shipping_id'];
            $order['number'] = $package['number'];
            $order['path'] = '';
            $order['shipping_number'] = $package['shipping_number'];
            $order['channel_account_id'] = $package['channel_account_id']; //订单所属平台
            $order['channel_id'] = $package['channel_id']; //订单所属平台
            $re = Cache::store('shipping')->getShipping($shipping_id);
            $order['shipping_method_code'] = $re['code'];
            $order['order_id'] = $package['order_id']; //系统订单id
            $order['customer_number'] = $package['customer_number'];
            $order['channel_id'] = $package['channel_id']; //订单所属平台
            $order['label_source_type'] = $re['label_source_type']; //订单所属平台
            $order['package_id'] = $packageId; //包裹ID
        }
        $token = $this->getToken($shipping_id);
        //$order['path'] = ROOT_PATH.'public/upload/'.$token['classType'];
        //api调用的密钥
        try {
//            $token['classType'] = 'Daiyun';
            $shipping = ShippingApi::instance()->loader($token['classType']);
            $result = $shipping->getLabel($token, $order);
            if ($result['success']) {
//                if(isset($result['data']['body'])&&!empty($result['data']['body'])){
//                    $data = [
//                        'package_id'=>$packageId,
//                        'package_number'=>$order['number'],
//                        'label_content'=>$result['data']['body'],
//                        'type'=>$result['data']['type']
//                    ];
//                    $this->saveLabel($data);
//                }
            }
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '程序错误，请联系开发人员 ' . $e->getMessage(),
                    'error_code' => 1
                ]];
        }
    }

    /**
     * 获取包裹详情
     */
    public function getPackageDetail($packageId)
    {
        $packages = PackageService::detail(0, $packageId, 1);
        if (empty($packages)) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '包裹不存在',
                    'error_code' => 1
                ]];
        }
        $order = [];
        foreach ($packages as $package) {
            $order['process_code'] = $package['process_code'];
            $shipping_id = $package['shipping_id'];
            $order['number'] = $package['number'];
            $order['shipping_number'] = $package['shipping_number'];
            $order['channel_account_id'] = $package['channel_account_id'];
            $order['warehouse_id'] = $package['warehouse_id'];
        }
        $re = Cache::store('shipping')->getShipping($shipping_id);
        $token = $this->getToken($shipping_id);
        $token['has_trackingNumber'] = $re['has_tracking_number'];
        //api调用的密钥
        try {
//            $token['classType'] = 'Daiyun';
            $shipping = ShippingApi::instance()->loader($token['classType']);
            $result = $shipping->getPackageDetails($token, $order);
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '程序错误，请联系开发人员 ' . $e->getMessage(),
                    'error_code' => 1
                ]];
        }
    }

    /**
     * 获取包裹重量及费用
     */
    public function getWeightFee($packageId)
    {
        $packages = PackageService::detail(0, $packageId, 1);
        if (empty($packages)) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '包裹不存在',
                    'error_code' => 1
                ]];
        }
        $order = [];
        foreach ($packages as $package) {
            $order['process_code'] = $package['process_code'];
            $shipping_id = $package['shipping_id'];
            $order['number'] = $package['number'];
            $order['shipping_number'] = $package['shipping_number'];
            $order['warehouse_id'] = $package['warehouse_id'];
            $order['order_id'] = $package['order_id'];
        }
        $re = Cache::store('shipping')->getShipping($shipping_id);
        $token = $this->getToken($shipping_id);
        $token['has_trackingNumber'] = $re['has_tracking_number'];
        //api调用的密钥
        try {
            $shipping = ShippingApi::instance()->loader($token['classType']);
            $result = $shipping->getWeightFee($token, $order);
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '程序错误，请联系开发人员 ' . $e->getMessage(),
                    'error_code' => 1
                ]];
        }
    }

    /**
     * 物流订单是否已发货
     * @param $packageId
     * @return array
     */
    public function hasSend($packageId)
    {
        $packages = PackageService::detail(0, $packageId, 1);
        if (empty($packages)) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '包裹不存在',
                    'error_code' => 1
                ]];
        }
        $order = [];
        foreach ($packages as $package) {
            $order['process_code'] = $package['process_code'];
            $shipping_id = $package['shipping_id'];
            $order['number'] = $package['number'];
            $order['shipping_number'] = $package['shipping_number'];
            $order['warehouse_id'] = $package['warehouse_id'];
            $order['channel_account_id'] = $package['channel_account_id']; //订单所属平台
            $order['channel_id'] = $package['channel_id']; //订单所属平台
            $order['order_id'] = $package['order_id']; //系统订单id
            $re = Cache::store('shipping')->getShipping($shipping_id);
            $order['shipping_method_code'] = $re['code'];
        }
        $re = Cache::store('shipping')->getShipping($shipping_id);
        $token = $this->getToken($shipping_id);
        $token['has_trackingNumber'] = $re['has_tracking_number'];
        //api调用的密钥
        try {
            $shipping = ShippingApi::instance()->loader($token['classType']);
            if (method_exists($shipping, 'getPackageStatus')) {
                $result = $shipping->getPackageStatus($token, $order);
                return $result;
            } else {
                return [
                    'success' => false,
                    'error' => [
                        'error_msg' => '物流商暂不支持',
                        'error_code' => 1
                    ]];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'error_msg' => '程序错误，请联系开发人员 ' . $e->getMessage(),
                    'error_code' => 1
                ]];
        }
    }

    /**
     * 获取包裹列表
     */
    public function getList()
    {

    }

    /**
     * 获取包裹轨迹信息
     */
    public function getTrackPoints()
    {

    }

    /**
     * 获取物流商授权信息
     * @param int $shipping_id
     * @return array
     */
    private function getToken($shipping_id)
    {
        // 运输方式
        $shippingMethod = Cache::store('shipping')->getShipping($shipping_id);
        // 取物流商的密钥
        $carrier = Cache::store('carrier')->getCarrier($shippingMethod['carrier_id']);
        $token = [];
        $token['client_id'] = $carrier['interface_user_name'];
        $token['client_secret'] = $carrier['interface_user_key'];
        $token['interface_user_password'] = $carrier['interface_user_password'];
        $token['accessToken'] = $carrier['interface_token'];
        $token['classType'] = $carrier['index']; // "Starpost"  //物流商的类名
        $token['customer_code'] = $carrier['customer_code']; //
        $token['pickup_account_id'] = $carrier['pickup_account_id'];
        $token['soldto_account_id'] = $carrier['soldto_account_id'];
        return $token;
    }

    private function saveLabel($data)
    {
        $model = new PackageLabel();
        $packageLabel = PackageLabel::get(['package_id' => $data['package_id']]);
        if (!empty($packageLabel)) {
            $packageLabel->isUpdate(true)->save($data);
        }
        $model->allowField(true)->save($data);
        return;
    }

}
