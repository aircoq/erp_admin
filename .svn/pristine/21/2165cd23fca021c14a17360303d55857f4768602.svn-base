<?php
namespace app\common\model;

use think\Model;
use app\common\cache\Cache;


/**
 * Created by NetBeans.
 * User: xueli
 * Date: 2018/12/11
 * Time: 17:18
 */
class WarehouseCargoShiftDetail extends Model
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
     * @desc 根据sku_id获取sku名称
     * @param int $value sku_id
     * @author xueli
     * @date 2018-12-14 12-14
     */
    public function getSkuNameAttr($value,$data)
    {
        $res = Cache::store('goods')->getSkuInfo($data['sku_id']);
        return param($res, 'name') ?: $res['spu_name'];
    }

}