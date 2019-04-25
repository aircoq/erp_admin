<?php


namespace app\common\model;


use app\common\cache\Cache;
use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;

class ReadyInventoryApplyList extends ErpModel
{
    const STATUS_CANCELED = 0;//已作废
    const STATUS_WAIT_COMMIT = 1;//待提交
    const STATUS_ED_COMMIT = 2;//已提交
    const STATUS_WAIT_REVIEW = 3; //待审核
    const STATUS_WAIT_DEVELOP_REVIEW = 4; //待开发审核
    const STATUS_WAIT_PURCHASE_REVIEW = 5; //待采购审核
    const STATUS_WAIT_FINISH = 6; //已处理
    const STATUS_ALLOCATION_FINISH = 10; //调拨完成
    const STATUS_ENFORCE_FINISH = 11; //强制完成
    const STATUS_PURCHASE_CANCELED = 12; //采购驳回
    const STATUS_DEVELOP_CANCELED = 13; //开发驳回
    const STATUS_TXT = [
        self::STATUS_CANCELED => '已作废',
        self::STATUS_WAIT_COMMIT => '待提交',
        self::STATUS_ED_COMMIT => '已提交',
        self::STATUS_WAIT_REVIEW => '待审核',
        self::STATUS_WAIT_DEVELOP_REVIEW => '待开发审核',
        self::STATUS_WAIT_PURCHASE_REVIEW => '待采购审核',
        self::STATUS_WAIT_FINISH => '已处理',
        self::STATUS_ALLOCATION_FINISH => '调拨完成',
        self::STATUS_ENFORCE_FINISH => '强制完成',
        self::STATUS_PURCHASE_CANCELED => '采购驳回',
        self::STATUS_DEVELOP_CANCELED => '开发驳回',
    ];

    //数据过滤器
    use ModelFilter;
    public function scopeReadyApply(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.creator_id', 'in', $params);
        }
    }
    //开发员数据过滤器
    public function scopeReadyApplyDeveloper(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.goods_id', 'in', $params);
        }
    }
    //采购员数据过滤器
    public function scopeReadyApplyPurchase(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.goods_id', 'in', $params);
        }
    }

    public function getStatusTxtAttr($value, $data)
    {
        return self::STATUS_TXT[$data['status']] ?? '';
    }

    public function getThumbAttr($value, $data)
    {
        $skuInfo = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return $skuInfo['thumb'] ?? '';
    }

    public function getGoodsNameAttr($value, $data)
    {
        $skuInfo = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return $skuInfo['spu_name'] ?? '';
    }

    public function getChannelAttr($value, $data)
    {
        return Cache::store('channel')->getChannelName($data['channel_id']);
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

    public function getCreatorAttr($value, $data)
    {
        if ($data['creator_id']) {
            $user = Cache::store('user')->getOneUser($data['creator_id']);
            if ($user) {
                return $user['realname'];
            }
        }
        return '';
    }

    public function ReadyInventory()
    {
        return $this->belongsTo(ReadyInventory::class, 'ready_inventory_id', 'id');
    }

}