<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-7
 * Time: 下午6:26
 */

namespace app\index\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\Carrier;
use app\common\model\TransferShippingFee as TransferShippingFeeModel;
use app\common\model\Warehouse;
use app\common\service\Common;
use app\index\validate\TransferShippingFee as TransferShippingFeeValidate;
use think\Exception;


class TransferShippingFee
{

	protected $transferFeeModel;
	protected $validate;

	/**
	 * TransferFee constructor.
	 */
	public function __construct()
	{
		if (is_null($this->transferFeeModel)) {
			$this->transferFeeModel = new TransferShippingFeeModel();
		}
		$this->validate = new TransferShippingFeeValidate();
	}

	/**
	 * @title 获取最新转运费
	 * @param array $params
	 * @param int $page
	 * @param int $pageSize
	 * @return array
	 */
	public function index($params = [], $page = 1, $pageSize = 10)
	{
		// warehouse_id carrier_id and date 为一个联合索引
		$count = 0;
		$data = [];
		try {
			//$params['status'] = $this->transferFeeModel::transferFeeOpen;
			// 显示最近转运费
			list($count, $data) = $this->latestFee($params, $page, $pageSize);
			foreach ($data as $key => $item) {
				$data[$key]['creator_name'] = Common::getNameByUserId($item['creator_id']);
				$data[$key]['warehouse_name'] = $this->warehouseName($item['warehouse_id']);
				$data[$key]['carrier_name'] = $this->carrierName($item['carrier_id']);
			}

			$result = [
				'data' => $data,
				'page' => $page,
				'pageSize' => $pageSize,
				'count' => $count
			];
			return $result;
		} catch (Exception $ex) {
			throw new JsonErrorException('页面出错');
		}
	}

	/**
	 * @title 获取最新转运费信息
	 * @param $param
	 * @param $page
	 * @param $pageSize
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function latestFee($param, $page, $pageSize)
	{
		$count = 0;
		$data = [];
		$join = [];
		$sql = $this->transferFeeModel->field('warehouse_id,carrier_id,max(date) date')->group('warehouse_id,carrier_id')->buildSql();
		$sqlField = 'a.warehouse_id = b.warehouse_id and a.carrier_id = b.carrier_id and a.date = b.date';
		$field = 'a.id,a.warehouse_id,a.carrier_id,a.date,a.currency_code,a.fee,a.status,a.create_time,a.creator_id,a.update_time';
		$join['transfer_shipping_fee'] = [[$sql => 'b'], $sqlField, 'inner'];
		$data = $this->transferFeeModel->alias('a')->field($field)->join($join)->where($param)->order('id desc')->page($page, $pageSize)->select();
		//$count = $this->alias('a')->field($field)->join([$sql => 'b'], 'a.warehouse_id = b.warehouse_id and a.carrier_id = b.carrier_id and a.date = b.date', 'inner')->where($param)->count();
		$count = $this->transferFeeModel->alias('a')->field($field)->join($join)->where($param)->count();
		return [$count, $data];
	}

	/**
	 * 获取 warehouse name
	 * @param $warehouse_id
	 * @return bool|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function warehouseName($warehouse_id)
	{
		$warehouseInfo = (new Warehouse())->where('id', $warehouse_id)->find();
		if (empty($warehouseInfo)) {
			$warehouseName = '';
		} else {

			$warehouseName = $warehouseInfo->name;
		}
		return $warehouseName;
	}

	/**
	 * 获取 carrier name
	 * @param $carrier_id
	 * @return mixed|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function carrierName($carrier_id)
	{
		$carrierInfo = (new Carrier())->find($carrier_id);
		if (empty($carrierInfo)) {
			$carrierName = '';
		} else {
			$carrierName = $carrierInfo->shortname;
		}
		return $carrierName;
	}

	/**
	 * @title 添加数据
	 * @param $data
	 * @param $userInfo
	 * @return array
	 * @throws Exception
	 */
	public function saveBase($data, $userInfo)
	{
		$flag = $this->validate->scene('save_base')->check($data);
		if ($flag == false) {
			throw new JsonErrorException($this->validate->getError());
		}

		$data = $this->formatParam($data, $userInfo);

		try {
			$this->transferFeeModel->allowField(true)->save($data);
			return ['data' => ['id' => $this->transferFeeModel->id, 'message' => '新增成功']];
		} catch (Exception $ex) {
			if ($ex->getCode() == 10501) {
				throw new JsonErrorException('转运费已存在');
			}
			throw new JsonErrorException('新增失败');
		}

	}

