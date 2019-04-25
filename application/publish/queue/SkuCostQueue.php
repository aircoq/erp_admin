<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-8-21
 * Time: 上午10:44
 */

namespace app\publish\queue;


use app\common\model\aliexpress\AliexpressProductSku;
use app\common\model\amazon\AmazonListing;
use app\common\model\amazon\AmazonPublishProductDetail;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\service\CommonQueuer;
use app\common\service\SwooleQueueJob;
use think\Exception;

class SkuCostQueue extends SwooleQueueJob
{

    public function getName(): string
    {
        return '刊登sku成本回写';
    }

    public function getDesc(): string
    {
        return '刊登sku成本回写';
    }

    public function getAuthor(): string
    {
        return 'php';
    }

    public function execute()
    {
        try{
            $params = $this->params;
            if($params){
                $sku_id = $params['sku_id'];
                $cost_price = $params['sku_cost'];
                $sku_pre_cost = $params['sku_pre_cost'];
                (new CommonQueuer(EbayAdjustCostPrice::class))->push($params);
                $this->updateSkuCostPrice($sku_id,$cost_price,$sku_pre_cost);
            }
        }catch (Exception $exp){
            throw $exp;
        }
    }
    private function updateSkuCostPrice($sku_id,$cost_price,$sku_pre_cost){
        $data=[
            'current_cost'=>$cost_price,
            'pre_cost'=>$sku_pre_cost,
        ];
        WishWaitUploadProductVariant::where('sku_id',$sku_id)->update($data);
        AliexpressProductSku::where('goods_sku_id',$sku_id)->update($data);
        //amazon刊登详情表；
        AmazonPublishProductDetail::where('sku_id',$sku_id)->update($data);
        AmazonListing::where('sku_id',$sku_id)->update($data);
    }
}