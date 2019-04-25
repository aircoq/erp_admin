<?php

namespace app\index\controller;

use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\common\model\ChannelUserAccountMap;
use app\common\service\Channel as ChannelServer;
use app\common\service\ChannelAccountConst;
use app\index\service\AccountUserMapService;
use app\index\service\ChannelService;
use app\index\service\ChannelAccount as ChannelAccountServer;
use app\index\service\JoomShopService;
use app\index\service\AccountService;
use think\Exception;
use think\Request;

/**
 * @module 通用系统
 * @title 平台
 * @url /channel
 * User: PHILL
 * Date: 2017/3/3
 * Time: 10:11
 */
class Channel extends Base
{
    protected $channelService;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        if (is_null($this->channelService)) {
            $this->channelService = new ChannelService();
        }
    }

    /**
     * @title 平台列表
     * @url /channel
     * @method get
     * @return \think\response\Json
     */
    public function index()
    {
        try {
            $request = Request::instance();
            $params = $request->param();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            $channelList = $this->channelService->channelList($params, $page, $pageSize);
            return json($channelList, 200);
        } catch (Exception $exp) {
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 平台select列表(公共)
     * @url /global/channels
     */
    public function channels(ChannelServer $server)
    {
        $channels = $server->getOptions();
        return json($channels);
    }

    /**
     * @title 平台账号
     * @url channelAccounts
     */
    public function channelAccounts(ChannelAccountServer $accounts, Request $request)
    {
        $params = $request->param();
        $accounts = $accounts->getOptionsAccounts($params);
        return json($accounts);
    }

    /**
     * @title 平台详情
     * @method GET
     * @param  int $id
     * @url /channel/:id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        if (empty($id)) {
            return json(['message' => '平台ID未知'], 400);
        }
        $result = $this->channelService->read($id);
        return json($result);
    }

    /**
     * @title 添加平台
     * @method POST
     * @url /channel
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        try {
            $params = $request->param();
            if (!isset($params['title']) || empty($params['title'])) {
                return json(['message' => '平台名称不能为空'], 400);
            }
            if (!isset($params['name']) || empty($params['name'])) {
                return json(['message' => '平台英文名称不能为空'], 400);
            }
            if (!isset($params['is_site']) || $params['is_site'] == '') {
                return json(['message' => '请选择是否有站点'], 400);
            }

            $channel_number = (new \app\common\model\Channel())->count();
            if ($channel_number >= 32) {
                return json(['message' => '平台总数量不能超过32个'], 400);
            }
            $result = $this->channelService->save($params);
            if ($result) {
                return json(['message' => '添加成功', 'data' => $result], 200);
            } else {
                return json(['message' => '添加失败', 'data' => []], 400);
            }
        } catch (Exception $exp) {
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 更新平台
     * @url /channel/:id(\d+)
     * @method put
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function update(Request $request, $id)
    {
        try {
            if ($id == 0) {
                return json(['message' => '请求参数错误'], 400);
            }
            $params = $request->param();
            $result = $this->channelService->update($params, $id);
            if (!empty($result['status'])) {
                return json(['message' => '编辑成功', 'data' => $result['data']], 200);
            } else {
                return json(['message' => '编辑失败', 'data' => $result['data']], 400);
            }
        } catch (Exception $exp) {
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 状态修改
     * @url /channel/states
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function changeStatus(Request $request)
    {
        $params = $request->param();
        if (!isset($params['id']) || empty($params['id'])) {
            return json(['message' => '平台ID未知'], 400);
        }
        if (!isset($params['status']) || $params['status'] == '') {
            return json(['message' => '平台状态未知'], 400);
        }
        $result = $this->channelService->changeStatus($params);
        if ($result) {
            return json(['message' => '操作成功'], 200);
        } else {
            return json(['message' => '操作失败'], 400);
        }
    }

    /**
     * @title 业务员列表
     * @url seller-list
     * @method get
     * @return \think\response\Json
     */
    public function seller(Request $request)
    {
        $params = $request->param();
        if (empty($params['channel_id'])) {
            return json(['message' => '平台ID未知'], 400);
        }
        $result = ChannelUserAccountMap::alias('a')
            ->field('a.seller_id,b.realname as username')
            ->join('user b', 'a.seller_id=b.id', 'LEFT')
            ->where(['channel_id' => $params['channel_id']])
            ->group('seller_id')->having("a.seller_id>0")
            ->select();
        return json($result, 200);
    }

    /**
     * @title 获取渠道占比信息
     * @method get
     * @url :id(\d+)/proportion
     * @noauth
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function getProportion($id)
    {
        $result = $this->channelService->getProportion($id);
        return json($result, 200);
    }

    /**
     * @title 获取当前渠道对应的销售部门
     * @url :id(\d+)/departments
     * @method get
     * @noauth
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function getDepartmentByChannelId($id)
    {
        $result = $this->channelService->getDepartmentByChannelId($id);
        return json($result, 200);
    }

    /**
     * @title 保存渠道占比信息
     * @method post
     * @url :id(\d+)/proportion
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function saveProportion($id)
    {
        $param = $this->request->param();
        try {
            if (!isset($param['lists']) || !$param['lists']) {
                throw new Exception('lists不能为空');
            }
            $lists = json_decode($param['lists'],true);
            $result = $this->channelService->saveProportion($id, $lists);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 平台站点配置列表
     * @return \think\response\Json
     * @url system-list
     */
    public function getSystemConfig(Request $request)
    {
        $params = $request->param();
        $validateConfig = validate('ChannelConfig');
        if (!$validateConfig->scene('system')->check($params)) {
            return json(['message' => $validateConfig->getError()], 400);
        }
        $result = (new ChannelService())->getSystemParams($params['channel_id']);
        return json($result);
    }

    /**
     * @title 新增平台配置
     * @param  \think\Request $request
     * @return \think\Response
     * @url add-config
     */
    public function addConfig($id, Request $request)
    {
        $params = $request->param();
        $validateConfig = validate('ChannelConfig');
        if (!$validateConfig->scene('add')->check($params)) {
            return json(['message' => $validateConfig->getError()], 400);
        }
        (new ChannelService())->addConfig($params['channel_id'], $params);
        return json(['message'=>'添加成功']);
    }

    /**
     * @title 引用系统平台配置
     * @return \think\response\Json
     * @url :id/use-config
     * @method post
     */
    public function useConfig($id, Request $request)
    {
        $params = $request->param();
        $validateConfig = validate('ChannelConfig');
        if (!$validateConfig->scene('use')->check($params)) {
            return json(['message' => $validateConfig->getError()], 400);
        }
        $config_id= json_decode($params['config_id'], true);
        (new ChannelService())->useConfig($id, $config_id);
        return json(['message'=>'添加成功']);
    }

    /**
     * @title 获取平台系统配置
     * @return \think\response\Json
     * @url :id/config
     * @method get
     */
    public function getConfigDetail($id)
    {
        $result =(new ChannelService())->getChannelConfigDetail($id);
        return json($result);
    }

    /**
     * @title 删除平台配置
     * @url config
     * @method delete
     * @return \think\Response
     */
    public function deleteConfig(Request $request)
    {
        $params = $request->param();
        $validateConfig = validate('ChannelConfig');
        if (!$validateConfig->scene('use')->check($params)) {
            return json(['message' => $validateConfig->getError()], 400);
        }
        (new ChannelService())->delete($params['id']);
        return json(['message' => '删除成功'], 200);
    }

    /**
     * @title 更新平台参数配置
     * @param  \think\Request $request
     * @url config
     * @method put
     * @return \think\Response
     */
    public function  updateConfig(Request $request)
    {
        $params = $request->param();
        $validateConfig = validate('ChannelConfig');
        if (!$validateConfig->scene('update')->check($params)) {
            return json(['message' => $validateConfig->getError()], 400);
        }
        $params = $request->param();
        try {
            (new ChannelService())->updateConfig($params['config_id'], $params);
            return json(['message' => '更新成功'], 200);
        }catch (Exception $ex) {
            return json(['message' => '更新失败，'.$ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取平台站点配置
     * @return \think\Response
     * @url config
     * @method get
     */
    public function getConfig(Request $request)
    {
        $params = $request->param();
        $validateConfig = validate('ChannelConfig');
        if (!$validateConfig->scene('delete')->check($params)) {
            return json(['message' => $validateConfig->getError()], 400);
        }
        $result  =  (new ChannelService())->getConfigDetail($params['config_id']);
        return json($result, 200);
    }

    /**
     * @title 参数设置
     * @url :id(\d+)/config
     * @method put
     */
    public function config(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $params = $request->param();
        try{
            (new ChannelService())->setting($id, $params);
            return json(['message'=>'修改成功'], 200);
        }catch(Exception $ex){
            return json(['message'=>$ex->getMessage()], 400);
        }

    }


    /**
     * @title 获取平台/站点账号信息
     * @url account
     * @method get
     */
    public function account(Request $request)
    {
        $channel_id = $request->get('channel_id', 0, 'intval');  //渠道id

        if (empty($channel_id) || !is_numeric($channel_id)) {
            return json(['message' => '参数错误'], 200);
        }

        // 获取已启用，已授权账号信息
        $accountSer = new AccountService();
        $accountAll = $accountSer->accountList($channel_id);

        // 排查账号不再基础表里的数据，例如： joom_account里的账号不在account表中
        if (ChannelAccountConst::channel_Merchant_Distribution != $channel_id) {
            $accountIds = array_filter(array_column($accountAll, 'base_account_id'));
            if (!empty($accountIds)) {
                $ids = $accountSer->accountIsExists($accountIds);
                foreach ($accountAll as $key=>$val){
                    if(!in_array($val['base_account_id'], $ids)){
                        unset($accountAll[$key]);
                    }
                }
            }

        }

        $result = ['data' => []];

        // 不是joom平台， 被绑定账号不显示在账号下拉中
        if (ChannelAccountConst::channel_Joom != $channel_id && !empty($accountAll)) {
            $oldIds = array_column($accountAll, 'id');
            $newIds = (new AccountUserMapService())->bindAccountIds($channel_id, $oldIds);

            foreach ($accountAll as $key=>$val){
                if (!in_array($val['id'], $newIds)){
                    $result['data'][] = $val;
                }
            }
        } else {
            $result['data'] = array_merge([], $accountAll);
        }
        return json($result, 200);
    }


    /**
     * @title joom平台下未绑定的商铺列表
     * @url joom-shop
     * @method get
     */
    public function getJoomShop(Request $request)
    {
        $joom_account_id = $request->get('joom_account_id', 0, 'intval');  //渠道id
        if (empty($joom_account_id) || !is_numeric($joom_account_id)) {
            return json(['message' => '参数错误'], 200);
        }

        // 根据账号id，获取joom平台下的商铺信息
        $shops = (new JoomShopService())->getListByAccountId($joom_account_id);

        // 查询已经绑定的商铺
        $shopIds = (new AccountUserMapService())->getJoomShopIds();

        // 已经绑定的商铺不显示
        $result = ['data' => []];
        foreach ($shops as $key=>$val){
            if(!in_array($val['id'], $shopIds)){
                $result['data'][] = $val;
            }
        }
        return json($result, 200);
    }
}