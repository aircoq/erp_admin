<?php
namespace app\common\interfaces;
/** 平台退款
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/6/8
 * Time: 17:26
 */
interface ChannelRefund
{
    /** 平台退款
     * @param $order_id
     * @return mixed
     */
    public function refund($order_id);
}