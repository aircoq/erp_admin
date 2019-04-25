<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2017/12/27
 * Time: 15:14
 */

namespace app\publish\service;

use app\common\model\JoomAttribute;
use app\common\model\JoomAttributeValue;
use \QL\QueryList;

/**
 * Class JoomReptitle 爬joomAPI 颜色衙size规格的爬虫，已排重，需要的时候可以重复执行更新
 * @package app\publish\service
 */
class JoomReptitle
{
    public $joom_attribute = null;

    public $joom_attribute_value = null;

    public $url = 'https://merchant.joom.com/documentation/api/v2';

    public function __construct() {

    }

    public function getColor() {
        $time = time();
        $ql = QueryList::getInstance();
        $ql = $ql->get($this->url);

        $aModel = new JoomAttribute();
        $avModel = new JoomAttributeValue();

        $name = trim($ql->find('#product-colors>h3')->text());
        $attr = $aModel->where(['name' => $name])->find();
        $attr_id = 0;
        if(!empty($attr)) {
            $attr_id = $attr->id;
        } else {
            $aModel->isUpdate(false)->save([
                'name' => $name,
                'field' => 'color',
                'create_time' => $time
            ]);
            $attr_id = $aModel->id;
        }

        $list = $ql->find('#product-colors>p>span.F')->map(function($item){
            $code = $item->text();
            $value = $item->children('span')->text();
            return [
                'code' => trim(str_replace($value, '', $code)),
                'value' => trim($value),
            ];
        })->toArray();

        if (empty($list)) {
            return [];
        }
        $codeArr = array_column($list, 'code');
        $oldList = $avModel->where([
            'joom_attribute_id' => $attr_id,
            'code' => ['in', $codeArr],
        ])->column('id,value', 'code');

        foreach($list as $data) {
            if(isset($oldList[$data['code']])) {
                if($oldList[$data['code']]['value'] != $data['value']) {
                    $avModel->update($data, ['id' =>$oldList[$data['code']]['id']]);
                }
            } else {
                $data['create_time'] = $time;
                $data['joom_attribute_id'] = $attr_id;
                $avModel->insert($data);
            }
        }
        return $list;
    }

    public function getSize() {
        $time = time();
        $ql = QueryList::getInstance();
        $ql = $ql->get($this->url);

        $aModel = new JoomAttribute();
        $avModel = new JoomAttributeValue();

        $list = $ql->find('#product-sizing-charts>table')->map(function($item) {
            $name = $item->children('caption')->text();

            $table = [];
            $dsclist = $item->find('tr>th')->texts()->toarray();

            $list = $item->find('tbody>tr')->map(function($tr){
                return $tr->children('td')->texts()->toArray();
            })->toarray();

            if(isset($list[0]) && count($list[0]) == (count($dsclist) * 2 -1)) {
                foreach($list as $li) {
                    foreach($dsclist as $key=>$dsc) {
                        if($key == 0) continue;
                        $data['code'] = $li[0];
                        $data['dsc'] = $dsc;
                        $data['value'] =  $li[$key * 2 - 1]. ','. $li[$key * 2];
                        array_push($table, $data);
                    }
                }
            } else {
                foreach($list as $li) {
                    foreach($dsclist as $key=>$dsc) {
                        if($key == 0) continue;
                        $data['code'] = $li[0];
                        $data['dsc'] = $dsc;
                        $data['value'] =  $li[$key];
                        array_push($table, $data);
                    }
                }
            }

            return ['name' => $name, 'table' => $table];
        })->toArray();

        foreach($list as $val) {
            $attr_id = 0;
            $attr = $aModel->where(['name' => $val['name']])->find();
            if(!empty($attr)) {
                $attr_id = $attr->id;
            } else {
                $attr_id = $aModel->insertGetId([
                    'name' => $val['name'],
                    'field' => 'size',
                    'create_time' => $time
                ]);
            }

            if (empty($val['table'])) {
                continue;
            }
            $codeArr = array_column($val['table'], 'code');
            $oldList = $avModel->where([
                'joom_attribute_id' => $attr_id,
                'code' => ['in', $codeArr],
            ])->column('id,value', 'code');

            foreach($val['table'] as $data) {
                if(isset($oldList[$data['code']])) {
                    if($oldList[$data['code']]['value'] != $data['value']) {
                        $avModel->update($data, ['id' =>$oldList[$data['code']]['id']]);
                    }
                } else {
                    $data['create_time'] = $time;
                    $data['joom_attribute_id'] = $attr_id;
                    $avModel->insert($data);
                }
            }
        }
        return $list;
    }
}