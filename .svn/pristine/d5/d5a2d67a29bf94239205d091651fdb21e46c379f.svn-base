<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2017/12/26
 * Time: 10:11
 */

namespace app\publish\controller;


use app\common\controller\Base;
use app\publish\service\EbayCategorySearch as EbayCategorySearchhelp;

/**
 * @module 刊登系统
 * @title Ebay刊登-关键字分类搜索
 * @author zhangdongdong
 */
class EbayCategorySearch extends Base
{

    /**
     * @title 关键字分类搜索
     * @url /ebay-category-search
     * @method POST
     * @apiParam keyword:搜索关键字 site:(int)站点
     * @Return \think\Response
     */
    public function index()
    {
        $param = request()->post();
        $result = $this->validate($param, [
            'keyword|搜索关键字' => 'require|length:1,100',
            'site|站点' => 'number',
        ]);

        if ($result !== true){
            return json(['message' => $result], 400);
        }
        $param['site'] = empty($param['site'])? 0 : $param['site'];
        $help = new  EbayCategorySearchhelp();
        $list = $help->query($param['keyword'], $param['site']);
        return json(['list' => $list]);
    }
}