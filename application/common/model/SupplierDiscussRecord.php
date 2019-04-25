<?php
/**
 * Created by PhpStorm.
 * User: hjt
 * Date: 2018/12/19
 * Time: 12:02
 */

namespace app\common\model;


use think\Model;

class SupplierDiscussRecord extends Model
{
    public function getDiscussWay()
    {
        $discuss_way = [
            0 => [
                'label' => 1,
                'name' => '电话'
            ],
            1 => [
                'label' => 2,
                'name' => '微信'
            ],
            2 => [
                'label' => 3,
                'name' => '旺旺'
            ],
            3 => [
                'label' => 4,
                'name' => '面谈'
            ],
            4 => [
                'label' => 5,
                'name' => 'QQ'
            ],
            5 => [
                'label' => 6,
                'name' => '其他'
            ],
        ];
        return $discuss_way;
    }

    public function getMakeProject()
    {
        $make_project = [
            0 => [
                'label' => 1,
                'name' => '账期'
            ],
            1 => [
                'label' => 2,
                'name' => '贴标/套袋'
            ],
            2 => [
                'label' => 3,
                'name' => '发票'
            ],
            3 => [
                'label' => 4,
                'name' => '降价'
            ],
            4 => [
                'label' => 5,
                'name' => '参观公司或工厂规模'
            ],
            5 => [
                'label' => 6,
                'name' => 'SUP种类及数量'
            ],
            6 => [
                'label' => 7,
                'name' => '合并SKU'
            ],
            7 => [
                'label' => 8,
                'name' => '发展外围供应链'
            ],
            8 => [
                'label' => 9,
                'name' => '其他'
            ],
        ];
        return $make_project;
    }
}