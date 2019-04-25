<?php

namespace app\index\controller;

use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\index\service\AccountService;
use app\index\service\CreditCardService;
use app\common\model\account\CreditCard as CreditCardModel;
use app\common\service\Common as CommonService;
use app\common\validate\CreditCard as CreditValidate;
use think\Request;

/**
 * @module 信用卡管理
 * @title 信用卡菜单管理
 * @url /credit-card
 * @author zhuda
 * @package app\index\controller
 */
class CreditCard extends Base
{

    protected $creditCardService;

    public function __construct()
    {
        parent::__construct();
        if (is_null($this->creditCardService)) {
            $this->creditCardService = new CreditCardService();
        }
    }

    /**
     * @title 信用卡账号列表
     * @method GET
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @apiRelate app\finance\controller\BankAccount::bank
     * @apiRelate app\index\controller\CreditCard::categoryList
     */
    public function index()
    {
        $result = $this->creditCardService->creditCardList();
        return json($result, 200);
    }

    /**
     * @title 新增信用卡记录
     * @method POST
     * @url /credit-card
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        $data = $request->param();

        $validate = new CreditValidate();
        $result = $validate->scene('add')->check($data);

        if ($result !== true) {
            return json(['message' => $validate->getError()], 400);
        }

        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['creator_id'] = $user['user_id'];
            $data['update_id'] = $user['user_id'];
        }

        $result = $this->creditCardService->save($data);

        if ($result === false) {
            return json(['message' => $this->creditCardService->getError()], 400);
        }

        return json(['message' => '新增成功', 'data' => $result]);
    }

    /**
     * @title 显示信用卡详细.
     * @param $id
     * @method GET
     * @url /credit-card/:id/edit
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        $result = $this->creditCardService->read($id);

        if (!$result) {
            return json(['message' => $this->creditCardService->getError()], 500);
        }

        $userName = (new \app\common\cache\driver\User())->getOneUserRealname($result['creator_id']);
        $result['creator'] = $userName;
        return json($result, 200);
    }

    /**
     * @title 修改信用卡记录
     * @param Request $request
     * @param $id
     * @method POST
     * @url /credit-card/:id/update
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function update(Request $request, $id)
    {
        $data = $request->param();
        $validate = new CreditValidate();
        $result = $validate->scene('edit')->check($data);

        if ($result !== true) {
            return json(['message' => $validate->getError()], 400);
        }

        //获取操作人信息
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['updater_id'] = $user['user_id'];
        }
        $result = $this->creditCardService->update($id, $data);
        if (!$result) {
            return json(['message' => $this->creditCardService->getError()], 400);
        }
        return json(['message' => '更改成功', 'data' => $result], 200);
    }

    /**
     * @title 删除信用卡记录
     * @param $id
     * @method delete
     * @url /credit-card/:id/del
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function del($id)
    {
        $find = $this->creditCardService->read($id);

        if (!$find) {
            return json(['message' => $this->creditCardService->getError()], 500);
        }

        $result = (new CreditCardModel)->where(['id' => $id, 'account_count' => 0])->delete();
        if (!$result) {
            return json(['message' => '删除失败'], 400);
        }
        return json(['message' => '删除成功']);
    }

    /**
     * @title 查询信用卡类别列表
     * @method get
     * @url /credit-card/category
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function categoryList()
    {
        $model = new \app\common\model\account\CreditCategory();
        $result = $model->select();
        return json($result, 200);
    }


    /**
     * @title 查询绑定账号的详情
     * @method get
     * @url /credit-card/:id/account-info
     * @return \think\response\Json
     * @throws \Exception
     */
    public function accountInfo($id)
    {
        $result = (new AccountService())->accountCredit($id);
        return json($result, 200);
    }

    /**
     * @title 批量导入
     * @method POST
     * @url batch-import
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function batchImport(Request $request)
    {
        $params = $request->param();
        $user = CommonService::getUserInfo();
        try {
            $re = $this->creditCardService->import($params, $user['user_id']);
            return json($re, 200);
        } catch (JsonErrorException $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 导入模板下载
     * @method GET
     * @url import-template
     * @return \think\response\Json
     */
    public function importTemplate()
    {
        try {
            $result = $this->creditCardService->importTemplate();
            return json($result);
        } catch (JsonErrorException $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


}