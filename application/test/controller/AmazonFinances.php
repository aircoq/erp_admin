<?php

namespace app\test\controller;

use service\amazon\Finances\FinancesService;
use app\common\cache\Cache;

/**
 * @module 付款 API测试
 * @title 付款 API测试
 * @description 接口说明
 * @url /amazon-finances
 */
class AmazonFinances
{
    
    /*
     * 获取亚马逊账号
     */
    private function getAccount(){
        
        $id = '438';
        
        if(!$acc = Cache::store('AmazonAccount')->getApiAccount($id)){
            die('获取账号授权失败');
        }
        return array_values($acc);
    }
    
    /**
     * @title 返回“付款 API”部分的运行状态
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
        $obj = new FinancesService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $re = $obj->getServiceStatus();
        print_r($re);
        die;
    }
    
    /**
     * @title 返回给定日期范围的财务事件组
     * @url /listFinancialEventGroups
     * @return \think\Response
     */
    public function listFinancialEventGroups(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new FinancesService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $FinancialEventGroupStartedAfter = '2007-11-16 03:36:02';
        $FinancialEventGroupStartedBefore='2018-11-16 04:36:02';
        $MaxResultsPerPage = 20;
        $re = $obj->listFinancialEventGroups($FinancialEventGroupStartedAfter,$FinancialEventGroupStartedBefore,$MaxResultsPerPage);
        print_r($re);
        die;
    }
    
    /**
     * @title 返回给定日期范围的财务事件组下一页
     * @url /listFinancialEventGroupsByNextToken
     * @return \think\Response
     */
    public function listFinancialEventGroupsByNextToken(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new FinancesService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $NextToken = 'e21hcmtldHBsYWNlSWQ6bnVsbCxtYXhSZXN1bHRzUGVyUGFnZToyMCxzZWFyY2hRdWVyeUNoZWNrc3VtOiJxTFB1TTlCZ29mYmJZb0VDTGZfVHZEZGhERGMiLHF1ZXJ5UGFnaW5hdGlvblRva2VuOm51bGwsc2VhcmNoUXVlcnk6InIxd0RUZTA1ajRPTVN5alFsTUttbU5wLWtqQlFaWDBjX0VkYjRjcjBzMHNXeUFhTlhEbWhGaGMwZ0hzQXBtMGVjQWRFcGFOYVQwLVA0T0NMUkd2SmozVUtTSEMwdUpMT3ktclVLM2dxd1V1YXZ3ZkpxTnJESENULVlaUVZJZ0Fyd2FYMExyc20xSGxvXy1BOU1xVFB5MHlzbjBqZnhzZEZ2RUw3ZnJfZk5SaHVsRWNfNVhTcm85TjBVMkQ4Y2RBcERDcHc4UTE1ZjdVX1NOVkYzVjhueXNmUjlQQ3dMTmotSk0tcHR2NmgzZnBfaWdROWFnS1RydzRFWU5QN2Fua210N1gxS3VVaWRkUUFDTUNmR0dYV0pJdjJfVVM2dmNheElzY3RUZWJ2RW1HQ0lMZUVjQUIzNDRZVG5tYlZrNVU2b0pSa3pXd2FIaWxCZnE3Y1NETk5QLW4yS2FZQ25XaS0yM0Z6ZlFlT2NPQTdVM3M0MW9xT3ZjbU1tbFFaeWNSdmY2RE9hYUVITDhVLVVZY1dRUHFzZXBsVURmYm1RR09GdEFyTmtwWHpaVlJiM01ibkUyM1ZNTDNTWFM0d0ZIV2d4Nmc2SmFRYm9tNWpVSTBNbVBnaWRFT2oxUDAzTzZlSTFUUHpWT3o3OFNyXy10bXlTV0dDWnpBYmVaTW14a0R6SVhWZ0lvQW1SSzVCYkpyYUk5Z3o2WTdMRHBrelZ3eUUyZzluSDRxSHlvZ05oV3Y4SHNwTG50b3A2dUs0NklUcFozNU1CSDRpajRNRkxLWmdHTzVKYTZVcnA3ZDRlMFNEWF81TFZsc25lTjlEWE81UHF1Mm9OVk9HZzdNMThTdnc3RldPNXdtT1N6Y1gxZVY1cjNGTHBWZjZUQnFTcHZ4V0RfS0l1R0FQUmpQcVFXei0yYVJpT0lCLW5XYTZfTlZKb0ZnWnhxdFg1Z29kSHdxUkxjNm9hVGtUTnJibnl6WWM5amdEWWJSbnhIQy1xMkJURXRNSjlITjdmV19yaDhNOTNpdEhJXzM3UWJiWWhWQnVWLThoczRQZnZ3QkVtQ3ZmbW9qUV9OajhZTDF6Qzk1Wnp0aVFUdGI0bmxEWFdhdWY4amd2SzBsX3ZYLWxJdG9zMjhReG9RIix0b2tlbkNyZWF0aW9uRGF0ZToxNTQyMzY2MjYzNzg5LHNlbGxlcklkOiJBMVpZTjAyNURDSjNUNyJ9';
        $re = $obj->listFinancialEventGroupsByNextToken($NextToken);
        print_r($re);
        die;
    }
    
