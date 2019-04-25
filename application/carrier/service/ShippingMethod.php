<?php

namespace app\carrier\service;

use app\warehouse\service\Carrier;
use think\Exception;

/**
 * Shipping 的各种运输方式
 */
class ShippingMethod
{
    private static $config = [];
    private static $instance = null;
    private static $object = [];
    private $package;
    private $carrierServer;

    public function __construct()
    {
        $this->carrierServer = new Carrier();
    }


    /**
     * 开放接口
     * @param  array $config [description]
     * @return [type]         [description]
     */
    public static function instance($type, $config = [])
    {
        try {
            $class = __NAMESPACE__ . '\\operation\\' . ucwords($type);
            // $class = false !== strpos($type, '\\') ? $type : '\\service\\operation\\' . ucwords($type);
            if ($class) {
                if (!isset(self::$object[$type]) || is_null(self::$object[$type])) {
                    self::$object[$type] = new $class();
                }
                return self::$object[$type];
            } else {
                throw new Exception("The api type file is not found", 1);
            }
        } catch (Exception $e) {
            throw new Exception("Error Processing Request", 1);
        }

        if (is_null(self::$instance)) {
            self::$instance = new ShippingMethod();
        }
        if (!empty($config)) {
            array_merge(self::$config, $config);
        }
        return self::$instance;
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

    /**
     * @doc 获取邮寄方式的全名称
     * @param $shippingId
     * @return string
     */
    public function getFullName($shippingId)
    {
         if($shipping = \app\common\model\ShippingMethod::getCache($shippingId)){
             //shortname
             if($carrier = $this->carrierServer->getCarrier($shipping->carrier_id)){
                 return "{$carrier->fullname}>>{$shipping->shortname}";
             }else{
                 return $shipping->shortname;
             }
         }else{
             return '';
         }
    }

}