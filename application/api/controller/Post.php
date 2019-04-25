<?php
namespace app\api\controller;

use app\api\help\ApiHelp;

/**
 * @title api对外接口
 * @author phill
 * @url /api
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/7
 * Time: 19:12
 */
class Post
{
    /**
     * @title 默认访问页面
     * @url post
     * @method post
     */
    public function index()
    {
        $api = new ApiHelp();
        $api->init('POST');
    }
}