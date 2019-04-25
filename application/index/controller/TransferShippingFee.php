<?php

namespace app\Index\controller;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\service\Common;
use think\Controller;
use think\Exception;
use think\Log;
use think\Request;
use app\index\service\TransferShippingFee as TransferShippingFeeService;

/**
 * @module 转运费管理
 * @title 转运费
 * @author dana
 * @url /transfer-fee
 * Class TransferShippingFee
 * @package app\Index\controller
 */
class TransferShippingFee extends Controller
{
	// 需要接口   发货仓库 物流商 币种

	protected $transferShippingFeeService;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);
		if (is_null($this->transferShippingFeeService)) {
			$this->transferShippingFeeService = new TransferShippingFeeService();
		}
	}

	/**
	 * @title 最新转运费列表
	 * @method GET
	 * @url /transfer-fee
	 * @return \think\response\Json
	 */
	public function index()
	{
		$request = Request::instance();
		$params = $request->param();
		$page = $request->get('page', 1);
		$pageSize = $request->get('pageSize', 10);
		unset($params['page']);
		unset($params['pageSize']);
		$transferFeeList = $this->transferShippingFeeService->index($params, $page, $pageSize);
		return json($transferFeeList);
	}

	/**
	 * @title 添加
	 * @method POST
	 * @param Request $request
	 * @url /transfer-fee
	 * @return \think\response\Json
	 */
	public function save(Request $request)
	{
		$param = $request->param();
		$userInfo = Common::getUserInfo();
		$result = $this->transferShippingFeeService->saveBase($param, $userInfo);
		return json($result);
	}

	/**
	 * @title 修改状态
	 * @method POST
	 * @param Request $request status and id
	 * @url /transfer-fee/status
	 * @return \think\response\Json
	 */
	public function status(Request $request)
	{
		$params = $request->param();
		$res = $this->transferShippingFeeService->status($params);
		if ($res){
			$data = ['message'=>'更新成功'];
			return json($data);
		}

	}

	/**
	 * @title 获取历史记录
	 * @method GET
	 * @param Request $request warehouse_id and carrier_id
	 * @url /transfer-fee/history
	 * @return \think\response\Json
	 */
	public function history(Request $request)
	{
		$params = $request->param();
		$page = $request->get('page', 1);
		$pageSize = $request->get('pageSize', 10);
		unset($params['page']);
		unset($params['pageSize']);

		$result = $this->transferShippingFeeService->historyFee($params, $page, $pageSize);
		return json($result);
	}

}
