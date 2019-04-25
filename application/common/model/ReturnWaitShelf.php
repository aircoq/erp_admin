<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
/**
 * @desc 退回待上架
 * @author libaimin
 * @date 2018-11-30 13:45:11
 */
class ReturnWaitShelf extends Model
{


    /**
     * @desc 获取数据库表字段
     * @author Jimmy
     * @date 2017-12-06 13:46:11
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

    /**
     * @desc 获取对应的条目信息
     * @author Jimmy <554511322@qq.com>
     * @date 2018-02-24 10:28:11
     */
    public function details()
    {
        return $this->hasMany(ReturnWaitShelfDetail::class, 'return_shelves_id', 'id')->order('sort asc');
    }

    /**
     * @desc 获取状态名称
     * @param type $value status值
     * @param type $data 本条数据
     * @return 状态名称
     */
    public function getStatusNameAttr($value, $data)
    {
        $status = ['0' => '未上架', '1' => '已上架'];
        return $status[$data['status']];
    }

    public function isHas($warehouseId, $skuId)
    {
        $where['warehouse_id'] = $warehouseId;
        $where['sku_id'] = $skuId;
        $where['status'] = 0;
        return $this->where($where)->find();
    }

    public function isHasCid($cargo_goods_id)
    {
        $where['cargo_goods_id'] = $cargo_goods_id;
        $where['status'] = 0;
        return $this->where($where)->find();
    }

}
