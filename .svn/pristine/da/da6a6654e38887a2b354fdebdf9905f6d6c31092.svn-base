<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-7-24
 * Time: 上午10:14
 */

namespace app\publish\task;


use app\common\model\GoodsPublishMap;
use app\goods\service\GoodsPublishMapService;
use app\index\service\AbsTasker;
use think\Exception;
use app\goods\service\GoodsSku;
class PublishStatistics extends AbsTasker
{

    public function getName()
    {
        return '刊登商品统计';
    }

    public function getDesc()
    {
        return '刊登商品统计';
    }

    public function getCreator()
    {
        return 'joy';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $channels = [
                3 => 'app\common\model\wish\WishWaitUploadProduct',
                7 => 'app\common\model\joom\JoomProduct',
                8 => 'app\common\model\pandao\PandaoProduct',
            ];
            foreach ($channels as $k => $channel) {
                $updateMap = [];
                //获取已刊登的商品信息
                $wh['goods_id'] = ['neq', 0];
                $wh['product_id'] = ['neq', ''];
                $publishField = 'goods_id,count(goods_id) as count';
                $goodsCount = $channel::field($publishField)->where($wh)->order('goods_id')->group('goods_id')->select();
                $goodsIds = $channel::distinct(true)->where($wh)->order('goods_id')->column('goods_id');
                $combineGoods = array_combine($goodsIds, $goodsCount);
                unset($goodsCount);
                //根据商品信息获取刊登映射表里面的对应信息
                $map['goods_id'] = ['in', $goodsIds];
                $map['channel'] = $k;
                $mapField = 'id,goods_id,publish_count';
                $mapGoods = GoodsPublishMap::field($mapField)->where($map)->select();
                unset($goodsIds);
                //打包需要更新的数据，以便后续批量更新
                foreach ($mapGoods as $key => $mapGood) {
                    $updateMap[$key] = $mapGood->toArray();
                    $updateMap[$key]['publish_count'] = $combineGoods[$mapGood['goods_id']]['count'];
                }
                unset($mapGoods);
                unset($combineGoods);
//                (new GoodsPublishMap())->saveAll($updateMap);


            }

//            $page = 1;
//            $pageSize=50;
//            $where=[
//                'channel'=>['IN',[6,7,8]]
//            ];
//            do{
//                $rows = GoodsPublishMap::where($where)->page($page,$pageSize)->select();
//                if(empty($rows)){
//                    break;
//                }else{
//                    $this->update($rows);
//                    $page = $page + 1;
//                }
//            }while(count($rows)==$pageSize);
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }
//    private static function getSkus($goods_id){
//        $skus = GoodsSku::getSkuByGoodsId($goods_id);
//        return $skus;
//    }
//    private function update($rows){
//        foreach ($rows as $row){
//            $channel_id = $row->channel;
//            $goods_id = $row->goods_id;
//            $skus = self::getSkus($row->goods_id);
//            $data = [];
//            switch ($channel_id){
//                case 1:
//                    $data = GoodsPublishMapService::ebay($skus,$goods_id);
//                    break;
//                case 2:
//                    $data = GoodsPublishMapService::amazon($skus,$goods_id);
//                    break;
//                case 3:
//                    $data = GoodsPublishMapService::wish($skus,$goods_id);
//                    break;
//                case 4:
//                    $data = GoodsPublishMapService::aliexpress($skus,$goods_id);
//                    break;
//                case 5:
//                    break;
//                case 6:
//                    break;
//                case 7:
//                    $data = GoodsPublishMapService::joom($skus,$goods_id);
//                    break;
//                case 8:
//                    $data = GoodsPublishMapService::pandao($skus,$goods_id);
//                    break;
//                case 9:
//                    //$data = GoodsPublishMapService::shopee($skus);
//                    break;
//                case 10:
//                    break;
//                default:
//                    break;
//            }
//            $data['statistics']=json_encode($data['statistics']);
//            GoodsPublishMap::where('id',$row->id)->update($data);
//        }
//    }
}