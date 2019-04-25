<?php
namespace app\common\validate;

use \think\Validate;
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:57
 */
class PurchaseProposal extends  Validate
{
    protected $rule = [
        
        ['sku_id','require:PurchaseProposal,sku_id','sku不能为空！'],
        
    ];
}



?>