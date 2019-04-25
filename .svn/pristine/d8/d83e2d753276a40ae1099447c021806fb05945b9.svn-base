<?php
namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\common\traits\ModelFilter;
use think\db\Query;
use erp\ErpModel;
use app\warehouse\service\LocalReadyInventoryService;

class LocalReadyInventory extends ErpModel
{
    
    use ModelFilter;
    
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    //数据过滤器
    public function scopeLocalReady(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.create_id', 'in', $params);
        }
    }
    
    /**
     * 关联信息
     */
    public function detail()
    {
        return parent::hasMany('LocalReadyInventoryDetail', 'local_ready_inventory_id','id');
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
    
    public function getCreatorAttr($value, $data)
    {
        if ($data['create_id']) {
            $user = Cache::store('user')->getOneUser($data['create_id']);
            if ($user) {
                return $user['realname'];
            }
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

    public function getAuditor3Attr($value,$data)
    {
        if ($data['auditor_id_3']) {

            $user = Cache::store('user')->getOneUser($data['auditor_id_3']);
            if ($user) {
                return $user['realname'];
            }
        }
        return '';
    }
    
    public function getStatusNameAttr($value, $data)
    {
        return isset($data['status']) ? param(LocalReadyInventoryService::$status, $data['status'], '') : '';
    }
    
//     /** 检查是否存在
//      * @param array $data
//      * @return bool
//      */
//     public function check(array $data)
//     {
//         $result = $this->get($data);
//         if (!empty($result)) {
//             return true;
//         }
//         return false;
//     }

//     public function purchaseOrder()
//     {
//         return $this->belongsTo(PurchaseOrder::class, 'id', 'purchase_plan_id');
//     }

}