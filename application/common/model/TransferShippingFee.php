<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 17:00
 */

namespace app\common\model;

use app\common\cache\Cache;
use think\Db;
use think\Model;

class TransferShippingFee extends Model
{
	const transferFeeOpen = 0; //转运费 启用
	const transferFeeOff = 1; //转运费 停用

	public function getId($id)
	{
		$code = 0;
		$info = $this->where('id', $id)->find();
		if (!$info) {
			$code = 500;
			$info = [
				'code' => 501,
				'msg' => '该转运费不存在',
			];
		}
		return [$info, $code];
	}

	/*	public function getWarehouseIDAttr($value)
		{
			$warehouseId = Cache::store('warehouse')->getWarehouseNameById($value) ?? '未匹配数据';
			return $warehouseId;
		}

		public function getCarrierIDAttr($value)
		{
			$carrierId = (Cache::store('carrier')->getCarrier($value))['shortname'] ?? '未匹配数据';
			return $carrierId;
		}*/

	public function setDateAttr($value)
	{
		$date = strtotime(date('Y-m',strtotime($value)));
		return $date;
	}

}