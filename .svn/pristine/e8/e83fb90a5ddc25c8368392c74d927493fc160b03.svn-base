<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/5/14
 * Time: 14:22
 */

namespace app\common\model\ebay;


use think\Model;

class EbayEmailList extends Model
{

    public function initialize() {

    }

    public function content()
    {
        return $this->hasOne('EbayEmailContent','list_id','id')->bind(['body'=>'content']);
    }

    /**
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusTextAttr($value,$data)
    {
        switch ($this->status){
            case 0:
                return '待发送';
            case 1:
                return '已发送';
            case 2:
                return '发送失败';
        }
        return '未知状态';

    }
}