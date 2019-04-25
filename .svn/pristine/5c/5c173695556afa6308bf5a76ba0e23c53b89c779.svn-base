<?php

namespace app\api\service;

/**
 * User: 曾绍辉
 * Date: 2017/4/26
 */
use think\Exception;
use think\Db;
use app\common\model\PurchaseOrder;
use app\common\model\GoodsSku;
use app\common\model\OrderPackage;
use app\common\model\UserMap;
use app\warehouse\service\StockIn;
use app\warehouse\service\StockOut;
use app\order\service\OrderService;
use app\common\model\Allocation as AllocationModel;
use app\common\model\PurchaseOrderDetail;
use app\common\model\WmsLog;
use app\common\model\StockOut as StockOutModel;
use app\common\model\PurchaseOrderLog;
use app\common\service\UniqueQueuer;
use app\warehouse\queue\OrderCostQueue;
use app\warehouse\queue\StockOutQueue;
use app\order\service\PackageService;
use app\purchase\service\PurchaseParcelsService;

class GuanYi {

    /**
     * @module 采购
     * @title 库存异动(采购入库)
     * @param array $list
     * @return boolean
     * @throw Exception
     */
    public function inventorychanged(array $list) 
    {
        if (empty($list['fromno']) || empty($list['whcode']) || empty($list['sku']) || empty($list['qty'])) {
            throw new Exception("采购单号、仓库编码、sku、入库数量都不能为空");
        }
        $purOrder = new PurchaseOrder();
        $gsku = new GoodsSku();
        $stockInService = new StockIn();
        $user = new UserMap();
        $purCodeList = explode('_', $list['fromno']);
        $purCode = $purCodeList[0]; // 采购订单单据号
        $purId = $purOrder->get(array("purchase_order_code" => $purCode));
        if (empty($purId)) {
            throw new Exception("找不到采购单号为： " . $purCode . "  这条数据");
        }
        $sku = $gsku->get(array("sku" => $list['sku'])); // sku
        if (empty($sku)) {
            throw new Exception("找不到SKU为： " . $list['sku'] . "  这条数据");
        }
        $purDetail = PurchaseOrderDetail::where(['sku_id' => $sku->id, 'purchase_order_id' => $purId->id])->find();
        if (empty($purDetail)) {
            throw new Exception("找不到sku为： " . $list['sku'] . "  这条采购记录");
        }
        $params['details'] = [];
        $params['details'][1]['original_id'] = isset($purId->id) ? $purId->id : 0; // 采购单ID
        $params['details'][1]['original_code'] = $purCode; // 采购单ID
        $params['details'][1]['warehouse_id'] = 2;           // $purId->warehouse_id; // 仓库ID
        $params['details'][1]['goods_id'] = $sku->goods_id; // 产品ID
        $params['details'][1]['sku_id'] = $sku->id;       // SKU ID
        $params['details'][1]['sku'] = $sku->sku;      // sku
        $params['details'][1]['quantity'] = $list['qty'];   // 入库数量
        $params['details'][1]['actual_quantity'] = $list['qty'];  // 实际入库数量
        $params['details'][1]['price'] = $purDetail->price;      // 产品单价
        $params['details'][1]['remark'] = $list['aptime'];       // 推送时间
        $apuser = $user->get(array("username" => $list['apuser']));
        $createId = $apuser ? $apuser->user_id : 1;
        Db::startTrans();
        try {
            $stockId = $stockInService->insert($createId, 2, $purCode, 11, StockIn::STATUS_AUDITED, $params['details'], $purId->id, $purCode);
            $stockInService->doInStock($stockId);
            $qty = $purDetail->in_qty + $list['qty'];
            $purDetail->save(['in_qty' => $qty]);
            $log = [
                'operate_time' => time(),
                'operate_user_id' => 0,
                'operate_content' => 'SKU为'. $sku->sku . '入库数量为'. $list['qty'],
                'update_time' => time(),
                'update_user_id' => 0,
                'purchase_order_id' => $purId->id,
                'log_type' => 0,
                'status' => 0
            ];
            $purchaseOrderLog = new PurchaseOrderLog();
            $purchaseOrderLog->allowField(true)->isUpdate(false)->save($log);
            Db::commit();            
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception("插入入库单失败-" . $e->getMessage());
        }
    }
    
    /**
     * 库存异动执行
     * @param array $list
     * @return boolean|string
     */
    public function inventoryExecute(array &$list) 
    {
        $count = $this->countWmsLog(['unique_code' => $list['fromno'].'_'.$list['sku'], 'status' => 1]);
        if ($count) {
            return true;
        }
        $logId = $this->addWmsLog($list, 'inventoryChanged', $list['parent_log_id'], $list['fromno'].'_'.$list['sku']); // 插入日志
        try {
            $this->inventorychanged($list);
            $this->updateWmsLog($logId, 1);
            return true;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->updateWmsLog($logId, 2, $message);
            return $message;
        }
    }
    
