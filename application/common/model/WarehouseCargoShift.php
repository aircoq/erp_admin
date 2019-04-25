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
class WarehouseCargoShift extends Model
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
     * @desc 一对多关联查询详表数据信息
     * @author Jimmy
     * @return obj 详表数据信息
     * @date 2017-11-23 14:46:11
     */
    public function details()
    {
        return $this->hasMany(WarehouseCargoShiftDetail::class, 'warehouse_cargo_shift_id', 'id');
    }

    public function getValue($value)
    {
        return $this->data[$value];
    }
    /**
     * @desc 根据创建人ID获取创建人姓名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 用户名
     * @author Jimmy <554511322@qq.com>
     * @date 2018-02-27 17:01:11
     */
    public function getCreatorNameAttr($value, $data)
    {
        $res = Cache::store('User')->getOneUser($data['creator_id']);
        return $res['realname'];
    }
    /**
     * @desc 根据操作人ID获取创建人姓名
     * @param int $value 空值
     * @param array $data 本条数据
     * @return realname 用户名
     * @author Jimmy <554511322@qq.com>
     * @date 2018-02-27 17:01:11
     */
    public function getUpdateNameAttr($value,$data)
    {
        $res = Cache::store('User')->getOneUser($data['update_id']);
        return $res['updatename'];
    }


    /**
     * @desc 获取数据库表字段
     * @author
     * @date 2017-12-06 13:46:11
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

}