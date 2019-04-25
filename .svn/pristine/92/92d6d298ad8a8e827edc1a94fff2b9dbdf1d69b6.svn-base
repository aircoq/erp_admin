<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/30
 * Time: 14:28
 */

namespace app\listing\queue;

use app\common\exception\TaskException;
use app\common\service\SwooleQueueJob;
use app\listing\service\AliexpressListingHelper;
use app\publish\queue\WishQueue;
use think\Db;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\exception\QueueException;
class AliexpressRsyncProductQueue extends  SwooleQueueJob{
	public function getName():string
	{
		return '速卖通同步商品信息(队列)';
	}
	public function getDesc():string
	{
		return '速卖通同步商品信息(队列)';
	}
	public function getAuthor():string
	{
		return 'joy';
	}

	public  function execute()
	{
		try{
			$job = $this->params;
			if($job)
			{
				(new AliexpressListingHelper())->findAeProductById($job);
			}
		}catch (QueueException $exp){
			throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
		}
	}
}