<?php
namespace app\carrier\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\carrier\model;
use app\carrier\service;


/**
 * 
 * 比利时物流
 * @package app\carrier\controller
 */
class Bl extends Base
{   
    private  $key='968696000040123585';
    
    /**
     * 密钥
     */
    function __construct($auth='968696000040123585'){
        if (!empty($auth)) {
            $this->key = $auth;
        }       
        return $auth;
    }
    
    /**
     * 上传包裹数据到sunYou
     */
    public function shipTo()
    {                   
       
        $order = [];
        $order['number']   =  'SO1231231b';
        $order['zip']      =  '54874';
        $order['phone']    =  '400-123456';
        $order['country']  =  'DE';
        $order['province'] =  'West Yorkshire';
        $order['city']     =  'Leeds';
        $order['county']   =  'St.';
        $order['street']   =  'Lotus Street';
        $order['name']     =  'Tom.k';   
        $order['email']     =  'Tom.k@qq.com';
        $products = [
            array(
            'title_cn' => '电脑',
            'title_en' => 'computer',
            'qty'      => '2',
            'sku'      =>'122123',
            'singleWeight'=> 0.23
                )
        ];
        $product = [] ;
        foreach ($products as $k=>$v) {
            $product[$k]['title_cn'] = '电脑' ;
            $product[$k]['title_en'] = 'computer' ;
            $product[$k]['qty']      = 2;
            $product[$k]['sku']      = 'JYGJ0103ZZ0ZZ001' ;
            $product[$k]['singleWeight'] = 0.23 ;
            
        }
        $re = self::upload($order, $product,'DEAT');
        $productList = [
            array(
                'declare_name_zh'  => '电脑',
                'declare_name'     => 'computer',
                'declare_value'    => '2',
                'sku'              =>'JYGJ0103ZZ0ZZ001',
                'fba_declare_value'=> 1.23,
                'hs_code'          =>23232323,
                'battery'          =>1
            )
        ];
       //$re = self::createSku($productList);        
       //$re = self::getOrderInfo('SO1231231b');//A00T062170216000001
        //$re = self::getExpress('DEAT');
        print_r($re);
        exit;
        $json['code'] = 200;
        $json['msg']  = $re;
        return json_encode($json);
    }
    
	 /**
     * 比利时
     * */
    private function bl($order,$pds){
        
        
        $pdCt=count($pds);
        $countryCh=$this->countries[$ob['obAdrCounty_code']]['ctNameZHCN'];
        $phone=strpos($ob['obReceiveCallNumber'],'+')===false?'+'.$ob['obReceiveCallNumber']:$ob['obReceiveCallNumber'];
        $header=$details=array();
        $priceUnit=$totalPrice=$weight=$totalQty=0;
        foreach($pds as $pd){
            $weight+=$pd['singleWeight']*$pd['obdQty'];
            $totalPrice+=(isset($this->prices[$pd['obdP_id']['price']])?$this->prices[$pd['obdP_id']['price']]:0)*$pd['obdQty'];
            $totalQty+=$pd['obdQty'];
        }
        $totalPrice=change_price_value($totalPrice,$ob['obAdrCounty_code']);
        $priceUnit=round($totalPrice/$totalQty,2);
        foreach($pds as $pd){
            $details[]=array(
                'Sku'=>$pd['sku'],
				'ChineseContentDescription'=>deleteProductTitle($pd['pNameZHCN']),
                'ItemContent'=>deleteProductTitle($pd['pNameEN']),
				'ItemCount'=>$pd['obdQty'],
				'Value'=>($priceUnit*$pd['obdQty']),
                'Currency'=>'USD',
				'Weight'=>intval($pd['singleWeight']),
				'SkuInInvoice'=>''
            );
        }
        $dataArr=array(
            'ContractId'=>1,
			'OrderNumber'=>$ob['obNumber'],
			'RecipientName'=>$ob['obReceiveName'],
			'RecipientStreet'=>$ob['obAdrStreet'],
            'RecipientHouseNumber'=>'',
			'RecipientBusnumber'=>'',
			'RecipientZipCode'=>$ob['obAdrZipCode'],
			'RecipientCity'=>$ob['obAdrCity'],
            'RecipientState'=>$ob['obAdrProvince'],
			'RecipientCountry'=>$ob['obAdrCounty_code'],
			'PhoneNumber'=>$phone,
            'Email'=>'',
			'SenderName'=>'ing',
			'SenderAddress'=>'Longgang',
            'SenderSequence'=>1,
			'IsSure'=>'true',
			'Customs'=>$details
        );
        $tockID='';
        $locationUrl='http://42.121.252.25/';
        $header[]="Content-type:text/json;charset=utf-8";
        $header[]="Authorization: basic {$tockID}";
        $jsonData=json_encode($dataArr);
        $response=$fc->get_curl($locationUrl.'api/LvsParcels',$header,$jsonData);
        $trackInfo='';
        if($response){
            $jsonDecode=json_decode($response,true);
            $trackInfo=$jsonDecode['ProductBarcode'];
        }
        return array('trackInfo'=>$trackInfo,'response'=>$response);
    }
    
