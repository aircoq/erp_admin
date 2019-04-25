<?php
namespace app\customerservice\controller;

use app\common\controller\Base;
use app\common\model\AfterSaleService;
use app\common\service\AfterSaleType;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\OrderSaleAdoptQueue;
use think\Request;
use think\Exception;
use app\customerservice\service\OrderSaleService;
use app\common\service\Common as CommonService;
use app\customerservice\service\OrderSaleExportService;

/**
 * @module 客服管理
 * @title 订单售后
 * @author phill
 * @url /order-sales
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/18
 * Time: 10:55
 */
class OrderSale extends Base
{
    protected $saleService;

    protected function init()
    {
        if (is_null($this->saleService)) {
            $this->saleService = new OrderSaleService();
        }
    }

    /**
     * @title 首页列表
     * @return \think\response\Json
     * @apiRelate app\index\controller\User::index
     * @apiFilter app\customerservice\filter\OrderSaleAccountFilter
     */
    public function index()
    {
        $request = Request::instance();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 10);
        $params = $request->param();
        $where = [];
        $join = [];
        if (isset($params['source']) && $params['source'] != '') { //来源类型
            $where['a.source_type'] = ['=', $params['source']];
        }
        if (isset($params['approve'])) {  //审批状态
            $where['a.approve_status'] = ['=', $params['approve']];
        }
        if (isset($params['status'])) {   //执行状态
            switch ($params['status']) {
                case 4:
                    $where['a.reissue_returns_status'] = ['=', 1];
                    break;
                case 5:
                    $where['a.reissue_returns_status'] = ['=', 2];
                    break;
                case 6:
                    $where['a.refund_status'] = ['=', 4];
                    break;
                case 7:
                    $where['a.reissue_returns_status'] = ['=', 3];
                    break;
                default:
                    $where['a.refund_status'] = ['=', $params['status']];
                    break;
            }
        }
        if (isset($params['channel_id']) && !empty($params['channel_id'])) {  //渠道id
            $where['a.channel_id'] = ['=', $params['channel_id']];
        }
        if (isset($params['submitter'])) {  //提交人
            $where['a.submitter_id'] = ['=', trim($params['submitter'])];
        }
        if (isset($params['snDate'])) {
            $params['date_b'] = isset($params['date_b']) ? $params['date_b'] : '';
            $params['date_e'] = isset($params['date_e']) ? $params['date_e'] : '';
            switch ($params['snDate']) {
                case 'submit_time':
                    $condition = timeCondition($params['date_b'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $where['a.submit_time'] = $condition;
                    }
                    break;
                case 'approve_time':
                    $condition = timeCondition($params['date_b'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $where['a.approve_time'] = $condition;
                    }
                    break;
                case 'create_time':
                    $condition = timeCondition($params['date_b'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $where['a.create_time'] = $condition;
                    }
                    break;
                default:
                    break;
            }
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            $snText = trim($params['snText']);
            switch ($params['snType']) {
                case 'order_num':
                    $where['a.order_number'] = ['like', '%' . $snText . '%'];
                    break;
                case 'buyer_id':
                    $where['a.buyer_id'] = ['like', '%' . $snText . '%'];
                    break;
                case 'sale_num':
                    $where['a.sale_number'] = ['like', '%' . $snText . '%'];
                    break;
                case 'sku':
                    $where['b.sku'] = ['like', '%' . $snText . '%'];
                    $join[] = ['order_detail b', 'a.order_id = b.order_id', 'left'];
                    break;
                default:
                    break;
            }
        }
        $orderBy = fieldSort($params);
        $result = $this->saleService->index($where, $page, $pageSize, $join, $orderBy);
        return json($result, 200);
    }

    /**
     * @title 获取编辑信息
     * @param $id
     * @return \think\response\Json
     */
    public function edit($id)
    {
        $result = $this->saleService->read($id);
        if (is_array($result)) {
            return json($result, 200);
        } else {
            return json(['message' => $result], 500);
        }
    }

    /**
     * @title 查看售后信息
     * @param $id
     * @return \think\response\Json
     */
    public function read($id)
    {
        $result = $this->saleService->read($id);
        if (is_array($result)) {
            return json($result, 200);
        } else {
            return json(['message' => $result], 500);
        }
    }

    /**
     * @title 更新售后信息
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     * @apiRelate app\order\controller\Order::info
     */
    public function update(Request $request, $id)
    {
        $params = $request->param();
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $params['operator'] = $user['realname'];
            $params['operator_id'] = $user['user_id'];
        }
        $result = $this->saleService->update($params, $id);
        return json(['message' => '更新成功', 'data' => $result]);
    }

    /**
     * @title 批量提交
     * @url /order-sales/batch-update
     * @method post
     * @throws Exception
     */
    public function batchUpdate()
    {
        $request = Request::instance();
        $afterIds = $request->post('ids', []);
        $afterIds = json_decode($afterIds, true);
        if (empty($afterIds) || !is_array($afterIds)) {
            throw new Exception('参数值错误');
        }
        //查出是谁操作的
        $operator = [];
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $operator['operator'] = $user['realname'];
            $operator['operator_id'] = $user['user_id'];
        }
        $orderSaleService = new OrderSaleService();
        $result = $orderSaleService->batchUpdate($afterIds, $operator);
        return json($result);
    }

