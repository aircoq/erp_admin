<?php

namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\AccountApplyLog;
use app\common\service\Common;
use app\index\service\AccountApplyService;
use think\Exception;
use think\Request;
use app\common\service\Common as CommonService;
use app\common\model\AccountApply as AccountApplyModel;

/**
 * @module 基础设置
 * @title 账号基础信息申请
 * @author phill
 * @url account-apply
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/22
 * Time: 17:45
 */
class AccountApply extends Base
{
    protected $accountApplyService;

    protected function init()
    {
        if (is_null($this->accountApplyService)) {
            $this->accountApplyService = new AccountApplyService();
        }
    }

    /**
     * @title 显示资源列表
     * @return \think\response\Json
     * @apiRelate app\index\controller\User::staffs
     * @apiRelate app\order\controller\Order::account
     * @apiRelate app\order\controller\Order::channel
     */
    public function index()
    {
        $param = $this->request->param();
        try {
            $page = $param['page'] ?? 1;
            $pageSize = $param['pageSize'] ?? 50;
            $accountApplyService = new AccountApplyService();
            $result = $accountApplyService->index($page, $pageSize, $param);
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
     * @title 保存新建的资源
     * @param  \think\Request $request
     * @return \think\Response
     * @apiRelate app\index\controller\User::staffs
     */
    public function save()
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $accountApplyService = new AccountApplyService();
            $result = $accountApplyService->saveBase($param, $userInfo);
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
     * @title 显示指定的资源
     * @param  int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $result = $this->accountApplyService->read($id);
        return json($result, 200);
    }

    /**
     * @title 显示编辑资源表单页.
     * @param  int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $result = $this->accountApplyService->read($id);
        return json($result, 200);
    }

    /**
     * @title 保存更新[基本资料]
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
     * @apiRelate app\index\controller\User::staffs
     */
    public function update($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $accountApplyService = new AccountApplyService();
            $result = $accountApplyService->updateBase($id, $param, $userInfo);
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
     * @title 保存更新[注册信息]
     * @param  \think\Request $request
     * @url :id/register
     * @method put
     * @param  int $id
     * @return \think\Response
     * @apiRelate app\index\controller\User::staffs
     */
    public function updateRegister($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $accountApplyService = new AccountApplyService();
            $result = $accountApplyService->register($id, $param, $userInfo);
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
     * @title 获取[注册信息]
     * @param $id
     * @author starzhan <397041849@qq.com>
     * @url :id/register
     */
    public function getRegister($id)
    {
        try {
            $accountApplyService = new AccountApplyService();
            $result = $accountApplyService->getRegister($id);
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
     * @title 查看密码
     * @method get
     * @url password
     * @author starzhan <397041849@qq.com>
     */
    public function viewPassword()
    {
        $param = $this->request->param();
        try {
            if (!isset($param['detail_id']) || !$param['detail_id']) {
                throw new Exception('详情id不能为空!');
            }
            if (!isset($param['password']) || !$param['password']) {
                throw new Exception('密码不能为空!');
            }
            $accountApplyService = new AccountApplyService();
            $result = $accountApplyService->viewPassword($param['detail_id'], $param['password']);
            return json($result,200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage(),
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 保存更新[审核]
     * @url :id(\d+)/audit
     * @method put
     * @param int $id
     * @return \think\Response
     * @apiRelate app\index\controller\User::staffs
     */
    public function updateAudit($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $accountApplyService = new AccountApplyService();
            $result = $accountApplyService->updateAudit($id, $param, $userInfo);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage(),
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 获取信息[审核]
     * @param $id
     * @method get
     * @url :id(\d+)/audit
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function getAudit($id)
    {
        try {
            $accountApplyService = new AccountApplyService();
            $result = $accountApplyService->getAudit($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage(),
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 保存更新[注册结果]
     * @param  \think\Request $request
     * @url :id/result
     * @method put
     * @param  int $id
     * @return \think\Response
     * @apiRelate app\index\controller\User::staffs
     */
    public function updateResult($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $accountApplyService = new AccountApplyService();
            $result = $accountApplyService->updateResult($id, $param, $userInfo);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage(),
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 获取注册结果
     * @url :id/result
     * @method get
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function getResult($id)
    {
        try {
            $accountApplyService = new AccountApplyService();
            $result = $accountApplyService->getResult($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage(),
            ];
            return json($err, 400);
        }
    }

    /**
     * @title 更改账号状态
     * @url batch/:type
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function batch(Request $request)
    {
        $ids = $request->post('ids', '');
        $status = $request->post('status', -1);
        $data['type_msg'] = $request->post('type_msg', '');

        if (empty($ids) || $status < 0) {
            return json(['message' => '参数值不能为空']);
        }
        $params = $request->param();
        $type = $params['type'];
        $data['status'] = $status;
        if ($data['status'] == AccountApplyModel::status_registerFail && $data['type_msg'] == '') {
            json(['message' => '审核失败时，备注信息必填'], 400);
        }
        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['updater_id'] = $user['user_id'];
        }
        $ids = json_decode($ids, true);
        $this->accountApplyService->status($ids, $data, $type);
        return json(['message' => '更改成功']);
    }


    /**
     * @title 服务器已绑定的账号列表
     * @url already-bind
     * @param Request $request
     * @return \think\response\Json
     */
    public function alreadyBind(Request $request)
    {
        $channel_id = $request->get('channel_id', 0);
        $server_id = $request->get('server_id', 0);
        if (empty($channel_id) || empty($server_id)) {
            return json(['message' => '参数不正确']);
        }
        $result = (new AccountApplyService())->alreadyBind($channel_id, $server_id);
        return json($result);
    }

    /**
     * @title 自动识别图片
     * @url automatic
     * @param Request $request
     * @return \think\response\Json
     */
    public function automatic(Request $request)
    {
        $type = $request->get('type', 0); //类型 1为身份证，2为营业执照
        $image = $request->get('image', 0);
        if (empty($channel_id) || empty($server_id)) {
            return json(['message' => '参数不正确']);
        }
        $result = (new AccountApplyService())->automatic($type, $image, $request);
        return json($result);
    }


    /**
     * @title 日志
     * @url :id/log
     * @method get
     */
    public function log($id)
    {
        $result = (new AccountApplyLog())->getLog($id);
        return json($result, 200);
    }

    /**
     * @title 读取运营负责人
     * @url user
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function getUser(Request $request)
    {
        $user = new User();
        return $user->index($request);
    }

    /**
     * @title 获取状态
     * @url status
     * @method get
     * @param Request $request
     * @return \think\response\Json
     */
    public function status()
    {
        $accountApplyService = new accountApplyService();
        $result = $accountApplyService->statusChangeInfo();
        return json($result);
    }

    /**
     * @title 选中公司数据后带出信息
     * @url :company_id(\d+)/:channel_id(\d+)/relate-info
     * @param $company_id
     * @param $channel_id
     * @author starzhan <397041849@qq.com>
     */
    public function relateInfo($company_id, $channel_id)
    {
        $accountApplyService = new accountApplyService();
        $result = $accountApplyService->relateInfo($company_id, $channel_id);
        return json($result, 200);
    }


}