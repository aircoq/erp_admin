<?php


namespace app\index\controller;


use app\common\controller\Base;
use app\common\service\Common;
use think\Exception;
use app\index\service\RegisterCompany as Service;

/**
 * @title 注册公司管理
 * @module 基础设置
 * @url register-company
 * @author starzhan <397041849@qq.com>
 */
class RegisterCompany extends Base
{

    public function index()
    {
        $param = $this->request->param();
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 50;
        $service = new Service();
        $result = $service->index($page, $pageSize, $param);
        return json($result, 200);
    }

    /**
     * @title 添加法人信息
     * @method post
     * @url legal-info
     * @author starzhan <397041849@qq.com>
     */
    public function saveLegalInfo()
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $Service = new Service();
            $result = $Service->saveLegalInfo($param, $userInfo);
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
     * @title 更新法人信息
     * @method put
     * @url :id(\d+)/legal-info
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function updateLegalInfo($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $Service = new Service();
            $result = $Service->updateLegalInfo($id, $param, $userInfo);
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
     * @title 获取法人信息详情
     * @method get
     * @url :id(\d+)/legal-info
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function getLegalInfo($id)
    {
        try {
            $Service = new Service();
            $result = $Service->getLegalInfo($id);
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
     * @url status
     * @method get
     * @title 状态列表
     * @author starzhan <397041849@qq.com>
     */
    public function getStatus()
    {
        $Service = new Service();
        return json($Service->getStatus(), 200);
    }

    /**
     * @title 保存公司信息
     * @method put
     * @url :id(\d+)/company-info
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function saveCompanyInfo($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $Service = new Service();
            $result = $Service->saveCompanyInfo($id, $param, $userInfo);
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
     * @title 获取公司信息
     * @method get
     * @url :id/company-info
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function getCompanyInfo($id)
    {
        try {
            $Service = new Service();
            $result = $Service->getCompanyInfo($id);
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
     * @title 上传营业执照
     * @param $id
     * @method put
     * @url :id/charter
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function saveCharter($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $Service = new Service();
            $result = $Service->saveCharter($id, $param, $userInfo);
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
     * @title 保存结账信息
     * @param $id
     * @method put
     * @url :id/settlement
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function saveSettlement($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $Service = new Service();
            $result = $Service->saveSettlement($id, $param, $userInfo);
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
     * @title 获取操作日志信息
     * @method get
     * @url :id(\d+)/logs
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function logs($id)
    {
        $Service = new Service();
        $result = $Service->logs($id);
        return json($result, 200);
    }

    /**
     * @title 获取结账信息
     * @method get
     * @url :id/settlement
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function getSettlement($id)
    {
        try {
            $Service = new Service();
            $result = $Service->getSettlement($id);
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
     * @title 获取营业执照
     * @method get
     * @url :id/charter
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function getCharter($id)
    {
        try {
            $Service = new Service();
            $result = $Service->getCharter($id);
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
}