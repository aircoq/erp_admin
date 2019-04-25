<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/17
 * Time: 18:35
 */

namespace app\common\model\shopee;


use erp\ErpModel;

class ShopeeReturnDetail extends ErpModel
{
    protected function initialize()
    {
        parent::initialize();
//        $this->query('set names utf8mb4');
    }

    public function returns()
    {
        return self::belongsTo(ShopeeReturn::class,'return_id','id');
    }

}