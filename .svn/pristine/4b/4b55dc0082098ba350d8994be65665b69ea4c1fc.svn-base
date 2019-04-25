<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/25
 * Time: 14:06
 */

namespace app\listing\queue;
use app\common\exception\QueueException;
use app\common\exception\TaskException;
use app\common\model\wish\WishWaitUploadProductInfo;
use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\service\SwooleQueueJob;
use app\publish\queue\WishQueue;
use app\common\model\wish\WishWaitUploadProduct;
use think\Db;
use service\wish\WishApi;
class WishUpdateListing extends  SwooleQueueJob{
	public function getName():string
	{
		return 'wish更新listing(队列)';
	}
	public function getDesc():string
	{
		return 'wish更新listing(队列)';
	}
	public function getAuthor():string
	{
		return 'joy';
	}

	public  function execute()
	{

		try{
			$job=$this->params;
			if($job)
			{
				$jobInfo = WishWaitUploadProduct::field('accountid,product_id,lock_update,lock_product')->with(['account'])->where(['product_id'=>$job])->find();
				if($jobInfo)
				{
					$jobInfo = is_object($jobInfo)?$jobInfo->toArray():$jobInfo;

					if($jobInfo['lock_update']==1  ) //更新了资料，且更新了商品信息
					{
						$access_token = $jobInfo['account']['access_token'];

						$api = WishApi::instance(['access_token'=>$access_token])->loader("Product");

						$skus = WishWaitUploadProductVariant::where(['product_id'=>$job,'lock_variant'=>1])->field('variant_id,sku,inventory,price,shipping,enabled,size,color,msrp,shipping_time,main_image')->select();

						if($skus)
						{
							self::updateVariantData($api,$skus,$access_token,$job);
						}

						if($jobInfo['lock_product']==1)
						{
							$product = (new WishWaitUploadProduct)->alias('a')->join('wish_wait_upload_product_info b','a.id=b.id')->where('a.product_id','=',$job)->field('product_id id,name,description,tags,brand,upc,main_image,original_images extra_images')->find();

							self::updateProductData($api,$product,$access_token);
						}
					}
				}
			}
		}catch (QueueException $exp){
			throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
		}
	}
	/**
	 * 更新在线listing商品数据
	 * @param type $api
	 * @param type $product
	 * @param type $access_token
	 */
	private static function updateProductData($api,$product,$access_token)
	{
		set_time_limit(0);
		try{
			if($api && $product && $access_token)
			{
				$product['access_token'] = $access_token;

				$response = $api->updateProduct($product);
				if($response['state']==true)
				{
					$update['lock_product']=0;
					$update['lock_update']=0;
					$update['update_message']=$response['message'];
				}else{
					$update['update_message']=$response['message'];
				}
                Db::startTrans();
				try{
                    (new WishWaitUploadProduct())->allowField(true)->isUpdate(true)->save($update,['product_id'=>$product['id']]);
                    Db::commit();
                }catch(\Exception $exp){
				    Db::rollback();
					throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
				}
			}
		}catch (QueueException $exp)
		{
			throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
		}
	}
	/**
	 * 更新sku数据
	 * @param type $api
	 * @param type $skus
	 * @param type $access_token
	 * @param type $product_id
	 */
	private static function updateVariantData($api,$skus,$access_token,$product_id)
	{
		set_time_limit(0);
		try{
			if(is_array($skus))
			{
				foreach ($skus as $k=> $sku)
				{
					$sku['access_token'] = $access_token;

					$sku['enabled']  = $sku['enabled']=='Enabled'?'True':'False';

					$response = $api->updateVariation($sku);
					if($response['state']==true)
					{
						$update['lock_variant']=0;
						$update['update_msg']=$response['message'];
					}else{
						$update['update_msg']=$response['message'];
					}
					Db::startTrans();
 					try{
						WishWaitUploadProductVariant::where('variant_id','=',$sku['variant_id'])->update($update);
						Db::commit();
 					}catch(\Exception $exp){
 					    Db::rollback();
 						throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
					}

				}

				$variant =WishWaitUploadProductVariant::where(['product_id'=>$product_id,'lock_variant'=>1])->find();

				if($variant)
				{
					$updateP=['lock_update'=>1];
				}else{
					$updateP=['lock_update'=>0];
				}
				Db::startTrans();
				try{
					WishWaitUploadProduct::where('product_id','=',$product_id)->update($updateP);
					Db::commit();
				}catch(\Exception $exp){
				    Db::rollback();
					throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
				}
			}
		}catch (QueueException $exp)
		{
			throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
		}
	}

}