    /**
     * @title 返回给定订单，财务事件组或日期范围的财务事件
     * @url /listFinancialEvents
     * @return \think\Response
     */
    public function listFinancialEvents(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new FinancesService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $AmazonOrderId='';
        $FinancialEventGroupId='';
        $PostedAfter='2018-11-15 18:49:32';
        $PostedBefore = '2018-11-16 18:55:30';
        $MaxResultsPerPage=20;
        $re = $obj->listFinancialEvents(
            $AmazonOrderId,
            $FinancialEventGroupId,
            $PostedAfter,
            $PostedBefore,
            $MaxResultsPerPage
            );
        print_r($re);
        die;
    }
    
    /**
     * @title 返回给定订单，财务事件组或日期范围的财务事件 下一页
     * @url /listFinancialEventsByNextToken
     * @return \think\Response
     */
    public function listFinancialEventsByNextToken(){
        /*
         * 1、获取账号数据
         */
        list($token_id,$token,$seller_id,$site,$mws_auth_token) = $this->getAccount();
        
        /*
         * 2、实例化接口服务类
         */
        $obj = new FinancesService($token_id, $token, $seller_id, $site, $mws_auth_token);
        
        /*
         * 3、组装参数、调用接口
         */
        $NextToken='e21hcmtldHBsYWNlSWQ6bnVsbCxtYXhSZXN1bHRzUGVyUGFnZTowLHNlYXJjaFF1ZXJ5Q2hlY2tzdW06bnVsbCxxdWVyeVBhZ2luYXRpb25Ub2tlbjoiVUQwelVqY1hXRU5ENHZhZERCWTdDYXBCdWtPZklpV0l2MWx5YmxqVGxkbFlkdmxDYlNMUlZNLUgyTnJUWHJ3UXFjQkxnU0ZPOTd5RUlyaTJhZDQyREI0WUwtcVJPRmN5VW9Odjg5ampTa0o3UTFoS1hsR0dPYWViZ0xTWXNJaE9CQmJIb2hVUjBDVlJiTnpFVEVVMy04UXhDdHZtcDg4UTNtRTY2Tzk2QjNsa0pQN19zVzV0MHFKMFkwUnQzMWxwMGJWcTJXUG03UWI1NnI4SUkxLWJlVkNqR0Yxb25ndHg1dGZqdDZfalVoZWNUNFJyMXZxTlRUUDRhQTNLY09Qa3otOUd3VDBzbUFVa2ZpZ05GZS1qZm9LZ0pmLXRsa0RGOW9yM2ZuN0JwVGJVMnRpenY1b2l4ZHVrRjJDeUl0ZkdWQ0xaSV9fUGlKaDg4RkVSWTdrWEVLUEdoVk9OZF9obDVBSW1hYU1EX2tCdVZ5cjJqalMtYmYwOHRheGpaYVlVN1ota3BreHM2MzRGRFk2aTEwRERvUSIsc2VhcmNoUXVlcnk6bnVsbCx0b2tlbkNyZWF0aW9uRGF0ZToxNTQyMzcwMTUzMDMzLHNlbGxlcklkOiJBMVpZTjAyNURDSjNUNyJ9';
        $re = $obj->listFinancialEventsByNextToken($NextToken);
        print_r($re);
        die;
    }
    
}