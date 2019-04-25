<?php


namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\OverAgeService;
use think\Exception;
use think\Request;
use app\warehouse\service\WarehouseConfig;



/**
 * Class OverAge
 * @package app\report\controller
 * @module 报表系统
 * @title 超库龄报表
 * @url /report/over-age
 */
class OverAge extends Base
{
    /**
     * @title 超库龄列表
     * @param \think\Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $params = $request->param();
        $service = new OverAgeService();
        try {
            $service->where($params);
            $page = param($params, 'page', 1);
            $pageSize = param($params, 'pageSize', 20);
            $data = $service->index($page, $pageSize);
            $count = $service->getCount();
            $result = [
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
                'data' => array_values($data)
            ];
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' =>$ex->getLine().$ex->getMessage()], 400);
        }
    }


    /**
     * @title 超库龄报表导出
     * @method post
     * @url export
     * @param  \think\Request $request
     * @return \think\response\Json
     */
    public function exportDetail(Request $request)
    {
        $params = $request->param();
        $service = new OverAgeService();
        try {
            $result = $service->batchExport($params);
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' => $ex->getMessage()], 400);
        }
    }
}
