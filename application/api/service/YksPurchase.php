<?php
/**
 * Created by PhpStorm.
 * User: Reece
 * Date: 2018/9/7
 * Time: 10:12
 */
namespace app\api\service;

use app\common\cache\Cache;
use app\common\model\FinancePurchase;
use app\common\model\PurchaseOrder;
use app\common\model\PurchaseOrderDetail;
use app\common\model\PurchaseParcelsRecords;
use app\common\model\VirtualFinancePurchase;
use app\common\model\VirtualPurchaseOrder;
use app\common\model\VirtualPurchaseOrderDetail;
use app\common\model\VirtualPurchaseParcelsRecords;
use app\common\model\VirtualSupplier;
use app\finance\service\FinancePurchase as FinancePurchaseService;
use app\goods\service\GoodsHelp;
use app\purchase\service\VirtualFinancePurchaseService;
use Nette\Utils\JsonException;
use think\Db;
use app\purchase\service\PurchaseOrder as PurchaseOrderService;
use think\Exception;

class YksPurchase
{
    //private $url = 'http://dev.hznewerp.kokoerp.com/commonapi/b2bapi/index';//YKS推送url地址
    //private $url = 'http://113.98.247.82:9703/commonapi/b2bapi/index';
    private $url = 'http://hznewerp.youkeshu.com/commonapi/b2bapi/index';
    //private const B2BKEY = '1efb0fd5c86528647c79af2a75d298f0';//头部信息header里传B2BKEY
    private const B2BKEY = 'c41e4f5bcd445869797d97dacd85a30e';
    private const YKS_ORDER_STATUS = [
        -1 => -1,
        10 => 1,
        11 => 2,
        15 => 2,
        20 => 9,
        21 => 9
    ];

    private function httpRequest($url, $postData){
        $header = ['B2BKEY:'.self::B2BKEY];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $result = curl_exec($ch);
        curl_close($ch);
        $result2Arr = json_decode($result, true);
        if($result2Arr && is_array($result2Arr)){
            return $result2Arr;
        }
        return $result;
    }

    /**
     * @desc 推送采购单信息
     */
    public function pushPurchaseOrder($purchaseOrderId)
    {
        $data = VirtualPurchaseOrder::relation('detail')->where(['id'=>$purchaseOrderId])->select();
        $yksData = $this->makeYksPurchaseOrderData($data);
        $pushData = [
            'method' => 'purchase_order_details',
            'data' => $yksData[0]
        ];
        $res = $this->httpRequest($this->url, json_encode($pushData, JSON_UNESCAPED_UNICODE));
        $res = $this->dealResponse($res);
        $update = $this->dealUpdateData($res);
        VirtualPurchaseOrder::where(['id'=>$purchaseOrderId])->update($update);
        return $res;
    }

    /**
     * @desc 获取采购单信息
     */
    public function getPurchaseOrderList($where, $page, $pageSize)
    {
        $where['virtual_supplier_id'] = ['>', 0];
        if(isset($where['sku']) && !isset($where['id'])){
            $orderIds = VirtualPurchaseOrderDetail::where(['sku'=>$where['sku']])->column('virtual_purchase_order_id');
            $where['id'] = ['in', array_unique($orderIds)];
        }
        unset($where['sku']);
        $count = VirtualPurchaseOrder::where($where)->count();
        $data = VirtualPurchaseOrder::relation('detail')->where($where)->page($page, $pageSize)->select();
        $resultData = $this->makeYksPurchaseOrderData($data);
        return [
            'code' => 200,
            'msg' => 'success',
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'data' => $resultData
        ];
    }

    /**
     * @desc 获取入库流水
     */
    public function getInStockList($where, $page, $pageSize)
    {
        $where['r.status'] = 1;
        $query = VirtualPurchaseParcelsRecords::alias('r')->join('virtual_purchase_order o', 'r.purchase_order_id=o.id','left')->where($where);
        $query2 = clone $query;
        $count = $query->count();
        $data = $query2->field('r.*,o.virtual_supplier_id')->page($page, $pageSize)->select();
        $resultData = $this->makeYksInStockData($data);
        return [
            'code' => 200,
            'msg' => 'success',
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'data' => $resultData
        ];
    }

