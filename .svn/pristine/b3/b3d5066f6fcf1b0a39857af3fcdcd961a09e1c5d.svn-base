<?php

namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class VirtualSupplier extends Model
{

    /** 获取供应商等级信息
     * @return array
     */
    public function getLevel()
    {
        $level = [
            0 => [
                'label' => 1,
                'name' => '一等供应商'
            ],
            1 => [
                'label' => 2,
                'name' => '二等供应商'
            ],
            2 => [
                'label' => 3,
                'name' => '三等供应商'
            ],
        ];
        return $level;
    }

    /** 获取供应商支付方式
     * @return array
     */
    public function getPayment()
    {
        $payment = [
            0 => [
                'label' => 1,
                'name' => '现金付款'
            ],
            1 => [
                'label' => 2,
                'name' => '银行转账(个人)'
            ],
            2 => [
                'label' => 3,
                'name' => 'PayPal'
            ],
            3 => [
                'label' => 4,
                'name' => '支付宝支付'
            ],
            4 => [
                'label' => 5,
                'name' => '银行转账(公司)'
            ],
        ];
        return $payment;
    }

    /** 结算方式
     * @return array
     */
    public function getBalance()
    {
        $balance = [
            0 => ['label' => 3, 'name' => '现金'],
            1 => ['label' => 4, 'name' => '货到付款'],
            2 => ['label' => 5, 'name' => '定期结算-周结'],
            3 => ['label' => 6, 'name' => '定期结算-半月结'],
            4 => ['label' => 7, 'name' => '定期结算-30天'],
            5 => ['label' => 8, 'name' => '定期结算-60天'],
            6 => ['label' => 9, 'name' => '定期结算-90天'],
        ];
        return $balance;
    }

    /** 发票类型
     * @return array
     */
    public function getInvoice()
    {
        $invoice = [
            [
                'label' => 1,
                'name' => '17%增值税专用发票'
            ],
            [
                'label' => 5,
                'name' => '17%的增值税普通发票'
            ],
            [
                'label' => 2,
                'name' => '3%增值税普通发票'
            ],
            [
                'label' => 3,
                'name' => '3%普通发票'
            ],
            [
                'label' => 6,
                'name' => '13%的增值税普通发票'
            ],
            [
                'label' => 9,
                'name' => '13%的增值税专用发票'
            ],
            [
                'label' => 8,
                'name' => '不能开票'
            ],
            [
                'label' => 4,
                'name' => '无税'
            ],
            [
                'label' => 7,
                'name' => '其他'
            ],
        ];
        return $invoice;
    }

    /**
     * 获取供应商类型
     * 0-企业（有限责任公司） 1-个人（个人工商户）2-股份有限公司 3-一人有限责任公司 4-个人独资企业 5-自然人独资
     * @return array
     */
    public function getType()
    {
        $types = [
            [
                'label' => 0,
                'name' => '有限责任公司'
            ],
            [
                'label' => 1,
                'name' => '个人工商户'
            ],
            [
                'label' => 2,
                'name' => '股份有限公司'
            ],
            [
                'label' => 3,
                'name' => '一人有限责任公司'
            ],
            [
                'label' => 4,
                'name' => '个人独资企业'
            ],
            [
                'label' => 5,
                'name' => '自然人独资'
            ],
        ];
        return $types;
    }

    /**
     * 1-线上交易 2-线下-款到发货
     * @return array
     */
    public function getTransactionType()
    {
        $types = [
            [
                'label' => 1,
                'name' => '线上交易'
            ],
            [
                'label' => 2,
                'name' => '线下交易'
            ],
        ];
        return $types;
    }
}
