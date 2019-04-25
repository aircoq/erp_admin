<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-8-2
 * Time: 上午11:35
 */
namespace app\progress\controller;

use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\common\service\Common;
use app\index\service\User;
use app\progress\service\ProgressService;
use Exception;
use think\Request;

/**
 * Class Schedule
 * @package app\progress\controller
 * @module 需求管理
 * @title 需求管理
 * @url /progress
 */
class Progress extends Base
{
    private $service=null;
    protected function init()
    {
        $this->service = new ProgressService();
    }

    /**
     * @title 需求管理首页
     * @param Request $request
     * @method GET
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        try{
            $params = $request->param();
            $page = $request->param('page',1);
            $pageSize = $request->param('pageSize',30);
            $response = $this->service->lists($params,$page,$pageSize);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException("{$exp->getFile()};{$exp->getLine()};{$exp->getMessage()}");
        }
    }
    /**
     * @title 新增需求
     * @method POST
     * @url add
     * @param Request $request
     * @return \think\response\Json
     */
    public function add(Request $request)
    {
        try{
            $params = $request->param();
            $user = Common::getUserInfo($request);
            $params['create_id']= $user? $user['user_id'] : 0;
            $response = $this->service->add($params);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }

    /**
     * @title 更新需求
     * @method POST
     * @url /progress-update
     * @param Request $request
     * @return \think\response\Json
     */
    public function update(Request $request)
    {
        try{
            $params = $request->param();
            $response = $this->service->update($params);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 更新需求状态
     * @method POST
     * @url /progress/update-status
     * @param Request $request
     * @return \think\response\Json
     */
    public function updateStatus(Request $request)
    {
        try{
            $ids = $request->param('ids','');
            if(empty($ids)){
                throw new JsonErrorException("请选择");
            }
            $status = $request->param('status',0);
            $response = $this->service->update($ids,$status);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 需求删除
     * @method DELETE
     * @url /progress-delete
     * @param Request $request
     * @return \think\response\Json
     */
    public function delete(Request $request)
    {
        try{
            $ids = $request->param('ids','');
            if(empty($ids)){
                throw new JsonErrorException("请选择");
            }
            $response = $this->service->delete($ids);
            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException($exp->getMessage());
        }
    }
    /**
     * @title 需求管理获取用户角色
     * @method GET
     * @url /progress-permission
     * @param Request $request
     * @return \think\response\Json
     */
    public function permission(Request $request){
        $result = ProgressService::permision($request);
        return json($result);
    }

    /**
     * @title 需求负责人
     * @method GET
     * @url /progress-principal
     * @return \think\response\Json
     * @throws Exception
     */
    public function principal()
    {
        try{
            $response = $this->service->principal();
            return json($response);
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }

    /**
     * @title 功能模块
     * @method GET
     * @url /progress-module
     * @return \think\response\Json
     * @throws Exception
     */
    public function module()
    {
        try{
            $response = $this->service->module();
            return json($response);
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }
}