<?php
namespace app\common\model;
use traits\model\SoftDelete;
use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class StockOut extends Model
{
    
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    
    //public $field = 'id, code, type, original_code, warehouse_id, status, create_time, update_time, create_id, update_id';
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

    /** 新增出库记录
     * @param array $array
     */
    public function add(array $array)
    {
        time_partition(__CLASS__, time(), null, [], true);
        $this->allowField(true)->save($array);
    }

    public function details()
    {
        return $this->hasMany(StockOutDetail::class,'stock_out_id','id');
    }


}