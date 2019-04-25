<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2018/10/11
 * Time: 14:00
 */

namespace app\publish\task;


use app\common\model\pandao\PandaoProduct;
use app\index\service\AbsTasker;
use think\Exception;

class PandaoPublishSPUStatistics extends AbsTasker
{
    public function getName()
    {
        return 'Pandao刊登SPU上下架统计';
    }

    public function getDesc()
    {
        return 'Pandao刊登SPU上下架统计';
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
            $wh['date_uploaded'] = ['BETWEEN', [$yesterday, $todayTime]];

            //$todayTime = strtotime(date('Y-m-d', time()));
            $wh['goods_id'] = ['neq', 0];
            //$wh['date_uploaded'] = ['gt', $todayTime];
            $wh['publish_status'] = 1;
            $field = 'goods_id,account_id,create_id,count(goods_id) as spu_count,date_uploaded';
            $group = 'goods_id,account_id';
            //统计上架
            $products = PandaoProduct::field($field)->where($wh)->group($group)->select();

            //调用接口
            foreach ($products as $product) {
                if (empty($product['account_id']) || empty($product['create_id'])
                    || empty($product['goods_id']) || empty($product['spu_count'])) {
                    continue;
                }
                \app\report\service\StatisticShelf::addReportShelf(8,
                    $product['account_id'],
                    $product['create_id'],
                    $product['goods_id'],
                    $product['spu_count'],
                    0,
                    $product['date_uploaded']
                    );
            }

            //统计下架的
            unset($wh['date_uploaded']);
//            $wh['enabled'] = 0;
            $wh['manual_end_time'] = ['gt', $todayTime];
            $products = PandaoProduct::field($field)->where($wh)->group($group)->select();
            //调用接口
            foreach ($products as $product) {
                if (empty($product['account_id']) || empty($product['create_id'])
                    || empty($product['goods_id']) || empty($product['spu_count'])) {
                    continue;
                }
                \app\report\service\StatisticPicking::addReportPicking(8,
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