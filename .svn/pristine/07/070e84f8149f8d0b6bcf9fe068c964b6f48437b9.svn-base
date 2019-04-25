<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/8/8
 * Time: 15:19
 */

namespace app\publish\task;


use app\common\model\Goods;
use app\common\model\GoodsPublishMap;
use app\index\service\AbsTasker;
use think\Db;
use think\Exception;

class EbayPublishStatistics extends AbsTasker
{
    public function getName()
    {
        return 'eBay刊登商品统计';
    }

    public function getDesc()
    {
        return 'eBay刊登商品统计';
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
            //spu
            $sql = 'select id,goods_id,spu,count(spu) as count from ebay_listing where draft=0 and item_id != 0 and application = 1 group by spu';
            $listings = Db::query($sql);
            $listIds = [];
            $spuCount = [];
            $goodsIds = [];
            foreach ($listings as $listing) {
                $listIds[] = $listing['id'];
                $goodsIds[] = $listing['goods_id'];
                $spuCount[$listing['goods_id']] = $listing['count'];
            }
            $listIdStr = implode(',', $listIds);
            //sku
            $varSql = 'select goods_id,v_sku,count(v_sku) as count from ebay_listing_variation where listing_id in ('.$listIdStr.') group by v_sku';
            $vars = Db::query($varSql);
            $skuCount = [];
            foreach ($vars as $var) {
                $skuCount[$var['goods_id']][$var['v_sku']] = $var['count'];
            }
            //publish goods map
            $field = 'id,goods_id,spu';
            $map['channel'] = 1;
            $map['goods_id'] = ['in', $goodsIds];
            $goodsMaps = (new GoodsPublishMap())->field($field)->where($map)->select();

            //打包更新数据
            $update = [];
            $updateGoodsIds = [];
            $i = 0;
            foreach ($goodsMaps as $goodsMap) {
                $update[$i] = $goodsMap->toArray();
                $update[$i]['publish_count'] = $spuCount[$goodsMap['goods_id']];
                isset($skuCount[$goodsMap['goods_id']]) && $update[$i]['statistics'] = json_encode($skuCount[$goodsMap['goods_id']]);
                $updateGoodsIds[] = $goodsMap['goods_id'];
                $i++;
            }
            (new GoodsPublishMap())->saveAll($update);//批量更新
            $insertGoodsIds = array_diff($goodsIds, $updateGoodsIds);//eBay平台并没有很好的维护表GoodsPublishMap,有些不存在的goods,需要新增进去
            if (!empty($insertGoodsIds)) {
                sort($insertGoodsIds);
                $spus = Goods::where(['id' => ['in', $insertGoodsIds]])->order('id')->column('spu');
                $insert = [];
                $j = 0;
                foreach ($spus as $k => $spu) {
                    $insert[$j]['channel'] = 1;
                    $insert[$j]['goods_id'] = $insertGoodsIds[$k];
                    $insert[$j]['spu'] = $spu;
                    $insert[$j]['platform_sale'] = 2;
                    $insert[$j]['sale_status'] = 1;
                    isset($skuCount[$insertGoodsIds[$k]]) && $insert[$j]['statistics'] = json_encode($skuCount[$insertGoodsIds[$k]]);
                    $insert[$j]['publish_count'] = $spuCount[$insertGoodsIds[$k]];
                }
                (new GoodsPublishMap())->isUpdate(false)->saveAll($insert);//批量新增
            }
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }
}