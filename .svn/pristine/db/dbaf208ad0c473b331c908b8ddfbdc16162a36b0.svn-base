<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\goods\service\GoodsSkuAlias;

/**
 * @desc 退货上架单详情
 * @author Jimmy
 * @date 2017-12-06 13:45:11
 */
class RebackShelvesOrderDetail extends Model
{

    protected $autoWriteTimestamp = false;

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
     * @desc 根据sku_id获取sku名称
     * @param int $value 空值
     * @param array $data 本条数据
     * @return SKU 名称
     * @author Jimmy
     * @date 2017-11-23 17:42:11
     */
    public function getSkuNameAttr($value, $data)
    {
        $res = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return param($res, 'name') ?: param($res, 'spu_name');
    }

    /**
     * @desc 根据sku_id获取sku图片
     * @param int $value 空值
     * @param array $data 本条数据
     * @return SKU 图片地址
     * @author Jimmy
     * @date 2017-12-07 16:36:11
     */
    public function getSkuImgAttr($value, $data)
    {
        $res = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return param($res, 'thumb', '');
    }

    /**
     * @descc 查询获取器，字段status
     * @param int $value 需要查询的值
     * @param array $data 
     * @return 返回对应的中文
     * @author Jimmy
     * @date 2017-12-07 16:20:11
     */
    public function getWarehouseAreaCodeAttr($value, $data)
    {
        $type = [11 => '拣货区', 12 => '快速发货区', 21 => '不良品区'];
        return param($type, $data['warehouse_area_type']);
    }

    /**
     * @desc 根据sku_id获取sku别名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return SKU 别名数组
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-15 10:48:11
     */
    public function getSkuAliaAttr($value, $data)
    {
        $res = GoodsSkuAlias::getAliasBySkuId($data['sku_id']);
        return $res;
    }

}
