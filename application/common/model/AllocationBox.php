<?php
namespace app\common\model;

use think\Model;
use app\common\cache\Cache;

/**
 * Created by PhpStorm
 * User: laiyongfeng
 * Date: 2018/11/29
 * Time: 18:28
 */
class AllocationBox extends Model
{
    const STATUS_CANCEL = -1;//作废
    const STATUS_WAIT_PACK = 0;//待装箱
    const STATUS_PACKING = 1;//装箱中
    const STATUS_FINISH_PACK = 2;//装箱完成
    const STATUS_SHIPPING = 3;//已发货
    const STATUS_PART_IN = 4;//部分入库
    const STATUS_ALL_IN = 5;//全部入库
    const STATUS_PART_IN_FINISH = 6;//部分入库完结
    const STATUS_TXT = [
        self::STATUS_CANCEL => '作废',
        self::STATUS_WAIT_PACK => '待装箱',
        self::STATUS_PACKING => '装箱中',
        self::STATUS_FINISH_PACK => '装箱完成',
        self::STATUS_SHIPPING => '已发货',
        self::STATUS_PART_IN => '部分入库',
        self::STATUS_ALL_IN => '全部入库',
        self::STATUS_PART_IN_FINISH => '部分入库完结',
    ];

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * @desc 箱子详情
     */
    public function detail()
    {
        return $this->hasMany(AllocationBoxDetail::class,'allocation_box_id','id');
    }

    public function getDeliverAttr($value, $data)
    {
        if ($data['deliver_id']) {
            $user = Cache::store('user')->getOneUser($data['deliver_id']);
            if ($user) {
                return $user['realname'];
            }
        }
        return '';
    }

    public function getStatusTxtAttr($value, $data)
    {
        $status_txt = self::STATUS_TXT;
        return isset($status_txt[$data['status']]) ? $status_txt[$data['status']] : '';
    }
}