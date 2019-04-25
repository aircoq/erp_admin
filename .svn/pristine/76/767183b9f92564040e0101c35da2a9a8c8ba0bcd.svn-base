<?php
namespace app\index\controller;

use app\common\service\Common;
use app\index\service\LocalBuyerAccountService;
use think\Request;

/**
 * @module 基础设置
 * @title 本地买手管理
 * @url local-buyers
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/10/30
 * Time: 16:03
 */
class LocalBuyerAccount
{
    /**
     * @title 本地买手列表
     * @param Request $request
     * @apiRelate app\order\controller\Order::channel
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $where = [];
        $params = $request->param();
        if ($server_id = param($params, 'server_id')) {
            $where['server_id'] = ['eq', $server_id];
        }
        if ($channel_id = param($params, 'channel_id')) {
            $where['channel_id'] = ['eq', $channel_id];
        }
        if ($status = param($params, 'status')) {
            $where['status'] = ['eq', $status];
        }
        if ($account_creator = param($params, 'account_creator')) {
            $where['account_creator'] = ['eq', $account_creator];
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'email':
                    $where['email'] = ['like', $params['snText'] . '%'];
                    break;
                case 'username':
                    $where['username'] = ['like', $params['snText'] . '%'];
                    break;
                default:
                    break;
            }
        }
        $localBuyerAccountService = new LocalBuyerAccountService();
        $result = $localBuyerAccountService->lists($where, $page, $pageSize);
        return json($result, 200);
    }

    /**
     * @title 获取服务器信息
     * @param $id
     * @return \think\response\Json
     */
    public function edit($id)
    {
        $localBuyerAccountService = new LocalBuyerAccountService();
        $result = $localBuyerAccountService->info($id);
        return json($result, 200);
    }

    /**
     * @title 保存服务器信息
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $user = Common::getUserInfo();
        if (!empty($user)) {
            $params['creator_id'] = $user['user_id'];
        }
        $params['create_time'] = time();
        $params['update_time'] = time();
        $validateLocalBuyerAccount = validate('LocalBuyerAccount');
        if (!$validateLocalBuyerAccount->check($params)) {
            return json(['message' => $validateLocalBuyerAccount->getError()], 400);
        }
        $localBuyerAccountService = new LocalBuyerAccountService();
        $id = $localBuyerAccountService->add($params);
        $result = $localBuyerAccountService->lists(['l.id' => $id])['data'][0];
        return json(['message' => '新增成功', 'data' => $result], 200);
    }

    /**
     * @title 更新服务器信息
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        $user = Common::getUserInfo();
        if (!empty($user)) {
            $params['updater_id'] = $user['user_id'];
        }
        $params['update_time'] = time();
        $validateLocalBuyerAccount = validate('LocalBuyerAccount');
        if (!$validateLocalBuyerAccount->check($params)) {
            return json(['message' => $validateLocalBuyerAccount->getError()], 400);
        }
        unset($params['id']);
        $localBuyerAccountService = new LocalBuyerAccountService();
        $localBuyerAccountService->update($params, $id);
        $result = $localBuyerAccountService->lists(['l.id' => $id])['data'][0];
        return json(['message' => '修改成功', 'data' => $result], 200);
    }

    /**
     * @title 删除服务器信息
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        $localBuyerAccountService = new LocalBuyerAccountService();
        $localBuyerAccountService->batch($id);
        return json(['message' => '删除成功'], 200);
    }

    /**
     * @title 批量删除
     * @url batch
     * @method post
     * @return \think\response\Json
     */
    public function batch()
    {
        $request = Request::instance();
        $ids = $request->post('ids', '');
        if (!is_json($ids)) {
            return json(['message' => '参数格式错误'], 400);
        }
        $ids = json_decode($ids, true);
        $localBuyerAccountService = new LocalBuyerAccountService();
        $localBuyerAccountService->batch($ids);
        return json(['message' => '删除成功']);
    }

    /**
     * @title 显示密码
     * @url password
     * @param  \think\Request $request
     * @return \think\Response
     */
    public function show(Request $request)
    {
        $id = $request->get('id', 0);
        $password = $request->get('password', 0);
        $type = $request->get('type', 0);
        if (empty($id) || empty($password) || empty($type)) {
            return json(['message' => '参数值不能为空'], 400);
        }
        $localBuyerAccountService = new LocalBuyerAccountService();
        $enablePassword = $localBuyerAccountService->viewPassword($password, $id, $type);
        return json(['password' => $enablePassword]);
    }
}