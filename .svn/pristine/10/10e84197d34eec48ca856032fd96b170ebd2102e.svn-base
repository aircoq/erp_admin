<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: zhaixueli
 * Date: 2019/3/4
 * Time: 9:57
 */
class AliexpressAddress extends  Validate
{
    protected $rule = [
        ['country','require','国家不能为空！'],
        ['province','require','省份不能为空！'],
        ['city','require','城市不能为空！'],
        ['name','require','联系人不能为空！'],
        ['type','require','批量设置类型不能能为空！']
    ];
}