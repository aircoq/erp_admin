<?php

namespace app\common\model;

use app\common\service\Common;
use app\index\service\AccountApplyService;
use app\warehouse\service\ReturnWaitShelvesService;
use think\Cache;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/2/19
 * Time: 11:47
 */
class ReturnWaitShelves extends Model
{


    const STATUS = [
        '1' => '待上架','2' => '处理中', '3' => '已完成'
    ];

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * @desc 获取数据库表字段
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

    /**
     * @desc 获取对应的条目信息
     */
    public function details()
    {
        return $this->hasMany(ReturnWaitShelvesService::class, 'return_shelves_id', 'id')->order('sort asc');
    }

    /**
     * @desc 获取状态名称
     * @param type $value status值
     * @param type $data 本条数据
     * @return 状态名称
     */
    public function getStatusNameAttr($value, $data)
    {
        return self::STATUS[$data['status']];
    }

    public function isHas($warehouseId, $skuId)
    {
        $where['warehouse_id'] = $warehouseId;
        $where['sku_id'] = $skuId;
        $where['status'] = ['<>', 3];
        return $this->where($where)->find();
    }


}