<?php

/**
 * Created by PhpStorm.
 * User: yangweiquan
 * Date: 2017-06-10
 * Time: 19:56
 */

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\common\model\PurchaseParcels;
use app\goods\service\GoodsSkuAlias;

class PurchaseParcelsRecords extends Model
{

    protected $autoWriteTimestamp = true;

    /**
     * @desc 获取数据库表字段
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-22 15:25:11
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

    /**
     * @desc 根据sku_id获取sku图片
     * @param int $value sku_id
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-22 15:56:11
     */
    public function getSkuAliasAttr($value, $data)
    {
        return GoodsSkuAlias::getAliasBySkuId($data['sku_id']);
    }

    /**
     * @desc 根据sku_id获取sku别名
     * @param int $value sku_id
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-22 15:56:11
     */
    public function getSkuImgAttr($value, $data)
    {
        $res = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return param($res, 'thumb', '');
    }

    /**
     * @desc 根据sku_id获取sku名称
     * @param int $value sku_id
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-22 17:09:11
     */
    public function getSkuNameAttr($value, $data)
    {
        $res = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return param($res, 'name') ?: $res['spu_name'];
    }

    /**
     * @desc 一对一关系
     * @author Jimmy <554511322@qq.com>
     * @return obj 详表数据信息
     * @date 2018-01-08 19:57:11
     */
    public function parcels()
    {
        return $this->belongsTo(PurchaseParcels::class, 'purchase_parcel_id', 'id');
    }

}
