<?php
namespace app\common\model;

use think\Model;

/**
 * Created by phpstorm.
 * User: zhaixueli
 * Date: 2017/11/28
 * Time: 11:13
 */
class AllocationBoxClass extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function picking()
    {
        return $this->belongsTo('picking', 'picking_id', 'id');
    }
}

