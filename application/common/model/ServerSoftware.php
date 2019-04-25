<?php
namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/12/8
 * Time: 17:46
 */
class ServerSoftware extends Model
{
    /**
     * 服务器软件管理
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
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