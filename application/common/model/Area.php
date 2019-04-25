<?php

namespace app\common\model;

use app\common\exception\JsonErrorException;
use think\Model;

/**
 * @desc 区域类
 * @author Jimmy <554511322@qq.com>
 * @date 2018-02-07 16:19:11
 */
class Area extends Model
{
	/**
	 * 初始化
	 */
	protected function initialize()
	{
		//需要调用 mdoel 的 initialize 方法
		parent::initialize();
	}

	public function country()
	{
		return $this->belongsTo('country', 'country_code', 'country_code');
	}

	public function hasId($id)
	{
		$data = [];
		$code = 0;
		$data = $this->where('id', $id)->find();
		if (!$data) {
			$code = 500;
			$data = '该地区不存在';
			return [$data, $code];
		}
		return [$data, $code];
	}

	public function isUniqueEnglishName($param)
	{
		$data = $this->where(['english_name' => $param])->find();
		if ($data) {
			throw new JsonErrorException('英文城市名已存在');
		}
	}

	public function isUniqueName($param)
	{
		$data = $this->where(['name' => $param])->find();
		if ($data) {
			throw new JsonErrorException('中文城市名已存在');
		}
	}
}
