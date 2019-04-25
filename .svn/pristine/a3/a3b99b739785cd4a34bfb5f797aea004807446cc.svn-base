<?php

namespace app\report\controller;

use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\report\service\SaleStockService;
use app\report\service\WarehousePackageService;
use think\Request;
use think\Validate;

/**
 * @module 报表系统
 * @title 首页仓库统计
 * @url report/warehouse-package
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/01/08
 * Time: 15:55
 */
class WarehousePackage extends Base
{
    protected $warehousePackageService;

    protected function init()
    {
        if (is_null($this->warehousePackageService)) {
            $this->warehousePackageService = new WarehousePackageService();
        }
    }

    /**
     * @title 仓库统计
     * @param Request $request
     * @return \think\response\Json
     * @apiRelate app\warehouse\controller\Warehouse::warehouses
     * @apiRelate app\goods\controller\Category::lists
     */
    public function index(Request $request)
    {
        $params = $request->param();
        $result = $this->warehousePackageService->lists($params);
        return json(['message' => '拉取成功', 'data' => $result]);
    }

    /**
     * @title 未操作包裹详情
     * @url unpacked-detail
     * @method get
     */
    public function unpackedDetail(Request $request)
    {
        $params = $request->param();
        $warehouseId = $request->get('warehouse_id', 2);
        if ($warehouseId < 1) {
            throw new JsonErrorException('仓库ID错误');
        }
        $result = $this->warehousePackageService->unpackedDetail($warehouseId);
        return json(['message' => '拉取成功', 'data' => $result]);
    }

    /**
     * @title 未发货记录
     * @url log-unfilled
     * @method get
     */
    public function logUnfilled(Request $request)
    {
        $params = $request->param();
        $result = $this->warehousePackageService->logUnfilled($params);
        return json(['message' => '拉取成功', 'data' => $result]);
    }

    /**
     * @title 未发货记录详情
     * @url log-unfilled-details
     * @method get
     */
    public function logUnfilledDetails(Request $request)
    {
        $warehouseId = $request->get('warehouse_id', 2);
        $dateline = $request->get('dateline', date('Y-m-d'));
        $result = $this->warehousePackageService->logUnfilledDetails($warehouseId, $dateline);
        return json(['message' => '拉取成功', 'data' => $result]);
    }

    /**
     * @title 已发货记录
     * @url log-shipped
     * @method get
     */
    public function logShipped(Request $request)
    {
        $params = $request->param();
        $result = $this->warehousePackageService->logShipped($params);
        return json(['message' => '拉取成功', 'data' => $result]);
    }

    /**
     * @title 已发货记录详情
     * @url log-shipped-details
     * @method get
     */
    public function logShippedDetails(Request $request)
    {
        $warehouseId = $request->get('warehouse_id', 2);
        $dateline = $request->get('dateline', date('Y-m-d'));
        $result = $this->warehousePackageService->logShippedDetails($warehouseId, $dateline);
        return json(['message' => '拉取成功', 'data' => $result]);
    }

    /**
     * @title 未拆包记录
     * @url log-not-opened
     * @method get
     */
    public function logNotOpen(Request $request)
    {
        $params = $request->param();
        $result = $this->warehousePackageService->logNotOpen($params);
        return json(['message' => '拉取成功', 'data' => $result]);
    }

    /**
     * @title 缺货记录
     * @url log-stock
     * @method get
     */
    public function logStock(Request $request)
    {
        $params = $request->param();
        $result = $this->warehousePackageService->logStock($params);
        return json(['message' => '拉取成功', 'data' => $result]);
    }

    /**
     * @title 缺货记录详情
     * @url log-stock-details
     * @method get
     */
    public function logStockDetails(Request $request)
    {
        $warehouseId = $request->get('warehouse_id', 2);
        $dateline = $request->get('dateline', date('Y-m-d'));
        $result = $this->warehousePackageService->logStockDetails($warehouseId, $dateline);
        return json(['message' => '拉取成功', 'data' => $result]);
    }

    /**
     * @title 仓库列表
     * @url warehouse
     * @method get
     */
    public function warehouse(Request $request)
    {
        $params = $request->param();
        $result = $this->warehousePackageService->getWarehouses($params);
        return json(['message' => '拉取成功', 'data' => $result]);
    }

    /**
     * @title 手动跑任务
     * @url manual
     * @method get
     */
    public function manualRunTask(Request $request)
    {
        $params = $request->param();
        $result = $this->warehousePackageService->manualRunTask();
        return json(['message' => '跑成功了', 'data' => $result]);
    }
}