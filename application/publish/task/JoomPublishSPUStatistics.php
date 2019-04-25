<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/10/11
 * Time: 9:07
 */

namespace app\publish\task;


use app\common\model\joom\JoomProduct;
use app\index\service\AbsTasker;
use think\Exception;

class JoomPublishSPUStatistics extends AbsTasker
{
    public function getName()
    {
        return 'Joom刊登SPU上下架统计';
    }

    public function getDesc()
    {
        return 'Joom刊登SPU上下架统计';
    }

    public function getCreator()
    {
        return 'wlw2533';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        try {
            $todayTime = strtotime(date('Y-m-d', time()));
            $wh['goods_id'] = ['neq', 0];
            $wh['date_uploaded'] = ['gt', $todayTime];
            $wh['enabled'] = 1;
            $field = 'goods_id,shop_id,create_id,count(goods_id) as spu_count';
            $group = 'goods_id,shop_id';
            //统计上架
            $products = JoomProduct::field($field)->where($wh)->group($group)->select();
            //调用接口
            foreach ($products as $product) {
                if (empty($product['shop_id']) || empty($product['create_id'])
                    || empty($product['goods_id']) || empty($product['spu_count'])) {
                    continue;
                }
                \app\report\service\StatisticShelf::addReportShelf(7,
                    $product['shop_id'],
                    $product['create_id'],
                    $product['goods_id'],
                    $product['spu_count']);
            }

            //统计下架的
            unset($wh['date_uploaded']);
            $wh['enabled'] = 0;
            $wh['manual_end_time'] = ['gt', $todayTime];
            $products = JoomProduct::field($field)->where($wh)->group($group)->select();

            //调用接口
            foreach ($products as $product) {
                if (empty($product['shop_id']) || empty($product['create_id'])
                    || empty($product['goods_id']) || empty($product['spu_count'])) {
                    continue;
                }
                \app\report\service\StatisticPicking::addReportPicking(7,
                    $product['account_id'],
                    $product['create_id'],
                    $product['goods_id'],
                    $product['spu_count']);
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

}