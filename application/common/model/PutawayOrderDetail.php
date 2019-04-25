<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\warehouse\service\WarehouseCargo as WarehouseCargoService;

/**
 * @desc 上架单
 * @author Jimmy
 * @date 2017-11-22 16:11:11
 */
class PutawayOrderDetail extends Model
{

    protected $autoWriteTimestamp = true;
    protected $createTime = false;

    /**
     * @desc 根据仓库货位ID获取仓库货位名称
     * @param int $value 仓库货位ID
     * @return 仓库货位名称
     * @author Jimmy
     * @date 2017-11-23 17:21:11
     */
    public function getWarehouseCargoIdAttr($value)
    {
        return WarehouseCargoService::getWarehouseCargoCodeById($value);
    }

    /**
     * @desc 根据sku_id获取sku名称
     * @param int $value sku_id
     * @author Jimmy
     * @date 2017-11-23 17:42:11
     */
    public function getSkuNameAttr($value,$data)
    {
        $res = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return param($res, 'name') ?: $res['spu_name'];
    }

}
