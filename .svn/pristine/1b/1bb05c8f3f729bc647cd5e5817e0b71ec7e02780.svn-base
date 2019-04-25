<?php

namespace app\common\model;

use think\Model;
use erp\ErpModel;
use app\common\cache\Cache;
use app\common\model\DefectiveGoodsDeclareDetail as DefectiveGoodsDeclareDetailModel;
use app\common\traits\ModelFilter;

/**
 * @desc 次品申报列表
 * @author lanshushu
 * @date 2017-12-08 17:01:11
 */
class DefectiveGoodsDeclare extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'audit_time';

    /**
     * @desc 获取数据库表字段
     * @author lan
     * @date 2019-1-4 17:02:11
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
     * @desc 根据申报人ID申报人姓名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 用户名
     * @author lan <554511322@qq.com>
     * @date 2019-1-4 14:45:11
     */
    public function getCheckerNameAttr($value, $data)
    {
        if(empty($data['auditor_id'])) {
            $realname='-';
        }else{
            $realname = Cache::store('User')->getOneUserRealname($data['auditor_id']);
        }
        return $realname;
    }
    /**
     * @desc 根据创建人ID获取创建人姓名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 用户名
     * @author lan <554511322@qq.com>
     * @date 2017-12-12 14:45:11
     */
    public function getCreatorNameAttr($value, $data)
    {
        if(empty($data['creator_id'])) {
            $realname='-';
        }else{
            $realname = Cache::store('User')->getOneUserRealname($data['creator_id']);

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
        $status = ['0' => '待审核', '1' => '已审核', '2' => '审核不通过'];
        return $status[$data['status']];
    }
    /**
     * @desc 根据类型
     * @param int $value 空值
     * @param array $data 本条数据
     * @return declare_type 状态名称
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-12 14:45:11
     */
    public function getTypeNameAttr($value,$data)
    {
        $status = ['0' => '申报', '1' => '盘点', '2' => '退货'];
        return $status[$data['declare_type']];
    }

}
