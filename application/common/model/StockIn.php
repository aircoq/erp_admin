<?php
namespace app\common\model;
use erp\ErpModel;
use think\db\Query;
use traits\model\SoftDelete;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class StockIn extends ErpModel
{
    
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 检查数据
     * @param array $data
     * @return bool
     */
    public function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /** 新增入库记录
     * @param array $array
     */
    public function add(array $array)
    {
        time_partition(__CLASS__, time(), null, [], true);
        $this->allowField(true)->save($array);
    }

    public function scopeWarehouse(Query $query, $warehouseId)
    {
        $query->where('warehouse_id', $warehouseId);
    }

    public function scopeType(Query $query, $type)
    {
        $query->where('type', $type);
    }

    public function details()
    {
        return $this->hasMany(StockInDetail::class,'stock_in_id','id');
    }
}