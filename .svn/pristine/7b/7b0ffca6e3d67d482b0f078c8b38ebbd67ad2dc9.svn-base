<?php
namespace app\publish\validate;

use think\Validate;
class AmazonAtrributeValidate extends Validate
{

    protected $rule = [
        ['category_id','require|number','类目不能为空|类目必须为数字类型'],
        ['site','require','必须选择一个站点'],
    ];

    protected $scene = [
        'search'    =>  ['category_id','site'],

    ];

}