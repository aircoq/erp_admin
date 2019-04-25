<?php
namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\PurchaseSubclassMapService;
use think\Request;

/**
 * @module 基础设置
 * @title 分类采购员绑定
 * @author phill
 * @url /sub-map
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/5/26
 * Time: 20:13
 */
class PurchaseSubclassMap extends Base
{
    protected $purchaseSubclassMapService;

    protected function init()
    {
        if (is_null($this->purchaseSubclassMapService)) {
            $this->purchaseSubclassMapService = new PurchaseSubclassMapService();
        }
    }

    /**
     * @title 列表
     * @apiParam name:category_id type:int desc:分类id
     * @apiParam name:purchase_id type:int desc:采购员id
     * @apiParam name:pid type:int desc:分类父id（开发分组调用）
     * @description 传了pid参数查询，返回数据会多出 is_bind 是否已绑定字段（0-未绑定 1-已绑定），developer_name 分组名称
     * @return \think\response\Json
     */
    public function index()
    {
        $request = Request::instance();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $params = $request->param();
        $result = $this->purchaseSubclassMapService->mapList($params,$page,$pageSize);
        return json($result, 200);
    }

    /**
     * @title 信息
     * @param $id
     * @return \think\response\Json
     */
    public function read($id)
    {
        $result = $this->purchaseSubclassMapService->info($id);
        return json($result, 200);
    }

    /**
     * @title 获取编辑信息
     * @param $id
     * @return \think\response\Json
     */
    public function edit($id)
    {
        $result = $this->purchaseSubclassMapService->info($id);
        return json($result, 200);
    }

    /**
     * @title 新增关系
     * @apiParam name:detail type:array desc:[{category_id:1,purchase_id:2},{}]
     * @param Request $request
     * @return \think\response\Json
     * @apiRelate app\index\controller\User::staffs
     * @apiRelate app\index\controller\DeveloperTeam::category
     */
    public function save(Request $request)
    {
        $params = $request->param();
        $this->purchaseSubclassMapService->add($params['detail']);
        return json(['message' => '新增成功'], 200);
    }

    /**
     * @title 更新关系
     * @param Request $request
     * @apiParam name:category_id type:int desc:分类id
     * @apiParam name:purchase_id type:int desc:采购员id
     * @param $id
     * @return \think\response\Json
     * @apiRelate app\index\controller\User::staffs
     * @apiRelate app\index\controller\DeveloperTeam::category
     */
    public function update(Request $request, $id)
    {
        $data['category_id'] = $request->put('category_id');
        $data['purchase_id'] = $request->put('purchase_id');
        $this->purchaseSubclassMapService->update($data, $id);
        return json(['message' => '更新成功'], 200);
    }

    /**
     * @title 删除
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        $this->purchaseSubclassMapService->delete($id);
        return json(['message' => '删除成功'], 200);
    }

    /**
     * @title 批量删除
     * @url batch/:type(\w+)
     * @method post
     * @apiParam name:type type:string desc:delete-删除
     * @apiParam name:data type:array desc:[0,1,2]
     * @return \think\response\Json
     */
    public function batch()
    {
        $request = Request::instance();
        $this->purchaseSubclassMapService->batch($request);
        return json(['message' => '删除成功'], 200);
    }
}