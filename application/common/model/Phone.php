<?php


namespace app\common\model;


use think\Model;
use app\common\cache\Cache;

class Phone extends Model
{
    const OPERATOR_CMCC = 2;
    const OPERATOR_CUCC = 3;
    const OPERATOR_CTCC = 1;

    const OPERATOR_TXT = [
        self::OPERATOR_CTCC => '中国电信',
        self::OPERATOR_CUCC => '中国联通',
        self::OPERATOR_CMCC => '中国移动',
    ];

    const STATUS_ENABLE = 1;//启用
    const STATUS_DISABLE = 0;//金庸

    public function getOperatorTxtAttr($value, $data)
    {
        return self::OPERATOR_TXT[$data['operator']] ?? '';
    }

    public function getRegTxtAttr($value, $data)
    {
        return Cache::store('user')->getOneUserRealname($data['reg_id']);
    }

    public function getRegTimeTxtAttr($value, $data)
    {
        return date('Y-m-d', $data['reg_time']);
    }




}