    public function stockOut(array &$list)
    {
        $uniqueCode = md5(json_encode($list));
        $count = $this->countWmsLog(['unique_code' => $uniqueCode, 'status' => 1]);
        if ($count) {
            return true;
        }
        $logId = $this->addWmsLog($list, 'inventoryChanged', $list['parent_log_id'], $uniqueCode); // 插入日志
        $sku = GoodsSku::where(['sku' => $list['sku']])->field('id,goods_id,sku')->find();
        if (empty($sku)) {
            return $list['sku'] . '不存在';
        }
        Db::startTrans();
        try {
            $warehouse_id = 2;
            $details[] = [
                'goods_id' => $sku->goods_id, // 产品ID
                'sku_id' => $sku->id,       // SKU ID
                'sku' => $sku->sku,      // sku
                'quantity' => abs($list['qty']),   // 出库数量
                'actual_quantity' => abs($list['qty']),  // 实际出库数量         
                'remark' => $list['aptime']. ' '. $list['fromty'],      // 推送时间
            ];
            $stockOutService = new StockOut();
            $stockId = $stockOutService->insert($warehouse_id, 23, $sku->sku, $sku->id, StockOut::STATUS_AUDITED, $details); // 创建出库单
            $stockOutService->doOutStock($stockId);
            $this->updateWmsLog($logId, 1);
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            $message = $e->getMessage();
            $this->updateWmsLog($logId, 2, $message);
            return $message;
        }
    }
    
    public function stockIn(array &$list)
    {
        $uniqueCode = md5(json_encode($list));
        $count = $this->countWmsLog(['unique_code' => $uniqueCode, 'status' => 1]);
        if ($count) {
            return true;
        }
        $warehouse_id = 2;
        $logId = $this->addWmsLog($list, 'inventoryChanged', $list['parent_log_id'], $uniqueCode); // 插入日志
        $sku = GoodsSku::where(['sku' => $list['sku']])->field('id,goods_id,sku')->find();
        if (empty($sku)) {
            return $list['sku'] . '不存在';
        }
        Db::startTrans();
        try {
            $stockOutService = new StockOut();
            $details[] = [
                'goods_id' => $sku->goods_id, // 产品ID
                'sku_id' => $sku->id,       // SKU ID
                'sku' => $sku->sku,      // sku
                'quantity' => abs($list['qty']),   // 入库数量
                'actual_quantity' => abs($list['qty']),  // 实际入库数量
                'price' => $stockOutService->getSkuPrice($warehouse_id, $sku->id),
                'remark' => $list['aptime'] . ' '. $list['fromty'],      // 推送时间
            ]; 
            $stockInService = new StockIn();
            $stockId = $stockInService->insert(0, $warehouse_id, $sku->sku, 23, StockIn::STATUS_AUDITED, $details); // 创建入库单
            $stockInService->doInStock($stockId);
            $this->updateWmsLog($logId, 1);
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            $message = $e->getMessage();
            $this->updateWmsLog($logId, 2, $message);
            return $message;
        }
    }
    
    /**
     * 执行发货
     */
    public function delivery() 
    {
        
    }

    /**
     * 发货回传(更新包裹状态)
     * @param array $list
     * @return \think\Response
     */
    private function deliveryReturnPacking(array $list) 
    {
        $orderSer = new OrderService();
        $packageNum = $list['fromno']; // 包裹号
        $date = $list['date'];  // 发货日期
        $orderSer->delivery($packageNum, $date, $list['weight'] * 1000);
    }

