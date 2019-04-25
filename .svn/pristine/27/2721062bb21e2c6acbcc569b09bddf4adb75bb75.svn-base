<?php
namespace app\finance\controller;

use app\common\controller\Base;
use app\common\model\VirtualFinancePurchase;
use think\Exception;
use think\Request;
use app\common\service\Common as CommonService;
use app\finance\service\FinancePurchase as FinancePurchaseService;
use app\common\model\PurchaseOrderLog as PurchaseOrderLogModel;
use app\common\exception\JsonErrorException;

/**
 * @title 采购单付款信息
 * @author RondaFul
 * @url /finance-purchase
 */
class FinancePurchase extends Base
{
    /** 
     * @title  显示列表
     * @return \think\response\Json
     * @throws \app\common\cache\Exception
     */
    public function index(Request $request, FinancePurchaseService $service)
    {   
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        //搜索条件
        $params = $request->param();
        $service->where($params);
        $result = [
            'data'       => $service->getList([], '*', $page, $pageSize),
            'page'       => $page,
            'pageSize'   => $pageSize,
            'count'      => $service->count(),
        ];
        return json($result, 200);
    }

    /**
     * @title  批量标记付款
     * @method post
     * @url /finance-purchase/batchChangeStatus
     * @param Request $request
     * @return \think\Response
     */
    public function batchChangeStatus(Request $request, FinancePurchaseService $service)
    {
        $params = $request->param();
        if (!isset($params['id']) || empty($params['id'])) {
            return json(['message' => 'ID不能为空'], 400);
        }
        
        if (!isset($params['status']) || empty($params['status'])) {
           return json(['message' => '状态时必填项'], 400);
        }else{
            if(!in_array($params['status'],[2,3])){
                return json(['message' => '目标状态只能是已付款，取消付款'], 400);
            }
        }

        $ids = explode(',', $params['id']);
        $ids  = array_unique($ids);
        if (empty($ids) || !is_array($ids)) {
           return json(['message' => 'ID不能为空'], 400);
        }
        $isYksFinance = VirtualFinancePurchase::alias('f')->field('f.finance_purchase_id')->join('virtual_supplier s', 'f.virtual_supplier_id=s.id', 'left')
            ->where(['f.finance_purchase_id'=>['in', $ids], 's.real'=>1])->select();
        if($isYksFinance){
            return json(['message' => 'ID '.implode(',', array_column($isYksFinance, 'finance_purchase_id')). ' 不能操作']);
        }
        $user = CommonService::getUserInfo($request);        
        try {
            $service->changStatus($ids, $params['status'], $user['user_id']);
            foreach($service->purchase_order_id_list as $tmp_purchase_order_id){
                $service->changePurchaseOrderPaymentStatus($tmp_purchase_order_id);
            }
            return json(['message' => '操作成功'], 200);
        }catch(Exception $ex) {
            return json(['message' => '操作失败' . $ex->getMessage()], 400);
        }
    }


    /**
     * @title 导出采购结算
     * @author Reece
     * @date 2018-05-08
     * @url /finance-purchase/export
     * @apiParam name:ids type:string require:1 desc:导出id[1,2]
     * @apiParam name:export_type type:string require:1 desc:导出类型：2部分导出，1全部导出
     * @apiReturn status:状态 1-成功
     * @apiReturn message:操作信息
     * @apiReturn file_code:文件状态码
     * @method POST
     */
    public function export(Request $request)
    {
        CommonService::repeatRequestLimit($request, 'finance_purchase_export', 5);
        set_time_limit(0);
        //搜索条件
        $params    = $request->param();
        $export_type = param($params, 'export_type', '');
        $ids = param($params, 'ids', '');
        $service = new FinancePurchaseService();
        if (!$export_type || !in_array($export_type, [1, 2])) {
            throw new JsonErrorException('参数错误');
        }
        if ($export_type == 2) {
            //勾选id导出
            if (!$ids || !is_array($ids_arr = json_decode($ids, true)) || empty($ids_arr)) {
                throw new JsonErrorException('勾选项格式错误');
            }
            $where = [
                'id' => ['in', $ids_arr]
            ];
        } else {
            //根据条件导出
            $where = $service->Where($params);
        }

        $count = $service->count($where);
        $page_size = 1000; //每次最多执行1000条数据
        $num = ceil($count/$page_size);
        $exportlist = [];
        for ($i=1;$i<=$num;$i++){
            $lists=$service->getList($where, '*', $i, $page_size);
            $exportlist = array_merge($exportlist,$lists);
        }

        $result = $service->export($exportlist);
        return json($result,200);
    }
}