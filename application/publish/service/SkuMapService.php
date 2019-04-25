<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-3
 * Time: 下午2:38
 */

namespace app\publish\service;
use app\common\exception\QueueException;
use app\common\model\aliexpress\AliexpressProductSku;
use app\common\model\GoodsSkuAlias;
use app\goods\service\GoodsSkuMapService;
use app\common\model\GoodsSku;
use app\common\cache\Cache;
use think\Db;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\model\wish\WishWaitUploadProduct;
use app\goods\service\GoodsPublishMapService;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use app\common\model\aliexpress\AliexpressProduct;
/**
 * 平台sku与本地sku映射关系
 * Class SkuMapService
 * @package app\publish\service
 */
class SkuMapService
{
    public static function findSku(string $sku_code,$account_id,$channel_id=4)
    {
        if(empty($sku_code) || empty($account_id))
        {
            return 0;
        }

        //查询goods_sku_map

        if($sku_id=(new GoodsSkuMapService())->getSkuInfo($sku_code,$channel_id,$account_id))
        {
            return $sku_id;
        }

        //查询sku
        $sku = GoodsSku::where(['sku'=>$sku_code])->field('id')->find();
        if(!empty($sku)){
            return $sku['id'];
        }
        //查询sku别名
        $alias = GoodsSkuAlias::where(['alias' => $sku_code])->field('sku_id')->find();
        if(!empty($alias)){
            return $alias['sku_id'];
        }
        return 0;
    }

    public static function findSpu(int $skuId)
    {
        if(empty($skuId)){
            return false;
        }
        $goods_sku = GoodsSku::get($skuId);
        if(empty($goods_sku)){
            return false;
        }

        //$goods = Goods::get($goods_sku['goods_id']);
        $goods =Cache::store('Goods')->getGoodsInfo($goods_sku['goods_id']);

        if(empty($goods)){
            return false;
        }
        return [
            'goods_id'=>$goods['id'],
            'spu'=>$goods['spu']
        ];
    }

    /**
     * 更新平台sku与线上sku关系
     * @param $sku
     * @param $channel_id
     */
    public static function wishSkuMap($sku,$channel_id=3)
    {
        try{
            $product=WishWaitUploadProduct::where('id','=',$sku['pid'])->field('id,accountid')->find();
            if($product)
            {
                $account_id = $product['accountid'];
                $goods_sku_id =self::findSku($sku['sku'],$account_id,$channel_id);
                if(!empty($goods_sku_id))
                {
                    $goods = self::findSpu($goods_sku_id);
                    if($goods)
                    {
                        Db::startTrans();
                        try{
                            $data['goods_id']=$goods['goods_id'];
                            WishWaitUploadProductVariant::where('vid','=',$sku['vid'])->update(['sku_id'=>$goods_sku_id]);
                            WishWaitUploadProduct::where('id','=',$product['id'])->update($data);
                            Db::commit();
                        }catch (PDOException $exp){
                            Db::rollback();
                            throw new QueueException($exp->getMessage());
                        }catch (DbException $exp){
                            Db::rollback();
                            throw new QueueException($exp->getMessage());
                        }catch (\Exception $exp){
                            Db::rollback();
                            throw new QueueException($exp->getMessage());
                        }
                        //更新刊登状态
                        GoodsPublishMapService::update($channel_id,$goods['spu'],$account_id,1);
                    }
                }
            }
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }
    public static function getSkuCode($goods_sku_id){
        $skuInfo = Cache::store('Goods')->getSkuInfo($goods_sku_id);
        $skuCode = $skuInfo?$skuInfo['sku']:'';
        $skuStatus =$skuInfo?$skuInfo['status']:0;
        if (empty($skuCode)){
            $skuInfo = GoodsSku::where('id',$goods_sku_id)->field('sku')->find();
            $skuCode = $skuInfo?$skuInfo['sku']:'';
            $skuStatus =$skuInfo?$skuInfo['status']:0;
        }
        if (empty($skuCode)){
            $skuInfo = GoodsSkuAlias::where('sku_id',$goods_sku_id)->field('sku_code')->find();
            $skuCode = $skuInfo?$skuInfo['sku_code']:'';
        }
        return $skuCode;
    }
    public static function AliexpressSkuMap($sku,$channel_id=4)
    {
        $product=AliexpressProduct::where('id','=',$sku['ali_product_id'])->field('id,account_id')->find();

        if($product)
        {
            $account_id = $product['account_id'];
            $goods_sku_id =self::findSku($sku['sku_code'],$account_id,$channel_id);
            if(!empty($goods_sku_id))
            {
                $goods = self::findSpu($goods_sku_id);
                //$skuCode = self::getSkuCode($goods_sku_id);
                if($goods)
                {
                    Db::startTrans();
                    try{
                        $data=[
                            'goods_id'=>$goods['goods_id'],
                            'goods_spu'=>$goods['spu'],
                        ];
                        (new AliexpressProductSku())->isUpdate(true)->save(['goods_sku_id'=>$goods_sku_id],['id'=>$sku['id']]);
                        (new AliexpressProduct())->isUpdate(true)->save($data,['id'=>$product['id']]);
                        Db::commit();
                    }catch (PDOException $exp){
                        Db::rollback();
                        throw new QueueException($exp->getMessage());
                    }catch (DbException $exp){
                        Db::rollback();
                        throw new QueueException($exp->getMessage());
                    }catch (\Exception $exp){
                        Db::rollback();
                        throw new QueueException($exp->getMessage());
                    }
                    //更新刊登状态
                    GoodsPublishMapService::update(4,$goods['spu'],$account_id,1);
                }
            }
        }
    }
}