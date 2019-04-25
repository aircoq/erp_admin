<?php


namespace app\index\controller;

use app\common\controller\Base;
use app\common\service\Common;
use app\index\service\Email as EmailService;
use think\Exception;

/**
 * @title 邮箱号管理
 * @module 基础设置
 * @url /email
 * @author starzhan <397041849@qq.com>
 */
class Email extends Base
{

    /**
     * @title 新建邮箱
     * @author starzhan <397041849@qq.com>
     */
    public function save()
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $EmailService = new EmailService();
            $result = $EmailService->save($param, $userInfo['user_id']);
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
     * @title 修改邮箱号
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function update($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $EmailService = new EmailService();
            $result = $EmailService->update($id, $param, $userInfo['user_id']);
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
     * @title 邮箱号列表
     * @author starzhan <397041849@qq.com>
     */
    public function index()
    {
        $param = $this->request->param();
        try {
            $EmailService = new EmailService();
            $page = $param['page'] ?? 1;
            $pageSize = $param['pageSize'] ?? 50;
            $result = $EmailService->index($page, $pageSize, $param);
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
     * @title 邮箱号详情
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function read($id)
    {
        $EmailService = new EmailService();
        $result = $EmailService->read($id);
        return json($result, 200);
    }

    /**
     * @title 查看密码
     * @method get
     * @url :id(\d+)/password
     * @author starzhan <397041849@qq.com>
     */
    public function viewPassword($id)
    {
        $param = $this->request->param();
        try {
            if (!isset($param['password']) || !$param['password']) {
                throw new Exception('密码不能为空!');
            }
            $EmailService = new EmailService();
            $result = $EmailService->viewPassword($id, $param['password']);
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
     * @title 批量去除错误信息
     * @method put
     * @url batch/error-msg
     * @author starzhan <397041849@qq.com>
     */
    public function clearError()
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            if (!isset($param['ids']) || !$param['ids']) {
                throw new Exception('ids不能为空!');
            }
            $ids = json_decode($param['ids'], true);
            $EmailService = new EmailService();
            $result = $EmailService->clearError($ids, $userInfo['user_id']);
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
     * @title 获取可用邮箱列表
     * @method get
     * @url available-list
     * @author starzhan <397041849@qq.com>
     */
    public function getCanUseEmail()
    {
        $param = $this->request->param();
        $page_size = $param['pageSize'] ?? 50;
        $page = $param['page'] ?? 1;
        $EmailService = new EmailService();
        $result = $EmailService->getCanUseEmail($page, $page_size, $param);
        return json($result, 200);
    }

    /**
     * @title 获取已注册帐号的邮箱
     * @method get
     * @url used-list
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function getUsedEmail()
    {
        $param = $this->request->param();
        $page_size = $param['pageSize'] ?? 50;
        $page = $param['page'] ?? 1;
        $EmailService = new EmailService();
        $result = $EmailService->getUsedEmail($page, $page_size, $param);
        return json($result, 200);
    }

    /**
     * @title 生成注册帐号的邮箱前缀
     * @method get
     * @url email-prefix
     */
    public function emailPrefix()
    {
        $EmailService = new EmailService();
        $result = $EmailService->createEmailPrefix(8, 15);
        return json($result, 200);
    }

    /**
     * @title 获取邮箱帐号信息
     * @method get
     * @url :id(\d+)/accounts
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function getAccounts($id)
    {
        try {
            $EmailService = new EmailService();
            $page = $param['page'] ?? 1;
            $pageSize = $param['pageSize'] ?? 50;
            $result = $EmailService->getAccounts($page, $pageSize, $id);
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

}