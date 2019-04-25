<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use app\common\model\WarehouseGoodsCheckDetail;

/**
 * @desc 盘点单列表
 * @author Jimmy
 * @date 2017-12-08 17:01:11
 */
class WarehouseGoodsCheck extends Model
{

    protected $autoWriteTimestamp = true;

    /**
     * @desc 获取数据库表字段
     * @author Jimmy
     * @date 2017-12-08 17:02:11
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

    /**
     * @desc 一对多关联查询详表数据信息
     * @author Jimmy <554511322@qq.com>
     * @return obj 详表数据信息
     * @date 2017-11-23 14:46:11
     */
    public function details()
    {
        return $this->hasMany(WarehouseGoodsCheckDetail::class, 'warehouse_goods_check_id', 'id');
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
     * @desc 根据盘点人ID获取盘点人姓名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 用户名
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-12 14:45:11
     */
    public function getCheckerNameAttr($value, $data)
    {
        $res = Cache::store('User')->getOneUser($data['checker_id']);
        return $res['realname']??'';
    }
    /**
     * @desc 根据创建人ID获取创建人姓名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 用户名
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-12 14:45:11
     */
    public function getCreatorNameAttr($value, $data)
    {
        $res = Cache::store('User')->getOneUser($data['creator_id']);
        return $res['realname']??"";
    }

    /**
     * @desc 根据完成人ID获取完成人姓名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 用户名
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-12 14:45:11
     */
    public function getUpdateNameAttr($value, $data)
    {
        $res = Cache::store('User')->getOneUser($data['update_id']);
        return $res['realname']??'';
    }

    /**
     * @desc 根据状态编码获取状态名称
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 状态名称
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-12 14:45:11
     */
    public function getStatusNameAttr($value,$data)
    {
        $status = ['0' => '待处理', '1' => '盘点中', '2' => '待审核', '3' => '盘点完成','4'=>'作废'];
        return $status[$data['status']];
    }
    /**
     * @desc 根据状态编码获取状态名称
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 状态名称
     * @author Jimmy <554511322@qq.com>
     * @date 2017-12-12 14:45:11
     */
    public function getTypeNameAttr($value,$data)
    {
        $status = ['0' => '动态', '1' => '静态', '2'=>'拣货异常'];
        return $status[$data['type']] ?? '';
    }

    /**
     * @desc 根据完成人ID获取完成人姓名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return string 用户名
     */
    public function getValidAuditorAttr($value, $data)
    {
        $valid_auditor = [];
        $valid_auditor_ids = explode(',', $data['valid_auditor_ids']);
        foreach ($valid_auditor_ids as $user_id) {
            $res = Cache::store('User')->getOneUser($user_id);
            $valid_auditor[] = $res['realname'] ?? '';
        }
        return implode(',', array_diff($valid_auditor, ['']));
    }
}
