<?php


namespace app\common\validate;


use think\Validate;

class PackageCollection extends Validate
{
    protected $rule = [
        ['code', 'require|unique:PackageCollection,code', '集包单号不能为空！|集包单号已存在！'],
        ['warehouse_id', 'require', '仓库不能为空！'],
        ['shipping_method_id', 'require', '邮寄方式不能为空！'],
        ['collector_id', 'require', '揽收商不能为空！'],
    ];

}