    /**
     * @title 新建售后信息
     * @param Request $request
     * @return \think\response\Json
     * @apiRelate app\order\controller\Order::info
     * @apiRelate app\order\controller\ManualOrder::shipping
     */
    public function save(Request $request)
    {
        $params = $request->param();
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $params['operator'] = $user['realname'];
            $params['operator_id'] = $user['user_id'];
        }
        $result = $this->saleService->add($params);
        return json(['message' => '新增成功', 'data' => $result]);
    }

    /**
     * @title 删除售后
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id)
    {
        if (empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        $this->saleService->delete($id);
        return json(['message' => '删除成功']);
    }

    /**
     * @title 获取状态
     * @url /order-sales/:type(\w+)/info
     * @return \think\response\Json
     */
    public function info()
    {
        $request = Request::instance();
        $params = $request->param();
        $type = $params['type'];
        $result = $this->saleService->$type();
        return json($result, 200);
    }

    /**
     * @title 获取渠道信息
     * @url channels
     * @return \think\response\Json
     */
    public function channels()
    {
        $result = $this->saleService->channel();
        return json($result, 200);
    }

    /**
     * @title 审批通过
     * @url /order-sales/adopt/status
     * @method post
     * @return \think\response\Json
     */
    public function adopt()
    {
        $request = Request::instance();
        $sale_id = $request->post('id', 0);
        $remark = $request->post('remark', '');
        if (empty($sale_id)) {
            return json(['message' => '参数值错误'], 400);
        }
        $operator = [];
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $operator['id'] = $user['user_id'];
            $operator['operator'] = $user['realname'];
        }
        $saleServiceInfo = (new AfterSaleService())->field(true)->where(['id' => $sale_id])->find();
        $type = $saleServiceInfo['type'];
        if ((AfterSaleType::Refund == $type) || (AfterSaleType::RefundAndReplacementGoods == $type)
            || (AfterSaleType::RefundAndReturnGoods== $type)
            || (AfterSaleType::RefundAndReplacementGoodsAndReturnGoods == $type)
        ) {
            //判断审批是否加入队列
            $result = $this->saleService->existsCache($sale_id);
            if ($result) {
                return json(['message'=> '售后单已加入队列，请勿重复操作'], 400);
            } else {
                //将售后单id存入缓存
                $this->saleService->setCache($sale_id);
            }
            $params = [];
            $params['id'] = $sale_id;
            $params['operator'] = $operator;
            $params['remark'] = $remark;
            (new UniqueQueuer(OrderSaleAdoptQueue::class))->push($params);
            return json(['message'=>'加入队列成功'], 200);
        } else {
            $result = $this->saleService->adopt($sale_id, $operator, $remark);
        }
        if($result['status']){
//            return json(['message'=>'操作成功', 'data'=>$result['data']], 200);
            return json(['message'=>'操作成功'], 200);
        }else{
            return json(['message'=>$result['message']], 400);
        }
    }

    /**
     * @title 退回修改
     * @url /order-sales/retreat/status
     * @method post
     * @return \think\response\Json
     */
    public function retreat()
    {
        $request = Request::instance();
        $sale_id = $request->post('id', 0);
        $remark = $request->post('remark', '');
        if (empty($sale_id)) {
            return json(['message' => '参数值错误'], 400);
        }
        $operator = [];
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $operator['id'] = $user['user_id'];
            $operator['operator'] = $user['realname'];
        }
        //查询售后单是否存在队列中
        $result = $this->saleService->existsCache($sale_id);
        if ($result) {
            return json(['message' => '当前售后单正在队列中审批，无法退回修改'], 400);
        }
        $result = $this->saleService->retreat($sale_id, $operator, $remark);
        if($result['status']){
//            return json(['message'=>'操作成功', 'data'=>$result['data']], 200);
            return json(['message'=>'操作成功'], 200);
        }else{
            return json(['message'=>$result['message']], 400);
        }
    }

    /**
     * @title 退款标记为完成
     * @url /order-sales/complete/status
     * @method post
     * @return \think\response\Json
     */
    public function complete()
    {
        $request = Request::instance();
        $sale_id = $request->post('id', 0);
        $remark = $request->post('remark', '');
        if (empty($sale_id)) {
            return json(['message' => '参数值错误'], 400);
        }
        $operator = [];
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $operator['id'] = $user['user_id'];
            $operator['operator'] = $user['realname'];
        }
        $result = $this->saleService->complete($sale_id, $operator, $remark);
        return json(['message' => '操作成功', 'data' => $result], 200);
    }

    /**
     * @title 退款重新执行
     * @url /order-sales/again/status
     * @method post
     * @return \think\response\Json
     */
    public function again()
    {
        $request = Request::instance();
        $sale_id = $request->post('id', 0);
        $remark = $request->post('remark', '');
        if (empty($sale_id)) {
            return json(['message' => '参数值错误'], 400);
        }
        $operator = [];
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $operator['id'] = $user['user_id'];
            $operator['operator'] = $user['realname'];
        }
        $this->saleService->again($sale_id, $operator, $remark);
        return json(['message'=>'重新加入退款队列成功'], 200);
