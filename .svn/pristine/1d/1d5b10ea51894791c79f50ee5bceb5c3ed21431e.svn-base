<?php


namespace app\common\model;


use think\Model;

class GoodsTortListing extends Model
{
    const STATUS_WAIT = 0;
    const STATUS_DONE = 1;
    const STATUS_FAIL = 2;
    const STATUS_TXT = [
        self::STATUS_WAIT=>'等待下架',
        self::STATUS_DONE=>'下架成功',
        self::STATUS_FAIL=>'下架失败'
    ];

    public function getStatusTxtAttr($value,$data){
        return self::STATUS_TXT[$data['status']];
    }
}