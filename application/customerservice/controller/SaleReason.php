<?php
namespace app\customerservice\controller;

use app\common\controller\Base;
use app\customerservice\service\SaleReasonService;
use think\Request;
use app\common\service\Common as CommonService;

/**
 * @module 客服管理
 * @title 售后原因
 * @author phill
 * @url /sale-reasons
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/1
 * Time: 10:03
 */
class SaleReason extends Base
{
    protected $reasonService;

    protected function init()
    {
        if(is_null($this->reasonService)){
            $this->reasonService = new SaleReasonService();
        }
    }

    /**
     * @title 列表
     * @return \think\response\Json
     */
    public function index()
    {
        $result = $this->reasonService->reason();
        return json($result,200);
    }

    /** @node 新增
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $data = [];
        $remark = $request->post('remark','');
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $data['operator'] = $user['realname'];
            $data['operator_id'] = $user['user_id'];
        }
        $result = $this->reasonService->addReason($remark,$data['operator_id']);
        return json(['message' => $result['message']],$result['code']);
    }

    /** @node 删除
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        return $this->reasonService->delReason($id);
    }
}