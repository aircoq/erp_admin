<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by tanbin.
 * Date: 2017/04/01
 * Time: 14:44
 */
class MsgTemplateGroup extends  Validate
{
    protected $rule = [
        ['template_type', 'require|number','模板类型不能为空！|模板类型ID为整形！'],
        ['channel_id', 'require|number','平台不能为空！|平台ID为整形！'],
        ['group_name', 'require|unique:MsgTemplateGroup,group_name', '分组名称不能为空！|分组名称已存在！'],
    ];
}

