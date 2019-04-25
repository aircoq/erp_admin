<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2018/09/05
 * Time: 9:13
 */
class WishCarrier extends Validate
{

    protected $rule = [
        ['code','require','物流代码不能为空！'],
        ['name','require|unique:Carrier,fullname','物流名称不能为空！|物流名称称已存在！'],
    ];

}