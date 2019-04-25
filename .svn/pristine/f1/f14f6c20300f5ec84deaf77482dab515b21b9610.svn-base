<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/6
 * Time: 16:26
 */

namespace app\common\model;


use erp\ErpModel;

class PurchaseParcelsReturn extends ErpModel
{
    /**
     * 一对一关系, 对异常表
     *
     * @return \think\model\relation\BelongsTo
     */
    public function abnormal()
    {
        return $this->belongsTo(PurchaseParcelsReceiveAbnormal::class);
    }
}