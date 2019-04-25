<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/24
 * Time: 9:31
 */

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\common\model\aliexpress\AliexpressProduct;
use app\goods\service\GoodsSkuMapService;
use app\common\cache\Cache;
use app\common\exception\JsonErrorException;

class AliexpressPublishQueue extends SwooleQueueJob {
    protected static $priority=self::PRIORITY_HEIGHT;

    protected $failExpire = 600;

    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

	public function getName():string
	{
		return '速卖通新增刊登队列';
	}
	public function getDesc():string
	{
		return '速卖通新增刊登队列';
	}
	public function getAuthor():string
	{
		return 'hao';
	}

	public  function execute()
	{
		set_time_limit(0);
		try{
			$id = $this->params;
			if(!$id) {
                return;
            }

            $key = 'aliexpress_publish_add';
            if(!Cache::handler()->hExists($key, $id)) {
                return;
            }

            $result = Cache::handler()->hGet($key, $id);
            if(!$result) {
                return;
            }

            $result = \GuzzleHttp\json_decode($result, true);

            if(isset($result['arrProductData']) && isset($result['arrProductInfoData']) && isset($result['arrProductSkuData'])) {
                //删除缓存
                Cache::handler()->hDel($key, $id);
            }

            $arrProductData = $result['arrProductData'];
            $arrProductInfoData = $result['arrProductInfoData'];
            $arrProductSkuData = $result['arrProductSkuData'];

            $goodsSkuMapService = new GoodsSkuMapService;


            if($arrProductSkuData){
                foreach($arrProductSkuData as &$productSku)
                {
                    $createRandSkuArray=[
                        'sku_code'=>$productSku['sku_code'] && strlen($productSku['sku_code']) > 9 ? mb_substr($productSku['combine_sku'], 0,9) : $productSku['sku_code'],
                        'channel_id'=>4,
                        'account_id'=>$arrProductData['account_id']
                    ];

                    if(isset($productSku['combine_sku']) && !empty($productSku['combine_sku']))
                    {
                        $createRandSkuArray['combine_sku'] = $productSku['combine_sku'];
                        $newSku = $goodsSkuMapService->addSkuCodeWithQuantity($createRandSkuArray,$arrProductData['publisher_id']);
                    }else{
                        $newSku = $goodsSkuMapService->addSku($createRandSkuArray,$arrProductData['publisher_id']);
                    }

                    if(!$newSku['result']){
                        throw new JsonErrorException('生成平台SKU失败：'.$newSku['message']);
                    }

                    $productSku['sku_code'] = $newSku['sku_code'];
                }
            }


            $productModel = new AliexpressProduct();
            $productModel->addProduct($arrProductData,$arrProductInfoData,$arrProductSkuData);

            return true;
		}catch (Exception $exp){
			throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
		}catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
	}
}