<?php
namespace app\carrier\service\operation;
use service\shipping\ShippingApi;
use app\carrier\service\ShippingMethodBase;


/**
 * 
 * 
 * @package app\carrier\controller
 */
class Fanya extends  ShippingMethodBase
{           
    /**
     *  创建订单到物流商
               英国专线-大包	UKBT
               德国专线-大包	DEAT
                英国专线-小包	UKAT               
     * @param unknown $order
     * @param unknown $product  
     * @return unknown[]|string[]
     */
    function createOrder($config,$order)
    {
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);        
        $requestApi = ShippingApi::instance()->loader("$class");
        $re         = $requestApi->createOrder($config,$order);
        return $re ;
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
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance()->loader($class);       
        $result     = $requestApi->getExpress($config);
        return $result;
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
     * 添加商品
     */
    function addSku($config,$products)
    {
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance()->loader($class);       
        $result     = $requestApi->addSku($config,$products);
        return $result;
    }

    
    
    }