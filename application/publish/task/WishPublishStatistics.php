<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-8-6
 * Time: 上午9:13
 */

namespace app\publish\task;
use app\common\model\GoodsPublishMap;
use app\goods\service\GoodsPublishMapService;
use app\index\service\AbsTasker;
use think\Exception;
use app\goods\service\GoodsSku;

class WishPublishStatistics extends AbsTasker
{
    public function getName()
    {
        return 'wish刊登商品统计';
    }

    public function getDesc()
    {
        return 'wish刊登商品统计';
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
            $page = 1;
            $pageSize=50;
            $where=[
                'channel'=>['=',3]
            ];
            do{
                $rows = GoodsPublishMap::where($where)->page($page,$pageSize)->select();
                if(empty($rows)){
                    break;
                }else{
                    $this->update($rows);
                    $page = $page + 1;
                }
            }while(count($rows)==$pageSize);
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }
    private static function getSkus($goods_id){
        $skus = GoodsSku::getSkuByGoodsId($goods_id);
        return $skus;
    }
    private function update($rows){
        foreach ($rows as $row){
            $goods_id = $row->goods_id;
            $skus = self::getSkus($row->goods_id);
            $data = GoodsPublishMapService::wish($skus,$goods_id);
            $data['statistics']=json_encode($data['statistics']);
            GoodsPublishMap::where('id',$row->id)->update($data);
        }
    }
}