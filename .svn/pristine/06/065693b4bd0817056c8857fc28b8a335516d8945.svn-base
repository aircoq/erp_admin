<?php
/**
 * Created by PhpStorm.
 * User: Zhuda
 * Date: 2019/3/20
 * Time: 15:00
 */

namespace app\index\controller;

use think\Request;
use app\common\controller\Base;
use app\index\service\PingpongService;
use app\common\service\Common as CommonService;
use app\common\validate\Pingpong as PingpongValidate;

/**
 * @module 收款账号管理
 * @title pingpong账户管理
 * @url /pingpong
 * @author zhuda
 * @package app\index\controller
 */
class PingpongAccount extends Base
{
    protected $service;

    public function __construct()
    {
        parent::__construct();
        if (is_null($this->service)) {
            $this->service = new PingpongService();
        }
    }

    /**
     * @title Pingpong账号列表
     * @method GET
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function index()
    {
        $result = $this->service->getPingpongList();
        return json($result, 200);
    }

    /**
     * @title 显示账号详细.
     * @param $id
     * @method GET
     * @url /pingpong/:id/edit
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function edit($id)
    {
        $result = $this->service->read($id);

        if (!$result) {
            return json(['message' => $this->service->getError()], 400);
        }
        return json($result, 200);
    }

    /**
     * @title 新增万里汇账号记录
     * @method POST
     * @url /pingpong/add
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        //获取操作人信息
        $user = CommonService::getUserInfo();
        if (empty($user)) {
            return json(['message' => '参数错误'], 400);
        }

        $data = $request->param();
        $validate = new PingpongValidate();
        $result = $validate->scene('add')->check($data);
        if ($result !== true) {
            return json(['message' => $validate->getError()], 400);
        }

        $time = time();
        $data['create_id'] = $user['user_id'];
        $data['updater_id'] = $user['user_id'];
        $data['create_time'] = $time;
        $data['update_time'] = $time;
        $data['status'] = $request->post('status', '');
        $data['account_id'] = $request->post('account_id', '');
        $data['site_code'] = $request->post('site_code', '');
        $result = $this->service->save($data);

        if ($result === false) {
            return json(['message' => $this->service->getError()], 400);
        }

        return json(['message' => '新增成功', 'data' => $result]);
    }

    /**
     * @title 修改记录
     * @param Request $request
     * @param $id
     * @method PUT
     * @url /pingpong/:id/save
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function update(Request $request, $id)
    {
        //获取操作人信息
        $user = CommonService::getUserInfo();
        if (empty($user)) {
            return json(['message' => '参数错误'], 400);
        }

        $data = $request->param();
        $validate = new PingpongValidate();
        $result = $validate->scene('edit')->check($data);
        if ($result !== true) {
            return json(['message' => $validate->getError()], 400);
        }
        $data['update_time'] = time();
        $data['updater_id'] = $user['user_id'];
        $data['account_id'] = $request->put('account_id', '');
        $data['site_code'] = $request->put('site_code', '');
        $result = $this->service->update($id, $data);
        if ($result === false) {
            return json(['message' => $this->service->getError()], 400);
        }
        return json(['message' => '更改成功', 'data' => $result], 200);
    }

    /**
     * @title 编辑状态.
     * @param $id
     * @param $status
     * @method GET
     * @url /pingpong/:id/status/:status
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function status($id, $status)
    {
        $result = $this->service->editStatus($id, $status);

        if (!$result) {
            return json(['message' => $this->service->getError()], 400);
        }
        return json(['message' => '变更成功'], 200);
    }


}