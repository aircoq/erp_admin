<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\service\Report as ReportCommon;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/6/26
 * Time: 14:20
 */
class Report extends Cache
{
	/** 获取分类统计信息
	 * @return array
	 */
	public function getSaleByCategory()
	{
		$categoryData = [];
		if ($this->persistRedis->exists(ReportCommon::saleByCategory)) {
			$tableData = $this->persistRedis->hGetAll(ReportCommon::saleByCategory);
			foreach ($tableData as $key => $value) {
				$categoryData[$key] = $this->persistRedis->hGetAll(ReportCommon::saleByCategoryPrefix . $key);
			}

		}
		return $categoryData;
	}

	/**
	 * 删除分类统计信息缓存
	 * @param string $key
	 */
	public function delSaleByCategory($key = '')
	{
		if (!empty($key)) {
			$this->persistRedis->del(ReportCommon::saleByCategoryPrefix . $key);
			$this->persistRedis->hDel(ReportCommon::saleByCategory, $key);
		} else {
			if ($this->persistRedis->exists(ReportCommon::saleByCategory)) {
				$tableData = $this->persistRedis->hGetAll(ReportCommon::saleByCategory);
				foreach ($tableData as $key => $value) {
					$this->persistRedis->del(ReportCommon::saleByCategoryPrefix . $key);
				}
				$this->persistRedis->del(ReportCommon::saleByCategory);
			}
		}
	}

	/** 获取产品统计信息
	 * @return array
	 */
	public function getSaleByGoods($key = '')
	{
		$goodsData = [];
		if (empty($key)) {
			if ($this->persistRedis->exists(ReportCommon::saleByGoods)) {
				$tableData = $this->persistRedis->hVals(ReportCommon::saleByGoods);
				foreach ($tableData as $k => $kk) {
					$goodsData[$kk] = $this->persistRedis->hGetAll(ReportCommon::saleByGoodsPrefix . $kk);
				}
			}
		} else {
			$goodsData = $this->persistRedis->hGetAll(ReportCommon::saleByGoodsPrefix . $key);
		}
		return $goodsData;
	}

	/** 获取产品统计信息
	 * @return array
	 */
	public function getSaleTableByGoods()
	{
		$goodsData = [];
		if ($this->persistRedis->exists(ReportCommon::saleByGoods)) {
			$goodsData = $this->persistRedis->hVals(ReportCommon::saleByGoods);
		}
		return $goodsData;
	}

	/** 获取产品统计信息
	 * @return array
	 */
	public function getSaleByGoods1()
	{
		set_time_limit(0);
		ini_set('memory_limit', '2048M');
		$goodsData = [];
		$cachePrefix = 'occupy';
		$saleByGoods = $cachePrefix . ':report_statistic_by_goods1:table';
		$saleByGoodsPrefix = $cachePrefix . ':report_statistic_by_goods1:';
		if ($this->redis->exists($saleByGoods)) {
			$tableData = $this->redis->hGetAll($saleByGoods);
			foreach ($tableData as $key => $value) {
				$goodsData[$key] = $this->redis->hGetAll($saleByGoodsPrefix . $key);
			}
		}
		return $goodsData;
	}

	/**
	 * 删除产品统计信息
	 * @param string $key
	 */
	public function delSaleByGoods1($key = '')
	{
		$cachePrefix = 'occupy';
		$saleByGoods = $cachePrefix . ':report_statistic_by_goods1:table';
		$saleByGoodsPrefix = $cachePrefix . ':report_statistic_by_goods1:';
		if (!empty($key)) {
			$this->redis->del($saleByGoodsPrefix . $key);
			$this->redis->hDel($saleByGoods, $key);
		} else {
			if ($this->redis->exists($saleByGoods)) {
				$tableData = $this->redis->hGetAll($saleByGoods);
				foreach ($tableData as $key => $value) {
					$this->redis->del($saleByGoodsPrefix . $key);
				}
				$this->redis->del($saleByGoods);
			}
		}
	}

