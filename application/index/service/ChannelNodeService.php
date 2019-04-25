<?php

namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\ChannelNode;
use app\common\cache\Cache;
use app\common\service\Common;
use think\Exception;
use think\Request;
use think\Db;

/**
 * Created by PhpStorm.
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/10/22
 * Time: 14:46
 */
class ChannelNodeService
{
	const firstSite = 1;     // 为主网址
	const notFirstSite = 0; // 不为主网址

	protected $channelNodeServer;

	public function __construct()
	{
		if (is_null($this->channelNodeServer)) {
			$this->channelNodeServer = new ChannelNode();
		}
	}

	/** 账号列表
	 * @param Request $request
	 * @return array
	 * @throws \think\Exception
	 */
	public function lists(Request $request)
	{
		$where = [];
		$params = $request->param();
		if (isset($params['channel_id']) && $params['channel_id'] !== '') {
			$where['channel_id'] = $params['channel_id'];
		}
		if (isset($params['channel_site']) && $params['channel_site'] !== '') {
			$where['channel_site'] = $params['channel_site'];
		}
		if (isset($params['type']) && $params['type'] != - 1 && !empty($params['type'])) {
			$where['type'] = $params['type'];
		}

		// 初始化网址
//		$this->checkFirstSite();

		$order = 'id';
		$sort = 'desc';
		$page = $request->get('page', 1);
		$pageSize = $request->get('pageSize', 10);
		$field = '*';
		$count = $this->channelNodeServer->field($field)->where($where)->count();
		$accountList = $this->channelNodeServer->field($field)
			->where($where)
			->order($order, $sort)
			->page($page, $pageSize)
			->select();
		foreach ($accountList as $k => &$v) {
			$v['channel_name'] = Cache::store('Channel')->getChannelName($v['channel_id']);
			$v['node_info'] = json_decode($v['node_info'], true);
			$userInfo = Cache::store('user')->getOneUser($v['creator_id']);
			$userName = '';
			if (!empty($userInfo)) {
				$userName = $userInfo['realname'];
			}
			$roleName = (new Role())->getRoleNameByUserId($v['creator_id']);
			$v['creator_id'] = $roleName . '-' . $userName;
			$roleName = (new Role())->getRoleNameByUserId($v['updater_id']);
			$userInfo = Cache::store('user')->getOneUser($v['updater_id']);
			$userName = $userInfo['realname'] ?? '';
			$v['updater_id'] = $roleName . '-' . $userName;
		}
		$result = [
			'data' => $accountList,
			'page' => $page,
			'pageSize' => $pageSize,
			'count' => $count,
		];
		return $result;
	}

	/**
	 * 检测是否需要重置主网址
	 * @throws \think\Exception
	 */
	public function checkFirstSite()
	{
		$preId = $this->getPreFirstSiteIds();
		$preNum = count($preId);
		$firstSiteNum = $this->getFirstNum();

		// 预设主网址数与平台实际主网址数不等，则重置平台主网址
		if ($preNum !== $firstSiteNum) {
			$this->setAllFirstSite($preId);
		}
	}


	/**
	 * 预设主网址IDs
	 * @return int|string
	 */
	public function getPreFirstSiteIds()
	{
		// 预定义平台主网址
		$firstSiteIDs = [];
		// 获取所有平台
		$res = $this->channelNodeServer->field('min(id) id ')->group('channel_id,channel_site')->select();
		foreach ($res as $key => $val) {
			array_push($firstSiteIDs, $val['id']);
		}

		return $firstSiteIDs;
	}

	/**
	 * 获取主网址数
	 * @return int|string
	 */
	public function getFirstNum()
	{
		$firstNum = $this->channelNodeServer->field('id,channel_id,first_site')->where('first_site', self::firstSite)->count();
		return $firstNum;
	}