    /**
     * 数组生成xml
     * @param unknown $source
     * @return string
     */
    function change($source) {        
        $string="";
        foreach($source as $k=>$v){
            $string .="<".$k.">";
            //判断是否是数组，或者，对像
            if(is_array($v) || is_object($v)){
                //是数组或者对像就的递归调用
                $string .= $this->change($v);
            }else{
                //取得标签数据
                $string .=$v;
            }
            $string .="</".$k.">";
        }
        return $string;
    }
        
    /**
     *  运输渠道
     CPC|Expedited
     ASP.CN2AU.AUPOST|E-Parcel
     NZPOST|Tracking-Non-Signatur
     SPSR     
            试环境URL：
        http://qa.etowertech.com
        Token: test5AdbzO5OEeOpvgAVXUFE0A
        Key: 79db9e5OEeOpvgAVXUFWSD
     * @param unknown $order
     * @param unknown $product  
     * @return unknown[]|string[]
     */
    private function upload($pack, $products,$type)
    {
        set_time_limit(0);
        $data = [];
        
        $packageList              = [];        
        $packageList[0]['reference_order_number']  = $pack['number'];
        $packageList[0]['ship_channel_code']       = $type;
        $packageList[0]['total_declare_value']     = 14.78;
        $packageList[0]['total_weight']            = 1.3;
        $packageList[0]['length']                  = 3.3;
        $packageList[0]['width']                   = 3.3;
        $packageList[0]['height']                  = 3.3;
        
        //收件人信息
        $packageList[0]['recipient_name']          = $pack['name'];
        $packageList[0]['recipient_company']       = ' ';
        $packageList[0]['recipient_country']       = $pack['country'];
        $packageList[0]['recipient_zip']           = $pack['zip'];
        $packageList[0]['recipient_state']         = $pack['province'];
        $packageList[0]['recipient_city']          = $pack['city'];
        $packageList[0]['recipient_address1']      = $pack['street'];
        $packageList[0]['recipient_address2']      = ' ';
        $packageList[0]['recipient_phone']         = $pack['phone'];
        $packageList[0]['recipient_email']         = $pack['email'];    
                        
        //产品信息
        $productList = [];
        foreach ($products as $key=>$product) {
            $productList[$key]['sku']           = $product['sku'];
            $productList[$key]['quantity']      = $product['qty'];
            $productList[$key]['description']   = ' ';
            
        }
        $packageList[0]['items']     = array_values($productList);
        $data['orders']              = array_values($packageList);
        $xml_data                    = "request_data=".json_encode($data);
        $header[]  = '';
        $response  = self::post_curl('http://sandboxapi.faryaa.com/order/create',$header,$xml_data);
        $response  = json_decode($response);        
        $trackInfo = '';
        $msg       = '';
        if($response->ack=='success'){   
            $data =  $response->data;
            $ack  = $data->$pack['number'];
            if($ack->ack == 'success'){          
                $trackInfo  = $ack->order_number;
            } else {
                $msg = $ack->msg;
            }
        }
        return array('trackNmber'=>$trackInfo,'response'=>$msg);
    }
    
