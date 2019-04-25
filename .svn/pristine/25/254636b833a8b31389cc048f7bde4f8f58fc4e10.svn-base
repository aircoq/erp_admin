<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/29
 * Time: 10:00
 */

namespace app\goods\service;

use app\common\model\GoodsDiscountLog as Model;
use app\index\service\BaseLog;

class GoodsDiscountLog extends BaseLog
{
    public function __construct()
    {
        $this->model = new Model();
    }

    protected $tableField = [
        'id' => 'discount_id',
        'remark' => 'remark',
        'operator_id' => 'create_id',
        'operator' => 'type'
    ];

    public function add($name)
    {
        $list = [];
        $list['type'] = '审批';
        $list['val'] = $name;
        $list['data'] = [];
        $list['exec'] = 'submit';
        $this->LogData[] = $list;
        return $this;
    }

    public function mdf($name, $val = '' , $exec = '')
    {
        $list = [];
        $list['type'] = $name;
        $list['val'] = $val;
        $list['data'] = '';
        $list['exec'] = $exec;
        $this->LogData[] = $list;
        return $this;
    }
}