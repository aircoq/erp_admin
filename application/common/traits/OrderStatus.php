<?php

namespace app\common\traits;

use app\common\service\OrderStatusConst;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/13
 * Time: 14:56
 */
trait OrderStatus
{
    /** 状态位查询
     * @disabled
     * @param int $code
     * @param string $alias
     * @return string
     */
    public function condition($code = 0, $alias = '')
    {
        $alias = !empty($alias) ? $alias . '.' : '';
        if (!empty($code)) {
            $code_arr = explode('_', $code);
            switch ($code_arr[0]) {
                case 0:
                    if ($code_arr[1] == 0) {
                        $condition = "  (" . $alias . "status >> 17  | 0 = 0 and " . $alias . "status = 0 and  (" . $alias . "status & 1023) | " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    } else {
                        if ($code_arr[1] == 64) {
                            $condition = " (" . $alias . "status >> 17  | 0 = 0  and  (" . $alias . "status & 1023) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                            //$condition .= " or " . $alias . "status >> 21 = 1 ";
                        } else {
                            $condition = " (" . $alias . "status >> 17  | 0 = 0  and  (" . $alias . "status & 1023) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                        }
                    }
                    break;
                case 1:
                    if ($code_arr[1] == 0) {
                        $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17)  = " . $code_arr[0] . " and  (" . $alias . "status & 3843) | " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    } else {
                        $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17)  = " . $code_arr[0] . " and  (" . $alias . "status & 3843) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    }
                    break;
                case 3:
                    if ($code_arr[1] == 12288) {   //待交运的
                        $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 12288)>= 4096 and (" . $alias . "status & 12288) <= 12288" . ")";
                    } else {
                        $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 63548) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    }
                    break;
                case 7:
                    $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 511) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    break;
                case 15:
                    $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 3) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    break;
                case 16:
                    $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 21) = 1 and " . $alias . "status != 4294967295" . ")";
                    break;
                case 32767:
                    $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 65535) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    break;
                default:
                    $condition = "";
                    break;
            }
            return $condition;
        }
    }

    /** 物流商下单查询
     * @disabled
     * @param int $code
     * @param string $alias
     * @return string
     */
    public function conditionByUpload($code = 0, $alias = '')
    {
        $condition = '';
        $alias = !empty($alias) ? $alias . '.' : '';
        if (!empty($code)) {
            $code_arr = explode('_', $code);
            switch ($code_arr[0]) {
                case 1:
                    switch ($code_arr[1]) {
                        case 0: // 已配货等待上传
                            $condition = " (" . $alias . "status = " . OrderStatusConst::AllocatedInventory . " and " . $alias . "shipping_time = 0 and " . $alias . "distribution_time > 0" . ")";
                            break;
                        case 2: //已分配库存等待交运
                            $condition = " (" . $alias . "status >> 17  = 3  and  " . $alias . "package_confirm_status  <= 0 and " . $alias . "shipping_time = 0" . ")";
                            break;
                        case 3: //已分配库存等待物流商发货
                            $condition = " (" . $alias . "status >> 17  = 7  and  " . $alias . "providers_shipping_time  = 0" . ")";
                            break;
                        case 4:
                            //已分配库存推送管易
                            $condition = " (" . $alias . "status >> 17  = 7  and  " . $alias . "upload_to_warehouse = 0 and " . $alias . "is_push = 3" . ")";
                            break;
                    }
                    break;
                case 0:
                    switch ($code_arr[1]) {
                        case 0: // 申报异常等待上传
                            $condition = " (" . $alias . "status in (" . OrderStatusConst::DeclareTheAddressWrong . "," . OrderStatusConst::DeclareThePriceError . ")" . ")";
                            break;
                        case 2: //申报异常等待交运
                            $condition = " (" . $alias . "status in (" . OrderStatusConst::DeclareTheAddressWrong . "," . OrderStatusConst::DeclareThePriceError . ")" . " and " . $alias . "package_confirm_status  <= 0 and " . $alias . "package_upload_status > 0" . ")";
                            break;
                        case 3: //申报异常等待物流商发货
                            $condition = " (" . $alias . "status in (" . OrderStatusConst::DeclareTheAddressWrong . "," . OrderStatusConst::DeclareThePriceError . ")" . " and " . $alias . "providers_shipping_time  = 0 and " . $alias . "package_confirm_status > 0" . ")";
                            break;
                        case 4:
                            $condition = " (" . $alias . "status in (" . OrderStatusConst::DeclareTheAddressWrong . "," . OrderStatusConst::DeclareThePriceError . ")" . " and  " . $alias . "upload_to_warehouse = 0 and " . $alias . "is_push = 3" . ")";
                            break;
                    }
                    break;
                case 16:
                    switch ($code_arr[1]) {
                        case 0: // 缺货等待上传
                            $condition = " (" . $alias . "status = " . OrderStatusConst::StockOut . " and " . $alias . "shipping_time = 0 and " . $alias . "distribution_time = 0" . ")";
                            break;
                        case 2: //缺货等待交运
                            $condition = " (" . $alias . "status >> 21  = 1  and  " . $alias . "package_confirm_status  <= 0 and " . $alias . "package_upload_status > 0 and " . $alias . "shipping_time = 0" . ")";
                            break;
                        case 3: //缺货等待物流商发货
                            $condition = " (" . $alias . "status >> 21  = 1  and  " . $alias . "providers_shipping_time  = 0 and " . $alias . "package_confirm_status > 0 and " . $alias . "shipping_time = 0" . ")";
                            break;
                        case 4:
                            $condition = " (" . $alias . "status >> 21  = 1  and  " . $alias . "upload_to_warehouse = 0 and " . $alias . "is_push = 3" . ")";
                            break;
                    }
                    break;
                default:
                    break;
            }
            return $condition;
        }
    }


    /** 状态位静态方式查询
     * @disabled
     * @param int $code
     * @param string $alias
     * @return string
     */
    public static function conditionStatic($code = 0, $alias = '')
    {
        if (is_numeric($code)) {
            $condition = $alias . "status = " . $code;
            return $condition;
        }
        $alias = !empty($alias) ? $alias . '.' : '';
        if (!empty($code)) {
            $code_arr = explode('_', $code);
            switch ($code_arr[0]) {
                case 0:
                    if ($code_arr[1] == 0) {
                        $condition = "  (" . $alias . "status >> 17  | 0 = 0  and  (" . $alias . "status & 1023) | " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    } else {
                        $condition = " (" . $alias . "status >> 17  | 0 = 0  and  (" . $alias . "status & 1023) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    }
                    break;
                case 1:
                    if ($code_arr[1] == 0) {
                        $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17)  = " . $code_arr[0] . " and  (" . $alias . "status & 3843) | " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    } else {
                        $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17)  = " . $code_arr[0] . " and  (" . $alias . "status & 3843) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    }
                    break;
                case 3:
                    if ($code_arr[1] == 12288) {   //待交运的
                        $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 12288)>= 4096 and (" . $alias . "status & 12288) <= 12288" . ")";
                    } else {
                        $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 63548) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    }
                    break;
                case 7:
                    $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 511) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    break;
                case 15:
                    $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 3) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    break;
                case 16:
                    // $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 31) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 21) = 1 and " . $alias . "status != 4294967295" . ")";
                    break;
                case 65534:
                    $condition = " (" . $alias . "status != 0 and (" . $alias . "status >> 17) = " . $code_arr[0] . " and  (" . $alias . "status & 65535) & " . $code_arr[1] . " = " . $code_arr[1] . ")";
                    break;
                default:
                    $condition = "";
                    break;
            }
            return $condition;
        }
    }

    /**
     * @disabled
     * 判断包裹订单是否缺货
     * @param integer $status
     * @return bool
     */
    public function isStockOut($status)
    {
        return $status & OrderStatusConst::OrderLackStatus ? true : false;
    }

    /**
     * @disabled
     * @param integer $status
     * @return bool
     */
    public function isAllocationInventory($status)
    {
        return $status & OrderStatusConst::OrderAllocatiedStatus ? true : false;
    }

    /** 订单是否在申请退款
     * @disabled
     * @param integer $status
     * @return bool
     */
    public function isSalesAfter($status)
    {
        return ($status >> 20) & 1 ? true : false;
    }

    /** 判断是否为未审核通过的单
     * @disabled   【true  未审核  false 审核通过】
     * @param integer $status
     * @return bool
     */
    public function isWithoutTrial($status)
    {
        return ($status >> 17) == 0 ? true : false;
    }
}
