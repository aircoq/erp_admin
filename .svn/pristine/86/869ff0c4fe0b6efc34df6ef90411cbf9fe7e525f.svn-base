<?php
namespace app\report\controller;

use app\common\controller\Base;
use app\report\service\SaleStockService;
use think\Request;
use think\Validate;

/**
 * @module 报表系统
 * @title 报表销量及库存
 * @url report/sale-stock
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2017/10/20
 * Time: 10:07
 */
class SaleStock extends Base
{
    protected $saleStockService;



    protected function init()
    {
        if(is_null($this->saleStockService)){
            $this->saleStockService = new SaleStockService();
        }
    }

    /**
     * @title 列表详情
     * @param Request $request
     * @return \think\response\Json
     * @apiRelate app\warehouse\controller\Warehouse::warehouses
     * @apiRelate app\goods\controller\Category::lists
     */
    public function index(Request $request)
    {
        $page = $request->get('page',1);
        $pageSize = $request->get('pageSize',10);
        $params = $request->param();
        $result = $this->saleStockService->lists($page,$pageSize,$params);
        return json($result);
    }

    /**
     * @title 导出
     * @url export
     * @method post
     */
    public function applyExport(Request $request)
    {

        $params = $request->param();
        if(isset($params['goods_list'])){
            unset($params['goods_list']);
        }
        try{
             $this->saleStockService->applyExport($params);
            return json(['message'=> '成功加入导出队列']);
        } catch (\Exception $ex) {
            $code = $ex->getCode();
            $msg  = $ex->getMessage();
            if(!$code){
                $code = 400;
                $msg  = '程序内错误';
            }
            return json(['message'=>$msg], $code);
        }
    }
}