<?php
namespace app\common\service;

use app\common\model\GoodsSku;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/2/3
 * Time: 14:03
 */
class Goods
{
    /** 生成sku标签信息
     * @param $sku_id
     * @return array
     */
    public static function generateTag($sku_id)
    {
        $tag = [];
        $goodsModel = new GoodsSku();
        $goodsInfo = $goodsModel->where(['id' => $sku_id])->find();
        if (empty($goodsInfo)) {
            $tag['sku'] = $goodsInfo['sku'];
        }
        return $tag;
    }
}