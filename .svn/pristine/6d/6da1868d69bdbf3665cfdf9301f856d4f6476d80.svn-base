<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/9/21
 * Time: 10:30
 */

namespace app\listing\queue;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayListing;
use app\common\service\SwooleQueueJob;
use app\listing\service\EbayListingHelper;
use app\publish\helper\ebay\EbayPublish;
use app\publish\service\EbayApiApply;
use app\publish\service\EbayPackApi;
use think\Exception;

class EbayRsyncListingQueue extends SwooleQueueJob
{
    protected  $maxFailPushCount = 0;

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }
	public function getName():string
	{
		return 'eBay同步listing队列';
	}
	public function getDesc():string
	{
		return 'eBay同步listing队列';
	}

	public function getAuthor():string
	{
		return 'wlw2533';
	}

	public  function execute()
	{
		$params = $this->params;
		if(!$params) {
		    return;
        }
		EbayApiApply::getSellerList($params['accountId'],$params['userId']);
	}
}