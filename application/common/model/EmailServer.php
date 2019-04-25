<?php
namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/10/27
 * Time: 16:46
 */
class EmailServer extends Model
{
    /**
     * 基础账号信息
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 检测是否存在，如果存在返回改数据
     * @param $where
     * @return array|bool|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isHas($where)
    {

        $result = $this->where($where)->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return $result;
    }


}