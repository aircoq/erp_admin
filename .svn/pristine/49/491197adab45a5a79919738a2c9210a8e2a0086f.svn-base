<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:57
 */
class Tag extends  Validate
{
    protected $rule = [
        ['name','require|unique:Tag,name','标签名称不能为空！|标签名称已存在！'],
    ];
}