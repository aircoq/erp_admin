<?php
/**
 * Created by PhpStorm.
 * User: Tom
 * Date: 2017/6/8
 * Time: 11:17
 */

namespace app\publish\interfaces;


use app\common\interfaces\MemberPublish;
use app\common\model\aliexpress\AliexpressProduct;

class AliexpressMember implements MemberPublish
{
    public function filterSeller($account_id, $spu)
    {
        $where = [
            'account_id'=>$account_id,
            'goods_spu'=>$spu
        ];
        $product = AliexpressProduct::where($where)->field('id')->find();
        return empty($product)?true:false;
    }
}