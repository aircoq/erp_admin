<?php

namespace app\test\controller;

use service\amazon\Report\ReportService;
use app\common\cache\Cache;

/**
 * @module 亚马逊报告测试
 * @title 亚马逊报告测试
 * @description 接口说明
 * @url /amazon-report
 */
class AmazonReport
{
    
    /*
     * 获取亚马逊账号
     */
    private function getAccount(){
        
//         $id = '438';
//         $id = '478';//chulaius
//         $id = '479';//chulaica
//         $id = '502';//chulaiuk
        $id = '427';//lurdajp
        
        if(!$acc = Cache::store('AmazonAccount')->getApiAccount($id)){
            die('获取账号授权失败');
        }
        return array_values($acc);
    }
    
    /**
     * @title 创建报告请求
     * @url /requestReport
     * @return \think\Response
     */
    public function requestReport(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new ReportService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
//         $ReportType = '_GET_MERCHANT_LISTINGS_DATA_BACK_COMPAT_';
        $ReportType = '_GET_FBA_MYI_ALL_INVENTORY_DATA_';
        $StartDate = '';
        $EndDate = '';
        $ReportOptions = '';
        $MarketplaceIdList = array();
        $re = $obj->requestReport($ReportType, $StartDate, $EndDate, $ReportOptions, $MarketplaceIdList);
        print_r($re);
        die;
    }
    
    /**
     * @title 查询报告处理状态
     * @url /getReportRequestLists
     * @return \think\Response
     */
    public function getReportRequestList(){
        
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new ReportService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $RequestedFromDate = '';
        $RequestedToDate = '';
        $ReportRequestIdList = array(
//                     '102125017872'
        );
        $ReportTypeList = array(
//             '_GET_MERCHANT_LISTINGS_DATA_BACK_COMPAT_'
                    '_GET_FBA_MYI_ALL_INVENTORY_DATA_'
//             '_GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE_V2_'
        );
        $ReportProcessingStatusList = array();
        $MaxCount = 100;
        
        $re = $obj->getReportRequestList(
            $RequestedFromDate,
            $RequestedToDate,
            $ReportRequestIdList,
            $ReportTypeList,
            $ReportProcessingStatusList,
            $MaxCount);
        
        print_r($re);
        die;
    }
    
    /**
     * @title 下载报告内容
     * @url /getReport
     * @return \think\Response
     */
    public function getReport(){
        
//         $ContentType = ',text/plain;charset=Windows-31J';
//         $charset = preg_match('/charset=(.*)$/i', $ContentType,$cm);
//         var_dump($charset);
//         print_r($cm);
//         die;
        
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new ReportService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $ReportId = '2367902794017892';
        $re = $obj->getReport($ReportId);
        print_r($re);
        die;
    }
    
}