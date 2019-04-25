<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/20
 * Time: 18:36
 */

namespace app\index\service;

use app\common\cache\Cache;
use app\common\model\PublishInfringementVocabulary as vocabularyModel;

class PublishInfringementVocabulary
{
    const CACHE = 'cache:publish_infringement_vocabulary';          //redis缓存

    protected $model;

    public function __construct()
    {
        if (is_null($this->model)) {
            $this->model = new vocabularyModel();
        }
    }

    /**
     * @title 返回侵权词汇列表
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function vocabularylist()
    {
        $result = Cache::handler()->get(self::CACHE);
        if (!$result) {
            $result = $this->model->field('id,code,warning')->select();
            Cache::handler()->set(self::CACHE, json_encode($result));
        }

        return $result;
    }
}