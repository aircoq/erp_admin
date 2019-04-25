<?php
/**
 * Created by PhpStorm.
 * User: huangjintao
 * Date: 2019/3/29
 * Time: 14:34
 */

namespace app\api\controller;


use app\common\cache\Cache;
use app\common\cache\driver\Ali1688Account;
use app\index\service\Ali1688PaymentService;
use think\Controller;
use think\Request;

/**
 * @title 采购单1688付款信息接口
 * @author huangjintao
 * @package app\api\controller
 */
class Ali1688Purchase extends Controller
{
    /**
     * @title 接收付款信息
     * @method post
     * @url api/ali1688Purchase/receive-messages
     * @param Request $request
     * @return int
     */
    public function ReceiveMessages(Request $request)
    {
        $params = $request->param();
        if ($params){

            $data = json_decode($params['message'],true);
            //var_dump($data);exit();
            if ($data['message']['type'] == 'ORDER_BUYER_VIEW_ORDER_PAY' || $data['message']['type'] == 'ORDER_BUYER_VIEW_ORDER_STEP_PAY'){
                $order_id = $data['message']['data']['orderId'];
                $ali1688 = (new Ali1688Account())->getData(substr($order_id, -4));
                $user_id = Cache::handler()->hGet(Ali1688PaymentService::ALI_PAYMENT_OPERATION_USER_ID,$order_id) ?? 0;
                $params = ['user_id' => $user_id];
                $purchaseOrderData = [
                    'account_1688' => $ali1688['account_name'],
                    'external_number_arr' => ["$order_id"],
                    'refresh_token' => $ali1688['refresh_token'],
                    'access_token' => $ali1688['access_token'],
                    'client_id' => $ali1688['client_id'],
                    'client_secret' => $ali1688['client_secret'],
                ];

                $ali1688PaymentService = new Ali1688PaymentService();
                $ali1688PaymentService->OrderDetails($params,$purchaseOrderData);
                Cache::handler()->hDel(Ali1688PaymentService::ALI_PAYMENT_OPERATION_USER_ID,$order_id);
            }
        }
        return 200;
    }
}