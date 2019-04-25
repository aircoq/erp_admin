<?php
namespace app\index\controller;

use app\common\controller\Base;
use think\Request;
use think\Db;

/**
 * @module 基础设置
 * @title 国内快递
 * @author tanbin
 * @url /express
 * Created by tanbin.
 */
class Express extends Base
{
    /**
     * @title 读取国内快递信息
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function index(Request $request)
    {
        $result = Db::name('express')->select();
        return json($result, 200);
    }
}