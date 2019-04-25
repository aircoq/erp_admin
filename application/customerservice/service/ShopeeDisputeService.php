<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 2018/9/18
 * Time: 9:39
 */

namespace app\customerservice\service;
use app\common\cache\Cache;
use app\common\model\shopee\ShopeeCancel as ModelShopeeCancel;
use app\common\model\shopee\ShopeeOrder;
use app\common\model\shopee\ShopeeReturn as ModelShopeeReturn;
use app\common\model\shopee\ShopeeReturnDetail as ModelShopeeReturnDetail;
use app\common\model\shopee\ShopeeReturnDispute as ModelShopeeReturnDispute;
use app\common\model\shopee\ShopeeReturnLog as ModelShopeeReturnLog;
use app\common\service\Common;
use app\index\service\DownloadFileService;
use erp\Redis;
use service\shopee\ShopeeApi;
use think\Exception;
use think\Db;
use app\order\service\OrderService;
use app\order\service\ShopeeService;
use app\common\service\ChannelAccountConst;

class ShopeeDisputeService
{
    const UPLOAD_IMAGE_ALLOW_EXT = ['jpg','jpeg','png'];

    protected $accountId;
    protected $shopeeService;
    protected $shopeeReturnService;
    protected $shopeeOrderService;
    protected $shopeeImageService;

    public function __construct()
    {

    }

    private function getParam($account_id)
    {
        $account = Cache::Store('ShopeeAccount')->getTableRecord($account_id);
        return [
            'partner_id' => $account['partner_id'],
            'shop_id' => $account['shop_id'],
            'key' => $account['key']
        ];
    }

    public function setAccountId($accountId)
    {
        $param = $this->getParam($accountId);
        $this->shopeeService = ShopeeApi::instance($param);
    }

    public function getReturnService($accountId)
    {
        $param = $this->getParam($accountId);
        return ShopeeApi::instance($param)->loader('returns');
    }

    public function getOrderService($accountId)
    {
        $param = $this->getParam($accountId);
        return ShopeeApi::instance($param)->loader('order');
    }

    public function getImageService($accountId)
    {
        $param = $this->getParam($accountId);
        return ShopeeApi::instance($param)->loader('image');
    }

    public function getReturnList($accountId, $pageOffset=0, $pageLength=50)
    {
        return $this->getReturnService($accountId)->getReturnList($pageOffset,$pageLength);
    }

