<?php
/**
 * Created by Phpstorm
 * User: huangweijie
 * Date: 2018-9-4
 * Time: 9:40
 * PHP version: 7.1+
 */
namespace app\common\model;

use app\common\cache\Cache;
use think\Model;
use traits\model\SoftDelete;
use app\common\model\User as UserModel;
use app\common\model\PurchaseParcels;
use app\common\model\PurchaseParcelsBoxLog;


class PurchaseParcelsBox extends Model
{
    use SoftDelete;
    protected $deletTime = 'delete_time';
    /**
     * 初始化
     *
     * @return void
     */
    protected function initialize()
    {
        parent::initialize();
    }
    /**
     * 获取器获取接收时间
     *
     * @param int $value 字段值
     * 
     * @return string
     */
    public function getCreateTimeAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 获取器获取封板时间
     *
     * @param int $value 字段值
     * 
     * @return string
     */
    public function getRecieveEndTimeAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 获取器获取拆板拆包时间
     *
     * @param int $value 字段值
     * 
     * @return string
     */
    public function getUnpackingStartTimeAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 获取器获取拆包完成时间
     *
     * @param int $value 字段值
     * 
     * @return string
     */
    public function getUnpackingEndTimeAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 获取器获取接收人的名字
     *
     * @param int $value 字段值
     * 
     * @return string
     */
    public function getCreateIdAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return Cache::store('User')->getOneUserRealname($value);
    }

    /**
     * 获取器获取拆包人姓名
     *
     * @param int $value 字段值
     * 
     * @return string
     */
    public function getUnpackingIdAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return Cache::store('User')->getOneUserRealname($value);
    }
    /**
     * 一对多关联包裹表
     *
     * @return object
     */
    public function parcelsDetail()
    {
        return $this->hasMany(PurchaseParcels::class)
            ->field('id, purchase_parcels_box_id, tracking_number, purchase_parcel_weight, purchase_order_ids, supplier_name,purchaser_user_ids,is_use');
    }

    /**
     * 一对多关联包裹表
     *
     * @return object
     */
    public function parcels()
    {
        return $this->hasMany(PurchaseParcels::class);
    }

    /**
     * 一对多关联卡板日志表
     *
     * @return obeject
     */
    public function boxLog()
    {
        return $this->hasMany(PurchaseParcelsBoxLog::class, 'purcahse_purcels_box_id');
    }
}