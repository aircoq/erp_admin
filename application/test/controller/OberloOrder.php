<?php
namespace app\test\controller;

use service\Oberlo\Order\OrderService;
use app\common\cache\Cache;
use service\oberlo\Categorie\CategorieService;
use service\oberlo\ShippingCarrier\ShippingCarrierService;

/**
 * @module Oberlo订单测试
 * @title Oberlo订单测试
 * @description 接口说明
 * @url /oberlo-order
 */
class OberloOrder
{
    /*
     * 获取Oberlo账号
     */
    private function getAccount(){
        $id = '1';//
//         $accRow = Cache::store('OberloAccount')->getTableRecord($id);
//         if(!$accRow){
//             echo 'OberloAccount not exist!!!';
//             exit;
//         }
        $config = [
            'token_key'=>'sUMntbuty3G9IAkUU1Te2H4aKvfk7C2hXIuQ6A5oxB4zWO3fxMR5ejtGS1ln',
        ];
        return array_values($config);
    }
    
    /**
     * @title 批量获取订单
     * @url /getOrders
     * @return \think\Response
     */
    public function getOrders(){
        /*
         * 1、获取账号数据
         */
        list($token_key) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new OrderService($token_key);
        
        /*
         * 3、组装参数、调用接口
         */
        $params = [
            'page'=>1,
            'date_from'=>'2016-12-30',
            'date_to'=>'2017-12-31',
            'fulfillment_status'=>'not_fulfilled',
            'payment_status'=>'paid',
            'other'=>'not_shipped',
        ];
        $re = $obj->getOrders($params);
        print_r($re);
        die;
    }
    
    /**
     * @title 获取单个订单
     * @url /getOrder
     * @return \think\Response
     */
    public function getOrder(){
        /*
         * 1、获取账号数据
         */
        list($token_key) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new OrderService($token_key);
        
        /*
         * 3、组装参数、调用接口
         */
        $order_id = '1665753';
        $re = $obj->getOrder($order_id);
        print_r($re);
        die;
    }
    
    /**
     * @title 获取所有品类
     * @url /getCategoryTree
     * @return \think\Response
     */
    public function getCategoryTree(){
        /*
         * 1、获取账号数据
         */
        list($token_key) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new CategorieService($token_key);
        
        /*
         * 3、组装参数、调用接口
         */
        $re = $obj->getCategoryTree();
        print_r($re);
        die;
    }
    
    /**
     * @title 检索运输公司列表
     * @url /getCategoryTree
     * @return \think\Response
     */
    public function getCarriers(){
        /*
         * 1、获取账号数据
         */
        list($token_key) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new ShippingCarrierService($token_key);
        
        /*
         * 3、组装参数、调用接口
         */
        $re = $obj->getCarriers();
        print_r($re);
        die;
    }
    
}