	/**
	 * 删除产品统计信息
	 * @param string $key
	 */
	public function delSaleByGoods($key = '')
	{
		if (!empty($key)) {
			$this->persistRedis->del(ReportCommon::saleByGoodsPrefix . $key);
			$this->persistRedis->hDel(ReportCommon::saleByGoods, $key);
		} else {
			if ($this->persistRedis->exists(ReportCommon::saleByGoods)) {
				$tableData = $this->persistRedis->hGetAll(ReportCommon::saleByGoods);
				foreach ($tableData as $key => $value) {
					$this->persistRedis->del(ReportCommon::saleByGoodsPrefix . $key);
				}
				$this->persistRedis->del(ReportCommon::saleByGoods);
			}
		}
	}

	/** 获取销售业绩统计信息
	 * @return array
	 */
	public function getSaleByDeeps($key = '')
	{
		$deepsData = [];
		if (empty($key)) {
			if ($this->persistRedis->exists(ReportCommon::saleByDeeps)) {
				$tableData = $this->persistRedis->hVals(ReportCommon::saleByDeeps);
				foreach ($tableData as $k => $kk) {
					$deepsData[$kk] = $this->persistRedis->hGetAll(ReportCommon::saleByDeepsPrefix . $kk);
				}
			}
		}else{
			$deepsData = $this->persistRedis->hGetAll(ReportCommon::saleByDeepsPrefix . $key);
		}
		return $deepsData;
	}

	/** 获取销售业绩统计信息
	 * @return array
	 */
	public function getSaleTableByDeeps()
	{
		$deepsData = [];
		if ($this->persistRedis->exists(ReportCommon::saleByDeeps)) {
			$deepsData = $this->persistRedis->hVals(ReportCommon::saleByDeeps);
		}
		return $deepsData;
	}

	/**
	 * 删除销售业绩统计信息
	 * @param string $key
	 */
	public function delSaleByDeeps($key = '')
	{
		if (!empty($key)) {
			$this->persistRedis->del(ReportCommon::saleByDeepsPrefix . $key);
			$this->persistRedis->hDel(ReportCommon::saleByDeeps, $key);
		} else {
			if ($this->persistRedis->exists(ReportCommon::saleByDeeps)) {
				$tableData = $this->persistRedis->hGetAll(ReportCommon::saleByDeeps);
				foreach ($tableData as $key => $value) {
					$this->persistRedis->del(ReportCommon::saleByDeepsPrefix . $key);
				}
				$this->persistRedis->del(ReportCommon::saleByDeeps);
			}
		}
	}

	/** 获取日期统计产品信息
	 * @return array
	 */
	public function getSaleByDate($key = '')
	{
		$dateData = [];
		if (empty($key)) {
			if ($this->persistRedis->exists(ReportCommon::saleByDate)) {
				$tableData = $this->persistRedis->hVals(ReportCommon::saleByDate);
				foreach ($tableData as $k => $key) {
					$dateData[$key] = $this->persistRedis->hGetAll(ReportCommon::saleByDatePrefix . $key);
				}
			}
		}else{
			$dateData = $this->persistRedis->hGetAll(ReportCommon::saleByDatePrefix . $key);
		}
		return $dateData;
	}

	/** 获取日期统计产品信息
	 * @return array
	 */
	public function getSaleTableByDate()
	{
		$dateData = [];
		if ($this->persistRedis->exists(ReportCommon::saleByDate)) {
			$dateData = $this->persistRedis->hVals(ReportCommon::saleByDate);
		}
		return $dateData;
	}

	/**
	 * 删除销售业绩统计信息
	 * @param string $key
	 */
	public function delSaleByDate($key = '')
	{
		if (!empty($key)) {
			$this->persistRedis->del(ReportCommon::saleByDatePrefix . $key);
			$this->persistRedis->hDel(ReportCommon::saleByDate, $key);
		} else {
			if ($this->persistRedis->exists(ReportCommon::saleByDate)) {
				$tableData = $this->persistRedis->hVals(ReportCommon::saleByDate);
				foreach ($tableData as $k => $kk) {
					$this->persistRedis->del(ReportCommon::saleByDatePrefix . $kk);
				}
				$this->persistRedis->del(ReportCommon::saleByDate);
			}
		}
	}