    /**
     * 获取订单纠纷列表
     * @param $param
     * @return ModelShopeeOrder|array
     * @throws Exception
     */
    public function index($param)
    {
        try {
            $param['type'] = $param['type'] ? (int)$param['type'] : 2;//纠纷类型：退货
            isset($param['ids']) && $param['ids'] = json_decode($param['ids'],true);//与后续参数排斥

            $param['status_code'] = isset($param['status_code']) ? (int)$param['status_code'] : 1;//处理状态：待处理
            isset($param['account_id']) && $param['account_id'] = (int)$param['account_id'];
            isset($param['time_start']) && $param['time_start'] = (int)$param['time_start'];
            isset($param['time_end']) && $param['time_end'] = (int)$param['time_end'];
            isset($param['user_id']) && $param['user_id'] = (int)$param['user_id'];
            $param['page'] = isset($param['page']) ? (int)$param['page'] : 1;
            $param['page_size'] = isset($param['page_size']) ? (int)$param['page_size'] : 50;
            $param['export'] = isset($param['export']) ? (int)$param['export'] : 0;//是否导出：否

//        var_dump('<pre/>',$param);
            if($param['type']==1){//订单取消 1
                $result = $this->cancelList($param);
            }else{//订单退货 2
                $result = $this->returnList($param);
            }
            //回传请求参数
            $result = array_merge($result, $param);

            if(isset($param['export']) && $param['export']){//实时导出csv文件
                /*$file = $this->exportToCsv($result['list']);
                $result['file'] = $file;*/
                return self::export($result['list'], $param['type']);
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public static function export($data, $type=1)
    {
        //格式化数据：时间
        foreach ($data as &$v){
            $type==1 && $v['returnsn'] = '';
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $v['update_time'] = date('Y-m-d H:i:s', $v['update_time']);
            $v['due_date'] = date('Y-m-d H:i:s', $v['due_date']);
        }
        unset($v);
        $header = [
            ['title'=>'平台退货单号','key'=>'returnsn','width'=>20],
            ['title'=>'平台订单号','key'=>'ordersn','width'=>20],
            ['title'=>'买家ID','key'=>'username','width'=>25],
            ['title'=>'货币','key'=>'currency','width'=>8],
            ['title'=>'退款金额','key'=>'amount','width'=>12],
            ['title'=>'原因','key'=>'reason_text','width'=>12],
            ['title'=>'发起时间','key'=>'create_time','width'=>20],
            ['title'=>'更新时间','key'=>'update_time','width'=>20],
            ['title'=>'纠纷状态','key'=>'status_code_text','width'=>12],
            ['title'=>'最迟回应时间','key'=>'due_date','width'=>20],
            ['title'=>'平台状态','key'=>'status_text','width'=>12]
        ];
        $file = [
            'name' => 'shopee纠纷',
            'path' => 'shopee_dispute_export'
        ];

        $ExcelExport = new DownloadFileService();
        return $ExcelExport->export($data, $header, $file);

    }

    /**
     * 取消纠纷分组统计
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelGroupCount()
    {
        $data = [];
        $count = 0;
        $result = ModelShopeeCancel::group('status_code')->column('status_code,count(id)');
        foreach (ModelShopeeCancel::STATUS_CODE_TEXT as $k=>$v){
            $result[$k] = $result[$k] ?? 0;
            $line['code'] = $k;
            $line['count'] = $result[$k];
            $line['label'] = $v;
            $data[] = $line;
            $count += $result[$k];
        }
        array_unshift($data,[
            'code' => 0,
            'count' => $count,
            'label' => '全部'
        ]);
        return $data;
    }

    /**
     * 退货纠纷分组统计
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnGroupCount()
    {
        $data = [];
        $count = 0;
        $result = ModelShopeeReturn::group('status_code')->column('status_code,count(id)');
        foreach (ModelShopeeReturn::STATUS_CODE_TEXT as $k=>$v){
            $result[$k] = $result[$k] ?? 0;
            $line['code'] = $k;
            $line['count'] = $result[$k];
            $line['label'] = $v;
            $data[] = $line;
            $count += $result[$k];
        }
        array_unshift($data,[
            'code' => 0,
            'count' => $count,
            'label' => '全部'
        ]);
        return $data;
    }

    /**
     * 更新订单取消信息
     * @param string $ordersn 订单号
     * @return bool
     * @throws Exception
     */
    public function refreshCancel($ordersn)
    {
        try {
            set_time_limit(0);
            $cancel = ModelShopeeCancel::where('ordersn',$ordersn)
                ->field('ordersn, account_id')
                ->find();
            if(empty($cancel)){
                throw new Exception('无法获取订单取消信息');
            }
            $data = $this->getOrderService($cancel['account_id'])->getDetail([$ordersn]);
            if(!isset($data['orders']) || empty($data)){
                throw new Exception('抓取线上订单取消数据出错:'.$ordersn);
//                return false;
            }
            $update['update_time'] = $data['orders'][0]['update_time'];
            $update['status'] = $data['orders'][0]['order_status'];
            $update['tracking_number'] = $data['orders'][0]['tracking_no'];
            $this->updateCancelOrder($update, $ordersn);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 更新订单退货信息
     * @param string $returnsn 退货单号
     * @return bool
     * @throws Exception
     */
    public function refreshReturn($returnsn)
    {
        try {
            $return = ModelShopeeReturn::where('returnsn',$returnsn)
                ->field('returnsn, account_id, create_time')
                ->find();
            if(empty($return)){
                throw new Exception('无法抓取系统订单退货信息:'.$returnsn);
            }
            $data = $this->getReturnService($return['account_id'])->getReturnList(0, 100, $return['create_time'], $return['create_time']);
//            var_dump($data);//die;
            if(!isset($data['returns']) || empty($data['returns'])){
                throw new Exception('抓取线上订单退货数据出错:'.$returnsn);
//                return false;
            }
            $exist = 0;
            foreach ($data['returns'] as $v) {
                $v['returnsn'] = sprintf('%.0f',$v['returnsn']);
                if($v['returnsn'] == $returnsn){
                    $this->updateReturnOrder($v, $returnsn);
                    $exist++;
                    break;
                }
            }
            if($exist){
                return true;
            }else{
                return false;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }


    /**
     * 导出订单纠纷数据
     * @param $data 纠纷数据
     * @return string
     */
    public function exportToCsv($data)
    {
        $file = $this->newFileName('csv');
        $fh = fopen($file['filePath'].$file['fileName'],'w+');
        fwrite($fh, chr(0xEF).chr(0xBB).chr(0xBF));
        $header = [
            'returnsn'=>'平台退货单号',
            'ordersn'=>'平台订单号',
            'username'=>'买家ID',
            'currency'=>'货币',
            'amount'=>'退款金额',
            'reason_text'=>'原因',
            'create_time'=>'发起时间',
            'update_time'=>'更新时间',
            'status_code_text'=>'纠纷状态',
            'due_date'=>'最迟回应时间',
            'status_text'=>'平台状态'
        ];
        $fields = array_keys($header);
        fputcsv($fh,$header);
        foreach ($data as $v){
            $row = [];
            foreach ($fields as $vv){
                $row[$vv] = $v[$vv];
            }
            $row['returnsn'] = "'".$row['returnsn'];
            $row['create_time'] = date('Y-m-d H:i',$row['create_time']);
            $row['update_time'] = date('Y-m-d H:i',$row['update_time']);
            $row['due_date'] = date('Y-m-d H:i',$row['due_date']);
            fputcsv($fh, $row);
        }
        fclose($fh);
        return $file['fileSite'].$file['filePath'].$file['fileName'];
    }



    /**
     * 获取订单取消申请清单
     * @param $param
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function cancelList($param)
    {
        $statusTodo  = ['IN_CANCEL'];
        $statusDoing = ['IN_CANCEL'];
        $statusDone  = ['CANCELLED','READY_TO_SHIP','RETRY_SHIP','SHIPPED','COMPLETED'];//拒绝申请之后，继续原先流程
        $where = [];
        $model = new ModelShopeeCancel();
        switch ($param['status_code']) {
            case ModelShopeeCancel::STATUS_CODE_TODO://待处理
                $where['status'] = ['IN',$statusTodo];
                $where['status_code'] = $param['status_code'];
                break;
            case ModelShopeeCancel::STATUS_CODE_DOING://处理中
                $where['status'] = ['IN',$statusDoing];
                $where['status_code'] = $param['status_code'];
                break;
            case ModelShopeeCancel::STATUS_CODE_DONE://处理完
                $where['status_code'] = $param['status_code'];
                break;
            default:
                break;
        }
        if(!empty($param['ids'])){
            $where['id'] = ['IN',$param['ids']];
        }
        if(!empty($param['ordersn'])){
            $where['ordersn'] = $param['ordersn'];
        }
        if(!empty($param['account_id'])){
            $where['account_id'] = $param['account_id'];
        }
        if(!empty($param['time_start'])){
            $where['create_time'] = ['EGT',$param['time_start']];
        }
        if(!empty($param['time_end'])){
            $where['create_time'] = ['ELT',$param['time_end']];
        }
        $result = ['list' => []];
        $result['count'] = $model->where($where)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $model->where($where);
        if(!isset($param['export']) || empty($param['export'])){//是否导出
            $model->page($param['page'], $param['page_size']);
        }
//        if($result['count'] > 5000){//
//            throw new Exception('无法一次导出超过5000条数据，请缩小查询范围再试');
//        }
        if(isset($param['sort_field']) && !empty($param['sort_field'])){
            if(isset($param['sort_by']) && !empty($param['sort_by'])){
                $sort = [$param['sort_field']=>'desc'];
            }else{
                $sort = [$param['sort_field']=>'asc'];
            }
        }else{
            $sort = ['create_time'=>'desc'];
        }
        $rs = $model->order($sort)->select();//取消订单申请的响应3天后截止
//        var_dump($model->getLastSql());
        foreach ($rs as &$v){
            $v['order_id'] = strval($v['order_id']);
            $v['status_text'] = ModelShopeeCancel::STATUS_TEXT[$v['status']];
            $v['status_code_text'] = ModelShopeeCancel::STATUS_CODE_TEXT[$v['status_code']];
            $v['reason_text'] = ModelShopeeCancel::REASON_TEXT[$v['reason']];
//            $v['due_date'] = $v['create_time'] + 172800;//3600*24*2
        }
        unset($v);
        $result['list'] = array_values($rs);
        return $result;
    }

    /**
     * 获取订单退货申请清单
     * @param $param
     * @return array
     * @throws Exception
     */
    private function returnList($param)
    {
//        dump($param);
        $statusTodo  = ['REQUESTED'];
        $statusDoing = ['JUDGING', 'ACCEPTED', 'PROCESSING', 'SELLER_DISPUTE', 'REFUND_PAID', 'CANCELLED'];
        $statusDone  = ['CLOSED'];
        $where = [];
        $model = new ModelShopeeReturn();
        switch ($param['status_code']) {
            case ModelShopeeReturn::STATUS_CODE_DONE://处理完 CLOSED
                $where['status'] = ['IN',$statusDone];
//                $where['status_code'] = ModelShopeeReturn::STATUS_CODE_DONE;
                break;
            case ModelShopeeReturn::STATUS_CODE_DOING://处理中 （非CLOSED+已操作）
            case ModelShopeeReturn::STATUS_CODE_TODO://待处理 REQUESTED+未操作
                $where['status_code'] = $param['status_code'];
                break;
            default:
                break;
        }
        if(!empty($param['ids'])){
            $where['id'] = ['IN',$param['ids']];
        }
        if(!empty($param['ordersn'])){
            $where['ordersn'] = $param['ordersn'];
        }
        if(!empty($param['account_id'])){
            $where['account_id'] = $param['account_id'];
        }
        if(!empty($param['time_start'])){
            $where['create_time'] = ['EGT',$param['time_start']];
        }
        if(!empty($param['time_end'])){
            $where['create_time'] = ['ELT',$param['time_end']];
        }
        $result = ['list' => []];
        $result['count'] = $model->where($where)->count();
        if ($result['count'] == 0) {
            return $result;
        }
        $model->where($where);
        if(!isset($param['export']) || empty($param['export'])){//是否导出
            $model->page($param['page'], $param['page_size']);
        }
//        if($result['count'] > 5000){//
//            throw new Exception('无法一次导出超过5000条数据，请缩小查询范围再试');
//        }
        if(isset($param['sort_field']) && !empty($param['sort_field'])){
            if(isset($param['sort_by']) && !empty($param['sort_by'])){
                $sort = [$param['sort_field']=>'desc'];
            }else{
                $sort = [$param['sort_field']=>'asc'];
            }
        }else{
            $sort = ['create_time'=>'desc'];
        }
        $rs = $model->order($sort)
            ->column("id, returnsn, ordersn, order_id, username, currency, refund_amount amount, reason, 
            create_time, update_time, status, status_code, due_date, aftersale_id");
//        dump($model->getLastSql());
        foreach ($rs as &$v){
            if(!empty($v['aftersale_id'])){
                $result = Db::table('after_sale_service')->field('refund_status, approve_status')->find();
                $v['refund_status'] = $result['refund_status'];
                $v['approve_status'] = $result['approve_status'];
            }else{
                $v['refund_status'] = 0;
                $v['approve_status'] = 0;
            }
            $v['order_id'] = strval($v['order_id']);
            $v['status_text'] = ModelShopeeReturn::RETURN_STATUS_TEXT[$v['status']];
            $v['status_code_text'] = ModelShopeeReturn::STATUS_CODE_TEXT[$v['status_code']];
            $v['reason_text'] = ModelShopeeReturn::RETURN_REASON_TEXT[$v['reason']];
        }
        unset($v);
        $result['list'] = array_values($rs);
        return $result;

    }

    /*
     * 获取订单取消详情
     * @param $ordersn 取消订单ID
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCancelDetail($ordersn, $user_id=0)
    {
        try {
            $result = ModelShopeeCancel::where('ordersn',$ordersn)->find();
            $result['status_code_text'] = $result->statusCodeTxt;
            //订单取消申请
            $result['type'] = 1;
            return $result;
//            $orderService = new OrderService();
//            $result = $orderService->getOrderNumberByChannelNumber(ChannelAccountConst::channel_Shopee,$ordersn);
//            return $result['id'];
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 获取订单取消日志
     * @param $ordersn
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCancelLog($ordersn)
    {
        return ModelShopeeReturnLog::where('returnsn',$ordersn)->select();
    }

    /**
     * 获取退货纠纷详情
     * @param $returnsn
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getReturnDetail($returnsn){
        $result = ModelShopeeReturn::field('id,returnsn,ordersn,order_id,create_time,status,status_code,reason,
        due_date,images,text_reason,aftersale_id')
            ->where('returnsn',$returnsn)
            ->with('detail')
            ->find();

        $result['status_code_text'] = $result->statusCodeTxt;
        //退款退货
        $result['type'] = 2;
        return $result;
    }

    /**
     * 获取订单退货纠纷
     * @param $returnsn
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getReturnDispute($returnsn)
    {
        return ModelShopeeReturnDispute::where('returnsn',$returnsn)->find();
    }

    /**
     * 获取订单退货日志
     * @param $returnsn
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getReturnLog($returnsn)
    {
        return ModelShopeeReturnLog::where('returnsn',$returnsn)->select();
    }


    /**
     * @param $accountId
     * @param $status //UNPAID/READY_TO_SHIP/COMPLETED/IN_CANCEL/CANCELLED/TO_RETURN
     * @return mixed
     */
    public function getOrdersByStatus($accountId,$status)
    {
        return $this->getOrderService($accountId)->getOrdersByStatus($status);
    }



    /**
     * 卖方取消订单
     * @param $ordersn 订单号
     * @param $cancel_reason 取消原因
     * @param $item_id 缺货时商品ID
     * @param $variation_id 缺货时商品ID的变体ID
     * @return mixed
     * @throws Exception
     */
    public function cancelOrder($ordersn, $cancel_reason, $item_id, $variation_id)
    {
        $accountId = ModelShopeeCancel::where('ordersn',$ordersn)->value('account_id');
        $result = $this->getOrderService($accountId)->cancelOrder($ordersn, $cancel_reason, $item_id, $variation_id);
        if($result['modified_time']) {
            ModelShopeeCancel::where('ordersn',$ordersn)->update(['status_code'=>ModelShopeeCancel::STATUS_CODE_TODO]);
            $text = sprintf('商品ID：%s；变体ID：%s；原因：%s', $item_id, $variation_id, $cancel_reason);
            $this->cancelLog($ordersn, $text);
        }else{
            $error = sprintf('%s:%s',$result['error'],$result['msg']);
            throw new Exception($error);
        }
        return $result;
    }

    /**
     * 接受订单取消申请
     * @param $ordersn 订单号
     * @return mixed
     * @throws Exception
     */
    public function acceptBuyerCancellation($ordersn)
    {
        //订单状态为未发货时，需要人工审核
        $accountId = ModelShopeeCancel::where('ordersn',$ordersn)->value('account_id');
        $result = $this->getOrderService($accountId)->acceptBuyerCancellation($ordersn);
        if($result['modified_time']){
            $data = [
                'status' => ModelShopeeCancel::STATUS_CANCELLED,
                'status_code'=>ModelShopeeCancel::STATUS_CODE_DONE
            ];
            ModelShopeeCancel::where('ordersn',$ordersn)->update($data);
            $this->cancelLog($ordersn,'接受买方取消订单申请');
        }else{
            $error = sprintf('%s:%s',$result['error'],$result['msg']);
            throw new Exception($error);
        }
        return $result;
    }

    /**
     * 拒绝订单取消申请
     * @param $ordersn 订单号
     * @return mixed
     * @throws Exception
     */
    public function rejectBuyerCancellation($ordersn)
    {
        try {
            $accountId = ModelShopeeOrder::where('ordersn',$ordersn)->value('account_id');
            $result = $this->getOrderService($accountId)->rejectBuyerCancellation($ordersn);
            if($result['modified_time']) {
                $rsCancel = $this->getOrderService($accountId)->getDetail([$ordersn]);
                if(!empty($rsCancel['errors'])){
                    throw new Exception('获取订单数据出错');
                }
                $data = [
                    'status' => $rsCancel['orders'][0]['order_status'],
                    'status_code' => ModelShopeeCancel::STATUS_CODE_DONE
                ];
                ModelShopeeCancel::where('ordersn', $ordersn)->update($data);
                $this->cancelLog($ordersn, '拒绝买方取消订单申请');
                return $result;
            }else{
                $error = sprintf('%s:%s',$result['error'],$result['msg']);
                throw new Exception($error);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 是否需要建售后单
     * @param $ordersn
     * @return bool
     */
    public function afterSale($ordersn)
    {
        if($this->isPaidOrder($ordersn)){//已付款需要建售后单
            return true;
        }else{
            return false;
        }
    }

    /**
     * 关联售后单ID
     * @param $returnsn
     * @param $aftersale_id
     * @return bool
     */
    public function relateAfterSale($returnsn,$aftersale_id)
    {
        DB::startTrans();
        try {
            ModelShopeeReturn::where('returnsn',$returnsn)->update(['aftersale_id'=>$aftersale_id]);
            $this->returnLog($returnsn,'关联售后单');
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollback();
            return false;
        }
    }

    /**
     * 订单是否已付款
     * @param $ordersn
     * @return bool
     */
    protected function isPaidOrder($ordersn)
    {
        $accountId = ModelShopeeOrder::where('order_sn',$ordersn)->value('account_id');
        $ordersn_list[] = $ordersn;
        $result = $this->getOrderService($accountId)->getDetail($ordersn_list);
//        dump($result);//die;
        if($result['orders'][0]['total_amount'] > 0){//已付款
            return true;
        }else{//默认未付款
            return false;
        }
    }

    /**
     * 售后单审核通过之后确认买方退货申请
     * @param $returnsn
     * @return mixed
     */
    public function confirmReturn($returnsn)
    {
        $accountId = ModelShopeeReturn::where('returnsn',$returnsn)->value('account_id');
//        $accountId = 4;//todo debug
        $result = $this->getReturnService($accountId)->confirmReturn($returnsn);
        if(isset($result['returnsn'])){
            ModelShopeeReturn::where('returnsn',$returnsn)->update(['status_code'=>ModelShopeeReturn::STATUS_CODE_DOING]);
            $message = '确认买方订单退货申请';
            $status = 1;
        }else{
            $message = sprintf('%s:%s',$result['error'],$result['msg']);
            $status = 0;
        }
        $this->returnLog($returnsn,$message);
        $return = ['status'=>$status, 'message'=>$message];
        return $return;
    }

    /**
     * 发起争议
     * @param string $returnsn 退货单号
     * @param string $email 卖方邮箱
     * @param string $dispute_reason 争议原因
     * @param string $dispute_text_reason 争议原因详情
     * @param array $images 图片证据
     * @return bool
     * @throws Exception
     */
    public function disputeReturnOrder($returnsn, $email, $dispute_reason, $dispute_text_reason, $images){
        $accountId = ModelShopeeReturn::where('returnsn',$returnsn)->value('account_id');
//        $accountId = 1;//todo debug
        //转成平台图片
        $result = $this->getImageService($accountId)->uploadImage($images);
        if(isset($result['error'])){
            $error = sprintf('%s:%s',$result['error'],$result['msg']);
            throw new Exception($error);
        }
        $images = array_column($result['images'],'shopee_image_url');
        //发起纠纷
        $result = $this->getReturnService($accountId)->disputeReturn($returnsn, $email, $dispute_reason, $dispute_text_reason, $images);
//        var_dump('<pre/>',$result);
        if(isset($result['error'])){
            $error = sprintf('%s:%s',$result['error'],$result['msg']);
            throw new Exception($error);
        }
        ModelShopeeReturn::where('returnsn',$returnsn)->update(['status_code'=>ModelShopeeReturn::STATUS_CODE_DOING]);
        $data = [
            'returnsn' => $returnsn,
            'email' => $email,
            'dispute_reason' => $dispute_reason,
            'dispute_text_reason' => $dispute_text_reason,
            'images' => join(',',$images)
        ];
        ModelShopeeReturnDispute::create($data);
        $this->returnLog($returnsn,sprintf('争议买方订单退货申请.%s:%s', $dispute_reason, $dispute_text_reason));
    }

    /**
     * 批量上传图片到系统
     * @param $files
     * @return array
     * @return array
     * @throws Exception
     */
    public function uploadPictures($files)
    {
//        $base64 = $this->getImageBase64();
//        echo($base64);//die;
        $result = [];
        foreach ($files as $file){
            $result[] =$this->uploadPicture($file);
        }
        return $result;
    }

    protected function getImageBase64()
    {
        $steam = file_get_contents('./shopee_return/20180926/123.png');
        return base64_encode($steam);
    }

    /**
     * 保存base64编码图片
     * @param array $file ['image'=>base64_encode('DATA')]
     * @return string 图片地址
     * @throws Exception
     */
    protected function uploadPicture($file)
    {
        if(!is_array($file) || empty($file['image'])){
            throw new Exception('图片数据异常');
        }
        $ext_1 = strpos($file['image'], '/') + 1;
        $ext_2 = strpos($file['image'], ';');
        $ext = substr($file['image'], $ext_1, $ext_2 - $ext_1);
        if(!in_array($ext, self::UPLOAD_IMAGE_ALLOW_EXT)){
            $message = sprintf('图片类型非法,只能使用%s.',join('、',self::UPLOAD_IMAGE_ALLOW_EXT));
            throw new Exception($message);
        }

        $filePath = $this->newFileName($ext);

        $base64 = ltrim(strstr($file['image'],','),',');
        $decode = base64_decode($base64);
        $put = file_put_contents($filePath['filePath'].$filePath['fileName'], $decode);
        if(!$put){
            throw new Exception('上传图片数据出错');
        }
        return $filePath['fileSite'].$filePath['filePath'].$filePath['fileName'];

    }

    /**
     * 记录纠纷取消订单
     * @param $data
     */
    public function disputeCancelOrder($data)
    {
//        $required = ['ordersn','order_id','status','status_code','account_id','create_time','update_time','due_date', 'username','reason','currency','amount'];
        $data['due_date'] = $data['create_time'] + 172800;//3600*24*2
        $data['status_code'] = 1;
        $data['reason'] = 'BUYER_REQUESTED';//默认买家请求取消订单
        ModelShopeeCancel::create($data);
        $this->cancelLog($data['ordersn'],'发起订单取消申请',$data['username']);
    }

    /**
     * 手动刷新订单取消信息
     * @param $data
     * @param $ordersn
     */
    public function updateCancelOrder($data, $ordersn)
    {
        if($data['status']=='COMPLETED'){
            $data['status_code'] = ModelShopeeCancel::STATUS_CODE_DONE;
        }
        ModelShopeeCancel::where('ordersn',$ordersn)->update($data);
        $this->cancelLog($ordersn,'手动刷新纠纷信息');
    }

    /**
     * 手动刷新订单退货信息
     * @param $data
     * @param $returnsn
     */
    public function updateReturnOrder($data, $returnsn)
    {
        unset($data['images'], $data['user'], $data['items']);
        if($data['status']=='CLOSED'){
            $data['status_code'] = ModelShopeeCancel::STATUS_CODE_DONE;
        }elseif($data['status']!=='REQUESTED'){
            $data['status_code'] = ModelShopeeCancel::STATUS_CODE_DOING;
        }else{
            $data['status_code'] = ModelShopeeCancel::STATUS_CODE_TODO;
        }
        ModelShopeeReturn::where('returnsn',$returnsn)->update($data);
        $this->returnLog($returnsn,'手动刷新纠纷信息');
    }

    /**
     * 更新取消订单申请状态
     * @param $ordersn
     */
    public function doneCancelOrder($ordersn)
    {
        $data = [
            'status'=>ModelShopeeCancel::STATUS_CANCELLED,
            'status_code'=>ModelShopeeCancel::STATUS_CODE_DONE
        ];
        ModelShopeeCancel::where('ordersn',$ordersn)->update($data);
    }

    /**
     * 更新取消订单申请状态
     * @param ordersn
     * @param status
     * @param update_time
     */
    public function updateCancelOrderStatus($params)
    {
        if(empty(ModelShopeeCancel::where('ordersn',$params['ordersn'])->find())){
            return false;
        }
        switch ($params['status']){
            case 'READY_TO_SHIP':
                $data = [
                    'status'=>ModelShopeeCancel::STATUS_READY_TO_SHIP,
                    'status_code'=>ModelShopeeCancel::STATUS_CODE_DOING
                ];
                break;
            case 'SHIPPED':
                $data = [
                    'status'=>ModelShopeeCancel::STATUS_SHIPPED,
                    'status_code'=>ModelShopeeCancel::STATUS_CODE_DOING
                ];
                break;
            case 'TO_CONFIRM_RECEIVE':
                $data = [
                    'status'=>ModelShopeeCancel::STATUS_TO_CONFIRM_RECEIVE,
                    'status_code'=>ModelShopeeCancel::STATUS_CODE_DOING
                ];
                break;
            case 'CANCELLED':
                $data = [
                    'status'=>ModelShopeeCancel::STATUS_CANCELLED,
                    'status_code'=>ModelShopeeCancel::STATUS_CODE_DONE
                ];
                break;
            case 'COMPLETED':
                $data = [
                    'status'=>ModelShopeeCancel::STATUS_COMPLETED,
                    'status_code'=>ModelShopeeCancel::STATUS_CODE_DONE
                ];
                break;
            default:
                break;
        }

        if(!empty($data)){
            $data['update_time'] = $params['update_time'];
            ModelShopeeCancel::where('ordersn',$params['ordersn'])->update($data);
            return true;
        }
        return false;
    }

    /**
     * 订单取消日志
     * @param $ordersn
     * @param $text
     * @param $operator
     */
    private function cancelLog($ordersn,$text,$operator='')
    {
        $this->log($ordersn,$text,1,$operator);
    }

    /**
     * 订单退货日志
     * @param $ordersn
     * @param $text
     */
    private function returnLog($ordersn,$text)
    {
        $this->log($ordersn,$text,2);
    }

    /**
     * 退货操作日志
     * @param int $returnsn
     * @param string $text
     * @param int $type
     * @param string $operator
     */
    private function log($returnsn, $text, $type = 1,$operator=''){
        if(empty($operator)){
            $userInfo = Common::getUserInfo();
        }else{
            $userInfo = ['user_id' => 0, 'realname' => $operator];
        }
        $data = [
            'type' => $type,
            'returnsn' => $returnsn,
            'operate_id' => $userInfo['user_id'],
            'operator' => $userInfo['realname'],
            'create_time' => time(),
            'data' => $text
        ];
        ModelShopeeReturnLog::create($data);
    }


    /**
     * 缓存几天退货单号的更新时间
     * @param int $days
     * @return bool
     * @throws Exception
     */
    public function cachingReturnsn($days = 15)
    {
        try {
            $rsReturn = ModelShopeeReturn::where('create_time','EGT',strtotime("-$days days"))
                ->where('status','NOT IN',['CLOSED'])
                ->field('returnsn, update_time')
                ->select();
//        var_dump('<pre/>',$rsReturn);
            if(empty($rsReturn)){
                return false;
            }
            $loop = 1;
            $size = 200;//管道每次处理500条命令
            $key = 'shopee:returnsn';
//        $returnsn = [];
            $handle = Cache::handler(true);
            $handle->setTimeout($key,1800);
            $handle->multi(Redis::PIPELINE);
            foreach ($rsReturn as $v){
//            $returnsn[] = $v['returnsn'];//@todo 是否需要记录失败
                if($loop++ % $size == 0 ){
                    $handle->exec();
                    $handle->multi(Redis::PIPELINE);
                }
                $handle->hSet($key, $v['returnsn'], $v['update_time']);
            }
            $handle->exec();
//        var_dump('<pre/>',$returnsn);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getFile() . $e->getLine() . $e->getMessage());
        }

    }

    public static function getCachingReturnsn($returnsn)
    {
        return Cache::handler(true)->hGet('shopee:returnsn', $returnsn);
    }

    public static function delCachingReturnsn($returnsn)
    {
        return Cache::handler(true)->hDel('shopee:returnsn', $returnsn);
    }


    /**
     * 同步退货数据
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function syncReturn($data)
    {
        $updateTime = self::getCachingReturnsn( $data['returnsn']);

        $data['images'] = join(',',$data['images']);
        $data = array_merge($data,$data['user']);
        $detail = $data['items'];
        unset($data['user'], $data['items']);
        if($data['status']=='CLOSED'){//平台已关闭认定为系统处理完
            $data['status_code'] = ModelShopeeReturn::STATUS_CODE_DONE;
        }
//        $modelShopeeOrder = new ShopeeOrder();
        $orderService = new OrderService();
        try {
//            if($updateTime){//存在缓存中的
            if(0){//存在缓存中的
                if($data['update_time'] > $updateTime) {//更新：更新时间大于同步时间
                    ModelShopeeReturn::where('returnsn', $data['returnsn'])->update($data);
                }else{
                    self::delCachingReturnsn($data['returnsn']);
                    return false;
                }
            }else{//不在缓存中的
                DB::startTrans();
                $count = ModelShopeeReturn::where('returnsn', $data['returnsn'])->count();
                if($count){
                    ModelShopeeReturn::where('returnsn', $data['returnsn'])->update($data);
                }else{
                    $order = $orderService->getOrderNumberByChannelNumber(ChannelAccountConst::channel_Shopee,$data['ordersn']);
                    $data['order_id'] = $order['id'];
//                    $data['order_id'] = $modelShopeeOrder->where('order_sn',$data['ordersn'])->value('id');
//                    $data['order_id'] = $data['order_id'] ?: 0;
                    $rsReturn = ModelShopeeReturn::create($data);
                    if(!isset($rsReturn['id'])){
                        throw new Exception('插入退货数据失败');
                    }
                    foreach ($detail as $v){
                        $v['returnsn'] = $data['returnsn'];
                        $v['images'] = join(',',$v['images']);
                        ModelShopeeReturnDetail::create($v);
                    }
                }
                DB::commit();
            }
            self::delCachingReturnsn($data['returnsn']);
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw new Exception($e->getMessage().$e->getFile().$e->getLine());
        }
    }


    /**
     * 同步取消订单数据
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function syncCancel($data)
    {
        $orderService = new OrderService();
        try {
            DB::startTrans();
            $count = ModelShopeeCancel::where('ordersn', $data['ordersn'])->count();
            if($count){
                $data['due_date'] = $data['update_time'] + 172800;//3600*24*2
                ModelShopeeCancel::where('ordersn', $data['ordersn'])->update($data);
            }else{
                $orders = $orderService->getOrderNumberByChannelNumber(ChannelAccountConst::channel_Shopee,$data['ordersn']);

                if($orders){
                    $data['order_id'] = $orders['id'];
                    $data['username'] = $orders['buyer'];
                    $data['amount'] = $orders['order_amount'];
                    $data['create_time'] = $orders['create_time'];
//                    $data['update_time'] = $orders['update_time'];
                    $data['currency'] = $orders['currency_code'];
                    $data['due_date'] = $data['update_time'] + 172800;//3600*24*2

                    if($data['status'] == 'CANCELLED'){
                        $data['status_code'] = 3;
                    }elseif ($data['status'] == 'IN_CANCEL'){
                        $data['status_code'] = 1;
                    }
                }

                $rsReturn = ModelShopeeCancel::create($data);
                if(!isset($rsReturn['id'])){
                    throw new Exception('插入退货数据失败');
                }
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw new Exception($e->getMessage().$e->getFile().$e->getLine());
        }
    }


    private function newFileName($ext)
    {
        list($sec,$msec) = explode('.', microtime(1));
        $fileName = sprintf("%s%s%'02d.%s",date('YmdHis',(int)$sec), $msec, rand(0, 99), $ext);
        $filePath = sprintf('%s/%s/','upload/shopee', date('Ymd'));
        $fileSite = 'http://www.zrzsoft.com:8081/';//生产环境
        if(!is_dir($filePath)){
            mkdir($filePath,0777,true);
        }
        return [
            'fileName' => $fileName,
            'filePath' => $filePath,
            'fileSite' => $fileSite
        ];
    }



}