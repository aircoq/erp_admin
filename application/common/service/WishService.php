<?php
namespace app\common\service;

use app\common\cache\Cache;
use app\common\model\wish\WishAccount;
use service\wish\WishApi;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/31
 * Time: 14:09
 */
class WishService
{
    const path = ROOT_PATH . DS . 'public' . DS . 'wish' . DS;    //文件下载的路径

    const PENDING = 0;   # 等待
    const SHIPPED = 1;   # 运输中
    const APPROVED = 2;  # 批准
    const DECLINED = 3;   # 拒绝
    const DELAYING = 4;   # 拖延
    const CANCELLED_BY_CUSTOMER = 5;  # 买家要求取消订单
    const REFUNDED_BY_MERCHANT = 6;  # 商家退货
    const REFUNDED_BY_WISH = 7;  # 平台退货
    const REFUNDED_BY_WISH_FOR_MERCHANT= 8;  # 平台退还给商家
    const UNDER_REVIEW_FOR_FRAUD= 9;  # 列入欺诈
    const CANCELLED_BY_WISH_FLAGGED_TRANSACTION = 10;  # 平台退还给商家


    /**
     * 禁用token时
     * @param $access_token
     * @return bool
     * @throws \think\Exception
     */
    public function updateToken($access_token)
    {
        $result = false;
        $account_list = Cache::store('wishAccount')->getAccount();
        foreach ($account_list as $k => $v) {
            //检测账号的token
            if (isset($v['access_token']) && $v['access_token'] == $access_token) {
                $result = WishApi::handler('common')->refreshToken($v);
                if ($result) {
                    if (isset($result['data'])) {
                        //更新token
                        $temp = $v;
                        $temp['access_token'] = $result['data']['access_token'];
                        $temp['refresh_token'] = $result['data']['refresh_token'];
                        $temp['expiry_time'] = $result['data']['expiry_time'];
                        //入库
                        $wishAccountModel = new WishAccount();
                        $wishAccountModel->where(['id' => $v['id']])->update($temp);
                        //删除缓存
                        Cache::store('wishAccount')->delAccount($v['id']);
                        //读取最新的数据
                        Cache::store('wishAccount')->getAccount($v['id']);
                        $result = true;
                    }
                }
            }
        }
        return $result;
    }
}