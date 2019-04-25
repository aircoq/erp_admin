<?php
namespace app\carrier\service\operation;
use service\shipping\ShippingApi;

use app\carrier\service\ShippingMethodBase;


/**
 * 
 * 
 * @package app\carrier\controller
 */
class Ubi extends   ShippingMethodBase
{   
            
    
    /**
     * 密钥
     */
    function __construct($auth='968696000040123585'){
        

        //https://cn.etowertech.com – Beijing, China        
        //https://au.etowertech.com – Sydney, Australia
        
        //define('WALLTECH_SERVER', 'http://qa.etowertech.com');
        //define('ACCESS_TOKEN', 'test5AdbzO5OEeOpvgAVXUFE0A');
        //define('SECRET_KEY', '79db9e5OEeOpvgAVXUFWSD');
        
    }
        
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
        $class     = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance()->loader($class);
        $result                  = $requestApi->getExpress($config);
        return $result;
    }
  
    
 }