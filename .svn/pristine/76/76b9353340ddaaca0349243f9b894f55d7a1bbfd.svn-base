<?php
namespace app\common\validate;

use \think\Validate;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class SupplierGoodsOffer extends Validate
{
    protected $rule = [
        ['supplier_id','require','供应商不能为空'],
        ['sku_id','require','sku不能为空'],
        ['goods_id','require','产品不能为空'],
        ['link','url','链接格式错误'],
    ];

    protected $scene = [
        'validate_link' => ['link'],
    ];
}