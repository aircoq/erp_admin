<?php
/**
 * Created by PhpStorm.
 * User: Zhuda
 * Date: 2019/3/20
 * Time: 14:56
 */

namespace app\index\controller;

use think\Request;
use app\common\controller\Base;
use app\index\service\WorldfirstService;
use app\common\service\Common as CommonService;
use app\common\validate\WorldfirstValidate;


/**
 * @module 收款账号管理
 * @title WF万里汇账户管理
 * @url /worldfirst
 * @author zhuda
 * @package app\index\controller
 */
class WorldfirstAccount extends Base
{

    protected $service;

    public function __construct()
    {
        parent::__construct();
        if (is_null($this->service)) {
            $this->service = new WorldfirstService();
        }
    }

    /**
     * @title 万里汇账号列表
     * @method GET
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $result = $this->service->getWorldfirstList();
        return json($result, 200);
    }

    /**
     * @title 显示账号详细.
     * @param $id
     * @method GET
     * @url /worldfirst/:id/edit
     * @return \think\response\Json
     * @throws \think\exception\DbException
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
     * @url /worldfirst/add
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        $validate = new WorldfirstValidate();
        $data = $request->param();
        $time = time();
        $data['create_time'] = $time;
        $data['update_time'] = $time;
        $result = $validate->scene('add')->check($data);

        if ($result !== true) {
            return json(['message' => $validate->getError()], 400);
        }

        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['create_id'] = $user['user_id'];
            $data['updater_id'] = $user['user_id'];
        }
        $data['status'] = $request->param('status', '0');

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
     * @url /worldfirst/:id/save
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function update(Request $request, $id)
    {
        $validate = new WorldfirstValidate();
        $data = $request->param();
        $data['update_time'] = time();
        $result = $validate->scene('edit')->check($data);

        if ($result !== true) {
            return json(['message' => $validate->getError()], 400);
        }

        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['updater_id'] = $user['user_id'];
        }
        unset($data['wf_account']);
        $result = $this->service->update($id, $data);
        if (!$result) {
            return json(['message' => $this->service->getError()], 400);
        }
        return json(['message' => '更改成功', 'data' => $result], 200);
    }

    /**
     * @title 显示密码
     * @param Request $request
     * @method GET
     * @url /worldfirst/show-pw
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function showPassword(Request $request)
    {
        $id = $request->get('id', 0);
        $password = $request->get('password', 0);
        $type = $request->get('type', 0);
        if (empty($id) || empty($password) || empty($type)) {
            return json(['message' => '参数值不能为空'], 400);
        }
        $result = $this->service->viewPassword($password, $id, $type);

        if ($result === false) {
            return json(['message' => $this->service->getError()], 400);
        }
        return json(['password' => $result], 200);
    }

    /**
     * @title 编辑状态
     * @param $id
     * @method GET
     * @url /worldfirst/:id/status/:status
     * @return \think\response\Json
     * @throws \think\exception\DbException
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