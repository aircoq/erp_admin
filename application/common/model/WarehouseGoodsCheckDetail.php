<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\goods\service\GoodsSkuAlias;

/**
 * @desc 盘点单列表
 * @author Jimmy
 * @date 2017-12-08 17:55:11
 */
class WarehouseGoodsCheckDetail extends Model
{

    protected $autoWriteTimestamp = true;
    protected $createTime = false;

    /**
     * @desc 获取数据库表字段
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-08 17:59:11
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

    /**
     * @desc 根据状态编码获取状态名称
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 状态名称
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-14 16:19:11
     */
    public function getStatusNameAttr($value, $data)
    {
        $status = ['0' => '初始', '2' => '盘点中', '3' => '重盘', '4' => '盘盈', '6' => '盘亏', '8' => '正常'];
        return $status[$data['status']];
    }

    /**
     * @desc 根据盘点人ID获取盘点人姓名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 用户名
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-14 19:23:11
     */
    public function getCheckerNameAttr($value, $data)
    {
        $res = Cache::store('User')->getOneUser($data['checker_id']);
        return $res['realname']??"";
    }

    /**
     * @desc 根据sku_id获取sku别名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return SKU 别名数组
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-15 11:02:11
     */
    public function getSkuAliaAttr($value, $data)
    {
        $res = GoodsSkuAlias::getAliasBySkuId($data['sku_id']);
        return $res;
    }

}
