<?php
namespace app\carrier\service\operation;
use service\shipping\ShippingApi;

use app\carrier\service\ShippingMethodBase;


/**
 * 
 * 易通关物流
*/
class Etg extends   ShippingMethodBase
{       
    
    /**
     * 上传包裹数据
     */
    public function createOrder($config,$order)
    {                
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);        
        $requestApi = ShippingApi::instance()->loader("$class");
        $re         = $requestApi->createOrder($config,$order);
        return $re;
    }
    
    /** 
     * 订单提交预报 
     * @param number $orderId
     * @return unknown
     */
    public function confirmOrder($config,$order)
    {   
        //包裹条码:HTP17022700001 客户订单号:JNZ1488167523
        $result = [];
        if (!empty($order['name'])) {
            $refNos     = array($order['name']);
            $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
            $requestApi = ShippingApi::instance()->loader("$class");
            $result     = $requestApi->forecastByRefNo($config,$refNos);
        }
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
       // $result     = $requestApi->deleteOrder($mailNumber);
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
        if (is_array($order)) {
            foreach ($order as $ob) {
                $refNos[] = ob['name'];
            }
        } else {
            $refNos     = array($order['name']);
        }
        $class      = substr(str_replace(__NAMESPACE__,'',__CLASS__),1);
        $requestApi = ShippingApi::instance()->loader($class);
        $result     = $requestApi->queryParcelByRefNo($config,$refNos);
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

}

?>

