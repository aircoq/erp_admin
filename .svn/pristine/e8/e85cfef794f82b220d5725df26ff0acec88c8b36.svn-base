<?php
namespace app\common\validate;

use \think\Validate;

/**
 * 节点验证类
 * @author oldrind <oldrind@163.com>
 */
class Node extends Validate
{
    protected $rule = [
        ['name', ['require', 'unique:Node,name^pid'], '节点名不能为空！|同级节点下该名称已经存在'],
        ['title', 'require', '标题不能为空！'],
        ['pid', 'require', '所属上级节点没有填写！'],
        ['level', 'in:0,1,2,3,4', 'level等级错误，只能是0，1，2，3，4！'],
    ];
}

