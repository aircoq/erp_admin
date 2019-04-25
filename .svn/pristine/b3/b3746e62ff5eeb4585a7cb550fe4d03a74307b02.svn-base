<?php
namespace app\finance\service;
use app\common\model\FinancePurchase as FinancePurchaseModel;
use app\common\cache\Cache;
use app\common\model\PurchaseOrder as PurchaseOrderModel;
use app\common\model\PurchasePayment;
use app\common\model\PurchasePaymentLog;
use app\common\model\VirtualPurchaseOrder;
use app\common\model\VirtualSupplier;
use app\common\service\Common;
use app\purchase\service\PurchaseOrder as PurchaseOrderService;
use app\purchase\service\PurchaseOrder;
use think\Db;
use think\Exception;
use app\common\model\PurchaseOrderLog as PurchaseOrderLogModel;
use app\purchase\service\SupplierService;
use app\common\service\Excel;

/**
 * @class FinancePurchase
 */
class FinancePurchase
{
    private $_where = [];
    public $purchase_order_id_list = [];
    public $exportName;
    public $purchasePaymentRemark;
    private $model;
    public static $purchase_type_text = [
        0 => '样品',
        1 => '采购',
        2 => '补货'
    ];
    public function __construct()
    {
        $this->model = new FinancePurchaseModel();
    }
    
    /**
     * 获取采购单已申请的付款金额
     * @param int $purchase_order_id
     * @return float
     */
    public function getAppliedAmount($purchase_order_id)
    {
        $amount = 0;
        $list = $this->model->where(['purchase_order_id' => $purchase_order_id, 'payment_status' => ['in','1,2']])->select();
        foreach($list as $finance) {
            $amount += $finance['apply_amount'];
        }
        return $amount;
    }
    
    /**
     * 获取查询条件
     * @param array $params
     * @return array
     */
    public function where(array $params)
    {
        $exportName = $purchaseOrderIdArr = [];
        if (isset($params['payment_status']) && is_numeric($params['payment_status'])) {
            $this->_where['payment_status'] = ['EQ', $params['payment_status']];
            $exportName[] = self::getPaymentStatusTextForApply($params['payment_status']);
        }
        if (isset($params['purchase_type']) && is_numeric($params['purchase_type'])) {
            $this->_where['purchase_type'] = ['EQ', $params['purchase_type']];
            $exportName[] = self::$purchase_type_text[$params['purchase_type']] ?? '';
        }
        //结算方式，根据结算方式找到对应的供应商，根据供应商来查询
        if(param($params, 'balance_type')){
            $res=SupplierService :: getSupplierServiceByBalanceType($params['balance_type']);
            $ids=array_column($res, 'id');
            $this->_where['supplier_id']=['in',$ids];
            $exportName[] = Cache::store('supplier')->getBalanceTypeText($params['balance_type']);
        }
        if (isset($params['supplier_id']) && $params['supplier_id'] != "") {
            if (is_numeric($params['supplier_id'])) {
                $this->_where['supplier_id'] = ['EQ', $params['supplier_id']];
                $exportName[] = param(Cache::store('supplier')->getSupplier($params['supplier_id']), 'company_name');
            }
        }        
        if (isset($params['purchase_user_id']) && $params['purchase_user_id'] != "") {
            if (is_numeric($params['purchase_user_id'])) {
                $this->_where['purchase_user_id'] = ['EQ', $params['purchase_user_id']];
                $exportName[] = param(Cache::store('user')->getOneUser($params['purchase_user_id']), 'realname');
            }
        }
        if (param($params, 'purchase_order_id')) {
            $purchaseOrderIds = json_decode($params['purchase_order_id'], true);
            if($purchaseOrderIds && is_array($purchaseOrderIds)){
                $purchaseOrderIds = array_map(function($val){ return str_replace('PO', '', $val);}, $purchaseOrderIds);
                $purchaseOrderIdArr = array_merge($purchaseOrderIds, $purchaseOrderIdArr);
                $exportStr = implode(',', array_slice($purchaseOrderIds, 0, 5));
                if(count($purchaseOrderIds) > 5) $exportStr .= '......';
            }else{
                $id = str_replace('PO', '', $params['purchase_order_id']);
                $purchaseOrderIdArr[] = $id;
                $exportStr = $params['purchase_order_id'];
            }
            $exportName[] = '采购单号'.$exportStr;
        }
        //1688 订单搜索
        if (param($params, 'external_number')) {
            $map = ['external_number' => trim($params['external_number'])];
            $purchaseOrderId = PurchaseOrderModel::where($map)->column('id');
            $purchaseOrderIdArr = array_merge($purchaseOrderId, $purchaseOrderIdArr);
            $exportName[] = '外部流水号'.$params['external_number'];
        }
        //时间搜索
        if (isset($params['dateType'])) {
            $b_time = !empty(param($params, 'date_b'))?strtotime($params['date_b'].' 00:00:00'):'';
            $e_time = !empty(param($params, 'date_e'))?strtotime($params['date_e'].' 23:59:59'):'';
            
            switch ($params['dateType']) {
                case 'purchase_time':
                    $params['dateType'] = 'create_time';
                    break;
                default:
                    break;
            }
            $tmp = 1;
            if($b_time && $e_time){
                $this->_where[$params['dateType']]  =  ['BETWEEN', [$b_time, $e_time]];
            }elseif ($b_time) {
                $this->_where[$params['dateType']]  = ['EGT',$b_time];
            }elseif ($e_time) {
                $this->_where[$params['dateType']]  = ['ELT',$e_time];
            }else{
                $tmp = 0;
            }
            if($tmp){
                $dateTypeName = ['purchase_time'=> '采购日期/申请日期', 'payment_time'=>'付款日期'];
                $exportName[] = $dateTypeName[$params['dateType']].param($params, 'date_b').'-'.param($params, 'date_e');
            }
        }
        if($purchaseOrderIdArr){
            $this->_where['purchase_order_id'] = ['in', array_unique($purchaseOrderIdArr)];
        }
        array_filter($exportName) && $this->exportName = implode('|', $exportName);
        return $this->_where;
    }
    
