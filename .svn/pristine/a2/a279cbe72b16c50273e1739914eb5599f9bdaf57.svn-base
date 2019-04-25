<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2017/12/27
 * Time: 15:14
 */

namespace app\publish\service;

use app\common\model\joom\JoomAttribute;
use app\common\model\joom\JoomAttributeValue;
use \QL\QueryList;

/**
 * Class JoomReptitle 爬joomAPI 颜色衙size规格的爬虫，已排重，需要的时候可以重复执行更新
 * @package app\publish\service
 */
class JoomAttrHelp
{
    private $error = '未定义错误';

    public function __construct()
    {

    }

    public function getColorList($keyword)
    {
        $color = JoomAttribute::where(['field' => 'color'])->field('id')->find();
        if (empty($color)) {
            return [];
        }
        $where['joom_attribute_id'] = $color['id'];
        if (!empty($keyword)) {
            $where['code'] = ['like', '%' . $keyword . '%'];
        }
        $lists = JoomAttributeValue::where($where)->field('code value')->select();
        return array_values($lists);
    }

    public function getSizeList()
    {
        $arr = JoomAttribute::where(['field' => 'size'])->column('name,zh_name', 'id');
        if (empty($arr)) {
            return [];
        }
        $where['joom_attribute_id'] = ['in', array_keys($arr)];
        $lists = JoomAttributeValue::where($where)->group('code,joom_attribute_id')->field('code,joom_attribute_id')->select();
        $new_list = [];
        foreach ($arr as $key => $val) {
            $data['name'] = $val['name'];
            $data['zh_name'] = $val['zh_name'];
            $data['size_val'] = [];
            foreach ($lists as $v) {
                if ($key == $v['joom_attribute_id']) {
                    $data['size_val'][] = $v['code'];
                }
            }
            $new_list[] = $data;
        }
        return $new_list;
    }

    public function getError()
    {
        return $this->error;
    }
}