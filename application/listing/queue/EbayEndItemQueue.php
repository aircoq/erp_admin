<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/9/21
 * Time: 10:24
 */

namespace app\listing\queue;

use app\common\model\ebay\EbayActionLog;
use app\common\model\ebay\EbayListing;
use app\common\service\CommonQueuer;
use app\common\service\SwooleQueueJob;
use app\goods\queue\GoodsTortListingQueue;
use app\publish\helper\ebay\EbayPublish;
use app\publish\service\EbayApiApply;

class EbayEndItemQueue extends SwooleQueueJob {
	public function getName():string
	{
		return 'eBay产品下架队列';
	}
	public function getDesc():string
	{
		return 'eBay产品下架队列';
	}

	public function getAuthor():string
	{
		return 'joy';
	}

	public  function execute()
	{
		$logId = $this->params;
		$log = EbayActionLog::get($logId);
		if (!$log) {
		    return;
        }
		try {
            EbayApiApply::endItem($log);
            $status = true;
        } catch (\Exception $e) {
            $status = false;
        }
		$newVal = json_decode($log['new_val'],true);
		if (isset($newVal['tort_id'])) {//回写下架状态
            $backWriteData = [
                'goods_id' => $newVal['goods_id'],
                'goods_tort_id' => $newVal['tort_id'],
                'channel_id' => 1,
                'status' => $status ? 3 : 2,
                'listing_id' => $newVal['listing_id'],
                'item_id' => $newVal['item_id'],
            ];
            (new CommonQueuer(GoodsTortListingQueue::class))->push($backWriteData);//回写
        }
	}
}