	/**
	 * 获取国家统计信息
	 * @return array
	 */
	public function getSaleByCountry($key = '')
	{
		$countryData = [];
		if (empty($key)) {
			if ($this->persistRedis->exists(ReportCommon::saleByCountry)) {
				$tableData = $this->persistRedis->hVals(ReportCommon::saleByCountry);
				foreach ($tableData as $k => $key) {
					$countryData[$key] = $this->persistRedis->hGetAll(ReportCommon::saleByCountryPrefix . $key);
				}
			}
		}else{
			$countryData = $this->persistRedis->hGetAll(ReportCommon::saleByCountryPrefix . $key);
		}
		return $countryData;
	}

	/**
	 * 获取国家统计信息
	 * @return array
	 */
	public function getSaleTableByCountry()
	{
		$countryData = [];
		if ($this->persistRedis->exists(ReportCommon::saleByCountry)) {
			$countryData = $this->persistRedis->hVals(ReportCommon::saleByCountry);
		}
		return $countryData;
	}

	/**
	 * 删除国家统计信息
	 * @param string $key
	 */
	public function delSaleByCountry($key = '')
	{
		if (!empty($key)) {
			$this->persistRedis->del(ReportCommon::saleByCountryPrefix . $key);
			$this->persistRedis->hDel(ReportCommon::saleByCountry, $key);
		} else {
			if ($this->persistRedis->exists(ReportCommon::saleByCountry)) {
				$tableData = $this->persistRedis->hVals(ReportCommon::saleByCountry);
				foreach ($tableData as $k => $kk) {
					$this->persistRedis->del(ReportCommon::saleByCountryPrefix . $kk);
				}
				$this->persistRedis->del(ReportCommon::saleByCountry);
			}
		}
	}

	/** 获取包裹统计信息
	 * @return array
	 */
	public function getSaleByPackage()
	{
		$packageData = [];
		if ($this->persistRedis->exists(ReportCommon::saleByPackage)) {
			$tableData = $this->persistRedis->hGetAll(ReportCommon::saleByPackage);
			foreach ($tableData as $key => $value) {
				$packageData[$key] = $this->persistRedis->hGetAll(ReportCommon::saleByPackagePrefix . $key);
			}
		}
		return $packageData;
	}

	/**
	 * 删除包裹统计信息
	 * @param string $key
	 */
	public function delSaleByPackage($key = '')
	{
		if (!empty($key)) {
			$this->persistRedis->del(ReportCommon::saleByPackagePrefix . $key);
			$this->persistRedis->hDel(ReportCommon::saleByPackage, $key);
		} else {
			if ($this->persistRedis->exists(ReportCommon::saleByPackage)) {
				$tableData = $this->persistRedis->hGetAll(ReportCommon::saleByPackage);
				foreach ($tableData as $key => $value) {
					$this->persistRedis->del(ReportCommon::saleByPackagePrefix . $key);
				}
				$this->persistRedis->del(ReportCommon::saleByPackage);
			}
		}
	}

	/** 获取订单统计信息
	 * @return array
	 */
	public function getSaleByOrder($key = '')
	{
		$orderData = [];
		if (empty($key)) {
			if ($this->persistRedis->exists(ReportCommon::statisticOrder)) {
				$tableData = $this->persistRedis->hVals(ReportCommon::statisticOrder);
				foreach ($tableData as $k => $kk) {
					$orderData[$kk] = $this->persistRedis->hGetAll(ReportCommon::statisticOrderPrefix . $kk);
				}
			}
		} else {
			$orderData = $this->persistRedis->hGetAll(ReportCommon::statisticOrderPrefix . $key);
		}
		return $orderData;
	}

