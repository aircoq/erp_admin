<?php

namespace app\report\service;

use app\common\model\ShippingPriceLog;
use app\common\model\User;
use app\warehouse\service\ShippingMethod;
use think\Db;
use think\Exception;
use app\common\cache\Cache;


/**
 * Created by PhpStorm.
 * User: laiyongfeng
 * Date: 2019/04/09
 * Time: 14:17
 */
class ShippingPrice
{
    protected $model = null;
    protected $where = [];

    public function __construct()
    {
        if (is_null($this->model)) {
            $this->model = new ShippingPriceLog();
        }
    }

    /**
     * @desc 查询条件
     * @param array $params
     */
    public function where($params)
    {
        if (param($params, 'shipping_id')) {
            $this->where['shipping_id'] = $params['shipping_id'];
        }

        $date_from = param($params, 'date_from');
        $date_to = param($params, 'date_to');
        if ($date_from || $date_to) {
            $start_time = $date_from ? strtotime($params['date_from']) : 0;
            $end_time = $date_to ? strtotime($params['date_to'])+86400 : 0;
            if ($start_time && $end_time) {
                $this->where['create_time'] = [['>=', $start_time], ['<=', $end_time]];
            } else {
                if ($start_time) {
                    $this->where['create_time'] = ['>=', $start_time];
                }
                if ($end_time) {
                    $this->where['create_time'] = ['<=', $end_time];
                }
            }
        }
    }


    /**
     * @desc 获取总数
     */
    public function getCount()
    {
        return $this->model->where($this->where)->count();

    }

    /**
     * @desc 获取超库龄列表
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getLists($page = 1, $pageSize = 20)
    {
        $lists = $this->model->where($this->where)->page($page, $pageSize)->order('id desc')->select();
        $ShippingMethod = new ShippingMethod();
        foreach ($lists as &$item) {
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['creator'] = cache::store('user')->getOneUserRealname($item['create_id']);
            $item['shipping_name'] = $ShippingMethod->getFullShippingName($item['shipping_id']);
        }
        return $lists;
    }

}