    /**
     * 财务=》采购结算 获取查询列表
     * @param array $where
     * @param string|array $field
     * @param int $page
     * @param int $pageSize
     * @param int $group
     * @param int $order
     * @return array
     */
    public function getList(array $where = [], $field = '*', $page =1 , $pageSize = 10, $group = '', $order ='id desc')
    {
        $where = array_merge($this->_where, $where);
        $new_array = [];
        $list = $this->model->where($where)->field($field)->group($group)->order($order)->page($page,$pageSize)->select();
        //财务信息

        $PurchaseOrderService = new PurchaseOrderService();
        $Ali1688Account = new \app\common\cache\driver\Ali1688Account();
        foreach ($list as $v) {
            $v = $v->toArray();
            $supplier                   = Cache::store('supplier')->getSupplier($v['supplier_id']);
            $v['supplier']              = param($supplier, 'company_name');
            $purchaser                  = Cache::store('user')->getOneUser($v['purchase_user_id']);
            $v['purchaser']             = param($purchaser, 'realname');
            $paymenter                  = Cache::store('user')->getOneUser($v['payment_user_id']);
            $v['paymenter']             = param($paymenter, 'realname');
            $v['purchase_order_code']   = PurchaseOrderService::getOrderCode($v['purchase_order_id']);
            $purchaseOrderModel         = new PurchaseOrderModel();
            $purchaseOrder              = $purchaseOrderModel->where(['id'=>$v['purchase_order_id']])->find();
            $v['purchase_order_status'] = $purchaseOrder['status'];
            $v['balance_text'] = Cache::store('supplier')->getBalanceTypeText($purchaseOrder['supplier_balance_type']);
            $v['purchase_order_status_text'] = PurchaseOrderService::getPurchaseStatusText($purchaseOrder['status']);
            $v['purchase_order_partial_status_text'] = PurchaseOrderService::getPartialArrivalStatusText($purchaseOrder['partial_arrival_status']);
            $v['purchase_order_payment_status_text'] = PurchaseOrderService::getPaymentStatusText($purchaseOrder['payment_status']);
            $v['external_number'] = $purchaseOrder['external_number'];
            $v['account_1688'] = '';
            if($PurchaseOrderService->is1688ExternalNumber($v['external_number'])){
                $account = $Ali1688Account->getData(substr($v['external_number'], -4));
                if($account){
                    $v['account_1688'] = $account['account_name'];
                }
            }
            $v['payment_status_text'] = self::getPaymentStatusTextForApply($v['payment_status']);
            //以下为导出需要的数据
            $finance_payment_time = $v['finance_payment_time'] ? date("Y-m-d H:i:s",$v['finance_payment_time']) : 0;
            $v['payment_time_date'] = date("Y-m-d H:i:s",$v['create_time']).'/'.$finance_payment_time;
            $v['purchase_type_text'] = self::$purchase_type_text[$v['purchase_type']] ?? '';
            $v['create_time_date'] = date("Y-m-d H:i:s",$v['create_time']);
            // 是否能标记付款和取消付款
            $v['real'] = 0;
            $virtualPurchaseOrder = (new VirtualPurchaseOrder())->where('purchase_order_id', $v['purchase_order_id'])->field('virtual_supplier_id')->find();
            if ($virtualPurchaseOrder) {
                $virtualSupplierModel = (new VirtualSupplier())->field('real')->where('id', $virtualPurchaseOrder->virtual_supplier_id);
                $v['real'] = $virtualSupplierModel->real ?? 0;
            }
            $new_array[] = $v;
        }
       
        return $new_array;
    }
    