	/** 获取订单统计信息
	 * @return array
	 */
	public function getSaleTableByOrder()
	{
		$tableData = [];
		if ($this->persistRedis->exists(ReportCommon::statisticOrder)) {
			$tableData = $this->persistRedis->hVals(ReportCommon::statisticOrder);
		}
		return $tableData;
	}

	/**
	 * 删除订单统计信息
	 * @param string $key
	 */
	public function delSaleByOrder($key = '')
	{
		if (!empty($key)) {
			$this->persistRedis->del(ReportCommon::statisticOrderPrefix . $key);
			$this->persistRedis->hDel(ReportCommon::statisticOrder, $key);
		} else {
			if ($this->persistRedis->exists(ReportCommon::statisticOrder)) {
				$tableData = $this->persistRedis->hVals(ReportCommon::statisticOrder);
				foreach ($tableData as $k => $kk) {
					$this->persistRedis->del(ReportCommon::statisticOrderPrefix . $kk);
				}
				$this->persistRedis->del(ReportCommon::statisticOrder);
			}
		}
	}

	/** 获取买家统计信息
	 * @return array
	 */
	public function getSaleByBuyer($key = '')
	{
		$buyerData = [];
		if(empty($key)){
			if ($this->persistRedis->exists(ReportCommon::saleByBuyer)) {
				$tableData = $this->persistRedis->hGetAll(ReportCommon::saleByBuyer);
				foreach ($tableData as $key => $value) {
					$buyerData[$key] = $this->persistRedis->hGetAll(ReportCommon::saleByBuyerPrefix . $key);
				}
			}
		}else{
			$buyerData = $this->persistRedis->hGetAll(ReportCommon::saleByBuyerPrefix . $key);
		}
		return $buyerData;
	}

	/** 获取买家统计信息
	 * @return array
	 */
	public function getSaleTableByBuyer()
	{
		$buyerData = [];
		if ($this->persistRedis->exists(ReportCommon::saleByBuyer)) {
			$buyerData = $this->persistRedis->hVals(ReportCommon::saleByBuyer);
		}
		return $buyerData;
	}

	/**
	 * 删除买家统计信息
	 * @param string $key
	 */
	public function delSaleByBuyer($key = '')
	{
		if (!empty($key)) {
			$this->persistRedis->del(ReportCommon::saleByBuyerPrefix . $key);
			$this->persistRedis->hDel(ReportCommon::saleByBuyer, $key);
		} else {
			if ($this->persistRedis->exists(ReportCommon::saleByBuyer)) {
				$tableData = $this->persistRedis->hGetAll(ReportCommon::saleByBuyer);
				foreach ($tableData as $key => $value) {
					$this->persistRedis->del(ReportCommon::saleByBuyerPrefix . $key);
				}
				$this->persistRedis->del(ReportCommon::saleByBuyer);
			}
		}
	}


	/** 获取客服业绩统计信息
	 * @return array
	 */
	public function getSaleByMessage()
	{
		$orderData = [];
		if ($this->persistRedis->exists(ReportCommon::statisticMessage)) {
			$tableData = $this->persistRedis->hGetAll(ReportCommon::statisticMessage);
			foreach ($tableData as $key => $value) {
				$orderData[$key] = $this->persistRedis->hGetAll(ReportCommon::statisticMessagePrefix . $key);
			}
		}
		return $orderData;
	}

	/**
	 * 删除客服业绩统计信息
	 * @param string $key
	 */
	public function delSaleByMessage($key = '')
	{
		if (!empty($key)) {
			$this->persistRedis->del(ReportCommon::statisticMessagePrefix . $key);
			$this->persistRedis->hDel(ReportCommon::statisticMessage, $key);
		} else {
			if ($this->persistRedis->exists(ReportCommon::statisticMessage)) {
				$tableData = $this->persistRedis->hGetAll(ReportCommon::statisticMessage);
				foreach ($tableData as $key => $value) {
					$this->persistRedis->del(ReportCommon::statisticMessagePrefix . $key);
				}
				$this->persistRedis->del(ReportCommon::statisticMessage);
			}
		}
	}
}