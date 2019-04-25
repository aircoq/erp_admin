<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2018/10/10
 * Time: 20:46
 */

namespace app\publish\task;


use app\common\model\shopee\ShopeeProduct;
use app\common\model\shopee\ShopeeVariant;
use app\index\service\AbsTasker;
use think\Exception;

class ShopeePublishSPUStatistics extends AbsTasker
{
    public function getName()
    {
        return 'shopee刊登SPU上下架统计';
    }

    public function getDesc()
    {
        return 'shopee刊登SPU上下架统计';
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

            $todayTime = strtotime(date('Y-m-d', time())) - 1;
            $yesterday = $todayTime + 1 - 86400;
            $wh['publish_create_time'] = ['BETWEEN', [$yesterday, $todayTime]];

            //$todayTime = strtotime(date('Y-m-d', time()));
            $wh['goods_id'] = ['neq', 0];
            //$wh['publish_create_time'] = ['gt', $todayTime];
            $wh['status'] = 1;
            $field = 'goods_id,account_id,create_id,count(goods_id) as spu_count,publish_create_time';
            $group = 'goods_id,account_id';
            //统计上架
            $products = ShopeeProduct::field($field)->where($wh)->group($group)->select();

            //调用接口
            foreach ($products as $product) {
                if (empty($product['account_id']) || empty($product['create_id'])
                    || empty($product['goods_id']) || empty($product['spu_count'])) {
                    continue;
                }
                \app\report\service\StatisticShelf::addReportShelf(9,
                    $product['account_id'],
                    $product['create_id'],
                    $product['goods_id'],
                    $product['spu_count'],
                    0,
                    $product['publish_create_time']
                    );
            }

            //统计下架的
            unset($wh['publish_create_time']);
            $wh['status'] = 2;
            $wh['manual_end_time'] = ['gt', $todayTime];
            $products = ShopeeProduct::field($field)->where($wh)->group($group)->select();
            foreach ($products as $product) {
                if (empty($product['account_id']) || empty($product['create_id'])
                    || empty($product['goods_id']) || empty($product['spu_count'])) {
                    continue;
                }
                \app\report\service\StatisticPicking::addReportPicking(9,
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