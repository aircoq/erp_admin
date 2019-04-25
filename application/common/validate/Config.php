<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class Config extends Validate
{

    protected $rule = [
        ['name', 'require|unique:Config,name', '名称不能为空！|名称已存在！'],
        ['title', 'require|unique:Config,title', '标题不能为空！|标题已存在！'],
    ];

}