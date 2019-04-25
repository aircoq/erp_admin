<?php

namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/11/21
 * Time: 17:46
 */
class AccountCompany extends Model
{

    //类型 0-企业 1-个人
    const type_company = 0;
    const type_personage = 1;

    const TYPE = [
        self::type_company => '企业',
        self::type_personage => '个人',
    ];

    //资料来源 0-未知 1-A1 2-B1 3-C1 4-D1
    const source_unknown = 0;
    const source_A1 = 1;
    const source_B1 = 2;
    const source_C1 = 3;
    const source_D1 = 4;

    const SOURCE = [
        self::source_unknown => '未知',
        self::source_A1 => 'A1',
        self::source_B1 => 'B1',
        self::source_C1 => 'C1',
        self::source_D1 => 'D1',
    ];

    
    /**
     * @param $channel_id
     * @param $account_id
     * @param $server_id
     * @return array|bool|false|\PDOStatement|string|Model
     */
    public function isHas($where)
    {
        $result = $this->where($where)->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return $result;
    }

    public function getStatusTxtAttr($value, $data)
    {
        $map = [
            '0' => '正常',
            '1' => '异常',
            '2' => '注销',
        ];
        return $map[$data['status']] ?? '';
    }

    public function setIdDateStAttr($value)
    {
        return strtotime($value);
    }

    public function setIdDateNdAttr($value)
    {
        return strtotime($value);
    }

    public function setBusinessTermStAttr($value)
    {
        return strtotime($value);
    }

    public function setBusinessTermNdAttr($value)
    {
        return strtotime($value);
    }

    public function setOpenDateAttr($value)
    {
        return strtotime($value);
    }

}