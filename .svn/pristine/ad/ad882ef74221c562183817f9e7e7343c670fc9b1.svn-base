<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/6/26
 * Time: 15:26
 */

namespace app\publish\validate;


use think\Validate;

class AliBrandValidate extends Validate
{
    protected $rule = [
        ['account_id','require|number','account_id不能为空|account_id必须为数字类型'],
        ['category_id','require|number','category_id不能为空|category_id必须为数字类型'],
        ['brands','require','brands不能为空'],
    ];

    protected $scene = [
        'barand'    =>  ['category_id','account_id'],
        'save'    =>  ['category_id','account_id','brands'],
        'group'=>['account_id'],
        'transport'=>['account_id'],
        'promise'=>['account_id'],
    ];
}