	/**
	 * 设置各平台主网址
	 * @throws Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function setAllFirstSite($preId)
	{


		if (!empty($preId)) {
			try {
				Db::startTrans();
				// 初始化
				$data['first_site'] = 0;
				$this->channelNodeServer->where('first_site', 1)->update($data);
				foreach ($preId as $k => $item) {
					$data['first_site'] = 1; // 为主网址
					$data['id'] = $item;
					// 设置主网址
					$this->channelNodeServer->update($data);
				}
				Db::commit();
			} catch (Exception $ex) {
				Db::rollback();
				$msg = $ex->getMessage();
				throw new Exception($msg);
			}
		}

	}


	/**
	 * @param $data
	 * @return array|false|\PDOStatement|string|\think\Model
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function saveOld($data)
	{
		$time = time();
		unset($data['name']);
		$data['create_time'] = $time;
		$data['update_time'] = $time;
		if ($this->channelNodeServer->isHas($data['channel_id'], $data['channel_site'], $data['website_url'])) {
			throw new JsonErrorException('账号已经存在', 500);
		}
		$this->channelNodeServer->allowField(true)->isUpdate(false)->save($data);
		//获取最新的数据返回
		$new_id = $this->channelNodeServer->id;
		return $this->read($new_id);
	}

	/**
	 * 保存信息
	 * @param $data
	 * @return array|false|\PDOStatement|string|\think\Model
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function save($data)
	{
		$time = time();
		$data['create_time'] = $time;
		$data['update_time'] = $time;
		if ($this->channelNodeServer->isHas($data['channel_id'], $data['channel_site'], $data['website_url'])) {
			throw new JsonErrorException('账号已经存在', 500);
		}
		$userInfo = Common::getUserInfo();
		$data['creator_id'] = $data['updater_id'] = $userInfo['user_id'] ?? 0;

		try {
			Db::startTrans();
			// 是否可以为主网址
			/*$canFirstSite = $this->canFirstSite($data['channel_id'], $data['channel_site']);
			if (isset($canFirstSite['first_site'])) {
				$data['first_site'] = self::firstSite;
			}*/
			$this->channelNodeServer->allowField(true)->isUpdate(false)->save($data);

