<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by phpstorm
 * User: laiyongfeng
 * Date: 2017/11/06
 * Time: 10:56
 */
class SortingShelf extends  Validate
{
    protected $rule = [
        ['warehouse_id', 'require|number','仓库不能为空！|仓库为整形！'],
        ['name', 'require|unique:SortingShelf,name^warehouse_id|max:20', '播种车名称不能为空！|仓库播种车名称已经存在！|播种车名称最大长度为20！'],
        ['status', 'require|number|between:0,1', '状态不能为空！|状态必须必须是数字！|状态只能取0或1！'],
        ['row_column', 'require', '播种货架不能为空！'],
        ['is_default', 'require|between:0,1', '是否默认播种车不能为空！|是否默认播种车只能取0或1！'],
        ['is_sure', 'require', '是否确定设为默认播种车不能为空！'],
    ];

    protected $scene = [
        'add'  => ['warehouse_id', 'name','status','is_default','is_sure', 'row_column'],
        'change_status'  => ['status'],
        'lists'  => ['warehouse_id'],
    ];
}