    /**
     * @desc 推送入库流水(一次性推送单个订单的所有入库单)
     */
    public function pushInStock($purchaseOrderId)
    {
        $data = VirtualPurchaseParcelsRecords::alias('r')->field('r.*,o.virtual_supplier_id')->join('virtual_purchase_order o', 'r.virtual_purchase_order_id=o.id','left')->where(['r.virtual_purchase_order_id'=>$purchaseOrderId])->select();
        $yksData = $this->makeYksInStockData($data);
        $pushData = [
            'method' => 'purchase_warehouseorders',
            'data' => $yksData
        ];
        $res = $this->httpRequest($this->url, json_encode($pushData));
        $res = $this->dealResponse($res);
        $update = $this->dealUpdateData($res);
        VirtualPurchaseOrder::where(['id'=>$purchaseOrderId])->update($update);
        return $res;
    }

    /**
     * @desc 获取供应商
     */
    public function getSupplierList($where, $page, $pageSize)
    {
        $count = VirtualSupplier::where($where)->count();
        $data = VirtualSupplier::where($where)->page($page, $pageSize)->select();
        $resultData = $this->makeYksSupplierData($data);
        return [
            'code' => 200,
            'msg' => 'success',
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'data' => $resultData
        ];
    }

    /**
     * @desc 推送供应商
     */
    public function pushSupplier($virtualSupplierId)
    {
        $info = VirtualSupplier::where(['id'=>$virtualSupplierId])->select();
        $yksData = $this->makeYksSupplierData($info);
        $pushData = [
            'method' => 'purchase_supplier_info',
            'data' => $yksData[0]
        ];
        $res = $this->httpRequest($this->url, json_encode($pushData));
        return $this->dealResponse($res);
    }

    /**
     * @desc 获取付款单
     */
    public function getPaymentList($where, $page, $pageSize)
    {
        $where['payment_status'] = 1;//待付款
        $count = VirtualFinancePurchase::where($where)->count();
        $data = VirtualFinancePurchase::where($where)->page($page, $pageSize)->select();
        $resultData = $this->makeYksPaymentData($data);
        return [
            'code' => 200,
            'msg' => 'success',
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'data' => $resultData
        ];
    }

    /**
     * @desc 推送付款单
     */
    public function pushPayment($virtualFinanceId)
    {
        $info = VirtualFinancePurchase::where(['id'=>$virtualFinanceId])->select();
        $yksData = $this->makeYksPaymentData($info);
        $pushData = [
            'method' => 'purchase_payment',
            'data' => $yksData[0]
        ];
        $res = $this->httpRequest($this->url, json_encode($pushData, JSON_UNESCAPED_UNICODE));
        $res =  $this->dealResponse($res);
        $update = $this->dealUpdateData($res);
        VirtualFinancePurchase::where(['id'=>$virtualFinanceId])->update($update);
        return $res;
    }

    private function makeYksSupplierData($dataList)
    {
        $yksDataList = [];
        foreach($dataList as $data){
            $yksData = [
                'supplier_id' => $data['id'],
                'company_name' => $data['company_name'],
                'type' => $data['type'],
                'invocie_type' => $data['invoice'],
                'legal' => $data['legal'],
                'legal_phone' => $data['tel'] ?: $data['mobile'],
                'business_name' => $data['business_license'],
                'business_code' => $data['code'],
                'balance_type' => $data['balance_type'],
                'pay_type' => $data['pay_type'],
                'address' => $data['address'],
                'contacts' =>
                    [
                        [
                            'contact_name' => $data['contacts'],
                            'contact_phone' => $data['mobile'],
                        ],
                    ],
                'accounts' =>
                    [
                        [
                            'bank_name' => $data['bank'],
                            'bank_branch_name' => '无',
                            'account_num' => $data['bank_account'],
                            'account_username' => $data['account_name'],
                            'currency' => 'CNY',
                            'pay_type' => $data['pay_type'],
                        ],
                    ],
                'attachments' =>
                    [
                        [
                            'business_license_url' => $data['business_file'] ? $_SERVER['HTTP_HOST'] . '/' . $data['business_file'] : '无',
                        ],
                    ],
            ];
            $yksDataList[] = $yksData;
        }
        return $yksDataList;
    }

