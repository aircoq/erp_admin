<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2017/12/26
 * Time: 10:11
 */

namespace app\publish\controller;


use app\common\controller\Base;
use app\publish\service\Wangxiaowang as Wangxiaowangchhelp;

/**
 * @module 刊登系统
 * @title 速卖通-旺销王查询
 * @author zhangdongdong
 */
class Wangxiaowang extends Base
{

    /**
     * @title 旺销王-热词搜索
     * @url /alihelp-hot
     * @method POST
     * @apiParam category:分类ID page:页码 lang:语言 sort:按搜索人气排序(down||up)
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function hotQuery()
    {
        $param = request()->post();
        $result = $this->validate($param, [
            'category|分类ID' => 'require|number',
            'page|页码' => 'number',
        ]);

        if ($result !== true){
            return json(['message' => $result], 400);
        }
        $page = empty($param['page'])? 1 : $param['page'];

        $lang = empty($param['lang'])? 'en' : $param['lang'];
        if(!in_array($lang, ['en', 'ru'])) {
            return json(['message' => '未知lang数值'], 400);
        }
        $sort = empty($param['sort'])? 'down' : $param['sort'];
        if(!in_array($sort, ['down', 'up'])) {
            return json(['message' => '未知sort排序数值'], 400);
        }

        $help = new  Wangxiaowangchhelp();
        $list = $help->hotQuery($param['category'], $page, $lang, $sort);
        return json($list, 200);
    }


    /**
     * @title 旺销王-热词语言选项
     * @url /alihelp-hotlang
     * @method GET
     * @return \think\response\Json
     */
    public function hotlangList() {
        return json([
            'en' => '英语',
            'ru' => '俄语',
//            'sp' => '西班牙语',
//            'pt' => '葡萄牙语',
//            'fa' => '法语',
//            'it' => '意大刘语',
//            'de' => '德语',
//            'nl' => '荷兰语',
//            'kr' => '韩语',
//            'jp' => '日语',
        ], 200);
    }

    /**
     * @title 旺销王-直通车搜索
     * @url /alihelp-bcar
     * @method POST
     * @apiParam category:分类ID page:页码 sort:按热搜度排序(down||up)
     * @return \think\response\Json
     */
    public function bcarQuery()
    {
        $param = request()->post();
        $result = $this->validate($param, [
            'category|分类ID' => 'require|number',
            'page|页码' => 'number',
        ]);

        if ($result !== true){
            return json(['message' => $result], 400);
        }
        $page = empty($param['page'])? 1 : $param['page'];
        $sort = empty($param['sort'])? 'down' : $param['sort'];
        if(!in_array($sort, ['down', 'up'])) {
            return json(['message' => '未知sort排序数值'], 400);
        }
        $help = new  Wangxiaowangchhelp();
        $list = $help->bcarQuery($param['category'], $page, $sort);
        return json($list, 200);
    }
}