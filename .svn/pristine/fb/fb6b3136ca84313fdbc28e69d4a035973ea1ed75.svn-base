<?php
namespace app\report\validate;

use think\Validate;

class FileExportValidate extends Validate
{
    protected $rule = [
        ['apply_id', 'require', '导出申请id获取失败！'],
        ['file_name', 'require', '导出文件名未设置！'],
    ];

    protected $scene = [
        'export'  => ['apply_id', 'file_name'],
    ];
}