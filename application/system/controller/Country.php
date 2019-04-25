<?php
namespace app\system\controller;

use think\Request;
use app\common\controller\Base;
use app\common\cache\Cache;
use app\common\model\Country as CountryModel;

/**
 * @title 国家及地区管理
 * @module 系统设置
 * @author ZhaiBin
 * @package app\goods\controller
 */
class Country extends Base
{
    /**
     * @title 国家列表
     * @url /country
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $params  = $request->param();
        $wheres = [];
        if (isset($params['zone_code']) && $params['zone_code'] != 'all') {
            $wheres['zone_code'] = $params['zone_code'];
        }
        $params = array_map('trim', $params);
        if (!empty($params['snText']) && strpos($params['snText'], ' ') === false) {
            $wheres['country_cn_name|country_en_name|country_code'] = ['like', trim($params['snText']) . '%'];
        }
        $results = CountryModel::where($wheres)->field('zone_code,country_code,country_en_name,country_cn_name,country_alias1')->page(1, 300)->select();
        if (!empty($params['snText']) && strpos($params['snText'], ' ') !== false) {
            $names = explode(' ', $params['snText']);
            $names = array_map('trim', $names);
            foreach($results as $k => $result) {
                if (!in_array($result['country_cn_name'], $names) && !in_array($result['country_code'], array_map('strtoupper',$names))) {
                    unset($results[$k]);
                }
            }
        }
        //$lists = [];
        /*foreach($results as $list) {
            $lists[] = $list;
            $listOther = $list->toArray();
            if ($list['country_alias1']) {
                $listOther['country_code'] = $listOther['country_alias1'];
                $listOther['country_cn_name'] .= '(' . $listOther['country_code'] . ')';
                $listOther['country_en_name'] .= '(' . $listOther['country_code'] . ')';
                $lists[] = $listOther;
            }
        }*/
        return json(array_values($results), 200);
    }

    /**
     * @title 分区国家
     * @url country/lists
     * @return \think\Response
     */
    public function lists(Request $request)
    {
        $params  = $request->param();
        $wheres = [];
        if (isset($params['zone_code']) && $params['zone_code'] != 'all') {
            $wheres['zone_code'] = $params['zone_code'];
        }
        $params = array_map('trim', $params);
//        if (!empty($params['snText']) && (strpos($params['snText'], ' ') === false) {
        if (!empty($params['snText']) && preg_match('/(\r\n)|\r|\n| /', $params['snText']) === false) {
            $wheres['country_cn_name|country_en_name|country_code'] = ['like', trim($params['snText']) . '%'];
        }
        $no_match = [];
        $results = CountryModel::where($wheres)->field('zone_code,country_code,country_en_name,country_cn_name,country_alias1')->page(1, 300)->select();
        if (!empty($params['snText']) && preg_match('/(\r\n)|\r|\n| /', $params['snText']) !== false) {
            $names = preg_split('/(\r\n)|\r|\n| /', $params['snText']);
            $names = array_map('trim', $names);
            $match = [];
            foreach($results as $k => $result) {
                if (!in_array($result['country_cn_name'], $names) && !in_array($result['country_code'], array_map('strtoupper',$names))) {
                    unset($results[$k]);
                    continue;
                }
                $match[] = in_array($result['country_cn_name'], $names) ? $result['country_cn_name']:$result['country_code'];
            }
            foreach ($names as $item) {
                if(!in_array($item, $match) && !in_array(strtoupper($item), $match)){
                    $no_match[] =$item;
                }
            }
        }
        if (empty($results) && !empty($params['snText']) && strpos($params['snText'], ' ') === false) {
            $no_match[] = $params['snText'];
        }
        return json(['data'=>array_values($results), 'no_match'=>$no_match], 200);
    }
    
    /**
     * @title 显示地区列表
     * @method get
     * @url /zone
     * @return \think\Response
     */
    public function zone()
    {
        $lists = Cache::store('country')->getZone();
        array_unshift($lists, ['zone_code' => 'all', 'zone_cn_name' => '全部', 'zone_en_name' => 'ALL']);
        return json(array_values($lists), 200);
    }
}