			//获取最新的数据返回
			$new_id = $this->channelNodeServer->id;
			Db::commit();
			return $this->read($new_id);
		} catch (Exception $ex) {
			Db::rollback();
			throw new JsonErrorException($ex->getMessage());
		}


	}


	/**
	 * 账号信息
	 * @param $id
	 * @return array|false|\PDOStatement|string|\think\Model
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function read($id)
	{
		$accountInfo = $this->channelNodeServer->field(true)->where(['id' => $id])->find();
		if (empty($accountInfo)) {
			throw new JsonErrorException('账号不存在', 500);
		}
		$userName = '';
		$userInfo = Cache::store('user')->getOneUser($accountInfo['creator_id']);
		if (!empty($userInfo)) {
			$userName = $userInfo['realname'];
		}
		$roleName = (new Role())->getRoleNameByUserId($accountInfo['creator_id']);
		$accountInfo['creator_id'] = $roleName . $userName;
		$accountInfo['node_info'] = json_decode($accountInfo['node_info'], true);
		$accountInfo['verification_node_info'] = json_decode($accountInfo['verification_node_info'], true);
		$roleName = (new Role())->getRoleNameByUserId($accountInfo['updater_id']);
		$userInfo = Cache::store('user')->getOneUser($accountInfo['updater_id']);
		$userName = $userInfo['realname'] ?? '';
		$accountInfo['updater_id'] = $roleName . '-' . $userName;
		$accountInfo['type'] = strval($accountInfo['type']);
		return $accountInfo;
	}

	/**
	 * 更新
	 * @param $id
	 * @param $data
	 * @return array|false|\PDOStatement|string|\think\Model
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function update($id, $data)
	{
		$oldData = $this->channelNodeServer->isHas($data['channel_id'], $data['channel_site'], $data['website_url']);

		if ($oldData && $oldData['id'] != $id) {
			throw new JsonErrorException('账号已经存在', 500);
		}
		$userInfo = Common::getUserInfo();

		// 是否可以为主网址
		/*	$canFirstSite = $this->canFirstSite($data['channel_id'], $data['channel_site']);
			if (isset($canFirstSite['first_site'])) {
				$data['first_site'] = self::firstSite;
			}*/

		$data['update_time'] = time();
		$data['updater_id'] = $userInfo['user_id'];
		$this->channelNodeServer->allowField(true)->save($data, ['id' => $id]);
		return $this->read($id);
	}


	/**
	 * 删除信息
	 * @param $id
	 * @return int
	 */
	public function delete($id)
	{
		try {
			Db::startTrans();
			// 判断是否为主网址ID
			/*$fields = 'id, channel_id,first_site,channel_site';
			$info = $this->channelNodeServer->field($fields)->where('first_site', self::firstSite)->find($id);

			if ($info) {
				$this->isFirstSite($info);
			}*/

			$this->channelNodeServer->where('id', $id)->delete();
			Db::commit();
			return json(['message' => '删除成功'], 200);
		} catch (Exception $ex) {
			Db::rollback();
			throw new JsonErrorException($ex->getMessage());
		}
	}

	public $nodeType = [
		'输入框',
		'提交按钮',
		'验证码',
	];

	public function nodeTpye()
	{
		$reData = [];
		foreach ($this->nodeType as $k => $v) {
			$reData[] = [
				'value' => $k,
				'name' => $v,
			];
		}
		return $reData;
	}

	public function channelType()
	{
		$data = [
			'基础资料',
			'Paypal',
			'邮局',
			'海卖助手',

		];
		$newData = [];
		foreach ($data as $k => $v) {
			$newData[] = [
				'value' => $k,
				'name' => $v,
			];

		}
		return $newData;
	}

	/**
	 * 设置主网址/取消主网址
	 * @param $id
	 * @param $setType
	 * @return string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function firstSite($id, $setType)
	{
		$channelNodeInfo = $this->channelNodeServer->field('id,channel_id,channel_site')->find($id);
		if (!$channelNodeInfo) {
			throw new JsonErrorException('平台ID必填');
		}
		if(empty($setType)){
			throw new JsonErrorException('设置类型不能为空');
		}

		try {
			Db::startTrans();
			$update = [];
			if ($setType == 'first_site') {
				// 判断该站点是否有主网址
				$result = $this->isExistFirstSite($channelNodeInfo);
				if(!empty($result)){
					return '该站点或平台已有主网址';
				}
				$update['first_site'] = self::firstSite;
				$this->channelNodeServer->where('id', $id)->update($update);
			} elseif ($setType == 'not_first_site') {
				$update['first_site'] = self::notFirstSite;
				$this->channelNodeServer->where('id', $id)->update($update);
			}
			Db::commit();
			return '设置成功';
		} catch (Exception $ex) {
			Db::rollback();
			throw new JsonErrorException($ex->getMessage());
		}
	}

	public function isExistFirstSite($channelNodeInfo)
	{
		$where = [];
		$where['channel_id'] = $channelNodeInfo['channel_id'];
		$where['channel_site'] = $channelNodeInfo['channel_site'];
		$where['first_site'] = self::firstSite;
		$result = $this->channelNodeServer->where($where)->whereNotIn('id', $channelNodeInfo['id'])->find();

		return $result;

	}

	public function _firstSite($id)
	{
		$channelNodeInfo = $this->channelNodeServer->field('id,channel_id,channel_site')->find($id);
		if (!$channelNodeInfo) {
			throw new JsonErrorException('平台ID必填');
		}

		try {
			Db::startTrans();
			$update = [];
			// 取消旧主网址
			$condition['channel_id'] = $channelNodeInfo['channel_id'];
			$condition['channel_site'] = $channelNodeInfo['channel_site'];
			$condition['first_site'] = self::firstSite;
			$res = $this->channelNodeServer->where($condition)->find();
			if ($res) {
				$update['first_site'] = self::notFirstSite;
				$this->channelNodeServer->where('id', $res['id'])->update($update);
			}

			// 更新主网址
			$update['first_site'] = self::firstSite;
			$this->channelNodeServer->where('id', $channelNodeInfo['id'])->update($update);
			Db::commit();
		} catch (Exception $ex) {
			Db::rollback();
			throw new JsonErrorException($ex->getMessage());
		}
	}


	/**
	 * 是否可以为主网址
	 * @param $channel_id
	 * @param $channel_site
	 * @return array
	 * @throws Exception
	 */
	public function canFirstSite($channel_id, $channel_site)
	{
		$data = [];
		$where['channel_id'] = $channel_id;
		$where['channel_site'] = $channel_site;
		$whereChannel['channel_id'] = $channel_id;
		$this->channelNodeServer->field('id');

		// 是否新平台或是否为平台新站点
		if (empty($this->channelNodeServer->where($whereChannel)->find()) || empty($this->channelNodeServer->where($where)->find())) {
			$data['first_site'] = self::firstSite;
		}
		return $data;

	}

	/**
	 * 主网址
	 * @param $info
	 */
	public function isFirstSite($info)
	{
		$res = $this->newFirstSiteId($info);
		// 如果设置站点新主网址
		if (!empty($res['id'])) {
			$update['first_site'] = self::firstSite;
			$where['id'] = $res['id'];
			$this->channelNodeServer->where($where)->update($update);
		}
	}


	/**
	 * 删除后新的主网址
	 * @param $info
	 * @return array|false|\PDOStatement|string|\think\Model
	 */
	public function newFirstSiteId($info)
	{
		// 判断站点是否有重复
		$where = [];
		$where['channel_id'] = $info['channel_id'];
		$where['channel_site'] = $info['channel_site'];

		$this->channelNodeServer->where($where);
		$this->channelNodeServer->where('id', 'neq', $info['id']);
		$res = $this->channelNodeServer->find();

		return $res;
	}

}