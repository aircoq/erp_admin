<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 9:29
 */

namespace app\goods\controller;

use think\Request;
use think\Exception;
use app\common\controller\Base;
use app\common\service\Common as CommonService;
use app\common\validate\GoodsDiscount as goodsDiscountValidate;
use app\goods\service\GoodsDiscount as service;

/**
 * @module 跌价补贴
 * @title 跌价补贴模块
 * @url /goods-discount
 * @author zhuda
 * @package app\index\controller
 */
class GoodsDiscount extends Base
{

    protected $service;

    public function __construct()
    {
        parent::__construct();
        if (is_null($this->service)) {
            $this->service = new service();
        }
    }

    /**
     * @title 跌价申请（列表）
     * @method get
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $request = Request::instance();
        $params = $request->param();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $result = $this->service->getGoodsDiscountList($params, $page, $pageSize);
        return json($result, 200);
    }


    /**
     * @title 跌价申请（新增）
     * @method post
     * @url /goods-discount
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function save()
    {
        $request = Request::instance();
        $data = $request->param();

        $validate = new goodsDiscountValidate();
        $result = $validate->scene('add')->check($data);

        if ($result !== true) {
            return json(['message' => $validate->getError()], 400);
        }

        $checkInfo = $this->service->checkOnline($data['sku_id'], $data['warehouse_id']);
        if ($checkInfo === true) {
            return json(['message' => '当前sku正在跌价活动中'], 400);
        }
        //获取操作人信息
        $user = CommonService::getUserInfo();
        if (!empty($user)) {
            $data['create_id'] = $user['user_id'];
            $data['updater_id'] = $user['user_id'];
            $data['proposer_id'] = $user['user_id'];
        }

        $time = time();
        $data['valid_time'] = strtotime($data['valid_time']);
        $data['over_time'] = strtotime($data['over_time']);
        $data['create_time'] = $time;
        $data['update_time'] = $time;
        $data['proposer_time'] = $time;
        $data['remark'] = $data['remark'] ?? '';

        $result = $this->service->save($data, $user['user_id']);

        if ($result === false) {
            return json(['message' => $this->service->getError()], 400);
        }

        return json(['message' => '新增成功', 'data' => $result]);
    }

    /**
     * @title 跌价申请（修改、审批）
     * @method put
     * @param $id
     * @url /:id/goods-discount
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function update($id)
    {
        $request = Request::instance();
        $params = $request->param();

        if (!isset($params['status'])) {
            return json(['message' => '参数缺失'], 400);
        }

        //获取操作人信息
        $user = CommonService::getUserInfo();
        $time = time();
        if (!empty($user)) {
            $data['update_id'] = $user['user_id'];
            $data['update_time'] = $time;
        }

        $validate = new goodsDiscountValidate();
        if ($params['status'] == '1' || $params['status'] == '2' || $params['status'] == '3') {
            $result = $validate->scene('status')->check($params);
            $data['audit_id'] = $user['user_id'];
            $data['audit_time'] = $time;
            $data['status'] = $params['status'];
            $data['remark'] = $params['remark'] ?? '';
        } else {
            $result = $validate->scene('edit')->check($params);
            $data = $params;

            $checkInfo = $this->service->checkOnline($data['sku_id'], $data['warehouse_id']);
            if ($checkInfo === true) {
                return json(['message' => '当前sku正在跌价活动中'], 400);
            }

            $data['valid_time'] = strtotime($data['valid_time']);
            $data['over_time'] = strtotime($data['over_time']);
            $data['proposer_id'] = $user['user_id'];
        }

        if ($result !== true) {
            return json(['message' => $validate->getError()], 400);
        }

        $result = $this->service->update($id, $data);
        if ($result === false) {
            return json(['message' => $this->service->getError()], 400);
        }

        return json(['message' => '提交成功', 'data' => $result]);
    }

    /**
     * @title 跌价申请（查看）
     * @method get
     * @param $id
     * @url /goods-discount/:id/read
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $result = $this->service->read(['id' => $id]);

        if (!$result) {
            return json(['message' => $this->service->getError()], 400);
        }

        $result['log'] = $this->service->readLog($id);
        return json($result, 200);
    }

    /**
     * @title 跌价申请（自动匹配价格）
     * @method get
     * @param $id
     * @param $warehouse
     * @url /goods-discount/:id/auto-price/:warehouse/warehouse
     * @return \think\response\Json
     */
    public function autoPrice($id, $warehouse)
    {
        try {
            if (empty($id) || empty($warehouse)) {
                throw new Exception("请求参数错误");
            }

            $result = $this->service->getSkuInfo($id, $warehouse);
            return json($result, 200);

        } catch (Exception $e) {

            return json(['message' => $e->getMessage()], 400);

        }
    }

    /**
     * @title 跌价申请（批量审核）
     * @method post
     * @url /goods-discount/batch
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function batchSave(Request $request)
    {
        try {
            $status = $request->post('status');
            $ids = $request->post('ids');
            $remark = $request->post('remark');
            $idArr = json_decode($ids);

            if (empty($status) || empty($ids) || empty($idArr)) {
                throw new Exception("请求参数错误");
            }

            $this->service->batchSave($idArr, $status, $remark);
            return json(['message' => '操作成功'], 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @title 报告导出
     * @method POST
     * @url /goods-discount/export
     */
    public function export()
    {
        set_time_limit(0);
        try {
            $result = $this->service->reportExport();
            return json($result, 200);
        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * @title 批量导入
     * @method POST
     * @url /goods-discount/batch-import
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function batchImport(Request $request)
    {
        $params = $request->param();
        $user = CommonService::getUserInfo();
        try {
            $re = $this->service->import($params, $user['user_id']);
            return json($re, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @title 导入模板下载
     * @method GET
     * @url /goods-discount/import-template
     * @return \think\response\Json
     */
    public function importTemplate()
    {
        try {
            $result = $this->service->importTemplate();
            return json($result);
        } catch (\Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

}