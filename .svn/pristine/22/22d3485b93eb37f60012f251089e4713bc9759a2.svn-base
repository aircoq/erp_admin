<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\warehouse\service\WarehouseArea as WarehouseAreaService;

/**
 * @desc 上架单
 * @author Jimmy
 * @date 2017-11-22 16:11:11
 */
class PutawayOrder extends Model
{

    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    /**
     * @desc 获取数据库表字段
     * @author Jimmy
     * @date 2017-12-06 11:14:11
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }
    /**
     * @descc 查询获取器，字段status
     * @param int $value 需要查询的值
     * @return 返回对应的中文
     * @author Jimmy
     * @date 2017-11-23 14:01:11
     */
    public function getStatusAttr($value)
    {
        $status = [0 => '作废', 1 => '收集中', 2 => '上架中', 3 => '上架完成'];
        return $status[$value];
    }

    /**
     * @desc 将ID序列化为6位数
     * @param int $value 查询的ID字段
     * @return string 六位数
     * @author Jimmy
     * @date 2017-11-23 16:22:11
     */
    public function getIdAttr($value)
    {
        return str_pad($value, 6, "0", STR_PAD_LEFT);
    }

    /**
     * @desc 根据仓库ID获取仓库名称
     * @param type 仓库ID
     * @return string 仓库名称
     * @author Jimmy
     * @date 2017-11-23 16:46:11
     */
    public function getWarehouseIdAttr($value)
    {
        return Cache::store('warehouse')->getWarehouseNameById($value);
    }

    /**
     * @desc 根据仓库区域ID获取仓库区域名称
     * @param int $value 仓库区域ID
     * @return string 仓库区域名称
     * @author Jimmy
     * @date 2017-11-23 17:00:11
     */
    public function getWarehouseAreaIdAttr($value)
    {
        return WarehouseAreaService::getWarehouseAreaNameById($value);
    }

    /**
     * @desc 根据用户id获取用户名称
     * @param int $value 用户ID
     * @return string 用户名称
     * @author Jimmy
     * @date 2017-11-23 17:06:11
     */
    public function getCreatorIdAttr($value)
    {
        $res = Cache::store('User')->getOneUser($value);
        return $res['realname'];
    }

    /**
     * @desc 根据仓库区域类型编码获取名称
     * @param int $value 仓库区域类型编码
     * @author Jimmy
     * @date 2017-11-23 17:11:11
     */
    public function getWarehouseAreaTypeAttr($value)
    {
        $type = [11 => '拣货区', 12 => '快速发货区', 21 => '不良品区'];
        return $type[$value];
    }

    /**
     * @desc 一对多关联查询详表数据信息
     * @author Jimmy
     * @return obj 详表数据信息
     * @date 2017-11-23 14:46:11
     */
    public function details()
    {
        return $this->hasMany(PutawayOrderDetail::class, 'putaway_order_id', 'id');
    }

    public function getValue($value)
    {
        return $this->data[$value];
    }
}
