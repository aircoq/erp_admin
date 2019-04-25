<?php

namespace app\common\model;

use app\common\service\Common;
use think\Model;
use app\common\cache\Cache;
use app\warehouse\service\WarehouseCargo as WarehouseCargoService;
use app\goods\service\GoodsSkuAlias;
/**
 * @desc 待上架列表
 * @author Jimmy
 * @date 2017-11-13 11:25:11
 */
class PutawayWaitingGoods extends Model
{

    protected $autoWriteTimestamp = true;

    /**
     * @desc 获取数据库表字段
     * @author Jimmy
     * @date 2017-11-30 14:06:11
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

    /**
     * @desc 根据仓库ID获取仓库名称
     * @param type 仓库ID
     * @return string 仓库名称
     * @author Jimmy
     * @date 2017-11-30 16:15:11
     */
    public function getWarehouseIdAttr($value)
    {
        return Cache::store('warehouse')->getWarehouseNameById($value);
    }

    /**
     * @desc 根据仓库区域类型编码获取名称
     * @param int $value 仓库区域类型编码
     * @author Jimmy
     * @date 2017-11-30 15:19:11
     */
    public function getWarehouseAreaTypeAttr($value)
    {
        $type = [11 => '拣货区', 12 => '快速发货区', 21 => '不良品区'];
        return $type[$value];
    }

    /**
     * @desc 根据仓库货位ID获取仓库货位名称
     * @param int $value 仓库货位ID
     * @return 仓库货位名称
     * @author Jimmy
     * @date 2017-11-30 15:21:11
     */
    public function getWarehouseCargoIdAttr($value)
    {
        return WarehouseCargoService::getWarehouseCargoCodeById($value);
    }

    /**
     * @desc 根据sku_id获取sku名称
     * @param int $value sku_id
     * @author Jimmy
     * @date 2017-11-30 15:22:11
     */
    public function getSkuNameAttr($value, $data)
    {
        $res = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return param($res, 'name') ?: $res['spu_name'];
    }

    /**
     * @desc 根据sku_id获取sku名称
     * @param int $value sku_id
     * @author Jimmy
     * @date 2017-11-30 15:22:11
     */
    public function getSkuImgAttr($value, $data)
    {
        $res = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return param($res, 'thumb', '');
    }

    /**
     * @desc 查询获取器，字段status
     * @param int $value 需要查询的值
     * @return 返回对应的中文
     * @author Jimmy
     * @date 2017-11-23 14:01:11
     */
    public function getStatusAttr($value)
    {
        $status = [0 => '待上架', 1 => '上架中', 2 => '已上架'];
        return $status[$value];
    }

    /**
     * @desc 根据用户id获取用户名称
     * @param int $value 用户ID
     * @return string 用户名称
     * @author Jimmy
     * @date 2017-11-30 15:27:11
     */
    public function getCreatorIdAttr($value)
    {
        return Common::getNameByUserId($value);
    }

    /**
     * @desc 根据用户id获取用户名称
     * @param int $value 用户ID
     * @return string 用户名称
     * @author Jimmy
     * @date 2017-11-30 15:28:11
     */
    public function getUpdateIdAttr($value)
    {
        return Common::getNameByUserId($value);
    }

    /**
     * @desc 根据sku_id获取sku别名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return SKU 别名数组
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-18 16:00:11
     */
    public function getSkuAliaAttr($value, $data)
    {
        $res = GoodsSkuAlias::getAliasBySkuId($data['sku_id']);
        return $res;
    }

}
