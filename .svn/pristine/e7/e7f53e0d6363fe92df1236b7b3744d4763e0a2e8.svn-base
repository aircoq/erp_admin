<?php


namespace app\common\validate;

use \think\Validate;

class GoodsLang extends Validate
{
    protected $rule = [
        'title' => 'require',
        'description' => 'require'
    ];
    protected $message = [
        'title.require' => '标题不能为空',
        'description.require' => '描述不能为空',
        'declare_name.require' => '报关名称不能为空',
        'tags.require' => '标签不能为空',
        'selling_point.require' => '卖点描述不能为空',
    ];


    protected $scene = [
        'dev_insert' => [
            'title' => 'require',
            'description' => 'require',
            'declare_name' => 'require'
        ]
    ];


}