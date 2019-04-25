<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/1/11
 * Time: 17:00
 */

namespace app\publish\controller;


use app\common\controller\Base;
use think\Request;
use app\publish\service\JoomTagSearchHelp;

/**
 * @module 刊登系统
 * @title Joom关键字标签
 * @author zhangdongdong
 * @url /joomtag-search
 * Class Joom
 * @package app\index\controller
 */
class JoomTagSearch extends Base
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    /**
     * @title Joom关键字标签搜索
     * @url /joomtag-search
     * @method GET
     * @apiParam keyword:搜索关键字
     * @Return \think\Response
     */
    public function index(Request $request)
    {
        $param = $request->get();
        $result = $this->validate($param, [
            'keyword|搜索关键字' => 'require|length:1,100',
        ]);

        if ($result !== true){
            return json(['message' => $result], 400);
        }

        $help = new  JoomTagSearchHelp();
        $list = $help->query($param['keyword']);
        if($list === false) {
            return json(['message' => $help->getError()], 400);
        }

        return json($list);
    }
}