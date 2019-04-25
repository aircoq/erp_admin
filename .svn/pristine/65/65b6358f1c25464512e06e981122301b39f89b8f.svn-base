<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/3
 * Time: 14:20
 */
class DeveloperTeam extends Validate
{
    protected $rule = [
        ['name', 'require|unique:DeveloperTeam,name', '分组名称不能为空！|分组名称已存在！'],
        ['category_id', 'require', '类别必选！'],
        ['developer_id', 'require', '开发员必选！'],
    ];

    protected $scene = [
        'edit' => ['name'],
    ];
}