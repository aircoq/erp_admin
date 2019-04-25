<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\DarazAccountService;
use app\common\service\Common as CommonService;
use service\daraz\DarazLib;
use think\Exception;
use think\Request;

/**
 * @module 账号管理
 * @title Daraz账号管理
 * @url /daraz-account
 * @author donghaibo
 */
class DarazAccount extends  Base
{
    protected $darazAccountService;

    public function __construct()
    {
        parent::__construct();
        if(is_null($this->darazAccountService)){
            $this->darazAccountService = new DarazAccountService();
        }
    }

    /**
     * @title 保存新建资源
     * @param Request $request
     * @method POST
     * @url /daraz-account
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $data = $params;


        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['creator_id'] = $user['user_id'];
        }
        $accountInfo = $this->darazAccountService->save($data);
        return json(['message' => '新增成功','data' => $accountInfo]);
    }

    /**
     * @title 获取daraz站点
     * @url /daraz-account/sites
     * @return \think\response\Json
     * @method get
     */
    public function getSites()
    {
        $config = DarazLib::getDarazConfig();
        if($config)
        {
            $sites = [];
            foreach (array_keys($config) as $v)
            {
                $temp = [];
                $temp['site'] = $v;
                $sites[] = $temp;
            }
            return json($sites,200);
        }else{
            return json([],200);
        }
    }

    /**
     * @title 显示指定的资源
     * @param int $id
     * @method GET
     * @url /daraz-account/read
     * @return \think\response\Json
     */
    public function read(Request $request)
    {
        $id = $request->get("id",0);
        $result = $this->darazAccountService->read($id);
        return json($result, 200);
    }

    /**
     * 显示资源列表
     * @return \think\response\Json
     * @apiRelate app\index\controller\User::staffs
     * @apiRelate app\index\controller\MemberShip::memberInfo
     * @apiRelate app\index\controller\MemberShip::save
     * @apiRelate app\index\controller\MemberShip::update
     * @method GET
     * @url /daraz-account
     */
    public function index(Request $request)
    {
        return json(
            $this->darazAccountService->getList($request->param()),
            200
        );
        // $request = Request::instance();

        // $result = $this->darazAccountService->accountList($request);
        // return json($result,200);
    }

    /**
     * @title 保存更新的资源
     * @method PUT
     * @param Request $request
     * @param int $id
     * @return \think\response\Json
     * @url /daraz-account/:id
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        $data = $params;
        //获取操作人信息
        $user = CommonService::getUserInfo($request);

        if (!empty($user)) {
            $data['update_id'] = $user['user_id'];
            $data['realname'] = $user['realname'];
        }
        if(isset($data['name']))     //不允许更新账号名称
        {
            unset($data['name']);
        }
        $model=$this->darazAccountService->update($id,$data);
        return json(['message' => '操作成功', 'data' => $model]);
    }

    /**
     * @title 保存daraz账户授权
     * @method put
     * @url /daraz-account/authorization
     * @param Request $request
     * @return \think\response\Json
     */
    public function authorization(Request $request)
    {
        try {
            $params = $request->param();
            $userInfo = CommonService::getUserInfo($request);
            if (!empty($userInfo)) {
                $params['update_id'] = $userInfo['user_id'];
                $params['realname'] = $userInfo['realname'];
            }
            $this->darazAccountService->authorization($params);

            return json(['message'=>'授权成功'],200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 系统状态切换
     * @url /daraz-account/change-status
     * @method post
     */
    public function changeStatus(Request $request)
    {
        try {
            $params = $request->param();
            $userInfo = CommonService::getUserInfo($request);
            if (!empty($userInfo)) {
                $params['update_id'] = $userInfo['user_id'];
                $params['realname'] = $userInfo['realname'];
            }
            $this->darazAccountService->changeStatus($params);
            return json(['message' => '切换系统状态成功'], 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 获取Daraz账号日志
     * @method get
     * @url /daraz-account/log/:id
     * @param  \think\Request $request
     * @param  string $site
     * @return \think\Response
     */
    public function getLog(Request $request)
    {
        return json(
            $this->darazAccountService->getDarazLog($request->param())
            , 200
        );
    }
}