<?php

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;

/**
 * @desc 1688 账号类
 * @author Jimmy <554511322@qq.com>
 * @date 2018-01-19 10:16:11
 */
class Ali1688Account extends Model
{

    protected $autoWriteTimestamp = true;

    /**
     * @desc 获取数据库表字段
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-19 10:18:11
     */
    public function getFields()
    {
        return $this->getTableFields(['table' => $this->table]);
    }

    /**
     * @desc 初始化函数,注册回调事件
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-18 16:54:11
     */
    public static function init()
    {
        try {
            $old = null; //修改之前的数据
            //注册新增和修改之前的事件
            self::beforeWrite(function ($data) {
                global $old;
                //修改之前的数据
                $old = isset($data->id) ? self::get($data->id) : null;
            });
            //注册新增和修改之后的事件
            self::afterWrite(function ($data) {
                global $old;
                $cache = Cache::store('Ali1688Account');
                $old ? $cache->delData($old) : '';
                $cache->addData($data);
            });
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

}
