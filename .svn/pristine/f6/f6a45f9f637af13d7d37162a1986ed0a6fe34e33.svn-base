<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by phpstorm.
 * User: laiyongfeng
 * Date: 2017/11/13
 * Time: 11:17
 */
class TurnOverBox extends  Validate
{
    protected $rule = [
        ['warehouse_id','require|number','仓库Id不能为空！|仓库Id为数字！'],
        ['type', 'require|number', '箱号类型不能为空！|箱号类型已经存在！'],
        ['color', 'require|max:5', '颜色不能为空！| 颜色最大长度为5！'],
        ['num', 'require|number', '新增数量不能为空！|数量为数字！'],
        ['status', 'require','状态不能为空'],
        ['code', 'require|unique:TurnOverBox,code^warehouse_id','箱号不能为空！| 箱号已存在！'],
        ['turnover_box_num', 'require|number','箱号不能为空！| 箱号为数字！'],
        ['ids', 'require', '周转箱ID不能为空！'],
    ];

    protected $scene = [
        'add'  => ['warehouse_id', 'type','color', 'num'],
        'change_status' => ['status'],
        'check_code' => ['warehouse_id', 'code'],
        'mass' => ['warehouse_id', 'turnover_box_num'],
        'print'  =>  ['ids'],
    ];
}