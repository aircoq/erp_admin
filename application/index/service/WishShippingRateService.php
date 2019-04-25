<?php

namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\Order;
use app\common\model\wish\WishShippingCharge;
use app\common\model\wish\WishShippingCountryRate;
use app\common\cache\Cache;
use app\common\service\ChannelAccountConst;
use app\common\service\UniqueQueuer;
use app\warehouse\service\ShippingMethod;
use think\Request;
use service\wish\WishApi;
use think\Db;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/11/12
 * Time: 11:17
 */
class WishShippingRateService
{
    protected $wishShippingCountryRateModel;

    public function __construct()
    {
        if (is_null($this->wishShippingCountryRateModel)) {
            $this->wishShippingCountryRateModel = new WishShippingCountryRate();
        }
    }

    /**
     * 账号列表
     * @param $params
     * @param array $field
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function lists($params, $page = 1, $pageSize = 20)
    {

        $where = [
            'status' => 0,
        ];

        if (isset($params['country_code']) && $params['country_code']) {
            $where['country_code'] = ['eq', $params['country_code']];
        }
        if (isset($params['shipping']) && $params['shipping']) {

        }
        $orderBy = fieldSort($params);
        $orderBy .= 'create_time desc';
        $field = '*';
        $count = $this->wishShippingCountryRateModel->field($field)->where($where)->count();
        $accountList = $this->wishShippingCountryRateModel->field($field)->where($where)->order($orderBy)->page($page, $pageSize)->select();
        $new_array = [];
        $allCarrier = Cache::store('Carrier')->getCarrier();
        foreach ($accountList as $k => $v) {

            $new_array[] = $this->showOne($v,$allCarrier);
        }
        $result = [
            'data' => $new_array,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'times' => Cache::store('WishCarrier')->getShippingRate('shipping_rate'),
        ];
        return $result;
    }

    protected function showOne($v,$allCarrier = [])
    {
        $temp = $v->toArray();
        $temp['general_surface_shipping'] = $this->getCarrier($temp['general_surface_shipping'], $allCarrier);
        $temp['general_registration_shipping'] = $this->getCarrier($temp['general_registration_shipping'], $allCarrier);
        $temp['special_surface_shipping'] = $this->getCarrier($temp['special_surface_shipping'], $allCarrier);
        $temp['special_registration_shipping'] = $this->getCarrier($temp['special_registration_shipping'], $allCarrier);
        return $temp;
    }

    protected function showOneWto($v)
    {
        $temp = $v->toArray();
        $temp['general_surface_shipping'] = json_decode($temp['general_surface_shipping'], true);
        $temp['general_registration_shipping'] = json_decode($temp['general_registration_shipping'], true);
        $temp['special_surface_shipping'] = json_decode($temp['special_surface_shipping'], true);
        $temp['special_registration_shipping'] = json_decode($temp['special_registration_shipping'], true);
        return $temp;
    }

    private function getCarrier($ids, $carrier = '')
    {
        $reData = [];
        if (!$ids) {
            return $reData;
        }
        $ids = json_decode($ids, true);
        foreach ($ids as $id) {
            $shippingMethodInfo = Cache::store('Shipping')->getShipping($id);
            if($shippingMethodInfo){
                $fullname = '';
                if(isset($shippingMethodInfo['carrier_id'])){
                    $fullname .= $carrier[$shippingMethodInfo['carrier_id']]['fullname'] ?? '';
                }
                $reData[] = $fullname . '>>' . $shippingMethodInfo['shortname'];
            }

        }
        return $reData;
    }


    /** 账号信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function read($id,$isShpw = true)
    {
        $accountInfo = $this->wishShippingCountryRateModel->field('*')->where(['id' => $id])->find();
        if (empty($accountInfo)) {
            throw new JsonErrorException('占比信息不存在', 500);
        }
        if($isShpw){
            $allCarrier = Cache::store('Carrier')->getCarrier();
            $accountInfo = $this->showOne($accountInfo,$allCarrier);
        }else{
            $accountInfo = $this->showOneWto($accountInfo);
        }

        return $accountInfo;
    }

    /** 更新
     * @param $id
     * @param $data
     * @return \think\response\Json
     */
    public function update($id, $data)
    {
        unset($data['id']);
        return $this->wishShippingCountryRateModel->save($data, ['id' => $id]);

    }

    /**
     * 计算订单占比
     * @param $date_s
     * @param $date_e
     * @return array
     * @throws \think\Exception
     */
    public function orderRate($date_s, $date_e)
    {
        $date_s = strtotime($date_s);
        $date_e = strtotime($date_e);

        $where = [
            'channel_id' => 3,
            'order_time' => ['between', [$date_s, $date_e]],
            'shipping_time' => ['>', 0],
        ];

        $list = (new Order())->where($where)->group('country_code')->column('count(*)', 'country_code');

        if ($list) {
            $newCountry = [];
            $allNum = (new Order())->where($where)->count();
            foreach ($list as $countryCode => $num) {
                $one = [
                    'status' => 0,
                    'country_code' => $countryCode,
                    'order_number' => $num,
                    'rate' => round($num * 100 / $allNum, 2),
                ];
                (new WishShippingCountryRate())->add($one);
                $newCountry[] = $countryCode;
            }

            //将上一次的 设置为关闭状态
            $newWhere = [
                'country_code' => ['not in', $newCountry],
            ];
            $newSave = [
                'status' => 1,
                'updated_time' => time(),
            ];
            $this->wishShippingCountryRateModel->save($newSave, $newWhere);
            $cacheData = [
                'last_time' => time(),
                'last_monthly' => date('Y-m', $date_s) . '--' . date('Y-m', $date_e),
            ];
            Cache::store('WishCarrier')->getShippingRate('shipping_rate', $cacheData);
        }
        return true;//$this->lists([]);

    }

