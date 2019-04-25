<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by tanbin.
 * Date: 2017/03/31
 * Time: 17:03
 */
class MsgTemplate extends  Validate
{
    protected $rule = [
        ['channel_id', 'require|number','平台不能为空！|平台ID为整形！'],
        ['template_name', 'require|unique:MsgTemplate,template_name^channel_id^template_type', '模板名称不能为空！|模板名称已存在！'],
        ['template_no', 'require|unique:MsgTemplate,template_no', '模板编号不能为空！|模板编号已存在！'],
        ['template_type', 'require|number', '模板类型不能为空!|模板类型ID为整形'],      
        ['template_content', 'require', '模板内容不能为空!'],
        ['template_group_id', 'require|number','模板分组不能为空！|模板分组ID为整形！'],
    ];
}

