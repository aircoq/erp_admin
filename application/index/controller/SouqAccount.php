<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-4-24
 * Time: 上午10:58
 */

namespace app\index\controller;

use app\common\cache\Cache;
use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\common\service\Common;
use app\common\model\souq\SouqAccount as SouqAccountModel;
use app\index\service\SouqAccountService;
use think\Db;
use think\Exception;
use think\Request;

/**
 * @module 账号管理
 * @title souq账号
 * @url /souq-account
 * @package app\publish\controller
 * @author lanshushu
 */
class SouqAccount extends Base
{
    protected $souqAccountService;

    public function __construct()
    {
        parent::__construct();
        if(is_null($this->souqAccountService)){
            $this->souqAccountService = new SouqAccountService();
        }
    }

    /**
     * @title souq账号列表
     * @method GET
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function index(Request $request)
    {
        try{
            $params = $request->param();
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);

            $response = $this->souqAccountService->accountList($params,$page,$pageSize);

            return json($response);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 添加账号
     * @method POST
     * @url /souq-account
     * @param Request $request
     * @return \think\response\Json
     */
    public function add(Request $request)
    {

        try{
            $params = $request->param();
            $user = Common::getUserInfo($request);
            $uid = $user['user_id'];
            $response = $this->souqAccountService->add($params,$uid);

            if($response === false) {
                return json(['message' => $this->souqAccountService->getError()], 400);
            }
            return json(['message' => '添加成功','data' => $response]);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 更新账号
     * @method PUT
     * @url /souq-account
     * @return \think\Response
     */
    public function update(Request $request)
    {

        try{
            $params = $request->param();
            $user = Common::getUserInfo($request);
            $uid = $user['user_id'];

            $response = $this->souqAccountService->add($params,$uid);
            if($response === false) {
                return json(['message' => $this->souqAccountService->getError()], 400);
            }
            return json(['message' => '更新成功','data' => $response]);
        }catch (Exception $exp){
            throw new JsonErrorException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

    /**
     * @title 查看账号
     * @method GET
     * @param  int $id
     * @url /souq-account/:id
     * @return \think\response\Json
     */
    public function read($id)
    {
        $response = $this->souqAccountService->getOne($id);
        return json($response);
    }





}