<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-15
 * Time: 下午2:30
 */

namespace app\publish\task;

use app\common\service\UniqueQueuer;
use app\goods\service\GoodsSkuMapService;
use app\common\model\GoodsSku;
use app\common\model\GoodsSkuAlias;
use app\common\exception\TaskException;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\model\aliexpress\AliexpressProductSku;
use app\goods\service\GoodsPublishMapService;
use app\index\service\AbsTasker;
use app\publish\queue\AliexpressSkuMapQueue;
use think\Db;
use app\common\cache\Cache;
use think\Exception;

class AliexpressSkuMap extends AbsTasker
{
    public function getName()
    {
        return "Aliexpress平台sku与系统sku关系绑定";
    }

    public function getDesc()
    {
        return "Aliexpress平台sku与系统sku关系绑定";
    }

    public function getCreator()
    {
        return "joy";
    }

    public function getParamRule()
    {
        return [];
    }


    public function execute()
    {
        set_time_limit(0);
        try {
            $page=1;
            $pageSize=30;
            $where=[
                'goods_sku_id'=>['=',0]
            ];
            do{
                $skus = (new AliexpressProductSku())->field('id,ali_product_id,sku_code')->where($where)->page($page,$pageSize)->select();
                if(empty($skus))
                {
                    break;
                }else{
                    $this->update($skus);
                    $page = $page + 1;
                }

            }while(count($skus)==$pageSize);
            
        }catch (Exception $exp){
            throw new TaskException($exp->getMessage());
        }
    }

    public function update($skus)
    {
        foreach ($skus as $sku)
        {
            $sku = is_object($sku)?$sku->toArray():$sku;
            (new UniqueQueuer(AliexpressSkuMapQueue::class))->push($sku);
        }
    }

}