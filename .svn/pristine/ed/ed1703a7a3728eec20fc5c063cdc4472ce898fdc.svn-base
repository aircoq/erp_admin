<?php

namespace app\finance\controller;

use app\common\controller\Base;
use think\Exception;
use think\Request;
use app\finance\service\WishSettlementService;
use app\common\service\Common;

/**
 * @module 财务管理
 * @title wish结算报告
 * @url /wish-settlement
 * @author wangwei
 * @package app\finance\controller
 */
class WishSettlement extends Base
{
    /**
     * @title wish结算报告列表
     * @method get
     * @author wangwei
     * @url index_settle
     * @param Request $request
     * @throws \Exception
     */
    public function indexSettle(Request $request)
    {
        $params = $request->param();
        $service = new WishSettlementService();
        
        $data = $service->getIndexData($params);
        return json($data,200);
    }

    /**
     * @title wish结算报告导出
     * @method post
     * @url export
     * @author wangwei
     * @time 2018-12-11 17:06:27
     * @param Request $request
     * @return \think\response\Json
     * @desc wish结算报告详细情况导出，现暂时不用 time 2019/1/11 11:14 linpeng
     */
    public function export(Request $request)
    {
        try{
            $params = $request->param();
            $service = new WishSettlementService();
            $user = Common::getUserInfo($request);
            $service->export($params,$user['user_id']);
            return json(['message'=> '申请成功', 'join_queue' => 1], 200);
        }catch (Exception $ex){
            return json(['message' => $ex->getMessage()],400);
        }

    }

    /**
     * @title wish汇总结算报告导出
     * @method post
     * @url export-sum
     * @author linpeng
     * @time 2019/1/11 16:07
     * @param Request $request
     * @return \think\response\Json
     */
    public function exportSum(Request $request)
    {
        try{
            $params = $request->param();
            $service = new WishSettlementService();
            $user = Common::getUserInfo($request);
            $service->applyExportSum($params,$user['user_id']);
            return json(['message' =>'申请成功', 'join_queue' => 1], 200);
        }catch (Exception $ex){
            return json(['message' => $ex->getMessage()],400);
        }

    }

}
