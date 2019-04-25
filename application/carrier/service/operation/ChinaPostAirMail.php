<?php

namespace app\carrier\service\operation;
use service\shipping\ShippingApi;

use app\carrier\service\ShippingMethodBase;

/**
 * 中邮小包
 * @package app\carrier\service\operation
 * 
 */
class ChinaPostAirMail extends ShippingMethodBase{
    
    
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
        
    }
    
    /**
     * 订单提交预报
     * @param number $orderId
     * @return unknown
     */
    public function confirmOrder($config,$order)
    {
        $result = [];
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance($config)->loader($class);
        $result     = $requestApi->confirmOrder($order);
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
     * 获取物流信息
     */
    public function getLogisticsServiceList($config)
    {        
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance()->loader($class);       
        $result     = $requestApi->getDataServlet('queryBusinessType');
        return $result;
    }

}

