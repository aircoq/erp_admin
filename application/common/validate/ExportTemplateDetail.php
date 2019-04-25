<?php


namespace app\common\validate;


use think\Validate;

class ExportTemplateDetail extends Validate
{
    protected $rule = [
        ['export_template_id','require','模版id不能为空！'],
        ['show_field','require','字段展示名称不能为空'],
        ['field','require','字段不能为空']
    ];
}