    /**
     * 计算重量运费
     * @param int $data_s
     * @param int $data_e
     * @return array
     * @throws \think\Exception
     */
    public function addShippingCharge($data_s = 1, $data_e = 3)
    {
//        $lock = Cache::store('WishCarrier')->getShippingRate('shipping_charge_lock');
//        if($lock && $lock['lock'] == 1){
//            throw new JsonErrorException('正在计算中，不需要重复提交', 500);
//        }
        $temp['min'] = $data_s;
        $temp['max'] = $data_e;
        $service = new UniqueQueuer(\app\index\queue\WishShippingRateQueue::class);
        for ($i = $data_s; $i <= $data_e; $i++) {
            $service->push($i);
        }
        $cacheData = [
            'last_time' => time(),
            'last_monthly' => $data_s . '--' . $data_e,
        ];
        Cache::store('WishCarrier')->getShippingRate('shipping_charge', $cacheData);
        return ['message'=>'已经加入计算队列。请稍等'];
    }

    /**
     * 计算重量运费算法
     * @param int $data_s
     * @param int $data_e
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shippingCharge($data_s = 1, $data_e = 3)
    {
        $cacheData = [
            'last_time' => time(),
            'lock' => 1,
            'message' => '开始计算。。。',
        ];
        Cache::store('WishCarrier')->getShippingRate('shipping_charge_lock', $cacheData);
        set_time_limit(0);
        $where['status'] = 0;
        $field = 'warehouse_id,country_code,rate,general_surface_shipping,general_registration_shipping,special_surface_shipping,special_registration_shipping';
        $list = $this->wishShippingCountryRateModel->where($where)->field($field)->select();
        $service = new ShippingMethod();
        for ($i = $data_s; $i <= $data_e; $i++) {
            $saveData = [
                'weight' => $i,
                'general_surface_fee' => 0,
                'general_registration_fee' => 0,
                'special_surface_fee' => 0,
                'special_registration_fee' => 0,
            ];
            foreach ($list as $v) {
                $one = [
                    'volume' => 0,
                    'warehouse_id' => $v['warehouse_id'],
                    'country_code' => $v['country_code'],
                    'weight' => $i,
                ];

                $saveData['general_surface_fee'] += $this->getWeightValue($v['rate'], $v['general_surface_shipping'], $one, $service);
                $saveData['general_registration_fee'] += $this->getWeightValue($v['rate'], $v['general_registration_shipping'], $one, $service);
                $saveData['special_surface_fee'] += $this->getWeightValue($v['rate'], $v['special_surface_shipping'], $one, $service);
                $saveData['special_registration_fee'] += $this->getWeightValue($v['rate'], $v['special_registration_shipping'], $one, $service);
            }
            $saveData['general_surface_fee'] = $this->formattingValue($saveData['general_surface_fee']);
            $saveData['general_registration_fee'] = $this->formattingValue($saveData['general_registration_fee']);
            $saveData['special_surface_fee'] = $this->formattingValue($saveData['special_surface_fee']);
            $saveData['special_registration_fee'] = $this->formattingValue($saveData['special_registration_fee']);
            (new WishShippingCharge())->add($saveData);
        }
        $cacheData = [
            'last_time' => time(),
            'last_monthly' => $data_s . '--' . $data_e,
        ];
        Cache::store('WishCarrier')->getShippingRate('shipping_charge', $cacheData);
        //删除
        $delWhere = [
            'weight' => ['not between',[$data_s,$data_e]],
        ];

        (new WishShippingCharge())->where($delWhere)->delete();
        $cacheData = [
            'last_time' => time(),
            'lock' => 0,
            'message' => '计算完成。。。',
        ];
        Cache::store('WishCarrier')->getShippingRate('shipping_charge_lock', $cacheData);
        return true;

    }



    /**
     * 计算重量运费算法
     * @param int $data_s
     * @param int $data_e
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shippingChargeRunOne($i)
    {

        set_time_limit(0);
        $where['status'] = 0;
        $field = 'warehouse_id,country_code,rate,general_surface_shipping,general_registration_shipping,special_surface_shipping,special_registration_shipping';
        $list = $this->wishShippingCountryRateModel->where($where)->field($field)->select();
        $service = new ShippingMethod();

        $saveData = [
            'weight' => $i,
            'general_surface_fee' => 0,
            'general_registration_fee' => 0,
            'special_surface_fee' => 0,
            'special_registration_fee' => 0,
        ];
        foreach ($list as $v) {
            $one = [
                'volume' => 0,
                'warehouse_id' => $v['warehouse_id'],
                'country_code' => $v['country_code'],
                'weight' => $i,
            ];

            $saveData['general_surface_fee'] += $this->getWeightValue($v['rate'], $v['general_surface_shipping'], $one, $service);
            $saveData['general_registration_fee'] += $this->getWeightValue($v['rate'], $v['general_registration_shipping'], $one, $service);
            $saveData['special_surface_fee'] += $this->getWeightValue($v['rate'], $v['special_surface_shipping'], $one, $service);
            $saveData['special_registration_fee'] += $this->getWeightValue($v['rate'], $v['special_registration_shipping'], $one, $service);
        }
        $saveData['general_surface_fee'] = $this->formattingValue($saveData['general_surface_fee']);
        $saveData['general_registration_fee'] = $this->formattingValue($saveData['general_registration_fee']);
        $saveData['special_surface_fee'] = $this->formattingValue($saveData['special_surface_fee']);
        $saveData['special_registration_fee'] = $this->formattingValue($saveData['special_registration_fee']);
        (new WishShippingCharge())->add($saveData);


        $cacheData = [
            'last_time' => time(),
            'lock' => 0,
            'message' => '计算完成。。。',
        ];
        Cache::store('WishCarrier')->getShippingRate('shipping_charge_lock', $cacheData);
        return true;

    }


    /**
     * 格式化数据
     * @param $value
     * @return float|int
     */
    private function formattingValue($value)
    {
        if (!$value) return 0;
        return round($value / 100, 2);
    }

