<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\warehouse\service\WarehouseCargo as WarehouseCargoService;
use app\warehouse\service\WarehouseCargoGoods as WarehouseCargoGoodsService;
use app\goods\service\GoodsSkuAlias;
/**
 * Created by NetBeans.
 * User: Leslie
 * Date: 2017/02/06
 * Time: 17:18
 */
class WarehouseCargoGoods extends Model
{

    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /**
     * @desc 获取数据库表字段
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-21 15:01:11
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

    /**
     * @desc 根据sku_id获取sku图片
     * @param int $value 空值
     * @param array $data 本条数据
     * @return SKU 图片地址
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-21 15:02:11
     */
    public function getSkuImgAttr($value, $data)
    {
        $res = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return param($res, 'thumb', '');
    }

    /**
     * @desc 根据sku_id获取sku名称
     * @param int $value 空值
     * @param array $data 本条数据
     * @return SKU 名称
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-21 15:03:11
     */
    public function getSkuNameAttr($value, $data)
    {
        $res = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return param($res, 'name') ?: $res['spu_name'];
    }

    /**
     * @desc 根据仓库id区域类型获取拣货区的货位编码
     * @param int $value 仓库货位ID
     * @return 仓库货位名称
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-27 20:15:11
     */
    public function getWarehouseCargoCodeAttr($value, $data)
    {
        return WarehouseCargoService::getWarehouseCargoCodeById($data['warehouse_cargo_id']);
    }
    /**
     * @desc 获取拣货区货位编码
     * @param type $value
     * @param array $data 本条数据
     * @return string 货位编码
     * @author Jimmy <554511322@qq.com>
     * @date 2018-04-25 11:05:11
     */
    public function getWarehouseCargoCode11Attr($value, $data)
    {
        return (new WarehouseCargoGoodsService())->getSkuCargoCode($data['warehouse_id'], $data['sku_id']);
    }

    /**
     * @desc 根据sku_id获取sku别名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return SKU 别名数组
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-15 10:55:11
     */
    public function getSkuAliaAttr($value, $data)
    {
        $res = GoodsSkuAlias::getAliasBySkuId($data['sku_id']);
        return $res;
    }

}
