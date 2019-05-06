<?php

namespace app\report\service;

use app\order\service\OrderService;
use app\common\cache\Cache;

/**
 * Created by PhpStorm.
 * User: ZhouFurong
 * Date: 2019/4/20
 * Time: 15:58
 */
class StatisticAccountOperation
{
	const CACHE_PREFIX = 'occupy';
	const STATISTIC_ACCOUNT_OPERATION = self::CACHE_PREFIX . ':account_operation_analysis:table';
	const STATISTIC_ACCOUNT_OPERATION_PREFIX = self::CACHE_PREFIX . ':account_operation_analysis:';


	public static function accountOperation($account_id, $channel_id, $site, array $data, $time = 0)
	{
		date_default_timezone_set("PRC");
		$cache = Cache::handler(true);
		if (empty($time)) {
			$time = strtotime(date('Y-m-d', time()));
		}
		if (empty($channel_id) || empty($account_id) ) {
			return false;
		}
		$key = $account_id . ':' . $channel_id . ':' .$site  .':'. $time;
		if ($cache->exists(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key)) {
			foreach ($data as $k => $v) {
				switch ($k) {
					case "publish_quantity":
						$cache->hIncrBy(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key, 'publish_quantity', $v);
						break;
					case "online_listing_quantity":
						$cache->hIncrBy(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key, 'online_listing_quantity', $v);
						break;
					case "sale_amount":
						$cache->hIncrByFloat(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key, 'sale_amount', $v);
						break;
					case "order_quantity":
						$cache->hIncrBy(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key, 'order_quantity', $v);
						break;
					case "odr":
						$cache->hIncrByFloat(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key, 'odr', $v);
						break;
					case "virtual_order_quantity":
						$cache->hIncrBy(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key, 'virtual_order_quantity', $v);
						break;
					case "online_asin_quantity":
						$cache->hIncrBy(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key, 'online_asin_quantity', $v);
						break;
				}
			}
			$cache->hSet(self::STATISTIC_ACCOUNT_OPERATION, $key, $key);
		} else {
			$accountOperationData['dateline'] = $time;
			$accountOperationData['channel_id'] = $channel_id;
			$accountOperationData['site'] = $site;
			$accountOperationData['account_id'] = $account_id;
			$accountOperationData['publish_quantity'] = 0;
			$accountOperationData['online_listing_quantity'] = 0;
			$accountOperationData['order_quantity'] = 0;
			$accountOperationData['odr'] = 0;
			$accountOperationData['virtual_order_quantity'] = 0;
			$accountOperationData['online_asin_quantity'] = 0;

			$orderService = new OrderService();
			$userData = $orderService->getSales($channel_id, $account_id);
			if(!empty($userData)){
				$accountOperationData['seller_id'] = $userData['seller_id'];
			}
			foreach ($data as $k => $v) {
				switch ($k) {
					case "publish_quantity":
						$accountOperationData['publish_quantity'] = $v;
						break;
					case "online_listing_quantity":
						$accountOperationData['online_listing_quantity'] = $v;
						break;
					case "order_quantity":
						$accountOperationData['order_quantity'] = $v;
						break;
					case "sale_amount":
						$accountOperationData['sale_amount'] = $v;
						break;
					case "odr":
						$accountOperationData['odr'] = $v;
						break;
					case "virtual_order_quantity":
						$accountOperationData['virtual_order_quantity'] = $v;
						break;
					case "online_asin_quantity":
						$accountOperationData['online_asin_quantity'] = $v;
						break;
				}
			}
			//保存到缓存里
			foreach ($accountOperationData as $field => $value) {
				$cache->hSet(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key, $field, $value);
			}
			$cache->hSet(self::STATISTIC_ACCOUNT_OPERATION, $key, $key);
			dump($cache->hGetAll(self::STATISTIC_ACCOUNT_OPERATION));
			dump($cache->hGetAll(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key));
		}
		dump((self::STATISTIC_ACCOUNT_OPERATION));
		dump(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key);
		return true;
	}

	/**
	 * 获取账户运行分析统计信息
	 * @return array
	 */
	public function getAccountByOperation($key = '')
	{
		$accountOperationData = [];
		$cache = Cache::handler(true);
		if (empty($key)) {
			if ($cache->exists(self::STATISTIC_ACCOUNT_OPERATION)) {
				$tableData = $cache->hVals(self::STATISTIC_ACCOUNT_OPERATION);
				foreach ($tableData as $k => $kk) {
					$accountOperationData[$kk] = $cache->hGetAll(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $kk);
				}
			}
		}else{
			$accountOperationData = $cache->hGetAll(self::STATISTIC_ACCOUNT_OPERATION_PREFIX  . $key);
		}
		return $accountOperationData;
	}

	/**
	 * 获取账户运行分析统计信息
	 * @return array
	 */
	public function getAccountTableByOperation()
	{
		$accountOperationData = [];
		$cache = Cache::handler(true);
		if ($cache->exists(self::STATISTIC_ACCOUNT_OPERATION)) {
			$accountOperationData = $cache->hVals(self::STATISTIC_ACCOUNT_OPERATION);
		}
		return $accountOperationData;
	}

	/**
	 * 删除账户运行分析统计信息
	 * @param string $key
	 */
	public function delAccountByOperation($key = '')
	{
		$cache = Cache::handler(true);
		if (!empty($key)) {
			$cache->del(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key);
			$cache->hDel(self::STATISTIC_ACCOUNT_OPERATION, $key);
		} else {
			if ($cache->exists(self::STATISTIC_ACCOUNT_OPERATION)) {
				$tableData = $cache->hGetAll(self::STATISTIC_ACCOUNT_OPERATION);
				foreach ($tableData as $key => $value) {
					$cache->del(self::STATISTIC_ACCOUNT_OPERATION_PREFIX . $key);
				}
				$cache->del(self::STATISTIC_ACCOUNT_OPERATION);
			}
		}
	}
}