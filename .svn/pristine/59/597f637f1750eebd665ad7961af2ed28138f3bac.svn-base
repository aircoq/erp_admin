<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/9/21
 * Time: 10:35
 */

namespace app\listing\queue;
use app\common\model\ebay\EbayListing;
use app\common\service\SwooleQueueJob;
use app\listing\service\EbayListingHelper;

class EbayReviseItemQueue extends SwooleQueueJob {
	public function getName():string
	{
		return 'eBay编辑item队列';
	}
	public function getDesc():string
	{
		return 'eBay编辑item队列';
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
			$product= EbayListing::where('item_id','=',$params)->alias('a')->with(['account'=>function($query){$query->field('id,token,code');},'promotion','variant','images'=>function($query){$query->where(['status'=>0]);},'setting','specifics','internationalShipping','shipping'])->find();
			(new EbayListingHelper())->reviseItem($product);
		}
	}
}