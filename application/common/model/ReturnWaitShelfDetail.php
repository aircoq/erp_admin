<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
/**
 * @desc 退回待上架包裹详情
 * @author libaimin
 * @date 2018-11-30 13:45:11
 */
class ReturnWaitShelfDetail extends Model
{


    /**
     * @desc 获取数据库表字段
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

    /**
     * @desc 获取对应的条目信息
     */
    public function details()
    {
        return $this->hasMany(ReturnShelvesDetail::class, 'return_shelves_id', 'id')->order('sort asc');
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

    public function isHas($packageNumber)
    {
        $where['package_number'] = $packageNumber;
        return $this->where($where)->find();
    }
}
