<?php

namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class Country extends Model
{
	protected $pk = 'country_code';

	/**
	 * 订单
	 */
	protected function initialize()
	{
		//需要调用 mdoel 的 initialize 方法
		parent::initialize();
	}

	/**
	 * 一对多
	 *
	 */
	public function area()
	{
		return $this->hasMany('area', 'country_code', 'country_code');
	}

	public function hasCountryCode($country_code)
	{
		$data = [];
		$code = 0;
		$data = $this->where('country_code', $country_code)->find();
		if (empty($data)) {
			$code = 500;
			$data = '请选择国家';
		}

		return [$data, $code];
	}

}