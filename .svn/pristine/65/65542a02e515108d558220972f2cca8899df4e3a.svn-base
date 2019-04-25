<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/13
 * Time: 17:24
 */

namespace app\common\model;


use think\Model;

class PurchaseReturnOrderLog extends Model
{
    protected $autoWriteTimestamp = true;

    protected $updateTime = false;

    const STATUS = [
        '待审核',
        '审核通过',
        '审核未通过',
        '已退款',
        '出库',
        '作废'
    ];

    /**
     * 获取日志操作状态文本
     *
     * @param int $value
     * @return string
     */
    public function getStatusText(int $value): string
    {
        return key_exists($value, self::STATUS) ? self::STATUS[$value] : '';
    }
}