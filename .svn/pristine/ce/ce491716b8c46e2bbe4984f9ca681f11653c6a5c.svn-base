<?php

namespace app\common\model;

use think\Model;
use erp\ErpModel;
use app\common\cache\Cache;
use app\common\model\PurchaseReturnDetailManagement ;
use app\common\traits\ModelFilter;

/**
 * @desc 采购退货管理
 * @author lanshushu
 * @date 2019-03-13 17:01:11
 */
class PurchaseReturnManagement extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = '';

    /**
     * @desc 获取数据库表字段
     * @author lan
     * @date 2019-03-13 17:01:11
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

    /**
     * @desc 一对多关联查询详表数据信息
     * @author lan <554511322@qq.com>
     * @return obj 详表数据信息
     * @date  2019-1-4 17:02:11
     */
    public function details()
    {
        return $this->hasMany(DefectiveGoodsDeclareDetailModel::class, 'defective_goods_declare_id', 'id');
    }

    /**
     * @desc 根据仓库ID获取仓库编码
     * @param int $value 空值
     * @param array $data 本条数据
     * @return warehouse_code 仓库编码
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-11 11:02:11
     */
    public function getWarehouseCodeAttr($value, $data)
    {
        return Cache::store('warehouse')->getWarehouseNameById($data['warehouse_id']);
    }

    /**
     * @desc 根据处理人ID处理人姓名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 用户名
     * @author lan <554511322@qq.com>
     * @date 2019-1-4 14:45:11
     */
    public function getOperatorNameAttr($value, $data)
    {
        if(empty($data['operator_id'])) {
            $realname='-';
        }else{
            $realname = Cache::store('User')->getOneUserRealname($data['operator_id']);
        }
        return $realname;
    }

    /**
     * @desc 根据状态编码获取状态名称
     * @param int $value 空值
     * @param array $data 本条数据
     * @return status 状态名称
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-12 14:45:11
     */
    public function getStatusNameAttr($value,$data)
    {
        $status = ['0' => '待处理', '1' => '已处理','2' => '作废'];
        return $status[$data['status']];
    }
    /**
     * @desc 根据支付方式
     * @param int $value 空值
     * @param array $data 本条数据
     * @return status 状态名称
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-12 14:45:11
     */
    public function getPayNameAttr($value,$data)
    {
        $status = ['0' => '到付', '1' => '垫付'];
        return $status[$data['pay_type']];
    }


}
