<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 17-3-8
 * Time: 下午2:38
 */


namespace app\common\service;
use think\Loader;
use app\common\cache\Cache;
use paypal\PayPalApi;


class Paypal
{


    /**
     * paypal退款操作
     * @param string $transaction_id     paypal交易号
     * @param array $refund_part         部分退款（全额退款时，此参数为空） [ amount|退款金额 , currency|货币类型  , note|退款备注说明 ]
     * @return array [ state|状态（1成功，0失败）, error_msg|失败错误信息 ]
     */
    public static function paypalRefund($transaction_id = '' , $refund_part = [])
    {
        //从缓存通过交易号中获取id
        //查看是否已经有这条数据
        $cache_info = Cache::store('PaypalOrder')->paypalOrderByTxnid($transaction_id);
        
        if(empty($cache_info) || param($cache_info, 'account_id')){
            return [
                'state' => 0,
                'error_msg' => '找不到PayPal交易订单'
            ];
        }
        
        $accountList = Cache::store('PaypalAccount')->getTableRecord();
        $paypal = $accountList[$cache_info['account_id']];
          
        if(!empty($paypal['api_user_name']) && !empty($paypal['api_secret']) && !empty($paypal['api_signature'])){
            $userRequest = new PayPalApi($paypal['api_user_name'], $paypal['api_secret'], $paypal['api_signature']);
            $re = $userRequest->paypalRefund($transaction_id , $refund_part);
            return $re;
        }
    }


}