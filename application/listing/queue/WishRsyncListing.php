<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/25
 * Time: 15:47
 */

namespace app\listing\queue;


use app\common\exception\QueueException;

use app\common\model\wish\WishWaitUploadProductInfo;
use app\common\service\SwooleQueueJob;

use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductVariant;
use think\Db;
use app\listing\service\WishListingHelper;
use think\Exception;
use think\exception\PDOException;

class WishRsyncListing extends  SwooleQueueJob{
    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

	public function getName():string
	{
		return 'wish同步listing(队列)';
	}
	public function getDesc():string
	{
		return 'wish同步listing(队列)';
	}
	public function getAuthor():string
	{
		return 'joy';
	}

	public  function execute()
	{
        set_time_limit(0);
		try{
			$job = $this->params;
			if($job)
			{

				$jobInfo = (new  WishWaitUploadProduct())->with(['variants'])->field('id,accountid,product_id')->where(['product_id'=>$job])->find();

				if($jobInfo)
				{
					$jobInfo = is_object($jobInfo)?$jobInfo->toArray():$jobInfo;

					$variants = $jobInfo['variants'];
					$pid = $jobInfo['id'];

					$response = WishListingHelper::retrieveProduct($jobInfo['accountid'],$jobInfo['product_id']) ;

					if($response['state'])
					{
						$product = $response['data']['product'];
						$skus = $response['data']['skus'];
						$main_image = $product['main_image'];

                        Db::startTrans();
                        try{
                            (new  WishWaitUploadProduct)->allowField(true)->isUpdate(true)->save($product,['id'=>$pid]);
                            (new WishWaitUploadProductInfo)->allowField(true)->isUpdate(true)->save($product,['id'=>$pid]);
                            Db::commit();
                        }catch (PDOException $exp){
                            Db::rollback();
                            throw new Exception($exp->getMessage());
                        }

                        if($skus && $variants)
                        {
                            Db::startTrans();
                            try{
                                foreach($skus as $sku)
                                {
                                    if ($sku['enabled'] == 1) {
                                        $sku['status'] = 1;
                                        $sku['message'] = '';
                                        $sku['code'] = 0;
                                    }
                                    foreach ($variants as $k=>$variant)
                                    {
                                        if($variant['sku']==$sku['sku'])
                                        {
                                            if($k==0 && empty($variant['main_image']))
                                            {
                                                $sku['main_image']=$main_image;
                                            }
                                            WishWaitUploadProductVariant::where(['vid'=>$variant['vid']])->update($sku);
                                        }
                                    }
                                }
                                Db::commit();
                            }catch (PDOException $exp){
                                Db::rollback();
                                throw new Exception($exp->getMessage());
                            }
                        }

                        $update=(new WishListingHelper)->ProductStat((new  WishWaitUploadProductVariant()),['pid'=>$pid]);

                        if($update)
                        {
                            (new  WishWaitUploadProduct())->isUpdate(true)->allowField(true)->save($update, ['id'=>$pid]);
                        }
					}
				}
			}
		}catch (Exception $exp){
			throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
		}
	}

}