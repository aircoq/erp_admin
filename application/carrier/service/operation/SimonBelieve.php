<?php
namespace app\carrier\service\operation;
use service\shipping\ShippingApi;

use app\carrier\service\ShippingMethodBase;
/**
 * 
 * 淼信物流
 * @package app\carrier\service\operaiton
 */
class SimonBelieve extends ShippingMethodBase
{ 
    
    /**
     * 上传包裹数据
     */
    public function createOrder($config,$order)
    {                 
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);        
        $requestApi = ShippingApi::instance($config)->loader("$class");
        $re         = $requestApi->createOrder($order);
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
        $requestApi = ShippingApi::instance($config)->loader($class);
        $result     = $requestApi->deleteOrder($order);
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
        $requestApi = ShippingApi::instance($config)->loader($class);
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
        $requestApi = ShippingApi::instance($config)->loader($class);
        //$result     = $requestApi->confirmation($config,$order);
        return $result;
    }
    
    /**
     * 获取线上发货物流服务列表
     */
    public function getLogisticsServiceList($config)
    {        
        $class     = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance($config)->loader($class);       
        $result                  = $requestApi->getChannelList();
        return $result;
    }
    
}