    private function makeYksInStockData($dataList)
    {
        $yksDataList = $companyNameList = [];
        foreach($dataList as $data){
            if(isset($companyNameList[$data['virtual_supplier_id']])){
                $companyName = $companyNameList[$data['virtual_supplier_id']];
            }else{
                $companyName = VirtualSupplier::where(['id'=>$data['virtual_supplier_id']])->value('company_name','');
                $companyNameList[$data['virtual_supplier_id']] = $companyName;
            }
            $yksDataList[] = [
                'purchaseorder_id' => $data['virtual_purchase_order_id'],
                'warehouseorder_id' => $data['id'],
                'sku' => $data['sku'],
                'ware_quantity' => $data['accepted_goods_qty'],
                'single_price' => round($data['price']+$data['shipping_cost'], 4),
                'warehouse_date' => date('Y-m-d H:i:s', $data['update_time']),
                'supplier_id' => $data['virtual_supplier_id'],
                'supplier_name' => $companyName,
            ];
        }
        return $yksDataList;
    }

    private function makeYksPurchaseOrderData($dataList)
    {
        if(empty($dataList)) return [];
        $yksDataList = $supplierList = [];
        $cacheGoods = Cache::store('Goods');
        $cacheWarehouse = Cache::store('Warehouse');
        foreach($dataList as $data){
            $detail = [];
            foreach ($data['detail'] as $v) {
                $sku = $cacheGoods->getSkuInfo($v['sku_id']);
                $goods = $cacheGoods->getGoodsInfo($sku['goods_id']);
                $attr = GoodsHelp::getAttrbuteInfoSku($v['sku']);
                $skuAttr = $attr ? array_combine(array_column($attr, 'name'), array_column($attr, 'value')) : [];
                $skuName = param($goods, 'declare_name') ?: param($goods, 'declare_en_name');
                $detail[] = [
                    'purchaseorder_detail_id' => $v['id'],
                    'sku' => $v['sku'],
                    'sku_name' => $skuName ?: param($goods, 'name'),
                    'quantity' => $v['qty'],
                    'single_price' => round($v['price']+$v['shipping_cost'], 4),
                    'ware_quantity' => $v['in_qty'],
                    'purchase_url' => $v['link'] ?: '无',
                    'remark' => $v['remark']==='' ? '无' : $v['remark'],
                    'sku_attr' => $skuAttr,
                    'thumb_url' => $sku['thumb'],
                    'weight' => $sku['weight'],
                    'length' => $sku['length'],
                    'width' => $sku['width'],
                    'height' => $sku['height'],
                    'asweight' => $sku['weight'],
                    'aslength' => $sku['length'],
                    'aswidth' => $sku['width'],
                    'asheight' => $sku['height'],
                ];
            }
            if(isset($supplierList[$data['virtual_supplier_id']])){
                $supplier = $supplierList[$data['virtual_supplier_id']];
            }else{
                $supplier = VirtualSupplier::where(['id'=>$data['virtual_supplier_id']])->field('company_name,balance_type')->find();
                $supplierList[$data['virtual_supplier_id']] = $supplier;
            }
            $warehouse = $cacheWarehouse->getWarehouse($data['warehouse_id']);
            $yksData = [
                'purchaseorder_id' => $data['id'],
                'supplier_id' => $data['virtual_supplier_id'],
                'supplier_name' => $supplier ? $supplier['company_name'] : '',
                'warehouse' => param($warehouse, 'name'),
                'purchaseorder_date' => date('Y-m-d H:i:s', $data['create_time']),
                'money' => $data['payable_amount'],//采购总金额
                'invoice_detail' => $data['invoice_description'],
                'invoice_num' => $data['invoice_no'],
                'status' => self::YKS_ORDER_STATUS[$data['status']] ?? 0,
                'remark' => $data['remark']==='' ? '无' : $data['remark'],
                'freight' => 0,
                'tracking_num' => $data['tracking_number'],
                'serial_num' => $data['external_number'],
                'payment_days' => $supplier ? $supplier['balance_type'] : '',
                'payment_money' => $data['actual_payment'],
                'currency' => $data['currency_code'],
                'purchaseorder_detail' => $detail
            ];
            $yksDataList[] = $yksData;
        }
        return $yksDataList;
    }