    /**
     * 发货回传(更新库存)
     * @param array $list
     * @return \think\Response
     */
    private function deliveryReturnStock(array $list) 
    {
        $stockOutService = new StockOut();
        $orPack = new OrderPackage();
        $warehouse_id = 2;
        $pack = $orPack->get(["number" => trim($list['fromno'])]);  // 包裹
        $param['warehouse_id'] = $warehouse_id;               // 仓库ID
        $param['original_code'] = trim($list['fromno']);             // 订单包裹号
        $count = StockOutModel::where(['original_code' => $param['original_code']])->count();
        if ($count) {
            return true;
        }
        $details = array();
        foreach ($list['detail'] as $k => $v) {
            $gsku = new GoodsSku();
            $sku = $gsku->get(array("sku" => $v['sku']));     // sku
            $details[$k]['goods_id'] = $sku->goods_id;
            $details[$k]['sku'] = $sku->sku;
            $details[$k]['sku_id'] = $sku->id;
            $details[$k]['quantity'] = $v['qty'];
            $details[$k]['actual_quantity'] = $v['qty'];
            $details[$k]['remark'] = $list['date'];
        }
        $param['details'] = $details;
        Db::startTrans();
        try {
            $stockId = $stockOutService->insert($warehouse_id, 21, $param['original_code'], $pack->id, StockOut::STATUS_AUDITED, $details); // 创建出库单
            $stockOutService->doOutStock($stockId);            
            Db::commit();
        } catch (\Exception $ex) {
            Db::rollback();
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @module 调拨
     * @title 库存异动(调拨出库)
     * @param $list
     */
    public function allocationInventorychanged($list) {
        print_r($list);
        exit;
        if (empty($list['fromno']) || empty($list['whcode']) || empty($list['detail']) || empty($list['qty'])) {
            return ["isSuccess" => false, "ErrMsg" => "采购单号、仓库编码、sku、入库数量都不能为空"];
        }

        $ordcode = $list['fromno']; #采购订单单据号
        $allocation = AllocationModel::where(['order_code' => $ordcode])->with('detail')->find();
        foreach ($allocation['detail'] as $detail) {
            $allocation_goods[$detail['sku']] = $detail;
        }
        $userModel = new UserMap();
        $apuser = $userModel->get(array("username" => $list['apuser']));
        $createId = $apuser ? $apuser->user_id : 1;

        $gsku = new GoodsSku();
        $details = array();
        foreach ($list['detail'] as $k => $v) {
            $sku = $gsku->get(array("sku" => $v['sku'])); #sku
            $details[$k]['goods_id'] = $sku->goods_id;
            $details[$k]['sku_id'] = $sku->id;
            $details[$k]['quantity'] = $v['qty'];
            $details[$k]['price'] = $sku->cost_price;
            $details[$k]['actual_quantity'] = $v['qty'];
            $details[$k]['remark'] = "ceshi";
        }
        $param['details'] = $details;

        $code = rand(100000, 999999); //for test         
        $stockInService = new StockIn();
        $stockOutService = new StockOut();
        //$stockOutId = $stockOutService->insert($allocation['out_warehouse_id'],$code,22,$ordcode,$list['descri'],2,$details);#创建出库单
        // $stockInId = $stockInService->insert($createId,$allocation['in_warehouse_id'],$list['descri'],12 ,1,$details);
        //修改出库仓库可用数量            
        //$stockOutService->doOutStock($stockOutId);
        //$stockInService->doInStock($stockInId);
        //修改调拨单状态为：调拨在途
    }

    /**
     * 管易日志插入
     * @param array $data
     * @param string $type
     * @param string $table_name
     * @param string $unique
     * @return int
     */
    public function AddWmsLog($data = [], $type = 'inventoryChanged', $table_name = '', $unique = '') 
    {
        // 日志数据
        $logData = [
            'api_type' => $type,
            'table_name' => $table_name,
            'field' => 'fromty',
            'field_val' => param($data, 'fromty', 'wms推送'),
            'post_data' => json_encode($data),
            'status' => false,
            'created_time' => time(),
            'created_by' => 0,
            'status' => false,
            'unique_code' => $unique
        ];

        try {
            $wmsLog = new WmsLog();
            $wmsLog->allowField(true)->save($logData);
            $logId = $wmsLog->id;
        } catch (Exception $ex) {
            $logId = 0;
        }
        return $logId;
    }
    
    /**
     * 插入wms日志
     * @param array $data
     * @return boolean
     */
    public function insertWmsLog(array $data)
    {
        $wmsLog = new WmsLog();
        $data['created_time'] = time();
        return $wmsLog->allowField(true)->isUpdate(false)->save($data);
    }
    
    /**
     * 更新管易日志
     * @param int $logId
     * @param array $data
     * @return boolean
     */
    public function updateWmsLog($logId, $status, $msg = '') 
    {
        $data = [
            'status' => $status,
            'return_msg' => $msg
        ];
        $wmsLog = new WmsLog();
        try {
            $wmsLog->where(['id' => $logId])->update($data);
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * 查询管易日志count
     * @param array $where
     * @return int
     */
    public function countWmsLog(array $where) 
    {
        return WmsLog::where($where)->count();
    }

    /**
     * 处理发货
     * @param array $list
     * @return boolean|string
     */
    public function deliveryExecute(array $list) 
    {
        $count = $this->countWmsLog(['unique_code' => $list['fromno'], 'status' => 1]);
        if ($count) {
            return true;
        }
        $log_id = $this->AddWmsLog($list, 'deliveryReturnDetail', $list['parent_log_id'], $list['fromno']);
        try {
            $this->deliveryReturnPacking($list); // 更新包裹状态
            $this->deliveryReturnStock($list);   // 更新库存
            $this->updateWmsLog($log_id, 1);
            return true;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->updateWmsLog($log_id, 2, $message);
            return $message;
        }
    }
    
    /**
     * 仓库发起拒单业务
     * @param array $list
     * @return boolean
     */
    public function rejectExecute(array $list)
    {
        $count = $this->countWmsLog(['unique_code' => $list['orderno'], 'status' => 1]);
        if ($count) {
            return true;
        }
        $log_id = $this->AddWmsLog($list, 'rejectDetail', $list['parent_log_id'], $list['orderno']);
        try {
            $packageInfo = OrderPackage::where(['reference_number' => $list['orderno']])->field('id')->find();
            if (!$packageInfo) {
                throw new Exception($list['orderno'] . '不存在');
            }
            $packageService = new PackageService();
            $result = $packageService->releasePackage([$packageInfo->id], true);
            if (!$result['success']) {
                throw new Exception('执行失败');
            }
            $this->updateWmsLog($log_id, 1);
            return true;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->updateWmsLog($log_id, 2, $message);
            return $message;
        }
    }

}
