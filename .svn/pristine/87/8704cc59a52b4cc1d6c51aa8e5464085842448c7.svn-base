<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12
 * Time: 10:11
 */

namespace app\index\controller;


use app\api\controller\Get;
use app\common\controller\Base;
use app\index\service\OberloAccountService;
use app\common\service\Common as CommonService;
use think\Request;

/**
 * @module 账号管理
 * @title oberlo账号管理
 * @url /oberlo-account
 * @author donghaibo
 */
class OberloAccount extends Base
{
    private $service;
    public function __construct(\think\Request $request = null)
    {
        parent::__construct($request);
        $this->service = new OberloAccountService();
    }

    /**
     * @title 账号列表
     * @method get
     * @param Request $request
     */
     public function index(Request $request)
     {
         $params = $request->param();
         $result = $this->service->getList($params);
         return json($result);
     }

    /**
     * @title 读取指定账号资源
     * @method get
     * @param $id int
     * @url /oberlo-account/:id(\d+)
     */
     public function read(Request $request,$id)
     {
         $id = $request->param("id",0);
         $result = $this->service->read($id);
         return json($result, 200);
     }

    /**
     * @title 更新账号信息
     * @method post
     * @param Request $request
     * @url /oberlo-account/update
     */
     public function update(Request $request)
     {
         $params = $request->param();
         $data = $params;
         //获取操作人信息
         $user = CommonService::getUserInfo($request);

         if (!empty($user)) {
             $data['update_id'] = $user['user_id'];
             $data['realname'] = $user['realname'];
         }
        /* if(isset($data['name']))     //不允许更新账号名称
         {
             unset($data['name']);
         }*/
         $model=$this->service->update($data);
         return json(['message' => '操作成功', 'data' => $model]);
     }

    /**
     * @param Request $request
     * @title 账号授权
     * @method post
     * @url authorize
     */
     public function authorize(Request $request)
     {
         $params = $request->param();
         //获取操作人信息
         $user = CommonService::getUserInfo($request);
         if (!empty($user)) {
             $params['update_id'] = $user['user_id'];
             $params['realname'] = $user['realname'];
         }
         $result  = $this->service->authorize($params);
         return json(['message'=>$result['message']],$result['code']);
     }

    /**
     * @title 系统状态切换
     * @url change-status
     * @method post
     */
    public function change_status(Request $request)
    {
        try {
            $params = $request->param();
            $userInfo = CommonService::getUserInfo($request);
            if (!empty($userInfo)) {
                $params['update_id'] = $userInfo['user_id'];
                $params['realname'] = $userInfo['realname'];
            }
            $this->service->changeStatus($params);
            return json(['message' => '切换系统状态成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 批量设置oberlo账号
     * @method post
     * @url /oberlo-account/batch-set
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     * @author Reece
     * @date 2018-08-14 17:51:14
     */
    public function batchUpdate(Request $request)
    {
        try{
            $params = $request->post();
            if(!param($params, 'ids')) throw new Exception('请先选择数据');
            $ids = json_decode($params['ids'], true);

            $user = CommonService::getUserInfo($request);
            $params['user_id'] = $user['user_id'];
            $params['realname'] = $user['realname'];

            $result = $this->service->batchUpdate($ids, $params);
            return json(['message'=>'操作成功', 'data'=>$result]);
        }catch (Exception $ex){
            return json(['message'=>$ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取Oberlo账号日志
     * @method get
     * @url /oberlo-account/log/:id
     * @param  \think\Request $request
     * @param  string $site
     * @return \think\Response
     */
    public function getLog(Request $request)
    {
        return json(
            $this->service->getOberloLog($request->param())
            , 200
        );
    }
}