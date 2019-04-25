<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by tanbin.
 * User: tb
 * Date: 2017/06/10
 * Time: 9:57
 */
class EbayAddress extends  Validate
{
    protected $rule = [
        ['name','require','退货地址 - 收货人不能为空'],
        ['country','require','退货地址 - 国家不能为空'],
        ['province','require','退货地址 - 省/州不能为空'],
        ['city','require','退货地址 - 城市不能为空'],
        ['street','require','退货地址 - 详细地址不能为空'],
        ['postal_code','require','退货地址 - 邮编不能为空'],
    ];
}