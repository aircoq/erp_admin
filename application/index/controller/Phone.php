<?php


namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\Phone as PhoneService;
use think\Exception;

/**
 * @title 手机号管理
 * @module 基础设置
 * @url /phone
 * @author starzhan <397041849@qq.com>
 */
class Phone extends Base
{
    public function index()
    {
        $param = $this->request->param();
        try {
            $page_size = $param['pageSize'] ?? 50;
            $page = $param['page'] ?? 1;
            $PhoneService = new PhoneService();
            $result = $PhoneService->getList($page, $page_size, $param);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($err, 200);
        }
    }

    public function read($id)
    {
        try {
            $PhoneService = new PhoneService();
            $result = $PhoneService->read($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($err, 200);
        }
    }

    public function save()
    {
        $param = $this->request->param();
        try {
            $PhoneService = new PhoneService();
            $result = $PhoneService->save($param);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($err, 200);
        }
    }

    /**
     * @url :id(\d+)/status
     * @method put
     * @title 切换状态
     * @author starzhan <397041849@qq.com>
     */
    public function changeStatus($id)
    {
        $param = $this->request->param();
        try {
            if (!isset($param['status']) || $param['status'] === '') {
                throw new Exception('状态不能为空');
            }
            $PhoneService = new PhoneService();
            $result = $PhoneService->changeStatus($id, $param['status']);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($err, 200);
        }
    }

    /**
     * @title 获取可用手机号列表
     * @method get
     * @url can-use
     * @noauth
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function getCanUsePhoneList()
    {
        $param = $this->request->param();
        try {
            $PhoneService = new PhoneService();
            $page_size = $param['pageSize'] ?? 50;
            $page = $param['page'] ?? 1;
            $result = $PhoneService->getCanUsePhoneList($page,$page_size,$param);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($err, 200);
        }
    }

    /**
     * @title 获取邮箱可用手机号列表
     * @method get
     * @url email-use
     * @noauth
     * @author starzhan <397041849@qq.com>
     */
    public function getCanUserEmailPhone(){
        $param = $this->request->param();
        try {
            $PhoneService = new PhoneService();
            $page_size = $param['pageSize'] ?? 50;
            $page = $param['page'] ?? 1;
            $result = $PhoneService->getCanUserEmailPhone($page,$page_size,$param);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($err, 200);
        }
    }
    /**
     * @title 获取关联的帐号
     * @param $id
     * @method get
     * @url :id(\d+)/accounts
     * @author starzhan <397041849@qq.com>
     */
    public function accounts($id)
    {
        try {
            $PhoneService = new PhoneService();
            $result = $PhoneService->accounts($id);
            return json($result, 200);
        } catch (Exception $ex) {
            $err = [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'message' => $ex->getMessage()
            ];
            return json($err, 200);
        }
    }


}