<?php

namespace app\test\controller;

use service\amazon\Order\OrderService;
use app\common\cache\Cache;

/**
 * @module 亚马逊订单测试
 * @title 亚马逊订单测试
 * @description 接口说明
 * @url /amazon-order
 */
class AmazonOrder
{
    
    /*
     * 获取亚马逊账号
     */
    private function getAccount(){
//         $id = '438';
//         $id = '478';//chulaius
//         $id = '479';//chulaica
//         $id = '502';//chulaiuk
//         $id = '427';//lurdajp
//         $id = '1434';//portit
//         $id = '825';//xues
//         $id = '145';//zsluk
//         $id = '3994';//knowuk
//         $id = '4141';//peacede
//         $id = '4480';//bayde
//         $id = '584';//jiulingjp
        $id = '703';//yingjp

        if(!$acc = Cache::store('AmazonAccount')->getApiAccount($id)){
            die('获取账号授权失败');
        }
        return array_values($acc);
    }
    
    /**
     * @title 返回“订单 API”部分的运行状态
     * @url /getServiceStatus
     * @return \think\Response
     */
    public function getServiceStatus(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new OrderService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $re = $obj->getServiceStatus();
        print_r($re);
        die;
    }
    
    /**
     * @title 返回您在指定时间段内所创建或更新的订单
     * @url /listOrders
     * @return \think\Response
     */
    public function listOrders(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new OrderService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $Params = [
            'LastUpdatedAfter'=>'2019-01-15 17:11:36',
            'LastUpdatedBefore'=>'2019-02-22 17:13:36',
            'MaxResultsPerPage'=>'2',
        ];
        $re = $obj->listOrders($Params);
        print_r($re);
        die;
    }
    
    /**
     * @title 使用 NextToken 参数返回下一页订单
     * @url /listOrdersByNextToken
     * @return \think\Response
     */
    public function listOrdersByNextToken(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new OrderService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $NextToken = 'FT6cBao9ITF/YYuZQbKv1QafEPREmauvizt1MIhPYZZlkZZjJNnRdjcIKvrc76SQpD5CCGuPuktRBUDTTfkLYd8ytOVgN7d/KyNtf5fepe3iG+vCyuJg6hanQHYOdTc+S+0zfa1p/rlwwUVaIJ5xpkIjuUQcQ1sp7yktgvFMt9iuJWrxyrXt8F7TMh5o5U17y+9VsJ0xnRjrlvCrBUfHOqRUGVZR1pOFMMvThwZlrFkn+AwiN87vnp+lUkZL6+7mkUcxfLpcvmVNgfkgqJq/Ltlx/IhUNqB+HGGtljBBkTiLdbAGTjFVaOrSyOslVIrxnQVQjIzrILlugdfSI3iP1kdjLFTRp25Fy3P6j/Gu0CcZ2WTqVvk6tju7KVJPNsL1cawcthgh/MESjqLT68fnGE+F61vF3FM3kqmN86ZQGmHYbKuIPs/P0w+U051Y5+xafuzuxRyQtpffCjhi+VcyTRTArC6F/K1KEz/cddhIyOJmvgOHNdkjopW3TpDkS/UD';
        $re = $obj->listOrdersByNextToken($NextToken);
        print_r($re);
        die;
    }
    
    /**
     * @title 根据您指定的 AmazonOrderId 值返回订单
     * @url /getOrder
     * @return \think\Response
     */
    public function getOrder(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new OrderService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
//         $AmazonOrderId = '405-7984675-5652357';
//         $AmazonOrderId = '405-9643255-151632455787878';
//         $AmazonOrderId = '503-9402626-3927035666';
//         $AmazonOrderId = '114-9890398-9849006';
        $AmazonOrderId = '249-8509473-8155809';
        $re = $obj->getOrder($AmazonOrderId);
        print_r($re);
        die;
    }
    
    /**
     * @title 根据您指定的 AmazonOrderId 返回订单商品
     * @url /listOrderItems
     * @return \think\Response
     */
    public function listOrderItems(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new OrderService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
//         $AmazonOrderId = '503-9402626-392703566';
//         $AmazonOrderId = '404-8914550-4347538';
        $AmazonOrderId = '114-9890398-9849006';
        $re = $obj->listOrderItems($AmazonOrderId);
        print_r($re);
        die;
    }
    
}