    private function makeYksPaymentData($dataList)
    {
        $yksDataList = [];
        foreach($dataList as $data){
            $yksDataList[] = [
                'payment_id' => $data['id'],
                'purchaseorder_id' => $data['virtual_purchase_order_id'],
                'supplier_id' => $data['virtual_supplier_id'],
                'money' => $data['apply_amount'],
                'currency' => $data['currency_code'],
            ];
        }
        return $yksDataList;
    }

    /**
     * @desc 回写付款信息
     */
    public function doPayForYks($data, $status)
    {
        try{
            $where = [
                'id' => $data['payment_id'],
                'virtual_purchase_order_id' => $data['purchaseorder_id'],
            ];
            $financeInfo = VirtualFinancePurchase::where($where)->find();
            $orderInfo = VirtualPurchaseOrder::alias('o')->join('virtual_supplier s', 'o.virtual_supplier_id=s.id')
                ->field('o.*,s.real')->where(['o.id'=>$data['purchaseorder_id']])->find();
            if(empty($financeInfo)) throw new JsonException('付款单不存在');
            if(empty($orderInfo)) throw new JsonException('采购单不存在');
            $statusText = ['未审核', '待付款', '已付款', '取消付款'];
            if($financeInfo['payment_status'] != 1){
                $msg = $statusText[$financeInfo['payment_status']] ?? '状态错误';
                throw new JsonException('付款单'.$msg);
            }
            if ($status == 2) {
                $paymentMoney = round($data['payment_money'], 4);
                if(abs(round($financeInfo['apply_amount']-$paymentMoney, 4)) < 1){
                    $paymentMoney = $financeInfo['apply_amount'];
                }else if($paymentMoney < $financeInfo['apply_amount']){
                    $status = 4;//变成部分付款
                }else if($paymentMoney > $financeInfo['apply_amount']){
                    throw new JsonException('付款金额不能大于申请金额');
                }
                $yksFinanceData = ['payment_money' => $paymentMoney, 'payment_time' => strtotime($data['payment_time'])];
            } else {
                $yksFinanceData = ['remark' => $data['reason']];
            }
            Db::startTrans();
            if($orderInfo['real']){
                //真实供应商
                $service = new FinancePurchaseService();
                $service->changStatus([$financeInfo['finance_purchase_id']], $status, 0, [$financeInfo['finance_purchase_id'] => $yksFinanceData]);
                $updateData = FinancePurchase::find($financeInfo['finance_purchase_id'])->toArray();
                unset($updateData['id']);
                $financeInfo->isUpdate(true)->allowField(true)->save($updateData);
            }else{
                //虚拟供应商 只回写虚拟付款单
                $updateData = [
                    'payment_status' => $status,
                    'payment_amount' => $yksFinanceData['payment_money'] ?? 0,
                    'finance_payment_time' => $yksFinanceData['payment_time'] ?? 0
                ];
                (new VirtualFinancePurchaseService())->changeStatus($updateData);
            }
            Db::commit();
            return json_encode(['code'=>200, 'msg'=>'success', 'data'=>[]]);
        }catch (JsonException $ex){
            Db::rollback();
            throw new JsonException($ex->getMessage());
        }
    }

    private function dealResponse($res)
    {
        if(is_array($res) && $res['code'] == 200){
            return true;
        }else{
            return $res;
        }
    }

    private function getErrorMsg($res)
    {
        if(is_array($res)){
            return $res['msg'] ?? '未知错误';
        }else{
            return $res;
        }
    }

    private function dealUpdateData($res)
    {
        if($res === true){
            $update = ['push_time' => time(), 'push_status' => 1];
        }else{
            $status = -1;
            $msg = $this->getErrorMsg($res);
            if($msg == '采购单已入库，不允许重复推送!' || preg_match('/采购单id:\d*付款信息已存在/', $msg) || preg_match('/采购单id:\d*已有入库记录,不允许多次入库/', $msg)){
                $status = 1;
            }
            $update = ['push_time' => time(), 'push_status' => $status, 'push_error_msg' => $msg];
        }
        return $update;
    }
}
