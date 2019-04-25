<?php


namespace app\common\model;


use erp\ErpModel;
use app\common\cache\Cache;
use app\common\traits\ModelFilter;
use think\db\Query;

class ReadyInventory extends ErpModel
{
    const STATUS_ED_CANCEL = 0;//已作废
    const STATUS_WAIT_COMMIT = 1;//未提交
    const STATUS_WAIT_CHECK = 2;//待审核
    const STATUS_ED_CHECK = 3;//已审核
    const STATUS_WAIT_ALLOCATION = 4; //等待调拨
    const STATUS_SECTION_WAIT_ALLOCATION = 5; //部分等待调拨
    const STATUS_ASSIGNED = 6; //已调拨
    const STATUS_FINISH = 7; //已完结
    const STATUS_TXT = [
        self::STATUS_ED_CANCEL => '已作废',
        self::STATUS_WAIT_COMMIT => '未提交',
        self::STATUS_WAIT_CHECK => '待审核',
        self::STATUS_ED_CHECK => '已审核',
        self::STATUS_WAIT_ALLOCATION => '等待调拨',
        self::STATUS_SECTION_WAIT_ALLOCATION => '部分等待调拨',
        self::STATUS_ASSIGNED => '已调拨',
        self::STATUS_FINISH => '已完结',
    ];
    //开发审核状态
//    const DEVELOP_NO_STATUS = 0; //无状态
    const DEVELOP_WAIT_REVIEW = 1; //待开发审核
    const DEVELOP_SECTION_REVIEW = 2; //开发部分处理
    const DEVELOP_FULL_REVIEW = 3; //开发全部处理
    const DEVELOP_STATUS_TXT = [
//        self::DEVELOP_NO_STATUS => '无状态',
        self::DEVELOP_WAIT_REVIEW => '待开发审核',
        self::DEVELOP_SECTION_REVIEW => '开发部分处理',
        self::DEVELOP_FULL_REVIEW => '开发全部处理',
    ];
    //采购审核状态
//    const PURCHASE_NO_STATUS = 0; //无状态
    const PURCHASE_WAIT_REVIEW = 1; //待采购审核
    const PURCHASE_SECTION_REVIEW = 2; //采购部分处理
    const PURCHASE_FULL_REVIEW = 3; //采购全部处理
    const PURCHASE_STATUS_TXT = [
//        self::PURCHASE_NO_STATUS => '无状态',
        self::PURCHASE_WAIT_REVIEW => '待采购审核',
        self::PURCHASE_SECTION_REVIEW => '采购部分处理',
        self::PURCHASE_FULL_REVIEW => '采购全部处理',
    ];
    //数据过滤器
    use ModelFilter;
    public function scopeReady(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.submitter_id', 'in', $params);
        }
    }

    public function getLastByWarehouseId($warehouseId,$transitWarehouseId=0)
    {
        if (is_array($warehouseId)) {
            $aWarehouseId = $warehouseId;
        } else {
            $aWarehouseId = [$warehouseId];
        }
        $ret = $this->where('status', self::STATUS_WAIT_CHECK)
            ->where('warehouse_id', 'in', $aWarehouseId)
            ->where('transit_warehouse_id',$transitWarehouseId)
            ->select();
        $result = [];
        foreach ($ret as $v) {
            $result[$v['warehouse_id']] = $v;
        }
        return $result;
    }

    public function getStatusTxtAttr($value, $data)
    {
        return self::STATUS_TXT[$data['status']] ?? '';
    }

    public function ApplyList()
    {
        return $this->hasMany(ReadyInventoryApplyList::class, 'ready_inventory_id', 'id');
    }

    public function detail()
    {
        return $this->hasMany(ReadyInventoryDetail::class, 'ready_inventory_id', 'id');
    }

    public function getWarehouseAttr($value, $data)
    {
        if ($data['warehouse_id']) {
            $tmp = Cache::store('warehouse')->getWarehouse($data['warehouse_id']);
            return $tmp ? $tmp['name'] : '';
        }
        return '';
    }

    public function getTransitWarehouseAttr($value, $data)
    {
        if ($data['transit_warehouse_id']) {
            $tmp = Cache::store('warehouse')->getWarehouse($data['transit_warehouse_id']);
            return $tmp ? $tmp['name'] : '';
        }
        return '';
    }

    public function getAuditorAttr($value, $data)
    {
        if ($data['auditor_id']) {
            $user = Cache::store('user')->getOneUser($data['auditor_id']);
            if ($user) {
                return $user['realname'];
            }
        }
        return '';
    }

    public function getAuditor2Attr($value, $data)
    {
        if ($data['auditor_id_2']) {
            $user = Cache::store('user')->getOneUser($data['auditor_id_2']);
            if ($user) {
                return $user['realname'];
            }
        }
        return '';
    }
    
    public function getAuditor3Attr($value, $data)
    {
        if ($data['auditor_id_3']) {
            $user = Cache::store('user')->getOneUser($data['auditor_id_3']);
            if ($user) {
                return $user['realname'];
            }
        }
        return '';
    }
    public function getSubmitterAttr($value, $data)
    {
        if ($data['submitter_id']) {
            $user = Cache::store('user')->getOneUser($data['submitter_id']);
            if ($user) {
                return $user['realname'];
            }
        }
        return '';
    }
}