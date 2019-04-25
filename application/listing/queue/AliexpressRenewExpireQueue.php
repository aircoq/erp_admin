<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/30
 * Time: 14:27
 */

namespace app\listing\queue;
use app\common\service\SwooleQueueJob;
use app\listing\service\AliexpressListingHelper;
use app\publish\queue\WishQueue;
use think\Exception;
use app\common\exception\QueueException;
class AliexpressRenewExpireQueue extends  SwooleQueueJob{
	public function getName():string
	{
		return '速卖通延长商品有效期(队列)';
	}
	public function getDesc():string
	{
		return '速卖通延长商品有效期(队列)';
	}
	public function getAuthor():string
	{
		return 'joy';
	}

	public  function execute()
	{
		 try{
			$queue = $this->params;
			if($queue)
			{
				(new AliexpressListingHelper)->renewExpire($queue);
			}
		 }catch (QueueException $exp){
			 throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
		 }
	}
}