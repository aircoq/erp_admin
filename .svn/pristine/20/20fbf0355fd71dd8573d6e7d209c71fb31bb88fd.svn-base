<?php


namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\ShippingPrice as Service;
use think\Exception;
use think\Request;



/**
 * Class ShippingLog
 * @package app\report\controller
 * @module 报表系统
 * @title 报价对比表格
 * @url /report/shipping-price
 */
class ShippingPrice extends Base
{
    /**
     * @title 报价对比表格列表
     * @param \think\Request $request
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $params = $request->param();
        $page = param($params, 'page', 1);
        $pageSize = param($params, 'pageSize', 20);
        $service = new Service();
        try {
            $service->where($params);
            $count =  $service->getCount();
            $data = $service->getLists($page, $pageSize);
            $result = [
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
                'data' =>$data
            ];
            return json($result, 200);
        } catch (Exception $ex) {
            return json(['message' =>$ex->getLine().$ex->getMessage()], 400);
        }
    }
}
