<?php

namespace app\report\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\account\AccountOperationAnalysis;
use app\common\service\Common;
use app\common\traits\Export;
use app\common\traits\User;
use app\index\service\AccountService;
use app\index\service\ChannelAccount;
use app\index\service\DepartmentUserMapService;
use app\order\service\AuditOrderService;
use app\order\service\OrderService;
use app\report\model\ReportExportFiles;
use app\report\queue\AccountOperationQueue;
use app\report\validate\FileExportValidate;
use function GuzzleHttp\Psr7\str;
use think\Db;
use think\Exception;
use think\Loader;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/**
 * Created by PhpStorm.
 * User: ZhouFurong
 * Date: 2019/4/20
 * Time: 14:24
 */
class AccountOperationAnalysisService
{

	use Export;
	use User;

	protected $accountOperationModel;

	protected $colMaps = [
		'account' => [
			'title' => [
				'A' => ['title' => '序号.', 'width' => 10],
				'B' => ['title' => '平台', 'width' => 10],
				'C' => ['title' => '站点', 'width' => 25],
				'D' => ['title' => '账户简称', 'width' => 10],
				'E' => ['title' => '销售员', 'width' => 20],
				'F' => ['title' => '销售组长', 'width' => 15],
				'G' => ['title' => '销售主管', 'width' => 15],
				'H' => ['title' => '销售所属部门', 'width' => 10],
				'I' => ['title' => '账户状态', 'width' => 10],
				'J' => ['title' => '是否有VAT', 'width' => 10],
				'K' => ['title' => '是否可发FBA', 'width' => 10],
				'L' => ['title' => '刊登数量', 'width' => 10],
				'M' => ['title' => '昨日在线listing数量', 'width' => 20],
				'N' => ['title' => '销售额（USD）', 'width' => 10],
				'O' => ['title' => '订单数', 'width' => 10],
				'P' => ['title' => 'ODR', 'width' => 10],
				//	'Q' => ['title' => '智持订单数量', 'width' => 10],
				'R' => ['title' => '在线asin数量', 'width' => 10],
				//	'S' => ['title' => '平均动销率', 'width' => 10],
				'T' => ['title' => '账户注册日期', 'width' => 15],
				'U' => ['title' => '账户交接日期', 'width' => 15],
			],
			'data' => [
				'id' => ['col' => 'A', 'type' => 'int'],
				'channel_name' => ['col' => 'B', 'type' => 'str'],
				'site' => ['col' => 'C', 'type' => 'time'],
				'account_name' => ['col' => 'D', 'type' => 'str'],
				'seller_name' => ['col' => 'E', 'type' => 'str'],
				'team_leader_name' => ['col' => 'F', 'type' => 'time'],
				'supervisor_name' => ['col' => 'G', 'type' => 'str'],
				'department_name' => ['col' => 'H', 'type' => 'str'],
				'account_status' => ['col' => 'I', 'type' => 'str'],
				'is_vat' => ['col' => 'J', 'type' => 'str'],
				'can_send_fba' => ['col' => 'K', 'type' => 'str'],
				'publish_quantity' => ['col' => 'L', 'type' => 'str'],
				'online_listing_quantity' => ['col' => 'M', 'type' => 'str'],
				'sale_amount' => ['col' => 'N', 'type' => 'str'],
				'order_quantity' => ['col' => 'O', 'type' => 'str'],
				'odr' => ['col' => 'P', 'type' => 'str'],
				//'virtual_order_quantity' => ['col' => 'Q', 'type' => 'str'],
				'online_asin_quantity' => ['col' => 'R', 'type' => 'str'],
				//	'average_retail_rate' => ['col' => 'S', 'type' => 'str'],
				'account_register_time' => ['col' => 'T', 'type' => 'str'],
				'account_transition_time' => ['col' => 'U', 'type' => 'str'],
			]
		]
	];


	public function __construct()
	{
		if (is_null($this->accountOperationModel)) {
			$this->accountOperationModel = new AccountOperationAnalysis();
		}
	}

	/**
	 *  导出所有的字段
	 */
	public function getExportField()
	{
		$exportFields = $this->title();
		$title = [];
		//$titleData = [];
		foreach ($exportFields as $key => $value) {
			if ($value['is_show'] == 1) {
				$temp['key'] = $value['title'];
				$temp['title'] = $value['remark'];
				array_push($title, $temp);
			}
		}
		return $title;
	}