    /**
     * 获取跟踪号
     * @param unknown $orderNumber
     * @return unknown[]
     */
    private function getOrderInfo($orderNumber)
    {
        $data['user_code']             = 'T062';
        $data['token']                 = 'dd8aa02e069baa55d162b1de5048dfc5';
        $data['reference_order_number']= $orderNumber;
        $xml_data                      = "request_data=".json_encode($data);
        $header[] = '';
        $response = self::post_curl('http://sandboxapi.faryaa.com/order/getOrderInfo',$header,$xml_data);
        $response = json_decode($response);
        if($response->ack=='success'){
            $data =  $response->data;
            if(!empty($data->order_number)){
                $trackInfo  = $data->order_number;
            } else {
                $msg        = $ack->msg;
            }
        }
        return array('trackNmber'=>$trackInfo,'response'=>$msg);
        
    }
    
    /**
     * 获取运输方式详细信息
     */
    private function getExpress($type)
    {
        $data['user_code']        = 'T062';
        $data['token']            = 'dd8aa02e069baa55d162b1de5048dfc5';
        $data['ship_channel_code']= $type;                
        $xml_data                 = "request_data=".json_encode($data);
        $header[]                 = '';
        $response = self::post_curl('http://api.faryaa.com/express/getExpress',$header,$xml_data);
        $response = json_decode($response);
        return $response;
    }
    
    
    /**
     * 添加商品
     */
    private function createSku($products)
    {
        $data['user_code']        = 'T062';
        $data['token']            = 'dd8aa02e069baa55d162b1de5048dfc5';
        //产品信息
        $productList = [];
        foreach ($products as $key=>$product) {
            $productList[$key]['sku']                = $product['sku'];
            $productList[$key]['declare_name']       = $product['declare_name'];      //英文申报品名
            $productList[$key]['declare_name_zh']    = $product['declare_name_zh'];   //中文申报品名
            $productList[$key]['declare_value']      = $product['declare_value'];     //申报价值
            $productList[$key]['fba_declare_value']  = $product['fba_declare_value']; //FBA进口申报价值
            $productList[$key]['hs_code']            = $product['hs_code'];           //海关编码
            $productList[$key]['battery']            = $product['battery'];           //是否带电池  1：不带电池   2：内置电池  3：配套电池   4：纯电池 
            
            $productList[$key]['battery_type']       = '';
            $productList[$key]['is_brand']           = 1;//是否有品牌
            $productList[$key]['brand_name']         = ''; //品牌名称
            $productList[$key]['remark']             = '';
        }
        $data['skus'] = $productList;
        $xml_data     = "request_data=".json_encode($data);
        $header[]     = '';
        $response = self::post_curl('http://sandboxapi.faryaa.com/Sku/createsku',$header,$xml_data);
        $response = json_decode($response);
        $sku      = '';
        $msg      = '';        
        if($response->ack=='success'){
            $data = $response->data;
            $ack  = $data->$products[0]['sku'];
            if($ack->ack == 'success'){
                $sku  = $ack->SKU;
            } else {
                $msg = $ack->msg;
            }
        }
        return array('sku'=>$sku,'response'=>$msg);
    }

    /**
     * CURL操作数据 
     * @param string $url
     * @param string $header
     * @param string $xml_data
     * @return mixed
     */
    function post_curl($url, $header, $xml_data)
    {           
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch,CURLOPT_HEADER,1);
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        /**
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Content-Length:'.strlen($xml_data))
            );
        **/
        
        if (!empty($xml_data)) {
            curl_setopt($ch, CURLOPT_POST, 1);      
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
        }
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }
    
    function get_curl($url, $header, $xml_data='')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch,CURLOPT_HEADER,1);
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }        
        if (!empty($xml_data)) {
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
        }
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }
    
    function del_curl($url, $header, $xml_data='')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch,CURLOPT_HEADER,1);
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");         
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }
    
    }