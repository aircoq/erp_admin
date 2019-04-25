<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/9/26
 * Time: 11:55
 */

namespace app\publish\task;


use app\common\model\GoodsPublishMap;
use app\common\model\joom\JoomProduct;
use app\index\service\AbsTasker;
use think\Exception;

class JoomPublishStatistics extends AbsTasker
{
    public function getName()
    {
        return 'joom刊登商品统计';
    }

    public function getDesc()
    {
        return 'joom刊登商品统计';
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
            $updateMap = [];
            //获取已刊登的商品信息
            $wh['goods_id'] = ['neq', 0];
            $wh['product_id'] = ['neq', ''];
            $joomField = 'goods_id,count(goods_id) as count';
            $goodsCount = JoomProduct::field($joomField)->where($wh)->order('goods_id')->group('goods_id')->select();
            $goodsIds = JoomProduct::distinct(true)->where($wh)->order('goods_id')->column('goods_id');
            $combineGoods = array_combine($goodsIds, $goodsCount);
            //根据商品信息获取刊登映射表里面的对应信息
            $map['goods_id'] = ['in', $goodsIds];
            $map['channel'] = 7;
            $mapField = 'id,goods_id,publish_count';
            $mapGoods = GoodsPublishMap::field($mapField)->where($map)->select();
            //打包需要更新的数据，以便后续批量更新
            foreach ($mapGoods as $k => $mapGood) {
                $updateMap[$k] = $mapGood->toArray();
                $updateMap[$k]['publish_count'] = $combineGoods[$mapGood['goods_id']]['count'];
            }
            (new GoodsPublishMap())->saveAll($updateMap);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

    }
}