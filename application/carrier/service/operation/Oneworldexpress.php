<?php
namespace app\carrier\service\operation;
use service\shipping\ShippingApi;

use app\carrier\service\ShippingMethodBase;
use org\Xml;
use org\Curl;

/**
 * 
 * 万欧物流
 * @class Oneworldexpress
 * @package app\carrier\controller
 */
class Oneworldexpress extends ShippingMethodBase
{      
       
    
    /**
     * 上传包裹数据
     */
    public function createOrder($config,$order)
    {
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance()->loader("$class");
        $re         = $requestApi->createOrder($config,$order);
        return $re ;
    }
    
    /**
     * 删除订单
     * @param number $orderId
     * @return unknown
     */
    public function deleteOrder($config,$order)
    {
        $class     = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance()->loader($class);
        $result     = $requestApi->deleteOrder($mailNumber);
        return $result;
    }
    
  
    /**
     * 获取跟踪号
     * {@inheritDoc}
     * @see \app\carrier\service\ShippingMethodBase::getTrackNumber()
     */
    public  function getTrackNumber($config,$order)
    {
        $result = [];
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance()->loader($class);
        //$result     = $requestApi->confirmation($config,$order);
        return $result;
    }
    
    /**
     * 获取线上发货物流服务列表
     */
    public function getLogisticsServiceList($config)
    {        
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance()->loader($class);
        $result     = $requestApi->getExpress($config);
        return $result;
    }
    


    /**
     * 订单提交预报
     * @param number $orderId
     * @return unknown
     */
    public function confirmOrder($config,$order)
    {
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance()->loader($class);
        $result     = $requestApi->confirmation($config,$order);
        return $result;
    }
    
    
    /**
     * 获取仓库代码
     * @param unknown $ob
     * @param unknown $pds
     * @return mixed[]
     */
     function getWarehouses($config)
    {   
        $locationUrl = 'api.oneworldexpress.cn/'; //正式
        $header[]    = "Authorization:Hc-OweDeveloper ".$config['client_id'].";".$config['client_secret'].";".md5(time().mt_rand(1,1000000));
        $header[]    = "Host: api.oneworldexpress.cn"; //正式
        $response    = Curl::curlGet($locationUrl.'api/warehouses',$header);
        $response    = json_decode($response,true);
        $data        = [];
        if ($response['Succeeded']==true && !empty($response['Data']['Warehouses'])) {
            foreach ($response['Data']['Warehouses'] as $k=>$v) {
                $data[$k]['code']      = $v['Code'];
                $data[$k]['name']      = $v['Name'];
            }
        }
        return $data;
    }
    
    
    //万欧物流 获取标签
     function getLabel($config,$ob)
    {                   
        
        //$re = self::createOrder($order,$product,'3HPA');
        // $ob['process_code'] = 'OWEAA0020404067YQ';//
        // $re = self::deletePackage($ob);
        // $obc['process_code'] = 'OWEAA0020404244YQ';//这个是真实订单，没有删除
        // $re = self::confirmation($obc);
        //$oba['process_code']  = 'OWEAA0020404587YQ';//这个是真实订单，没有删除
        //$oba['process_code']  = 'OWEAA0020404836YQ';//这个是真实订单，没有删除        
        // $oba['process_code']  = 'OWEAA0020404848YQ';
        
        //$ob['process_code']  = 'OWEAA0020404244YQ';  
        
        $header[]     = "Content-Type:application/json";
        $header[]     = "Accept:application/json";                
        $locationUrl  = 'api.oneworldexpress.cn/'; //正式
        $header[]     = "Authorization:Hc-OweDeveloper ".$config['client_id'].";".$config['client_secret'].";".md5(time().mt_rand(1,1000000));
        $header[]     = "Host: api.oneworldexpress.cn"; //正式        
        if (!empty($ob)) {            
            $response   = Curl::curlGet($locationUrl."api/parcels/".$ob['process_code']."/label",$header);         
            //$fs = file_get_contents('php://input');            
            ob_start();
            ob_end_clean();
            header("Content-type: application/pdf");
            echo $response;
            exit(0);
            exit;
            
            //             if (!is_dir($path)) {
            //                 mkdir($path,0777);
            //             }
            //$file = $path."xxx.pdf";
            //file_put_contents($file, $response);//存盘
        }
    }
    
    
  }