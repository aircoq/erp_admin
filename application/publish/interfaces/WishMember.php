<?php
namespace app\publish\interfaces;

use app\common\interfaces\MemberPublish;
use app\common\model\wish\WishWaitUploadProduct;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/6/8
 * Time: 10:46
 */
class WishMember implements MemberPublish
{
    public function filterSeller($account_id, $spu)
    {
        $where['accountid'] = $account_id;
        $where['parent_sku'] = $spu;
        $model = new WishWaitUploadProduct();
        $product = $model->field('product_id')->where($where)->find();
        if ($product) {
            return false;
        }
        return true;
    }
}