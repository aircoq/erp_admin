<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/25
 * Time: 15:31
 */

namespace app\listing\queue;

use app\common\service\SwooleQueueJob;
use app\publish\queue\WishQueue;
use app\common\model\wish\WishWaitUploadProduct;
use service\wish\WishApi;
use think\Exception;

class WishProductEnable extends  SwooleQueueJob{
	public function getName():string
	{
		return 'wish商品上架(队列)';
	}
	public function getDesc():string
	{
		return 'wish商品上架(队列)';
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
				self::enable($job);
			}
		}catch (QueueException $exp){
			throw new QueueException($exp->getMessage());
		}
	}
	private static function enable($pid='')
	{
		try{
			$info = WishWaitUploadProduct::get(['product_id'=>$pid],['variants','account']);
			if($info->product_id && $info->account->access_token)
			{
				$config['access_token']=$info->account->access_token;

				$api  = WishApi::instance($config)->loader('Product');

				$data['id'] = $info->product_id;

				$res = $api->enableProduct($data);

				if($res === true) //上架成功，将pid从redis集合中删除
				{
					(new WishQueue('wishBatchEnable'))->remove($pid);
				}
			}
		}catch (Exception $exp){
			throw new QueueException($exp->getMessage());
		}
	}
}