    /**
     * 获取最小的运费值
     * @param $rate
     * @param $shipping_methods
     * @param $one
     * @param $service
     * @return float|int
     */
    private function getWeightValue($rate, $shipping_methods, $one, $service)
    {
        if (!$shipping_methods) {
            return 0;
        }
        $one['shipping_methods'] = $shipping_methods;
        $min = 0;
        $list = $service->trial($one);
        if ($list) {
            $min = $list[0]['cny_amount'];
            foreach ($list as $v) {
                if ($min > $v['cny_amount']) {
                    $min = $v['cny_amount'];
                }
            }
        }
        return $rate * $min;
    }


    /**
     * 重量与费用列表
     * @param $params
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function weightList($params, $page = 1, $pageSize = 20)
    {
        $where = [];
        if ((isset($params['weight_start']) && $params['weight_start']) && (isset($params['weight_end']) && $params['weight_end'])) {
            $where['weight'] = ['BETWEEN', [$params['weight_start'],$params['weight_end']]];
        }elseif(isset($params['weight_start']) && $params['weight_start']){
            $where['weight'] = ['>=',$params['weight_start']];
        }elseif(isset($params['weight_end']) && $params['weight_end']){
            $where['weight'] = ['<=',$params['weight_end']];
        }

        //费用选择
        if (!empty($params['s_type']) && $params['s_type'] > 0) {

            if (!empty($params['s_start']) || !empty($params['s_end'])) {
                $allDateType = ['', 'general_surface_fee', 'general_registration_fee', 'special_surface_fee','special_registration_fee'];
                $b_time = !empty($params['s_start']) ? $params['s_start'] : '';
                $e_time = !empty($params['s_end']) ? $params['s_end'] : '';

                if ($b_time && $e_time) {
                    $where[$allDateType[$params['s_type']]] = ['BETWEEN', [$b_time, $e_time]];

                } elseif ($b_time) {
                    $where[$allDateType[$params['s_type']]] = ['EGT', $b_time];
                } elseif ($e_time) {
                    $where[$allDateType[$params['s_type']]] = ['ELT', $e_time];
                }

            }
        }

        $model = new WishShippingCharge();
        $orderBy = fieldSort($params);
        $orderBy .= 'create_time desc';
        $field = '*';
        $count = $model->field($field)->where($where)->count();
        $accountList = $model->field($field)->where($where)->order($orderBy)->page($page, $pageSize)->select();
        $new_array = [];
        foreach ($accountList as $k => $v) {
            $temp = $v->toArray();
            $new_array[] = $temp;
        }
        $result = [
            'data' => $new_array,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'times' => Cache::store('WishCarrier')->getShippingRate('shipping_charge'),
        ];
        return $result;
    }

    /**
     * @param $weight
     * @param int $wishShippingMode
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getFee($weight,$wishShippingMode = 0)
    {
        $reData = [
            'surface_fee' => 0,
            'registration_fee' => 0,
        ];
        $info = (new WishShippingCharge())->where(['weight'=>$weight])->find();
        if($info){
            if($wishShippingMode == 1){
                $reData['surface_fee'] = $info['special_surface_fee'];
                $reData['registration_fee'] = $info['special_registration_fee'];
            }else{
                $reData['surface_fee'] = $info['general_surface_fee'];
                $reData['registration_fee'] = $info['general_registration_fee'];
            }
        }
        return $reData;
    }

}