	/**
	 * 导出字段
	 */
	public function title()
	{
		$title = [
			'id' => [
				'title' => 'id',
				'remark' => '序号',
				'is_show' => 1
			],
			'dateline' => [
				'title' => 'dateline',
				'remark' => '日期',
				'is_show' => 1
			],
			'channel_name' => [
				'title' => 'channel_name',
				'remark' => '平台',
				'is_show' => 1
			],
			'site' => [
				'title' => 'site',
				'remark' => '站点',
				'is_show' => 1
			],
			'account_name' => [
				'title' => 'account_name',
				'remark' => '账户简称',
				'is_show' => 1
			],
			'seller_name' => [
				'title' => 'seller_name',
				'remark' => '销售员',
				'is_show' => 1
			],
			'team_leader_name' => [
				'title' => 'team_leader_name',
				'remark' => '销售组长',
				'is_show' => 1
			],
			'supervisor_name' => [
				'title' => 'supervisor_name',
				'remark' => '销售主管',
				'is_show' => 1
			],
			'department_name' => [
				'title' => 'department_name',
				'remark' => '销售所属部门',
				'is_show' => 1
			],
			'account_status' => [
				'title' => 'account_status',
				'remark' => '账户状态',
				'is_show' => 1
			],
			'is_vat' => [
				'title' => 'is_vat',
				'remark' => '是否有VAT',
				'is_show' => 1
			],
			'can_send_fba' => [
				'title' => 'can_send_fba',
				'remark' => '是否可发FBA',
				'is_show' => 1
			],
			'publish_quantity' => [
				'title' => 'publish_quantity',
				'remark' => '刊登数量',
				'is_show' => 1
			],
			'online_listing_quantity' => [
				'title' => 'online_listing_quantity',
				'remark' => '昨日在线listing数量',
				'is_show' => 1
			],
			'sale_amount' => [
				'title' => 'sale_amount',
				'remark' => '销售额（USD）',
				'is_show' => 1
			],
			'order_quantity' => [
				'title' => 'order_quantity',
				'remark' => '订单数',
				'is_show' => 1
			],
			'odr' => [
				'title' => 'odr',
				'remark' => 'ODR',
				'is_show' => 1
			],
		/*	'virtual_order_quantity' => [
				'title' => 'virtual_order_quantity',
				'remark' => '智持订单数量',
				'is_show' => 1
			],*/
			'average_retail_rate' => [
				'title' => 'average_retail_rate',
				'remark' => '平均动销率',
				'is_show' => 1
			],
			'online_asin_quantity' => [
				'title' => 'online_asin_quantity',
				'remark' => '在线asin数量',
				'is_show' => 1
			],
			'account_register_time' => [
				'title' => 'account_register_time',
				'remark' => '账号注册时间',
				'is_show' => 1
			],
			'account_transition_time' => [
				'title' => 'account_transition_time',
				'remark' => '账号交接时间',
				'is_show' => 1
			],
		];
		return $title;
	}

	/**
	 * @param $params
	 * @param $header
	 * @return \think\response\Json
	 */
	public function createExport($params, $header)
	{
		$field = '';
		$ids = param($params, 'ids', 0);
		if (!isset($header['x-result-fields'])) {
			$field = [];
		}

		if (isset($header['x-result-fields'])) {
			$field = $header['x-result-fields'];
			$field = explode(',', $field);
		}

		$type = param($params, 'export_type', 0);
		$ids = json_decode($ids, true);

		if (empty($ids) && empty($type)) {
			return json(['message' => '请先选择一条记录'], 400);
		}

		if (!empty($type)) {
			$ids = [];
		}

		return $this->applyExport($ids, $field, $params);

	}

