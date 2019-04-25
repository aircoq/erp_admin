<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by phpstorm.
 * User: zhaixueli
 * Date: 2017/11/28
 * Time: 11:17
 */
class AllocationBoxClass extends  Validate
{

    protected $rule = [

        ['warehouse_id','require|number','仓库Id不能为空！|仓库Id为数字！'],
        ['length', 'require|number|max:3|gt:0', '箱子长度不能为空！|箱子长度为数字！|不超过3位的整数!|箱子长度必须大于0!'],
        ['width', 'require|number|max:3|gt:0', '箱子宽度不能为空！| 箱子宽度为数字！|不超过3位的整数!|箱子宽度必须大于0!'],
        ['height', 'require|number|max:3|gt:0', '箱子高度度不能为空！| 箱子高度为数字！|不超过3位的整数!|箱子高度必须大于0!'],
        ['status', 'require','状态不能为空'],
    ];


    protected $scene = [
        'add'  => ['warehouse_id', 'length','width', 'height'],
        'change_status' => ['status'],
        'edit'  => ['warehouse_id', 'length','width', 'height'],


    ];
}