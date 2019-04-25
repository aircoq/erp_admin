<?php


namespace app\common\validate;


use think\Validate;

class PackageCollectionException extends Validate
{
    protected $rule = [
        ['package_id', 'require', '包裹id不能为空'],
        ['warehouse_id', 'require', '仓库id不能为空'],
        ['number', 'require', '运单号不能为空'],
        ['exception_type', 'require', '异常类型不能为空'],
        ['creator_id', 'require', '记录人不能为空']
    ];
}