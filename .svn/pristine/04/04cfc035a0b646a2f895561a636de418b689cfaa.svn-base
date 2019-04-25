<?php

namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/1/17
 * Time: 17:46
 */
class BrowserCustomer extends Model
{
    /**
     * 服务器浏览器UA软件管理
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


    public static function getUA()
    {
        $model = (new BrowserCustomer());
        $info = $model->field('id,content')->order('update_time')->find();
        $model->save(['update_time' => time()], ['id' => $info['id']]);
        return $info['content'];

    }


}