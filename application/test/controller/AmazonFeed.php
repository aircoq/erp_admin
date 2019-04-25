<?php

namespace app\test\controller;

use service\amazon\Feed\ProductFeedService;
use app\common\cache\Cache;

/**
 * @module 亚马逊上传数据测试
 * @title 亚马逊上传数据测试
 * @description 接口说明
 * @url /amazon-feed
 */
class AmazonFeed
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
        $id = '4480';//bayde
        
        if(!$acc = Cache::store('AmazonAccount')->getApiAccount($id)){
            die('获取账号授权失败');
        }
        return array_values($acc);
    }
    
    /**
     * @title 返回过去 90 天内提交的上传数据计数
     * @url /getFeedSubmissionCount
     * @return \think\Response
     */
    public function getFeedSubmissionCount(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new ProductFeedService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $re = $obj->getFeedSubmissionCount();
        print_r($re);
        die;
    }
    
}