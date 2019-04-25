<?php
namespace app\common\validate;

use think\Validate;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-14
 * Time: 20:28
 */
class PurchaseReturnManagement extends Validate
{
    protected $rule = [
        ['number','require|unique:PurchaseReturnManagement,number','退货单号不能为空！|退货单号已存在'],
    ];

}