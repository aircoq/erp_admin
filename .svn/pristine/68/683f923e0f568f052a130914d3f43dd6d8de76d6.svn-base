<?php

namespace app\index\controller;

use think\Db;
use think\Request;
use think\Exception;
use app\common\controller\Base;
use app\common\cache\Cache;
use app\common\service\Common as CommonService;
use app\common\model\Ali1688Account as Ali1688AccountModel;
use app\index\service\Ali1688AccountService;

/**
 * @module 基础设置
 * @title 1688账号管理
 * @url ali1688-account
 */
class Ali1688Account extends Base
{

    private $service;
    private $successCode = 200; //操作成功返回给前端的编码
    private $failureCode = 400; //操作失败返回给前端的编码

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        //依赖注入
        $this->service = new Ali1688AccountService();
    }

    /**
     * @title 1688账号列表
     * @method get
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-19 10:10:11
     */
    public function index()
    {
        //获取请求参数
        $param = $this->request->param();
        $this->service->getWhere($param);
        //查询数据
        $page = param($param, 'page', 1);
        $pageSize = param($param, 'pageSize', 20);
        $sortFeild = param($param, 'sort_field');
        $sortType = param($param, 'sort_type');
        if($sortFeild && $sortType){
            $data = $this->service->getList([], '*', $page, $pageSize, '', $sortFeild.' '.$sortType);
        }else{
            $data = $this->service->getList([], '*', $page, $pageSize);
        }
        //返回结果数据
        $result = [
            'data' => $data['data'],
            'page' => $page - 0,
            'pageSize' => $pageSize - 0,
            'count' => $data['count'],
        ];
        return json($result, $this->successCode);
    }

    /**
     * @title 查看
     * @param $id
     * @author Jimmy <554511322@qq.com>
     */
    public function read($id)
    {
        try {
            $map = [];
            $map['id'] = $id - 0; //转化为整数
            $res = $this->service->read($map);
            return json($res, $this->successCode);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], $this->failureCode);
        }
    }

    /**
     * @title 新增
     * @method post
     * @author Jimmy <554511322@qq.com>
     */
    public function save()
    {
        try {
            $params = $this->request->param();
            $userId = param(CommonService::getUserInfo($this->request), 'user_id', 0);
            $model = $this->service->save($params, $userId);
            return json(['message' => '操作成功', 'data' => $model], $this->successCode);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], $this->failureCode);
        }
    }

    /**
     * @title 更新
     * @method put
     * @author Jimmy <554511322@qq.com>
     */
    public function update()
    {
        try {
            $params = $this->request->param();
            $userId = param(CommonService::getUserInfo($this->request), 'user_id', 0);
            $model = $this->service->update($params, $userId);
            return json(['message' => '操作成功', 'data' => $model], $this->successCode);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], $this->failureCode);
        }
    }

    /**
     * @title 启用停用
     * @method post
     * @url states
     * @author Jimmy <554511322@qq.com>
     */
    public function isInvalid()
    {
        try {
            $params = $this->request->param();
            $userId = param(CommonService::getUserInfo($this->request), 'user_id', 0);
            $this->service->isInvalid($params, $userId);
            return json(['message' => '操作成功'], $this->successCode);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], $this->failureCode);
        }
    }

    /**
     * @title 获取授
     * @url getAuthorCode
     * @method post
     * @author Jimmy <554511322@qq.com>
     */
    public function getAuthorCode()
    {
        try {
            $params = $this->request->param();
            if (!param($params, 'client_id')) {
                return json(['message' => '请求错误!请输入账号ID!'], $this->failureCode);
            }
            if (!param($params, 'client_secret')) {
                return json(['message' => '请求错误!请输入账号秘钥!'], $this->failureCode);
            }
            $res = $this->service->getAuthorCode($params);
            return json(['message' => '操作成功', 'url' => $res], $this->successCode);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], $this->failureCode);
        }
    }

    /**
     * @title 获取token
     * @url getToken
     * @method post
     * @author Jimmy <554511322@qq.com>
     */
    public function getToken()
    {
        try {
            $params = $this->request->param();
            if (!param($params, 'id')) {
                return json(['message' => '参数错误,无ID!'], $this->failureCode);
            }
            if (!param($params, 'client_id')) {
                return json(['message' => '请求错误!请输入账号ID!'], $this->failureCode);
            }
            if (!param($params, 'client_secret')) {
                return json(['message' => '请求错误!请输入账号秘钥!'], $this->failureCode);
            }
            if (!param($params, 'code')) {
                return json(['message' => '请求错误!请输入账号秘钥!'], $this->failureCode);
            }
            $userId = param(CommonService::getUserInfo($this->request), 'user_id', 0);
            $this->service->getToken($params, $userId);
            return json(['message' => '操作成功'], $this->successCode);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], $this->failureCode);
        }
    }

    /**
     * @title 批量开启
     * @url batch-set
     * @method post
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     */
    public function batchSet(Request $request)
    {
        $params = $request->post();
        $result = $this->validate($params, [
            'ids|帐号ID' => 'require|min:1',
            'is_invalid|系统状态' => 'require|number',
            'membership|会员身份' => 'require|number',
        ]);

        if ($result != true) {
            throw new Exception($result);
        }
        //实例化模型
        $model = new Ali1688AccountModel();

        if (isset($params['is_invalid']) && $params['is_invalid'] != '') {
            $data['is_invalid'] = (int)$params['is_invalid'];   //1-启用  0-停用
        }
        if (isset($params['membership']) && $params['membership'] != '') {
            $data['membership'] = (int)$params['membership'];
        }

        $idArr = array_merge(array_filter(array_unique(explode(',', $params['ids']))));
        //开启事务
        Db::startTrans();
        try {
            if (empty($data)) {
                return json(['message' => '数据参数不能为空'], 200);
            }
            $data['update_time'] = time();
            $model->where(['id' => ['in', $idArr]])->update($data);
            Db::commit();

            return json(['message' => '更新成功'], 200);
        } catch (Exception $ex) {
            Db::rollback();
            return json(['message' => '更新失败' . $ex->getMessage() . $ex->getFile() . $ex->getLine()], 400);
        }
    }

    /**
     * @title 获取1688下拉框列表
     * @method get
     * @url /ali1688Account/get-ali-account
     * @return \think\response\Json
     */
    public function getAliAccount()
    {
        $ali1688AccountService = new Ali1688AccountService();
        $result = $ali1688AccountService->getAccount();
        return json($result, 200);
    }
}
