<?php

/**
 * @desc 上架单验证器
 * @author Jimmy
 * @date 2017-11-24 11:03:11
 */

namespace app\common\validate;

use think\Validate;

class PutawayOrder extends Validate
{

    //规则
    protected $rule = [
        'warehouse_id' => 'require|number',
        'warehouse_area_id' => 'require|number',
        'warehouse_area_type' => 'require|number',
        'status' => 'require|number',
        'creator_id' => 'require|number',
    ];
    //信息
    protected $message = [
        'warehouse_id.require' => '仓库ID必须',
        'warehouse_id.number' => '仓库ID必须是数字',
        'warehouse_area_id.require' => '仓库分区必须',
        'warehouse_area_id.number' => '仓库分区必须是数字',
        'warehouse_area_type.require' => '仓库分区类型必须',
        'warehouse_area_type.number' => '仓库分区类型必须是数字',
        'status.require' => '状态必须',
        'status.number' => '状态必须是数字',
        'creator_id.require' => '创建人ID必须',
        'creator_id.number' => '创建人ID必须是数字',
    ];
    //字段信息
    protected $field = [
        'warehouse_id' => '仓库ID',
        'warehouse_area_id' => '仓库分区ID',
        'warehouse_area_type' => '仓库分区类型',
        'status' => '状态',
        'creator_id' => '创建人ID',
    ];
    //场景
    protected $scene = [
        //新增
        'create' => ['warehouse_id', 'warehouse_area_id', 'warehouse_area_type', 'status', 'creator_id'],
    ];

}
