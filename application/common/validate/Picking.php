<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/11/10
 * Time: 10:24
 */
class Picking extends  Validate
{
    protected $rule = [
        ['warehouse_id','require','仓库不能为空！'],
        ['type','number','拣货单类型必须为数字'],
        ['status','number','状态必须为数字'],
    ];
}