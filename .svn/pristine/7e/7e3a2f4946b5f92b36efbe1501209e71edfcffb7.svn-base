<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/20
 * Time: 18:35
 */

namespace app\index\controller;

use app\common\controller\Base;
use app\index\service\PublishInfringementVocabulary as vocabularySerivce;


/**
 * @module 侵权词汇
 * @title 侵权词汇管理
 * @url /infringement-vocabulary
 * @author zhuda
 * @package app\index\controller
 */
class PublishInfringementVocabulary extends Base
{
    protected $service;


    public function __construct()
    {
        parent::__construct();
        if (is_null($this->service)) {
            $this->service = new vocabularySerivce();
        }
    }

    /**
     * @title 侵权词汇列表
     * @method GET
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $result = $this->service->vocabularylist();
        return json(json_decode($result), 200);
    }


}