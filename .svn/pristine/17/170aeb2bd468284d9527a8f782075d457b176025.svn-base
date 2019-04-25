<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
/**
 * @desc 上架单
 * @author Jimmy
 * @date 2017-12-06 13:45:11
 */
class ReturnShelves extends Model
{

    protected $autoWriteTimestamp = true;

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
     * @desc 获取对应的条目信息
     * @author Jimmy <554511322@qq.com>
     * @date 2018-02-24 10:28:11
     */
    public function details()
    {
        return $this->hasMany(ReturnShelvesDetail::class, 'return_shelves_id', 'id')->order('sort asc');
    }

    /**
     * @desc 获取状态名称
     * @param type $value status值
     * @param type $data 本条数据
     * @return 状态名称
     * @author Jimmy <554511322@qq.com>
     * @date 2018-02-27 15:35:11
     */
    public function getStatusNameAttr($value, $data)
    {
        $status = ['0' => '作废', '1' => '待处理', '3' => '上架中', '5' => '已完成'];
        return $status[$data['status']];
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
}
