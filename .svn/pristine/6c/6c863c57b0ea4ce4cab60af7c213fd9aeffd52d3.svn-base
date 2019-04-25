<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/13
 * Time: 17:00
 */

namespace app\common\model;


use think\Model;

class PurchaseReturnOrder extends Model
{
    const STATUS = [
        'confirming_audit' => 0,
        'allowed_to_pass' => 1,
        'not_allowed_to_pass' => 2,
        'returned' => 3,
        'invalid' => 4,
    ];

    const STATUS_TEXT = [
        '待审核',
        '审核通过',
        '审核不通过',
        '已退款',
        '作废'
    ];

    protected $autoWriteTimestamp = true;

    protected $updateTime = false;

    /**
     * 获取退款单状态
     *
     * @param int $value
     * @return string
     */
    public function getStatusText(int $value = 0): string
    {
        return key_exists($value, self::STATUS_TEXT) ? self::STATUS_TEXT[$value] : '';
    }

    /**
     * 相对一对多
     *
     * @return \think\model\relation\BelongsTo
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * 一对多, 对退款详情
     *
     * @return \think\model\relation\HasMany
     */
    public function detail()
    {
        return $this->hasMany(PurchaseReturnOrderDetail::class, 'purchase_return_order_id');
    }

    /**
     * 一对多,对操作日志
     *
     * @return \think\model\relation\HasMany
     */
    public function log()
    {
        return $this->hasMany(PurchaseReturnOrderLog::class, 'purchase_return_order_id');
    }
}