	/**
	 * @title 初始化数据
	 * @param $data
	 * @param $userInfo
	 * @return mixed
	 */
	protected function formatParam($data, $userInfo)
	{
		if (isset($data['status'])) {
			$data['status'] = intval($data['status']);
		} else {
			$data['status'] = $this->transferFeeModel::transferFeeOpen;
		}
		$data['creator_id'] = $userInfo['user_id'];
		$data['create_time'] = time();
		$data['update_time'] = time();
		$data['updater_id'] = 0;//默认修改者为 0
		$data['id'] = time();
		return $data;
	}

	/**
	 * @title 修改转运费状态
	 * @param $param
	 * @return bool
	 */
	public function status($param)
	{
		$flag = $this->validate->scene('status')->check($param);
		if ($flag == false) {
			throw new JsonErrorException($this->validate->getError());
		}
		$data = [];
		$data['status'] = $param['status'];
		try {
			list($info, $code) = $this->transferFeeModel->getId($param['id']);
			if ($code) {
				return $info;
			}
			$this->transferFeeModel->where('id', $param['id'])->update($data);
			return true;
		} catch (Exception $ex) {
			throw new JsonErrorException('修改转运费失败');
		}


	}

	/**
	 * @param $param
	 * @param int $page
	 * @param int $pageSize
	 * @return array
	 * @throws Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function historyFee($param, $page = 1, $pageSize = 10)
	{
		$count = 0;
		$data = [];
		$flag = $this->validate->scene('history')->check($param);
		if ($flag == false) {
			throw new JsonErrorException($this->validate->getError());
		}
		$where = $this->checkParam($param);

		try {
			$field = 'id,warehouse_id,carrier_id,date,currency_code,fee,status,create_time,creator_id';
			// 不包括最新
			//$count = $this->transferFeeModel->field($field)->where($where)->whereNotIn('id', $whereNotIn)->order('date desc')->count();
			$count = $this->transferFeeModel->field($field)->where($where)->order('date desc')->count();
			$data = $this->transferFeeModel->field($field)->where($where)->order('date desc')->page($page, $pageSize)->select();
			$result = [
				'data' => $data,
				'page' => intval($page),
				'pageSize' => intval($pageSize),
				'count' => $count
			];
			return $result;
		} catch (Exception $ex) {
			throw new JsonErrorException('显示历史转运费失败');
		}
	}

	/**
	 * 检测 参数
	 * @param $param
	 * @return array
	 */
	public function checkParam($param)
	{
		if (isset($param['warehouse_id'])) {
			$where['warehouse_id'] = intval($param['warehouse_id']);
		}
		if (isset($param['carrier_id'])) {
			$where['carrier_id'] = intval($param['carrier_id']);
		}

		/*if (isset($param['id'])) {
			$whereNotIn['id'] = intval($param['id']);
		}*/
		return $where;
	}

	/**
	 * @title 获取运费
	 * @param $param   weight,warehouse_id,carrier_id,date
	 * @return array
	 * @throws Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function transShippingFee($data)
	{
		// 通过 date,warehouse_id,carrier_id get fee
		if ($data['weight'] && $data['warehouse_id'] && $data['carrier_id'] && $data['date']) {
			$weight = bcdiv($data['weight'], 1000, 3);
			$field = 'warehouse_id,carrier_id,date,currency_code,fee,status';
			$data['status'] = $this->transferFeeModel::transferFeeOpen;
			// 处理时间
			$data['date'] = $date = strtotime(date('Y-m', strtotime(($data['date']))));
			unset($data['weight']);
			$result = $this->transferFeeModel->field($field)->where($data)->find();
			if ($result) {
				// 计算原币金额
				$fee = $result->fee;
				$transFee = bcmul($fee, $weight, 5);
				return ['originalTransFee' => $transFee];
			} else {
				return '未找到对应的转运费信息';
			}
		}
	}

}