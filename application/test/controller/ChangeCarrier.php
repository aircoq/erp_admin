<?php

namespace app\test\controller;

use app\common\controller\Base;
use app\common\model\OrderPackage;
use app\common\cache\Cache;
use org\Curl;

/**
 * @module 物流转化
 * @title 物流转化
 * @url /change-carrier
 * @package app\test\controller
 * @author Jimmy
 */
class ChangeCarrier extends Base
{

    private $config;

    public function __construct(\think\Request $request = null)
    {
        parent::__construct($request);
        $this->config['url'] = 'http://api.yunexpress.com/LMS.API/api/WayBill/BatchAdd';
        $this->config['header'] = [
            'Accept: text/json',
            'Accept-Language: zh-cn',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode('C64350&OJ7Vs3QBCPw=')
        ];
    }

    /**
     * @title 获取数据
     * @url /change-carrier/get-data
     */
    public function getData()
    {
        $number = 'P1782890481017929';
        $map['number'] = $number;
        $res = OrderPackage::get($map);
        return $res ?: false;
    }

    /**
     * @title 设置数据
     * @url /change-carrier/set-data:
     */
    public function setData()
    {
        $data = $this->getData();
        $shipping = Cache::store('shipping')->getShipping($data->shipping_id);
        //组装请求数据
        $postData = [];
        $postData['OrderNumber'] = $data->number;
        $postData['ShippingMethodCode'] = $shipping['code'];
        $postData['TrackingNumber'] = $data->shipping_number;
        $postData['PackageNumber'] = 1;
        $postData['Weight'] = number_format($data->estimated_weight / 1000, 2);
        $postData['IsReturn'] = $shipping['is_need_return'];
        //收件人信息
        $postData['ShippingInfo']['ShippingTaxId'] = '';
        $postData['ShippingInfo']['CountryCode'] = $data->address->country_code;
        $postData['ShippingInfo']['ShippingFirstName'] = $data->address->consignee;
        $postData['ShippingInfo']['ShippingLastName'] = '';
        $postData['ShippingInfo']['ShippingCompany'] = '';
        $postData['ShippingInfo']['ShippingAddress'] = $data->address->address;
        $postData['ShippingInfo']['ShippingCity'] = $data->address->city;
        $postData['ShippingInfo']['ShippingState'] = $data->address->province;
        $postData['ShippingInfo']['ShippingZip'] = $data->address->zipcode;
        $postData['ShippingInfo']['ShippingPhone'] = $data->address->mobile;
        //发件人信息
        $postData['SenderInfo']['CountryCode'] = $shipping['sender_country'];
        $postData['SenderInfo']['SenderFirstName'] = $shipping['sender_name'];
        $postData['SenderInfo']['SenderLastName'] = '';
        $postData['SenderInfo']['SenderCompany'] = $shipping['sender_company'];
        $postData['SenderInfo']['SenderAddress'] = $shipping['sender_street'];
        $postData['SenderInfo']['SenderCity'] = $shipping['sender_city'];
        $postData['SenderInfo']['SenderState'] = $shipping['sender_state'];
        $postData['SenderInfo']['SenderZip'] = $shipping['sender_zipcode'];
        $postData['SenderInfo']['SenderPhone'] = $shipping['sender_phone'];
        //产品数据
        $temp = [];
        foreach ($data->declareInfo as $declareInfo) {
            $skus = Cache::store('Goods')->getSkuInfo($declareInfo['sku_id']);
            $temp[] = [
                'ApplicationName' => $declareInfo['goods_name_en'],
                'HSCode' => $declareInfo['hs_code'],
                'Qty' => $declareInfo['quantity'],
                'UnitPrice' => number_format($declareInfo['unit_price'], 2),
                'UnitWeight' => number_format($declareInfo['unit_price'], 2),
                'PickingName' => $declareInfo['goods_name_cn'],
                'Remark' => $declareInfo['goods_name_cn'],
                'SKU' => $skus['sku'],
            ];
        }
         $postData['ApplicationInfos']=$temp;
         return $postData;
    }
    /**
     * @title 发送数据请求
     * @url /change-carrier/sender-data
     */
    public function senderData(){
        $postData[0]=$this->setData();
        $responseJson = Curl::curlPost($this->config['url'], json_encode($postData),$this->config['header']);
        $response = json_decode($responseJson,true);
        var_dump($response);exit;
    }
}