    /**
     * 获取查询总数
     * @param array $where 查询条件
     * @return int
     */
    public function count(array $where = [])
    {
        $where = array_merge($this->_where, $where);
        return $this->model->where($where)->count();
    }
    
    /**
     * 更改付款状态
     * @param array $ids
     * @param int $status
     * @param int $user_id
     * @return boolean
     * @throws Exception
     */
    public function changStatus(array $ids, $status, $user_id, $payInfo = [])
    {
        $financePurchaseModel = new FinancePurchaseModel();
        $list = $financePurchaseModel->where(['id'=>['in', $ids]])->field(true)->select();
        if (empty($list)) {
            throw new Exception('付款单不存在');
        }

        $purchaseOrderModel = new PurchaseOrderModel;
        $purchase_order_id_list = [];
        foreach($list as &$temp_finance) {
            $purchase_order_id_list[] = $temp_finance['purchase_order_id'];
            $purchaseOrder = $purchaseOrderModel->where(['id'=>$temp_finance['purchase_order_id']])->find();
            if(!$purchaseOrder){
                throw new Exception($temp_finance['purchase_order_id'].'采购单找不到');
            }
            if($purchaseOrder->status == -1 && in_array($status, [2, 4])){
                throw new Exception($temp_finance['purchase_order_id'].'采购单已作废,请取消付款');
            }
            $temp_finance['purchase_order_status'] = $purchaseOrder->status;
        }
        $this->purchase_order_id_list = array_unique($purchase_order_id_list);
        // 开启事务
        Db::startTrans();
        try {
            foreach($list as $finance) {
                $data = [];
                $time = $payInfo[$finance['id']]['payment_time'] ?? time();
                $data['update_time'] = $time;
                $remark = '';
                if($status == 2 || $status == 4){
                    if(isset($payInfo[$finance['id']]['payment_money'])){
                        $payMoney = $payInfo[$finance['id']]['payment_money'];
                        $paymentAmount = round($payMoney + $finance['payment_amount'], 4);
                        $data['payment_amount'] = $paymentAmount;
                        if($paymentAmount < $finance['apply_amount']){
                            $status = 4;
                        }else if($paymentAmount == $finance['apply_amount']){
                            $status = 2;
                        }else{
                            throw new Exception('付款金额不能大于申请金额');
                        }
                    }else{
                        continue;//没有付款信息的跳过
/*                        $payMoney = $finance['apply_amount'];
                        $data['payment_amount'] = $finance['apply_amount'];*/
                    }
                    $data['finance_payment_time'] = $time;
                    $text = $status==4 ? '部分付款' : '已付款';
                    $remark = "采购单编号:{$finance['purchase_order_id']},支付记录ID:{$finance['id']},金额:{$payMoney},由待付款变成了".$text;
                }
                $data['update_user_id'] = $user_id;
                $data['payment_status'] = $status;
                $financeModel = new FinancePurchaseModel();
                $financeModel->allowField(true)->save($data, ['id' => $finance['id']]);
                if ($status == 3) {
                    if(isset($payInfo[$finance['id']]['remark'])){
                        $remark = "采购单编号:{$finance['purchase_order_id']},支付状态变为取消付款,备注:{$payInfo[$finance['id']]['remark']}";
                    }else{
                        $remark = "采购单编号:{$finance['purchase_order_id']},支付记录ID:{$finance['id']},金额:{$finance['apply_amount']},支付状态变为取消付款";
                    }
                }
                //写日志
                PurchaseOrderService::addPurchaseOrderLog($finance['purchase_order_id'], $user_id, $remark, $finance['purchase_order_status']);
                $purchaseOrderRemark = '';
                if($this->purchasePaymentRemark) $purchaseOrderRemark = '付款单'.$finance['purchase_payment_id'].$this->purchasePaymentRemark;
                $purchaseOrderService = new PurchaseOrderService();
                if ($status == 2 || $status == 4) {
                    $purchaseOrderService->confirmPayment($finance['purchase_order_id'], $payMoney, $user_id, $purchaseOrderRemark);
                } else {
                    $purchaseOrderService->rejectPayment($finance['purchase_order_id'], $finance['apply_amount'], $user_id, $purchaseOrderRemark);
                }
                
            }
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 根据金额申请记录的状态更新采购单
     * @param array $ids
     * @param int $status
     * @author yangweiquan
     * @date 2017-07-05
     * @time 10:45
     * @return boolean
     * @throws Exception
     */
    public function changePurchaseOrderPaymentStatus($purchase_order_id){
        if(empty($purchase_order_id)){return;}

        $purchaseOrderModel = new PurchaseOrderModel();
        $financePurchaseModel = new FinancePurchaseModel();
        $purchaseOrder =   $purchaseOrderModel->where(['id'=>$purchase_order_id])->find();
        if(!$purchaseOrder){
           return;
        }
        $paid_amount = 0.00;
        $process_amount = 0.00;
        $list = $financePurchaseModel->where(['purchase_order_id'=>$purchase_order_id])->order('id desc')->select();
        $data = [];
        if(!$list){
            $data['payment_apply_status'] = 1;//未申请
        }else{
            $last_finance = $list[0];
            if($purchaseOrder['virtual_supplier_id']){
                $payable_amount = $purchaseOrder['payable_amount'];
            }else{
                $payable_amount = $purchaseOrder['amount'] + $purchaseOrder['shipping_cost'] - $purchaseOrder['discount_amount'];
            }

            foreach($list as $finance){
                if(in_array($finance['payment_status'], [2, 4])){
                    $paid_amount += $finance['payment_amount'];
                }
                if(in_array($finance['payment_status'], [0, 1])){
                    $process_amount += $finance['apply_amount'];
                }
            }

            if($paid_amount == 0){
                $data['payment_status'] = 7;
            }
            if($paid_amount >0 && $paid_amount < $payable_amount){
                $data['payment_status'] = 9;
            }
            if($paid_amount >0 && $paid_amount >= $payable_amount){
                $data['payment_status'] = 8;
            }
            if($last_finance->payment_status == 1 || $last_finance->payment_status == 2){
                $data['payment_apply_status'] = 2;//已申请
            }
            if($last_finance->payment_status == 3){//已取消
                $data['payment_apply_status'] = 3;//已取消
            }
        }

        $data['update_time'] = time();
        $data['actual_payment'] = $paid_amount;//已支付金额
        $data['process_amount'] = $process_amount;//待审核，处理中金额
        $purchaseOrderModel->allowField(true)->save($data, ['id' => $purchase_order_id]);
        if($purchaseOrder['virtual_supplier_id']){
            VirtualPurchaseOrder::update($data, ['purchase_order_id'=>$purchase_order_id]);
        }
    }

    /*  付款申请记录的状态
        @date 2017/7/27
        @time 17:21
        @author 杨伟权
    */
    public static function getPaymentStatusTextForApply($payment_status){
        $list = [0=>'待审核',1=>'已审核(待付款)', 2=>'已付款', 3=>'取消付款'];
        return $list[$payment_status] ?? '';
    }

    /**
     * 采购结算/采购付款 导出
     * @param $lists
     * @param int $fileType
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function export($lists,$fileType=1)
    {
        $fileName = $this->getExportFileName($fileType);
        if($fileType){
            $header=[
                ['title'=>'ID','key'=>'id','width'=>10,'need_merge'=>1],
                ['title'=>'采购单号','key'=>'purchase_order_code','width'=>20,'need_merge'=>1],
                ['title'=>'外部流水号','key'=>'external_number','width'=>20,'need_merge'=>1],
                ['title'=>'采购员','key'=>'purchaser','width'=>20,'need_merge'=>1],
                ['title'=>'供应商','key'=>'supplier','width'=>30,'need_merge'=>1],
                ['title'=>'结算方式','key'=>'balance_text','width'=>20,'need_merge'=>1],
                ['title'=>'采购单状态','key'=>'purchase_order_status_text','width'=>20,'need_merge'=>1],
                ['title'=>'本次付款','key'=>'apply_amount','width'=>20,'need_merge'=>1],
                ['title'=>'付款状态','key'=>'payment_status_text','width'=>20,'need_merge'=>1],
                ['title'=>'采购类型','key'=>'purchase_type_text','width'=>20,'need_merge'=>1],
                ['title'=>'申请时间/付款时间','key'=>'payment_time_date','width'=>40,'need_merge'=>1],
            ];
            $file = [
                'name' => $fileName,
                'path' => 'finance_purchase_export'
            ];
        }else{
            $header=[
                //['title'=>'ID','key'=>'id','width'=>10,'need_merge'=>1],
                ['title'=>'采购单号','key'=>'purchase_order_code','width'=>20,'need_merge'=>1],
                ['title'=>'外部流水号','key'=>'external_number','width'=>20,'need_merge'=>1],
                ['title'=>'采购员','key'=>'purchaser','width'=>20,'need_merge'=>1],
                ['title'=>'供应商','key'=>'supplier','width'=>30,'need_merge'=>1],
                ['title'=>'结算方式','key'=>'balance_text','width'=>20,'need_merge'=>1],
                ['title'=>'采购单状态','key'=>'purchase_order_status_text','width'=>20,'need_merge'=>1],
                ['title'=>'本次付款','key'=>'apply_amount','width'=>20,'need_merge'=>1],
                ['title'=>'付款状态','key'=>'payment_status_text','width'=>20,'need_merge'=>1],
                ['title'=>'采购类型','key'=>'purchase_type_text','width'=>20,'need_merge'=>1],
                ['title'=>'申请时间','key'=>'create_time_date','width'=>40,'need_merge'=>1],
            ];
            $file = [
                'name' => $fileName,
                'path' => 'finance_purchase_export'
            ];
        }
        $result=Excel::exportExcel2007($header,$lists,$file);
        return $result;
    }

    public function getExportFileName($fileType)
    {
        $fileTypeName = $fileType ? '采购结算单' : '付款申请单';
        if($this->exportName){
            $fileName = $this->exportName.'_'.$fileTypeName;
        }else{
            $fileName = $fileTypeName.'('. $userId = Common::getUserInfo()->toArray()['realname'].')__'.date("Y_m_d_H_i_s");
        }
        return $fileName;
    }

    public function returnMarkPayed($fpId, $returnMoney, $userId)
    {
        try{
            Db::startTrans();
            $fp = FinancePurchaseModel::find($fpId);
            if(empty($fp) || !in_array($fp['payment_status'], [2, 4])) throw new Exception('数据状态错误');
            $paymentAmount = $fp['payment_amount'] - $returnMoney;
            if($paymentAmount < 0) throw new Exception('退回金额错误');
            $po = PurchaseOrderModel::find($fp['purchase_order_id']);
            $fp->payment_status = self::getPaymentStatus($fp['apply_amount'], $paymentAmount);
            $fp->payment_amount = $paymentAmount;
            $fp->save();
            $po->actual_payment -= $returnMoney;
            $po->process_amount += $returnMoney;
            $po->payment_status = PurchaseOrderService::getPaymentStatus(PurchaseOrderService::getPayableAmount($po), $po->actual_payment);
            $po->save();
            $remark = '付款单'.$fp->purchase_payment_id.'退回付款，金额'.$returnMoney.'，付款状态变为'.PurchaseOrderService::getPaymentStatusText($po->payment_status);
            PurchaseOrderService::addPurchaseOrderLog($po->id, $userId, $remark, $po->status);
            // 回退付款, 金额为负数
            (new \app\purchase\service\PurchasePayment())->insertMarkPayedToFinanceRecord($po, -$returnMoney);
            Db::commit();
        }catch (Exception $ex){
            Db::rollback();
            throw new Exception($ex->getMessage());
        }
    }

    private static function getPaymentStatus($applyAmount, $paymentAmount)
    {
        if($applyAmount > $paymentAmount){
            if($paymentAmount == 0){
                return 1;
            }else{
                return 4;
            }
        }else{
            return 2;
        }
    }

    public function getFinancePurchaseByPurchaseOrderId($purchase_order_id,$purchase_payment_id = '')
    {
        $where['purchase_order_id'] = ['=',$purchase_order_id];
        $where['purchase_payment_id'] = ['neq',$purchase_payment_id];
        $where['payment_status'] = ['=',1];
        return $this->model->field('id,apply_amount,payment_amount')->where($where)->select();
    }
}
