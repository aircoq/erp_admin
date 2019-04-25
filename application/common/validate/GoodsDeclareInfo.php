<?php
namespace app\common\validate;

use think\Validate;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/11/30
 * Time: 13:48
 */
class GoodsDeclareInfo extends Validate
{
    protected $rule = [
        ['sku_id', 'require|unique:GoodsDeclareInfo,sku_id', 'SKU ID 不能为空！| SKU已存在！'],
        ['sku', 'require', 'SKU 不能为空！'],
        ['thumb', 'require', 'SKU 图片不能为空！'],
        ['declare_price', 'require', '申报价不能为空！'],
        ['title', 'require', '标题不能为空！']
    ];

    protected $scene = [
        'edit' => ['thumb','declare_price','title']
    ];
}