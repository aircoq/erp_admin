<?php

namespace app\carrier\service;

use think\Exception;

/**
 * Shipping 的各种运输方式
 */
abstract class ShippingMethodBase
{
   protected   $package;
    
    // abstract protected function load();        
    
    /**
     * 创建包裹信息
     * $config  配置信息
     * $package  包裹信息
     * $products 包裹中的所有产品信息
     * $channel  渠道的英文名
     * @param unknown $name
     */
    abstract public function createOrder($config,$package);
    
    /**
     * 提交预报包裹信息
     * @param unknown $name
     */
    abstract public function confirmOrder($config, $package);
    
    /**
     * 删除包裹信息
     * @param unknown $name
     */
    abstract public function deleteOrder($config, $package);
    
    
    /**
     * 获取物流信息
     * @param unknown $name
     */
    abstract public function getLogisticsServiceList($config);
    
    /**
     * 获取跟踪号
     * @param unknown $config
     * @param unknown $package
     */
    abstract public function getTrackNumber($config,$package);
    
    
    /**
     * 更新包裹信息
     * @param unknown $name
     */
    public function save(){
        $this->package->save();
    }
    
    /**
     * 资源配置
     * @param  array $config [description]
     * @return [type]         [description]
     */
    public function config(array $config)
    {
        array_merge(self::$config, $config);
    }

}