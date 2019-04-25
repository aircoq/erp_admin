<?php


namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\Postoffice as ServicePostoffice;
use think\Exception;
use app\common\service\Common;

/**
 * @title 邮局管理
 * @module 基础设置
 * @url /postoffice
 * @author starzhan <397041849@qq.com>
 */
class Postoffice extends Base
{
    /**
     * @title 邮局信息列表
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function index()
    {
        $param = $this->request->param();
        try {
            $ServicePostoffice = new ServicePostoffice();
            $page = $param['page'] ?? 1;
            $pageSize = $param['pageSize'] ?? 50;
            $result = $ServicePostoffice->index($page, $pageSize, $param);
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
     * @title 获取单条邮局详情
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function read($id)
    {
        $ServicePostoffice = new ServicePostoffice();
        return json($ServicePostoffice->read($id), 200);
    }

    /**
     * @title 新增邮局信息
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function save()
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $ServicePostoffice = new ServicePostoffice();
            $result = $ServicePostoffice->save($param, $userInfo['user_id']);
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
     * @title 修改邮局信息
     * @param $id
     * @return \think\response\Json
     * @author starzhan <397041849@qq.com>
     */
    public function update($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            $ServicePostoffice = new ServicePostoffice();
            $result = $ServicePostoffice->update($id, $param, $userInfo['user_id']);
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
     * @title 切换状态
     * @method put
     * @url :id(\d+)/status
     * @param $id
     * @author starzhan <397041849@qq.com>
     */
    public function changeStatus($id)
    {
        $param = $this->request->param();
        $userInfo = Common::getUserInfo();
        try {
            if(!isset($param['status'])){
                throw new Exception('状态不能为空');
            }
            $data = [
                'status'=>$param['status']
            ];
            $ServicePostoffice = new ServicePostoffice();
            $result = $ServicePostoffice->changeStatus($id, $data, $userInfo['user_id']);
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
     * @title 获取可用邮局列表
     * @method get
     * @url available-list
     * @author starzhan <397041849@qq.com>
     */
    public function getCanUsePost()
    {
        $ServicePostoffice = new ServicePostoffice();
        $result = $ServicePostoffice->getCanUsePost();
        return json($result, 200);
    }
}