<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by phpstorm.
 * User: laiyongfeng
 * Date: 2017/11/13
 * Time: 11:37
 */
class MassZone extends  Validate
{
    protected $rule = [
        ['id', 'require|unique:massZone,id|/^[1-9]\d*$/','仓库Id不能为空！|仓库Id为数字！'],
        ['warehouse_id', 'require|number','仓库Id不能为空！|仓库Id为数字！'],
        ['start_num', 'require|/^[1-9]\d*$/','起始集结区号不能为空！|起始集结区号为数字！'],
        ['end_num', 'require|/^[1-9]\d*$/ ','结束集结区号不能为空！|结束集结区号为数字！'],
        ['status', 'require','状态不能为空'],
    ];

    protected $scene = [
        'add'  => ['warehouse_id', 'start_num', 'end_num'],
        'change_status'  => ['status'],
        'lists'  => ['warehouse_id'],
    ];
}