<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\SaleRefundService;
use think\Request;

/**
 * @module 报表系统
 * @title 销退报表
 * @url report/sale-refund
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2017/10/19
 * Time: 09:26
 */
class SaleRefund extends Base
{
    protected $saleRefundService;
    protected $orderDetailModel;

    protected function init()
    {
        if(is_null($this->saleRefundService)){
            $this->saleRefundService = new SaleRefundService();
        }
    }

    /**
     * @title 列表详情
     * @param Request $request
     * @return \think\response\Json
     * @apiRelate app\warehouse\controller\Warehouse::warehouses
     */
    public function index(Request $request)
    {
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',10);
        $params = $request->param();
        $result = $this->saleRefundService->lists($page,$pageSize,$params);
        return json($result);
    }

    /**
     * @title 导出
     * @url export
     * @method post
     */
    public function export(Request $request)
    {
        $params = $request->param();
        try{
            $this->saleRefundService->applyExport($params);
            return json(['message'=> '成功加入导出队列']);
        }catch (\Exception $ex){
            $code = $ex->getCode();
            $msg  = $ex->getMessage();
            if(!$code){
                $code = 400;
                $msg  = '程序内部错误';
            }
            return json(['message'=>$msg], $code);
        }
    }
}