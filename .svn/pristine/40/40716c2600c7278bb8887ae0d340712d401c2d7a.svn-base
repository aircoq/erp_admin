<?php

/**
 * Created by PhpStorm.
 * User: yangweiquan
 * Date: 2017-6-1
 * Time: 上午10:03
 * 产生各种单号
 */

namespace app\index\service;

use app\common\cache\Cache;
use think\Db;

class BillCodeCreator
{

    const KEY_PURCHASE_PARCEL = 'key:purchase_parcel';

    private $cache;

    public function __construct()
    {
        $this->cache = Cache::handler();
    }

    public function createPurchaseParcelCode(): string
    {
        $now = now('Ymd');
        $key = $this->setKeyForParcel();
        $hasValue = $this->cache->get($key);
        if (!$hasValue) {
            $maxValue = $this->getParcelCodeByDatabase();
            $this->cache->set($key, $maxValue, 3600*24);
        }
        $number = $this->cache->incr($key);
        return 'PP'.$now.$this->fix($number);
    }

    private function getParcelCodeByDatabase(): int
    {
        $sql = 'SELECT purchase_parcel_code AS BillNo FROM purchase_parcels ORDER BY purchase_parcel_code DESC LIMIT 1';
        $billQuery = Db::query($sql);
        $lastBillNo = substr($billQuery[0]['BillNo'], -1, 5);
        $maxValue =  (int)$lastBillNo + $this->randNumber();
        return $maxValue;
    }

    private function randNumber($len = 5): int
    {
        $number = mt_rand(1, 9);
        return $number*1000;
    }

    private function setKeyForParcel(): string
    {
        $day = now('d');
        return self::KEY_PURCHASE_PARCEL . ':' . $day;
    }

    private function fix(int $number, int $display = 5):string
    {
        $number = (string)$number;
        $len = strlen($number);
        if ($len < $display) {
            $fixZero = $display - $len;
            while ($fixZero--) {
                $number = '0' . $number;
            }
        }
        return $number;
    }

    /* 
     * 生成的单号个数
     */
    public static function CreateBillNos($prefix, $bill_count = 1, $suffix_len = 5) 
    {
        $result = array('status' => 0, 'message' => 'unknow error.');
        $prefixion = $prefix . date('Ymd');
        $last_serial_number = 0;
        $allow_prefix_list = array('PO', 'PL', 'PP');
        if (strlen($prefix) != 2) {
            $result['message'] = "前缀必须为两个字符。";
            return $result;
        }
        if (!preg_match('/^[A-Z_]{2}$/', $prefix)) {
            $result['message'] = "前缀必须为两个大写字母。";
            return $result;
        }
        if (!in_array($prefix, $allow_prefix_list)) {
            $result['message'] = "不允许的单号前缀，目前只支持以下前缀:\"" . implode(',', $allow_prefix_list) . "\"";
            return $result;
        }
        if (!is_numeric($bill_count) || $bill_count < 1 || strpos($bill_count, '.') !== false) {
            $result['message'] = "单的个数必须大于0，并且是整数。";
            return $result;
        }
        $sql = '';
        switch ($prefix) {
            case 'PO'://采购单号
                $sql = 'SELECT purchase_order_code AS BillNo FROM purchase_order ORDER  BY id DESC  LIMIT 1 FOR UPDATE';
                break;

            case 'PL'://采购计划编码
                $sql = 'SELECT purchase_plan_code AS BillNo FROM purchase_plan ORDER  BY id DESC  LIMIT 1 FOR UPDATE';
                break;

            case 'PP'://采购包裹编码
                $sql = 'SELECT purchase_parcel_code AS BillNo FROM purchase_parcels ORDER BY id DESC LIMIT 1';
                break;
        }

        if (!$sql) {
            $result['message'] = "内部错误，没有SQL.";
            return $result;
        }

        $billQuery = Db::query($sql);
        if ($billQuery && isset($billQuery[0]['BillNo']) && $billQuery[0]['BillNo']) {//如果存在记录

            $last_bill_no = $billQuery[0]['BillNo'];

            $last_bill_no_prefixion = substr($last_bill_no, 0, 10); //最后一单的单号

            if ($last_bill_no_prefixion == $prefixion) { //最后一单是今天的单子
                $last_serial_number = (int) substr($last_bill_no, 0 - $suffix_len, $suffix_len);
            }
        }
        $bill_number_arr = [];
        $cache = Cache::handler();
        for ($i = 1; $i <= $bill_count; $i++) {
            $billNumber = $prefixion . sprintf("%0{$suffix_len}s", $last_serial_number + $i);
            if($prefix == 'PP'){
                if(! $cache->set('purchase_parcel_code:'.$billNumber, 1, ['nx', 'ex'=>180])){
                    $billNumber = $prefixion . sprintf("%0{$suffix_len}s", $last_serial_number + $i + 1);
                }
            }
            $bill_number_arr[] = $billNumber;
        }

        if (!$bill_number_arr || !is_array($bill_number_arr) || count($bill_number_arr) != $bill_count) {
            $result['message'] = "创建单号失败。";
            return $result;
        }

        $result['list'] = $bill_number_arr;
        $result['status'] = 1;
        $result['message'] = 'OK';
        
        return $result;
    }

}
