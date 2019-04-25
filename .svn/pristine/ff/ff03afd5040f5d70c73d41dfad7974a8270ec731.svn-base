<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/25
 * Time: 15:35
 */

namespace app\listing\queue;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\publish\queue\WishQueue;
use app\common\model\wish\WishWaitUploadProduct;
use service\wish\WishApi;
use think\Exception;

class WishProductDisable extends  SwooleQueueJob{
	public function getName():string
	{
		return 'wish商品下架(队列)';
	}
	public function getDesc():string
	{
		return 'wish商品下架(队列)';
	}
	public function getAuthor():string
	{
		return 'joy';
	}

	public  function execute()
	{
		set_time_limit(0);
		try{
			$job= $this->params;
			if($job)
			{
				self::disable($job);
			}
		}catch (QueueException $exp){
			throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
		}
	}
	private static function disable($pid='')
	{
		try{
			$info = WishWaitUploadProduct::get(['product_id'=>$pid],['variants','account']);

			if($info->product_id && $info->account->access_token)
			{
				$config['access_token']=$info->account->access_token;

				$api  = WishApi::instance($config)->loader('Product');

				$data['id'] = $info->product_id;

				$res = $api->disableProduct($data);
				if($res === true) //下架成功，将pid从redis集合中删除
				{
					(new WishQueue('wishBatchDisable'))->remove($pid);
				}
			}
		}catch (Exception $exp){
			throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
		}
	}
}