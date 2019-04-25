<?php

namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use think\Db;
use app\common\model\Country;

class Area extends Cache
{
	const cachePrefix = 'hash:area';
	private $_countryList = 'countryList';
	public $_countryCodeList = 'countryCodeList';

	/**
	 * 获取省市区
	 * @return $trees: 省市区列表
	 */
	public function getArea()
	{
		if ($this->redis->exists('cache:Area')) {
			$result = json_decode($this->redis->get('cache:Area'), true);
			return $result ?: [];
		}
		$trees = [];
		$result = Db::name('area')->select();
		if ($result) {
			$trees = list_to_tree($result);
			$this->redis->set('cache:Area', json_encode($trees));
		}
		return $trees;
	}


	/**
	 * 读取所有 country_code_list
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function getAllCountryCodeList()
	{
		$data = json_decode($this->redis->get($this->_countryCodeList, true));
		if (empty($data)) {
			$this->setCountryCodeList();
			$data = json_decode($this->redis->get($this->_countryCodeList, true));
		}

		return $data;
	}


	/**
	 * 添加country_code
	 * @param $country_code
	 */
	public function addCountryCodeList($country_code)
	{
		// 获取现有的country_code
		$countryCodeList = json_decode($this->redis->get($this->_countryCodeList), true);
		if (empty($countryCodeList)) {
			$this->setCountryCodeList();
			$countryCodeList = json_decode($this->redis->get($this->_countryCodeList), true);
		}

		//判断是否该cuntry_code 是否在缓存内
		if (!in_array($country_code, $countryCodeList)) {
			array_push($countryCodeList, $country_code);
			$this->redis->set($this->_countryCodeList, $countryCodeList);
		}
	}

	/**
	 * 添加所以有城市的 country_code_list
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function setCountryCodeList()
	{
		$countryList = (new Country())->select();
		$countryCodeList = [];
		foreach ($countryList as $key => $item) {
			//获取有城市的国家
			$haveCity = ($countryList[$key]->area);
			if (!$haveCity) {
				continue;
			}
			unset($countryList[$key]['area']);
			$countryCodeList[] = $countryList[$key]['country_code'];
		}
		$this->redis->set($this->_countryCodeList, json_encode($countryCodeList, true));
	}

	public function delCountryCodeList()
	{
		if ($this->isExists($this->_countryCodeList)) {
			$this->redis->del($this->_countryCodeList);
		}
	}

	/**
	 * 判断key是否存在
	 * @param $key
	 * @return bool
	 */
	private function isExists($key)
	{
		if ($this->redis->exists($key)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 判断域是否存在
	 * @param $key
	 * @param $field
	 * @return bool
	 */
	private function isFieldExists($key, $field)
	{
		if ($this->redis->hExists($key, $field)) {
			return true;
		} else {
			return false;
		}
	}
}