	/**
	 * 页面导出
	 * @param array $ids
	 * @param array $field
	 * @param array $params
	 * @return array
	 * @throws Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function applyExport(array $ids = [], array $field = [], $params = [])
	{
		$userInfo = Common::getUserInfo();
		$cache = Cache::handler();
		$lastApplyTime = $cache->hget('hash:export_detail_apply', $userInfo['user_id']);
		if ($lastApplyTime && time() - $lastApplyTime < 5) {
			throw new JsonErrorException('请求过于频繁', 400);
		} else {
			$cache->hset('hash:export_apply', $userInfo['user_id'], time());
		}
		$fileName = $this->newExportFileName($params);
		//判断是否存在筛选条件，更改导出名
		if (isset($fileName) && $fileName != '') {
			$setFileName = 1;
			$name = $fileName . $userInfo['user_id'] . '_' . date('Y-m-d H:i:s', time()) . (isset($params['name']) ? $params['name'] : '账户运营分析');
			$fileName = $name;
		} else {
			$setFileName = 0;
			$name = isset($params['name']) ? $params['name'] : '账户运营分析';
			$fileName = $name . date('YmdHis', time());
		}
		$params['user_id'] = $userInfo['user_id'];
		$downLoadDir = '/download/report/account-operation/';
		$saveDir = ROOT_PATH . 'public' . $downLoadDir;
		if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
			throw new Exception('导出目录创建失败');
		}

		$fullName = $saveDir . $fileName;
		$titleData = $this->title();
		$remark = [];
		if (!empty($field)) {
			$title = [];
			foreach ($field as $k => $v) {
				if (isset($titleData[$v])) {
					array_push($title, $v);
					array_push($remark, $titleData[$v]['remark']);
				}
			}
		} else {
			$title = [];
			foreach ($titleData as $k => $v) {
				if ($v['is_show'] == 1) {
					array_push($title, $k);
					array_push($remark, $v['remark']);
				}
			}
		}

		//统计需要导出的数据行
		if (!empty($ids)) {
			$params['a.id'] = ['in', $ids];
		}

		//统计导出数量
		$count = $this->searchCount($params);

		if ($count > 1000) {
			$params['field'] = $field;
			//队列导出
			Db::startTrans();
			try {
				$params['field'] = $field;
				$this->exportApply($params, AccountOperationQueue::class, $name, $setFileName);
				Db::commit();
				return ['join_queue' => 1, 'message' => '已加入导出队列'];
			} catch (\Exception $ex) {
				Db::rollback();
				throw new JsonErrorException('申请导出失败');
			}
		} else {
			// 页面导出
			$records = $this->doSearch($params);
			$data = $this->assemblyDate($records, $title);
			$titleAccountData = [];
			foreach ($remark as $t => $tt) {
				$titleAccountData[$tt] = 'string';
			}

			$this->excelSave($titleAccountData, $fullName, $data);
			$auditOrderService = new AuditOrderService();
			$result = $auditOrderService->record($fileName, $saveDir . $fileName);
			return $result;
		}
	}

	/**
	 * 队列导出
	 * @param array $params
	 */
	public function export(array $params)
	{
		set_time_limit(0);
		try {
			ini_set('memory_limit', '4096M');
			if (!isset($params['apply_id']) || empty($params['apply_id'])) {
				throw new Exception('导出申请id获取失败');
			}
			if (!isset($params['file_name']) || empty($params['file_name'])) {
				throw new Exception('导出文件名未设置');
			}
			$fileName = $params['file_name'];
			$downLoadDir = '/download/report/account-operation/';
			$saveDir = ROOT_PATH . 'public' . $downLoadDir;
			if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
				throw new Exception('导出目录创建失败');
			}
			$fullName = $saveDir . $fileName;
			//创建excel对象
			$writer = new \XLSXWriter();
			$fields = $params['field'] ?? [];
			$titleData = $this->title();
			$title = [];
			if (!empty($fields)) {
				$titleNewData = [];
				foreach ($fields as $k => $v) {
					if (isset($titleData[$v])) {
						array_push($title, $v);
						$titleNewData[$v] = $titleData[$v];
					}
				}
				$titleData = $titleNewData;
			} else {
				foreach ($titleData as $k => $v) {
					if ($v['is_show'] == 0) {
						unset($titleData[$k]);
					} else {
						array_push($title, $k);
					}
				}
			}
			list($titleMap, $dataMap) = $this->getExcelMap($titleData);
			end($titleMap);
			$titleOrderData = [];
			foreach ($titleMap as $t => $tt) {
				$titleOrderData[$tt['title']] = 'string';
			}
			// 批量导出未写
			// 统计需要导出的数据行
			$count = $this->searchCount($params);
			$writer->writeSheetHeader('Sheet1', $titleOrderData);
			$writer->writeToFile($fullName);
			if (is_file($fullName)) {
				$applyRecord['exported_time'] = time();
				$applyRecord['download_url'] = $downLoadDir . $fileName;
				$applyRecord['status'] = 1;
				(new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
			} else {
				throw new Exception('文件写入失败');
			}

		} catch (\Exception $ex) {
			$applyRecord['status'] = 2;
			$applyRecord['error_message'] = $ex->getMessage();
			(new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
			Cache::handler()->hset(
				'hash:report_export',
				$params['apply_id'] . '_' . time(),
				'申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage() . ',错误行数：' . $ex->getLine());
		}
	}

	/**
	 * 新增文件名
	 * @param $params
	 * @return string
	 * @throws Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function newExportFileName($params)
	{
		$fileName = '';
		if ($channel_id = param($params, 'channel_id')) {
			$title = Cache::store('channel')->getChannelTitle($channel_id);
			$fileName .= '平台：' . $title . '|';
		}
		if ($site = param($params, 'site')) {
			$fileName .= '站点：' . $site . '|';
		}
		if ($account_id = param($params, 'account_id')) {
			$order_service = new OrderService();
			$accountName = $order_service->getAccountName($params['channel_id'], $params['account_id']);
			$fileName .= '账号简称：' . $accountName . '|';
		}
		/*if ($seller_id = param($params, 'seller_id')) {
			$cache = Cache::store('user');
			$fileName .= '销售员：' . $cache->getOneUserRealname($params['seller_id']) . '|';
		}*/
		/*if (isset($params['snDate'])) {
			$params['date_b'] = isset($params['date_b']) ? trim($params['date_b']) : 0;
			$params['date_e'] = isset($params['date_e']) ? trim($params['date_e']) : 0;
			switch (trim($params['snDate'])) {
				case 'transaction_date':
					if (!empty($params['date_b']) && !empty($params['date_e'])) {
						$fileName .= '下单时间：' . $params['date_b'] . '—' . $params['date_e'] . '|';
					}
					break;
				case 'shipping_time':
					if (!empty($params['date_b']) && !empty($params['date_e'])) {
						$fileName .= '发货时间：' . $params['date_b'] . '—' . $params['date_e'] . '|';
					}
					break;
				case 'pay_time':
					if (!empty($params['date_b']) && !empty($params['date_e'])) {
						$fileName .= '支付时间：' . $params['date_b'] . '—' . $params['date_e'] . '|';
					}
					break;
				case 'create_time':
					if (!empty($params['date_b']) && !empty($params['date_e'])) {
						$fileName .= '创建时间：' . $params['date_b'] . '—' . $params['date_e'] . '|';
					}
					break;
				default:
					break;
			}
		}*/


		return $fileName;

	}

	/**
	 * 获取搜索类型
	 * @param $params
	 * @return array
	 */
	public function getSearchType($params)
	{
		$search_type = [];
		if (isset($params['search_type']) && !empty($params['search_type'])) {
			if ($params['min_value'] !== '' || $params['max_value'] !== '') {
				$min_value = $params['min_value'];
				$max_value = $params['max_value'];
				$search_type_name = 'a.' . $params['search_type'];

				if ($min_value == '') {
					$search_type[$search_type_name] = ['elt', $params['max_value']];
				} elseif ($max_value == '') {
					$search_type[$search_type_name] = ['egt', $params['min_value']];
				} else {
					$search_type[$search_type_name] = ['between', [$min_value, $max_value]];
				}
			}
		}

		return $search_type;
	}

	/**
	 * 获取搜索时间字段
	 * @param $params
	 * @return array
	 */
	public function getTimeField($params)
	{
		date_default_timezone_set("PRC");
		$time_field = [];
		if (isset($params['time_field'])) {
			if ($params['date_from'] !== '' || $params['date_to'] !== '') {
				$date_from = strtotime($params['date_from']);
				$data_to = strtotime($params['date_to']);
				if (trim($params['time_field']) == 'dateline') {
					$time_field_name = 'a.' . $params['time_field'];
				} else {
					$time_field_name = 'ac.' . $params['time_field'];
				}

				if ($date_from == '') {
					$time_field[$time_field_name] = ['elt', $data_to];
				} elseif ($data_to == '') {
					$time_field[$time_field_name] = ['egt', $date_from];
				} else {
					$time_field[$time_field_name] = ['between', [$date_from, $data_to]];
				}
			}
		}

		return $time_field;
	}

	/**
	 * 列表详情
	 * @param int $page
	 * @param int $pageSize
	 * @param array $params
	 * @return array
	 */
	public function search($params)
	{

		$page = param($params,'page',1);
		$pageSite = param($params, 'pageSite', 10);
		$count = $this->searchCount($params);
		$data = $this->assemblyDate($this->doSearch($params, $page, $pageSite));

		return [
			'page' => $page,
			'pageSize' => $pageSite,
			'count' => $count,
			'data' => $data
		];
	}

	/**
	 * 获取数量
	 * @param $params
	 * @return int|string
	 */
	public function searchCount($params)
	{
		$field = $this->searchField($params);
		$this->accountOperationModel->alias('a');
		$this->accountOperationModel->join('`account` ac', '`ac`.`id` = `a`.`account_id`', 'left');
		$this->accountOperationModel->field($field);
		$this->whereCondition($params);

		return $this->accountOperationModel->count();
	}

	/**
	 * 执行条件
	 * @param $param
	 */
	public function whereCondition($param)
	{
		list($search_type, $time_type, $condition) = $this->getSearchCondition($param);

		if (!empty($search_type)) {
			$this->accountOperationModel->where($search_type);
		}
		if (!empty($time_type)) {
			$this->accountOperationModel->where($time_type);
		}
		if (!empty($condition)) {
			$this->accountOperationModel->where($condition);
		}

	}

	/**
	 * 组合数据
	 * @param $accountData
	 * @param array $title
	 * @return array
	 * @throws Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function assemblyDate($accountData, $title = [])
	{
		if (empty($accountData)) {
			return [];
		}

		$data = [];
		$info = [];
		foreach ($accountData as $key => $value) {
			//$value = $value->toArray();
			$data[$key]['id'] = $value['id'];
			$data[$key]['dateline'] = $value['dateline'];
			$data[$key]['channel_name'] = Cache::store('channel')->getChannelName($value['channel_id']);
			$data[$key]['channel_id'] = $value['channel_id'];
			$data[$key]['account_id'] = $value['account_id'];
			$data[$key]['seller_name'] = "";
			$data[$key]['team_leader_name'] = "";
			$data[$key]['supervisor_name'] = "";
			$data[$key]['department_name'] = "";
			if (!empty($value['seller_id'])) {
				//$data[$key]['seller_id'] = $value['seller_id'];
				$userMap = new DepartmentUserMapService();
				$user = $userMap->getDepartmentByUserId($value['seller_id']);
				// 部门ID
				$departmentID = $user;
				$users = (new ProfitStatement())->getLeaderDirector($value['seller_id']);
				$data[$key]['seller_name'] = Cache::store('user')->getOneUserRealname($value['seller_id']);
				$data[$key]['team_leader_name'] = $users['team_leader_name'];
				$data[$key]['supervisor_name'] = $users['supervisor_name'];
				$data[$key]['department_name'] = (Cache::store('department')->getDepartment($departmentID[0]))['name'];
			}

			if ($value['account_id'] && $value['channel_id']) {
				$account = (new ChannelAccount())->getAccount($value['channel_id'], $value['account_id']);
				$accountCode = param($account, 'code');
				$data[$key]['account_name'] = $accountCode;
			}

			$account = (new AccountService())->accountOperationAnalysis($value['account_id']);
			//dump($account);
			// '0-新增 1-已注册 2-审核中 3-注册成功 4-已交接 5-已回收 6-已作废',
			$data[$key]['account_status'] = $this->accountStatus($account['status']);


			if (!empty($account['vat_data'])) {
				$data[$key]['is_vat'] = $account['vat_data'];
			} else {
				$data[$key]['is_vat'] = '否';
			}

			if ($account['source'] === 1) {
				$data[$key]['can_send_fba'] = '是';
			} else {
				$data[$key]['can_send_fba'] = '否';
			}

			$data[$key]['site'] = $account['site_code'];
			$data[$key]['account_register_time'] = $account['account_create_time'];
			$data[$key]['account_transition_time'] = $account['fulfill_time'];
			$data[$key]['publish_quantity'] = $value['publish_quantity'];
			$data[$key]['online_listing_quantity'] = $value['online_listing_quantity'];
			$data[$key]['sale_amount'] = $value['sale_amount'];
			$data[$key]['order_quantity'] = $value['order_quantity'];
			$data[$key]['odr'] = sprintf("%.4f",$value['odr']);
			$data[$key]['average_retail_rate'] = sprintf("%.4f",$value['average_retail_rate']);
			$data[$key]['online_asin_quantity'] = $value['online_asin_quantity'];

			if (!empty($title)) {
				foreach ($data as $accounts => $item) {
					$temp = [];
					foreach ($title as $k => $v) {
						$temp[$v] = $item[$v];
					}
					$info[] = $temp;
				}
				unset($data);
			}
			unset($accountData);

		}


		if (!empty($title)) {
			return $info;
		} else {
			return $data;
		}
	}

	/**
	 * 账户状态
	 * @param $type
	 * @return string
	 */
	public function accountStatus($type)
	{
		$status = '';
		switch ($type) {
			case 0:
				$status = '新增';
				break;

			case 1:
				$status = '审核中';
				break;
			case 2:
				$status = '已注册';
				break;
			case 3:
				$status = '注册成功';
				break;
			case 4:
				$status = '已交接';
				break;
			case 5:
				$status = '已回收';
				break;
			case 6:
				$status = '已作废';
				break;
		}
		return $status;
	}

	/**
	 * 获取搜索条件
	 * @param $params
	 * @return array
	 */
	public function getSearchCondition($params)
	{
		date_default_timezone_set("PRC");
		$condition = [];
		// 可查看全部平台权限人员，默认显示Ebay平台,登录人的职位权限显示对应账号数据
		if (isset($params['account_id']) && !empty($params['account_id'])) {
			$condition['a.account_id'] = $params['account_id'];
		}
		if (isset($params['channel_id']) && !empty($params['channel_id'])) {
			$condition['a.channel_id'] = $params['channel_id'];
		}
		if (isset($params['site']) && !empty($params['site'])) {
			$condition['a.site'] = $params['site'];
		}
		if (isset($params['account_id']) && !empty($params['account_id'])) {
			$condition['a.account_id'] = $params['account_id'];
		}

		if (isset($params['a.id']) && !empty($params['a.id'])) {
			$condition['a.id'] = $params['a.id'];
		}

		// 不同的权限查看不同
		if (isset($params['seller_id']) && !empty($params['seller_id'])) {
			$condition['a.seller_id'] = $params['seller_id'];
		}

		// 数量数据
		$search_type = $this->getSearchType($params);
		// 日期数据
		$time_field = $this->getTimeField($params);

		return [$search_type, $time_field, $condition];
	}

	/**
	 * 开始搜索
	 * @param array $params
	 * @param int $page
	 * @param int $pageSite
	 * @return false|\PDOStatement|string|\think\Collection
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function doSearch(array $params = [], $page = 1, $pageSite = 10)
	{
		$field = $this->searchField($params);
		$this->accountOperationModel->alias('a');
		$this->accountOperationModel->field($field);
		$this->accountOperationModel->join('`account` ac', '`ac`.`id` = `a`.`account_id`', 'left');
		$this->whereCondition($params);
		$this->accountOperationModel->order($this->getOrder($params));

		return $this->accountOperationModel->page($page, $pageSite)->select();

	}

	/**
	 * 搜索字段
	 * @param $params
	 * @return string
	 */
	public function searchField($params)
	{
		$days = $this->getDays($params);
		$fields = '`a`.`dateline`,' .
			'`a`.`id`,' .
			'`a`.`channel_id`,' .
			'`a`.`seller_id`,' .
			'`a`.`account_id`,' .
			'`a`.`publish_quantity`,' .
			'`a`.`online_listing_quantity`,' .
			'`a`.`sale_amount`,' .
			'`a`.`order_quantity`,' .
			'`a`.`odr`,' .
			'`a`.`virtual_order_quantity`,' .
			'`a`.`online_asin_quantity`,' .
			"((sum(`a`.`order_quantity`)/{$days}) / (sum(`a`.`online_asin_quantity`)/{$days})) as  average_retail_rate,".
			'`ac`.`account_create_time`,' .
			'`ac`.`fulfill_time` As account_transition_time ';
		return $fields;
	}

	/**
	 * 获取时间
	 * @param $params
	 * @return float|int|string
	 */
	public function getDays($params)
	{
		date_default_timezone_set("PRC");
		$today = strtotime(date('Y-m-d', time()));
		$days = 0;
		if ($params['date_from'] !== '' || $params['date_to'] !== '') {
			$date_from = strtotime($params['date_from']);
			$data_to = strtotime($params['date_to']);

			if ($date_from == '') {
				$days = round(($today - $data_to) / 3600 / 24) + 1;
			} elseif ($data_to == '') {
				$days = round(($today - $date_from) / 3600 / 24) + 1;
			} else {
				$days = round(($data_to - $date_from) / 3600 / 24) + 1;
			}
		}


		if (empty($days)) {
			$days = $this->accountOperationModel->group('dateline')->count('dateline');
		}
		return $days;
	}

	/**
	 * 获取排序
	 * @param $param
	 * @return string
	 */
	public function getOrder($param)
	{
		$order = '';
		if (empty($param['sort_field'])) {
			$order = 'dateline asc';
		}

		if (isset($param['sort_field']) && !empty($param['sort_field'])) {
			if (trim($param['sort_field']) == 'account_create_time') {
				$sort_field_name = 'ac.' . $param['sort_field'];
			} else {
				$sort_field_name = 'a.' . $param['sort_field'];
			}

			$sort_type = $param['sort_type'] ?? 'asc';
			$order = $sort_field_name . ' ' . $sort_type;
		}
		return $order;
	}

	/**
	 * 回写 刊登数量 昨日在线listing数量 在线asin数量
	 * @param $channel_id
	 * @param $account_id
	 * @param array $data publish_quantity,online_listing_quantity,online_asin_quantity
	 * @param int $time
	 * @return bool
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function writePublishQuantity($channel_id, $account_id, array $data = [], $time = 0)
	{
		date_default_timezone_set("PRC");
		$add = [];
		if (empty($time)) {
			$time = strtotime(date('Y-m-d', time()));
		}
		if (empty($channel_id) || empty($account_id)) {
			return false;
		}
		if (!empty($data)) {
			if (isset($data['publish_quantity'])) {
				//刊登数量
				$add['publish_quantity'] = $data['publish_quantity'];
			}
			if (isset($data['online_listing_quantity'])) {
				//昨日在线listing数量
				$add['online_listing_quantity'] = $data['online_listing_quantity'];
			}
			if (isset($data['online_asin_quantity'])) {
				//在线asin数量
				$add['online_asin_quantity'] = $data['online_asin_quantity'];
			}
		}


		$where = [];
		$where['channel_id'] = $channel_id;
		$where['account_id'] = $account_id;
		$where['dateline'] = $time;
		try {
			if ($this->accountOperationModel->where($where)->find()) {
				$this->accountOperationModel->where($where)->update($add);
			} else {
				$add['channel_id'] = $channel_id;
				$add['account_id'] = $account_id;
				$add['dateline'] = $time;
				$this->accountOperationModel->isUpdate(false)->save($add);
			}

			return true;
		} catch (Exception $ex) {
			throw new JsonErrorException($ex->getMessage());
		}


	}

	/**
	 * 回写 销售额 订单量 odr 智持订单数量
	 * @param $channel_id
	 * @param $account_id
	 * @param array $data sale_amount order_quantity odr virtual_order_quantity
	 * @param int $time
	 * @return bool
	 */
	public function writeSaleAmount($channel_id, $account_id, array $data = [], $time = 0)
	{
		date_default_timezone_set("PRC");
		$add = [];
		if (empty($time)) {
			$time = strtotime(date('Y-m-d', time()));
		}
		if (empty($channel_id) || empty($account_id)) {
			return false;
		}
		if (!empty($data)) {
			if (isset($data['sale_amount'])) {
				//销售额
				$add['sale_amount'] = $data['sale_amount'];
			}
			if (isset($data['order_quantity'])) {
				//订单量
				$add['order_quantity'] = $data['order_quantity'];
			}
			if (isset($data['odr'])) {
				//订单缺陷率
				$add['odr'] = $data['odr'];
			}
			if (isset($data['virtual_order_quantity'])) {
				//智持订单数量
				$add['virtual_order_quantity'] = $data['virtual_order_quantity'];
			}
		}


		$where = [];
		$where['channel_id'] = $channel_id;
		$where['account_id'] = $account_id;
		$where['dateline'] = $time;
		try {
			if ($this->accountOperationModel->where($where)->find()) {
				$this->accountOperationModel->where($where)->update($add);
			} else {
				$add['channel_id'] = $channel_id;
				$add['account_id'] = $account_id;
				$add['dateline'] = $time;
				$this->accountOperationModel->isUpdate(false)->save($add);
			}
			return true;
		} catch (Exception $ex) {
			throw new JsonErrorException($ex->getMessage());
		}
	}

}