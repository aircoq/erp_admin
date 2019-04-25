<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\BuyerService;
use think\Request;
use app\common\service\Common as CommonService;

/**
 * @title 买家信息列表
 * @module 基础设置
 * @author phill
 * @url buyers
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/4
 * Time: 19:47
 */
class Buyer extends Base
{
    protected $buyerService;

    protected function init()
    {
        if (is_null($this->buyerService)) {
            $this->buyerService = new BuyerService();
        }
    }

    /**
     * @title 买家列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $params = $request->param();
        $where = [];
        if (isset($params['channel_id']) && !empty($params['channel_id'])) {
            $where['channel_id'] = ['=', $params['channel_id']];
        }
        if (isset($params['account_id']) && !empty($params['account_id'])) {
            $where['account_id'] = ['=', $params['account_id']];
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'buyer_id':
                    $where['buyer_id'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'link':
                    $where['tel|mobile'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                default:
                    break;
            }
        }
        $result = $this->buyerService->buyerList($where, $page, $pageSize);
        return json($result, 200);
    }

    /**
     * @title 查看买家信息
     * @param $id
     * @return \think\response\Json
     */
    public function read($id)
    {
        if(!is_numeric($id)){
            return json(['message' => '参数值错误'],400);
        }
        $result = $this->buyerService->info($id);
        return json($result,200);
    }

    /**
     * @title 获取编辑买家信息
     * @param $id
     * @return \think\response\Json
     */
    public function edit($id)
    {
        if(!is_numeric($id)){
            return json(['message' => '参数值错误'],400);
        }
        $result = $this->buyerService->info($id);
        return json($result,200);
    }

    /**
     * @title 保存买家信息
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $basic = $request->post('basic','');
        if(!is_json($basic)){
            return json(['message' => '参数格式错误'],400);
        }
        $basic = json_decode($basic,true);
        $user = CommonService::getUserInfo();
        if (!empty($user)) {
            $basic['creator_id'] = $user['user_id'];
            $basic['create_time'] = time();
        }
        $buyer_id = $this->buyerService->add($basic);
        return json(['message' => '保存成功','id' => $buyer_id],200);
    }

    /**
     * @title 更新买家信息
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function update(Request $request,$id)
    {
        $basic = $request->put('basic','');
        if(!is_json($basic) || !is_numeric($id)){
            return json(['message' => '参数格式错误'],400);
        }
        $basic = json_decode($basic,true);
        $user = CommonService::getUserInfo();
        if (!empty($user)) {
            $basic['updater_id'] = $user['user_id'];
            $basic['update_time'] = time();
        }
        $this->buyerService->update($basic,$id);
        return json(['message' => '更新成功'],200);
    }

    /**
     * @title 删除买家信息
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        if(!is_numeric($id)){
            return json(['message' => '参数值错误'],400);
        }
        $this->buyerService->delete($id);
        return json(['message' => '删除成功'],200);
    }

    /**
     * @title 批量删除
     * @url batch/delete
     * @method post
     * @return \think\response\Json
     */
    public function batch()
    {
        $request = Request::instance();
        $ids = $request->post('ids','');
        if(empty($ids)){
            return json(['message' => '参数错误'],400);
        }
        $ids = json_decode($ids,true);
        $this->buyerService->batch($ids);
        return json(['message' => '删除成功'],200);
    }

    /**
     * @title 导入买家批量修改状态
     * @method POST
     * @url batch-update
     * @param Request $request
     * @return \think\response\Json
     */
    public function batchUpdate(Request $request)
    {
        try {
            $result = $this->buyerService->batchUpdate($request);
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 买家批量修改导入模板下载；
     * @method GET
     * @url update-template
     * @param Request $request
     * @return \think\response\Json
     */
    public function updateTemplate(Request $request)
    {
        try {
            $result = $this->buyerService->updateTemplate($request);
            return json($result);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }
}