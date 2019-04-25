<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/9/21
 * Time: 9:34
 */

namespace app\listing\queue;

use app\common\service\SwooleQueueJob;
use app\common\model\ebay\EbayModelPromotion;
use app\listing\service\EbayListingHelper;
class EbayPromotionQueue extends SwooleQueueJob {
	public function getName():string
	{
		return '同步eBay促销队列';
	}
	public function getDesc():string
	{
		return '同步eBay促销队列';
	}

	public function getAuthor():string
	{
		return 'joy';
	}

	public  function execute()
	{
		$params = $this->params;

		if($params)
		{
			$promation =EbayModelPromotion::where('id','=',$params)->with(['account'=>function($query){$query->field('id,token');}])->find();
			$token = $promation['account']['token'];
			$response = (new EbayListingHelper)->promotionalSale($token,$promation);
		}
	}
}