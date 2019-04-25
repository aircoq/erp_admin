<?php

namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\SoftwareService;
use think\Request;
use app\common\service\Common as CommonService;
use app\common\model\AccountApply as AccountApplyModel;

/**
 * @module 基础设置
 * @title 服务器软件管理
 * @author libaimin
 * @url server-software
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/12/8
 * Time: 17:45
 */
class ServerSoftware extends Base
{
    protected $softwareService;

    protected function init()
    {
        if (is_null($this->softwareService)) {
            $this->softwareService = new SoftwareService();
        }
    }

    /**
     * @title 显示资源列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $params = $request->param();
        $accountList = $this->softwareService->serverLists($params, $page, $pageSize);
        return json($accountList);
    }


    /**
     * @title 批量操作【更新客户端版本】
     * @url batch/:type
     * @method post
     * @param Request $request
     * @return \think\response\Json
     */
    public function batch(Request $request)
    {
        $ids = $request->post('ids', '');

        if (empty($ids)) {
            return json(['message' => '参数值不能为空']);        }
        $params = $request->param();
        $type = $params['type'];
        $data['type_msg'] = $request->post('type_msg', '');
        $ids = json_decode($ids, true);
        $this->softwareService->batch($ids, $data, $type);
        return json(['message' => '已经添加到更新队列。请稍等']);
    }

    /**
     * @title 修改状态
     * @url :id/status
     * @method post
     */
    public function changeStatus(Request $request, $id)
    {
        if (empty($id)) {
            return json(['message' => '请求参数错误'], 400);
        }
        $data['status'] = $request->post('status', 0);
        $dataInfo = $this->softwareService->serverUpdate($id, $data);
        return json(['message' => '修改成功', 'data' => $dataInfo], 200);
    }

}