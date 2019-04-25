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
class StockInDetail extends Model
{
    
    //use SoftDelete;
    //protected $deleteTime = 'delete_time';
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 新增入库详情记录
     * @param array $array
     * @return bool
     */
    public function add(array $array)
    {
        time_partition(__CLASS__, time(), null, [], true);
        $this->allowField(true)->save($array);
    }

    public function goodsName()
    {
        return $this->hasOne(GoodsSku::class,'id', 'sku_id');
    }
   
    
}