<?php

/**
 * Created by PhpStorm.
 * User: yangweiquan
 * Date: 2017-6-9
 * Time: 20:12
 */

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use traits\model\SoftDelete;

class PurchaseParcels extends Model
{
    // 用Thinkphp5自带软删除delete_time
    use SoftDelete;
    /**
     * 拆包异常处理
     * @var array
     */
    const ABNORMAL_STATUS = [
        'not_processed' => 1, // 未处理拆包异常
        'can_unpack' => 2,  // 拆包异常处理为继续入库
        'return_parcel' => 3, // 拆包异常处理为退货
        'other_order' => 4, // 拆包异常处理为其他入库-继续入库
        'complete' => 5, // 拆包异常处理为已处理
        'other_order_end' => 6, // 拆包异常处理为其他入库-已入库
    ];

    /**
     * 包裹状态
     * @var array
     */
    const PARCEL_STATUS = [
        'no_unpack' => 0,
        'unpacked' => 1,
        'ready_receive' => 2,
        'receive_abnormal' => 3
    ];

    /**
     * 收包异常类型
     * @var array
     */
    const RECEIVE_ABNORMAL = [
        'no_parcel_order' => 1,
        'lost_parcel' => 2,
        'return_logistics' => 3,
        'return_supplier' => 4,
        'move_other_warehouse' => 5,
        'other' => 6,
    ];

    const NOT_ALLOW_UNPACK = [
        self::RECEIVE_ABNORMAL['no_parcel_order'],
        self::RECEIVE_ABNORMAL['return_logistics'],
        self::RECEIVE_ABNORMAL['return_supplier'],
        self::RECEIVE_ABNORMAL['other']
    ];

    // 删除时间
    protected $deleteTime = 'delete_time';

    public function getCreatorNameAttr($value, $data)
    {
        $res = Cache::store('User')->getOneUser($data['creator']);
        return $res['realname'];
    }

    public function getParcelsStatus($value)
    {
        $result = ['未拆', '已拆', '已接收', '异常'];
        return $result[$value];
    }
}
