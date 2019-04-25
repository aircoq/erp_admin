<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/5/14
 * Time: 14:22
 */

namespace app\common\model\ebay;


use think\Model;

class EbayEmailContent extends Model
{

    public function initialize() {
        parent::initialize();
        $this->query('set names utf8mb4');
    }

}