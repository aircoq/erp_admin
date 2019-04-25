<?php


namespace app\common\model;

use app\common\cache\Cache;
use think\Model;
use app\order\service\OrderRuleExecuteService;
use app\common\model\Bank;
use app\common\model\City;
use app\common\model\Province;

class BankAccount extends Model
{

    const STATUS_WAIT = 0;
    const STATUS_ENABLE = 1;
    const STATUS_FORBIDDEN = 2;
    const STATUS_TXT = [
        self::STATUS_WAIT =>'待激活',
        self::STATUS_ENABLE =>'启用',
        self::STATUS_FORBIDDEN =>'停用'
    ];

    public function getLocalMoneyAttr($value, $data)
    {
        if ($data['currency_code'] != 'CNY') {
            $OrderRuleExecuteService = new OrderRuleExecuteService();
            return $OrderRuleExecuteService->convertCurrency($data['currency_code'], 'CNY', $data['money']);
        }
        return $data['money'];
    }

    public function getCashierTxtAttr($value, $data)
    {
        return Cache::store('user')->getOneUserRealname($data['cashier_id']);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    public function province()
    {
        return $this->belongsTo(province::class, 'province_id', 'id');
    }

    public function getEnableTimeTxtAttr($value, $data)
    {
        if ($data['status'] == self::STATUS_ENABLE) {
            return date('Y-m-d H:i:s', $data['enable_time']);
        }
        return '';
    }

    public function getForbiddenTimeTxtAttr($value, $data)
    {
        if ($data['status'] == self::STATUS_FORBIDDEN) {
            return date('Y-m-d H:i:s', $data['forbidden_time']);
        }
        return '';
    }

    public function getStatusTxtAttr($value, $data)
    {
        return self::STATUS_TXT[$data['status']]??'';
    }


}