<?php

namespace app\finance\controller;

use app\common\controller\Base;
use think\Exception;
use think\Request;
use app\finance\service\AliexpressSettlementService;

/**
 * @module 财务管理
 * @title aliexpress结算报告
 * @url /aliexpress-settlement
 * @author wangwei
 * @package app\finance\controller
 */
class AliexpressSettlement extends Base
{
    /**
     * @title aliexpress结算报告列表
     * @method GET
     * @author wangwei
     * @url index_settle
     * @param Request $request
     * @throws \Exception
     */
    public function indexSettle(Request $request)
    {
        $params = $request->param();
        $service = new AliexpressSettlementService();
        $idRe = $service->getIndexData($params);
        return json($idRe,200);
    }

    /**
     * @title aliexpress结算报告导出
     * @method POST
     * @url export
     * @param Request $request
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        $params = $request->param();

        $export_type = intval(param($params,"export_type",0));

        if($export_type == 1)    //部分导出
        {
           $account_id_arr = json_decode(param($params,'account_id_arr',''),true);
           if(!is_array($account_id_arr) || empty($account_id_arr))
           {
               return json(['message' => '请先选择一条记录'], 400);
           }
           $params['account_id_arr'] = $account_id_arr;
        }

        $service = new AliexpressSettlementService();
        $service->exportApply($params);
        return json(['message' => '加入导出队列成功']);
    }

}
