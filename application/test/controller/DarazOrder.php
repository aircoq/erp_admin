<?php
namespace app\test\controller;

use service\daraz\Order\OrderService;
use app\common\cache\Cache;
use service\daraz\DarazLib;

/**
 * @module Daraz订单测试
 * @title Daraz订单测试
 * @description 接口说明
 * @url /daraz-order
 */
class DarazOrder
{
    /*
     * 获取Daraz账号
     */
    private function getAccount(){
        $id = '2';//awpk
        
        $accRow = Cache::store('DarazAccount')->getTableRecord($id);
        if(!$accRow){
            echo 'DarazAccount not exist!!!';
            exit;
        }
        $config = [
            'UserID'=>$accRow['api_user'],
            'APIKey'=>$accRow['api_key'],
            'Site'=>$accRow['site'],
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
        list($user_id,$api_key,$site) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new OrderService($user_id, $api_key, $site);
        
        /*
         * 3、组装参数、调用接口
         */
        $params = [
            //为了查询到的数据与条件看起来一致，这里做一个时间转换，实际拉单的时候直接传北京时间
            'CreatedAfter'=>DarazLib::getLocalDate('2019-02-1 15:55:47'),
            'CreatedBefore'=>DarazLib::getLocalDate('2019-02-02 15:55:48'),
        ];
        $re = $obj->GetOrders($params);
        echo "<pre>";
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
        list($user_id,$api_key,$site) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new OrderService($user_id, $api_key, $site);
        
        /*
         * 3、组装参数、调用接口
         */
        $params = [
//             'Format'=>'XML',
            'OrderId'=>'102116845449606',
        ];
        $re = $obj->GetOrder($params);
        var_dump($re);
        die;
    }

    public function getOrderItems()
    {
        /*
        * 1、获取账号数据
        */
        list($user_id,$api_key,$site) = $this->getAccount();

        /*
         * 2、实例化接口服务类
         */
        $obj = new OrderService($user_id, $api_key, $site);

        /*
         * 3、组装参数、调用接口
         */
        $params = [
//             'Format'=>'XML',
            'OrderId'=>'101971536622808',
        ];
        $re = $obj->GetOrderItems($params);
        echo "<pre>";
        print_r($re);
        die;
    }
    
}