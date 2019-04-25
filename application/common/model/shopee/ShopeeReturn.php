<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 2018/9/17
 * Time: 18:35
 */

namespace app\common\model\shopee;

use erp\ErpModel;

class ShopeeReturn extends ErpModel
{
    protected function initialize()
    {
        parent::initialize();
//        self::query('set names utf8mb4');
    }

    //退货处理状态码
    const STATUS_CODE_TODO = 1;
    const STATUS_CODE_DOING = 2;
    const STATUS_CODE_DONE = 3;
    const STATUS_CODE_TEXT = [
        self::STATUS_CODE_TODO=>'待处理',
        self::STATUS_CODE_DOING=>'处理中',
        self::STATUS_CODE_DONE=>'处理完'
    ];


    //退货状态
    const RETURN_STATUS_REQUESTED = 1;
    const RETURN_STATUS_ACCEPTED = 2;
    const RETURN_STATUS_CANCELLED = 3;
    const RETURN_STATUS_JUDGING = 4;
    const RETURN_STATUS_REFUND_PAID = 5;
    const RETURN_STATUS_CLOSED = 6;
    const RETURN_STATUS_PROCESSING = 7;
    const RETURN_STATUS_SELLER_DISPUTE = 8;
    const RETURN_STATUS_TEXT = [
        'REQUESTED'=>'申请中',
        'ACCEPTED'=>'已接受',
        'CANCELLED'=>'已取消',
        'JUDGING'=>'判定中',
        'REFUND_PAID'=>'已退款',
        'CLOSED'=>'已关闭',
        'PROCESSING'=>'处理中',
        'SELLER_DISPUTE'=>'争议中'
    ];

    //退货原因
    const RETURN_REASON_TEXT= [
        'NONE' => '无理由',
        'NOT_RECEIPT' => '未收到货',
        'WRONG_ITEM' => '商品错误',
        'ITEM_DAMAGED' => '商品损坏',
        'ITEM_MISSING' => '商品缺失',
        'ITEM_WRONGDAMAGED' => '商品错误和损坏',
        'ITEM_FAKE' => '假冒商品',
        'DIFFERENT_DESCRIPTION' => '描述差异',
        'EXPECTATION_FAILED' => '失望',
        'CHANGE_MIND' => '改变主意',
        'MUTUAL_AGREE' => '双方同意',
        'OTHER' => '其它'
    ];

    //退货争议原因
    const RETURN_DISPUTE_REASON_TEXT = [
        'NON_RECEIPT'=>'拒绝未收到货的索赔要求',
        'NOT_RECEIVED'=>'同意退货但未收到货',
        'OTHER'=>'其它',
        'UNKNOWN'=>'未知'
    ];


    public function getStatusTxtAttr($value, $data)
    {
        return self::RETURN_STATUS_TEXT[$data['status']];
    }

    public function getStatusCodeTxtAttr($value, $data)
    {
        return self::STATUS_CODE_TEXT[$data['status_code']];
    }

    public function getReasonTxtAttr($value, $data)
    {
        return self::RETURN_REASON_TEXT[$data['reason']];
    }

    public function getDisputeReasonTxtAttr($value, $data)
    {
        return self::RETURN_DISPUTE_REASON_TEXT[$data['dispute_reason']];
    }


    public function detail($field='*')
    {
        $result = self::hasMany(ShopeeReturnDetail::class,'returnsn','returnsn')
            ->field($field);
        return $result;
    }

    public function dispute($field='*')
    {
        $result = self::hasOne(ShopeeReturnDispute::class,'returnsn','returnsn')
            ->field($field);
        return $result;
    }

    public function log($field='*')
    {
        $result = self::hasMany(ShopeeReturnLog::class,'returnsn','returnsn')
            ->field($field);
        return $result;
    }


}