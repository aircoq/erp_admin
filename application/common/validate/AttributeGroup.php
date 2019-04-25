<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class AttributeGroup extends  Validate
{
    protected $rule = [
        ['name','require|unique:AttributeGroup,name^category_id','属性分组名不能为空！|属性分组名已存在！']
    ];
}
