<?php
/**
 * Created by PhpStorm.
 * User: huangjintao
 * Date: 2019/3/20
 * Time: 17:15
 */

namespace app\index\controller;


use app\common\controller\Base;
use app\common\service\Common;
use app\index\service\Ali1688PaymentService;
use think\Request;

/**
 * @title 付款申请单1688线上支付
 * @author huangjintao
 * @url /ali1688payment
 * @package \app\index\controller
 */
class Ali1688Payment extends Base
{
    /**
     * @title 付款申请单线上支付
     * @author huangjintao
     * @method post
     * @url /ali1688payment/online-payment
     * @return \think\response\Json
     */
    public function onlinePayment()
    {
        $request = Request::instance();
        $params = $request->param();
        $user = Common::getUserInfo($request);
        if (!empty($user)){
            $params['user_id'] = $user['user_id'];
        }
        $ali1688PaymentService = new Ali1688PaymentService();
        $result = $ali1688PaymentService->payment($params);
        return json($result,200);
    }

    /**
     * @title 付款申请单线上支付完成
     * @author huangjintao
     * @method post
     * @url /ali1688payment/get-order-details
     * @return \think\response\Json
     */
    public function getOrderDetails()
    {
        $request = Request::instance();
        $params = $request->param();
        $user = Common::getUserInfo($request);
        if (!empty($user)) {
            $params['user_id'] = $user['user_id'];
        }
        $ali1688PaymentService = new Ali1688PaymentService();
        $result = $ali1688PaymentService->OrderDetails($params);
        return json($result,200);
    }

    /**
     * @title 1688支付方式下拉框
     * @author huangjintao
     * @method get
     * @url /ali1688payment/pay-type
     * @return array
     */
    public function payType()
    {
        $request = Request::instance();
        $params = $request->param();
        $ali1688PaymentService = new Ali1688PaymentService();
        $result = $ali1688PaymentService->getPayTypeList($params);
        return json($result,200);
    }

}