//        if ($result['status']) {
//            return json(['message' => '操作成功', 'data' => $result['data']], 200);
//        } else {
//            return json(['message'=>$result['message']], 400);
//        }
    }

    /**
     * @title 提交审批
     * @url submit
     * @method post
     * @return \think\response\Json
     */
    public function submit()
    {
        $request = Request::instance();
        $id = $request->post('id', 0);
        $operator_id = 0;
        if (empty($id)) {
            return json(['message' => '参数错误'], 400);
        }
        //查出是谁操作的
        $user = CommonService::getUserInfo($request);
        if (!empty($user)) {
            $operator_id = $user['user_id'];
        }
        $result = $this->saleService->submitApproval($id, $operator_id);
        return json(['message' => '提交成功', 'data' => $result]);
    }

    /**
     * @title 查找订单
     * @url find
     * @return \think\response\Json
     */
    public function findOrder()
    {
        $request = Request::instance();
        $content = $request->get('content', '');
        return $this->saleService->find($content);
    }

    /**
     * @title execl字段信息
     * @url export-title
     * @method get
     * @return \think\response\Json
     */
    public function title()
    {
        $orderSaleExportService = new OrderSaleExportService();
        $exportTitle = $orderSaleExportService->title();
        $title = [];
        foreach ($exportTitle as $key => $value) {
            if ($value['is_show'] == 1) {
                $temp['key'] = $value['title'];
                $temp['title'] = $value['remark'];
                array_push($title, $temp);
            }
        }
        return json($title);
    }

    /**
     * @title 导出execl
     * @url export
     * @method post
     * @apiParam name:ids desc:选中的id---不传表示全部
     * @apiParam name:export_type desc:0-部分  1-全部
     * @return \think\response\Json
     * @apiRelate app\index\controller\DownloadFile::downExportFile
     */
    public function export()
    {
        $request = Request::instance();
        $params = $request->param();
        $ids = $request->post('ids', 0);
        if (isset($request->header()['x-result-fields'])) {
            $field = $request->header()['x-result-fields'];
            $field = explode(',', $field);
        } else {
            $field = [];
        }
        $type = $request->post('export_type', 0);
        $ids = json_decode($ids, true);
        if (empty($ids) && empty($type)) {
            return json(['message' => '请先选择一条记录'], 400);
        }
        if (!empty($type)) {
            $params = $request->param();
            $ids = [];
        }
        $result = ( new OrderSaleExportService())->exportOnline($ids, $field, $params);
        return json($result);
    }

    /**
     * @title 批量审核
     * @method post
     * @url /order-sales/batch-adopt
     * @return \think\response\Json
     * @author Reece
     * @date 2018-08-29 15:04:56
     */
    public function batchAdopt()
    {
        try{
            $request = Request::instance();
            $saleIds = $request->post('ids', []);
            $saleIds = json_decode($saleIds, true);
            $remark = $request->post('remark', '');
            if (empty($saleIds) || !is_array($saleIds)) {
                throw new Exception('参数值错误');
            }
            $operator = [];
            //查出是谁操作的
            $user = CommonService::getUserInfo($request);
            if (!empty($user)) {
                $operator['id'] = $user['user_id'];
                $operator['operator'] = $user['realname'];
            }
            $res = [];
            $afterSaleServiceModel = new AfterSaleService();
            $num = 0;
            foreach($saleIds as $id){
                $saleServiceInfo = $afterSaleServiceModel->field(true)->where(['id' => $id])->find();
                $type = $saleServiceInfo['type'];
                if ((AfterSaleType::Refund == $type) || (AfterSaleType::RefundAndReplacementGoods == $type)
                    || (AfterSaleType::RefundAndReturnGoods== $type)
                    || (AfterSaleType::RefundAndReplacementGoodsAndReturnGoods == $type)
                ) {
                    //判断审批是否加入队列
                    $result = $this->saleService->existsCache($id);
                    if ($result) {
                        $result['status'] = false;
                        $result['message'] = '售后单已加入队列，请勿重复操作';
                    } else {
                        //将售后单id存入缓存
                        $ttl = $num + 300;
                        $this->saleService->setCache($id, $ttl);
                        $params = [];
                        $params['id'] = $id;
                        $params['operator'] = $operator;
                        $params['remark'] = $remark;
                        (new UniqueQueuer(OrderSaleAdoptQueue::class))->push($params);
                        //将售后单改为退款中
                        $afterSaleServiceModel->update(['refund_status' => 4], ['id' => $id]);
                        $result['status'] = true;
                        $result['message'] = '操作成功';
                        $num += 30;
                    }
                } else {
                    $result = (new OrderSaleService())->adopt($id, $operator, $remark);
                }
                $data = (new OrderSaleService())->index(['a.id' => $id]);
                isset($data['data']) && $result['data'] = $data['data'];
                $res[$id] = $result;
            }
            return json($res);
        }catch (Exception $ex){
            return json(['message'=>$ex->getMessage()], 400);
        }
    }

    /**
     * @title 批量退回修改
     * @method post
     * @url /order-sales/batch-retreat
     * @return \think\response\Json
     * @author Reece
     * @date 2018-08-29 15:18:16
     */
    public function batchRetreat()
    {
        try{
            $request = Request::instance();
            $saleIds = $request->post('ids', []);
            $saleIds = json_decode($saleIds, true);
            $remark = $request->post('remark', '');
            if (empty($saleIds) || !is_array($saleIds)) {
                throw new Exception('参数值错误');
            }
            if(strlen($remark)<1){
                throw new Exception('备注必填');
            }
            $operator = [];
            //查出是谁操作的
            $user = CommonService::getUserInfo($request);
            if (!empty($user)) {
                $operator['id'] = $user['user_id'];
                $operator['operator'] = $user['realname'];
            }
            $res = [];
            foreach($saleIds as $id){
                $result = (new OrderSaleService())->retreat($id, $operator, $remark);
                $res[$id] = $result;
            }
            return json($res);
        }catch (Exception $ex){
            return json(['message'=>$ex->getMessage()], 400);
        }
    }

}