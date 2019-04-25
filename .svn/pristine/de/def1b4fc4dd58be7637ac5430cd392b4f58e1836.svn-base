<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\PackageDeclareRuleSet;
use app\common\service\UniqueQueuer;
use app\warehouse\queue\LogisticsUploadQueue;
use app\warehouse\queue\ThirdLogisticsUploadQueue;
use app\warehouse\queue\LazadaLogisticsUploadQueue;
use app\warehouse\queue\ShopeeLogisticsUploadQueue;


/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/15
 * Time: 16:06
 */
class Package extends Cache
{
    private $_zset_create_order = 'zset:carrier:createOrder'; // 物流商下单集合
    private $_hash_create_order = 'hash:carrier:createOrder'; // 物流商下单hash
    private $_zset_carrier_send = 'zset:carrier_send';  //检查物流商发货状态
    private $_history_package_shipping = 'hash:labelNumber';   //历史面单号
    private $_hash_have_carrier_send = 'hash:have:carrier:send';   //需拉取物流发货
    private $_hash_have_weight_fee = 'hash:have:weight:fee';       //需拉取物流重量与费用
    private $_package_apply_cancel = 'hash:package:applyCancel';       //包裹申请取消

    /** 获取包裹规则申报内容
     * @return array|mixed
     */
    public function ruleSetInfo()
    {
        if ($this->persistRedis->exists('cache:packageRuleSet')) {
            return json_decode($this->persistRedis->get('cache:packageRuleSet'), true);
        }
        $PackageRuleSetModel = new PackageDeclareRuleSet();
        $result = $PackageRuleSetModel->field('id,title,default_value')->with('items')->where([
            'status' => 0,
            'is_default' => 0
        ])->order('sort asc')->select();
        $new_array = [];
        foreach ($result as $k => $v) {
            $v['default_value'] = json_decode($v['default_value'], true);
            array_push($new_array, $v);
        }
        $this->persistRedis->set('cache:packageRuleSet', json_encode($new_array));
        return $new_array;
    }

    /** 获取包裹申报默认设置信息
     * @return array|mixed
     */
    public function defaultRuleInfo()
    {
        if ($this->persistRedis->exists('cache:defaultRuleInfo')) {
            return json_decode($this->persistRedis->get('cache:defaultRuleInfo'), true);
        }
        $PackageRuleSetModel = new PackageDeclareRuleSet();
        $result = $PackageRuleSetModel->field('id,title,default_value')->where([
            'status' => 0,
            'is_default' => 1
        ])->find();
        $result['default_value'] = json_decode($result['default_value'], true);
        $this->persistRedis->set('cache:defaultRuleInfo', json_encode($result));
        return $result;
    }

    /**
     * 删除缓存
     */
    public function delPackageDeclareRule()
    {
        $this->persistRedis->del('cache:packageRuleSet');
        $this->persistRedis->del('cache:defaultRuleInfo');
    }

    /**
     * 加入重量和运费的集合
     * @param $number
     * @param int $time
     */
    public function setTrackingWeightFee($number, $time = 0)
    {
        if (empty($time)) {
            $time = time();
        }
        $this->persistRedis->zAdd('zset:tracking_weight_fee', $time, $number);
    }

    /** 获取重量和运费的集合
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getTrackingWeightFee($start = 0, $end = 50)
    {
        $package_number = [];
        if ($this->persistRedis->exists('zset:tracking_weight_fee')) {
            $package_number = $this->persistRedis->zRange('zset:tracking_weight_fee', $start, $end, true);
        }
        return $package_number;
    }

    /** 删除获取重量和运费的集合
     * @param $number
     */
    public function delTrackingWeightFee($number)
    {
        $this->persistRedis->zRem('zset:tracking_weight_fee', $number);
    }

    /**
     * 加入面单的集合
     * @param $number
     * @param int $time
     */
    public function setTrackingLabel($number, $time = 0)
    {
        if (empty($time)) {
            $time = time();
        }
        $this->persistRedis->zAdd('zset:tracking_label', $time, $number);
    }

    /** 获取面单的集合
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getTrackingLabel($start = 0, $end = 50)
    {
        $package_number = [];
        if ($this->persistRedis->exists('zset:tracking_label')) {
            $package_number = $this->persistRedis->zRange('zset:tracking_label', $start, $end, true);
        }
        return $package_number;
    }

    /** 删除面单的集合
     * @param $number
     */
    public function delTrackingLabel($number)
    {
        $this->persistRedis->zRem('zset:tracking_label', $number);
    }

    /**
     * 加入跟踪号的集合
     * @param $number
     * @param int $time
     */
    public function setGainTrackingNumber($number, $time = 0)
    {
        if (empty($time)) {
            $time = time();
        }
        $this->persistRedis->zAdd('zset:gain_tracking_number', $time, $number);
    }

    /** 获取跟踪号的集合
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getGainTrackingNumber($start = 0, $end = 50)
    {
        $package_number = [];
        if ($this->persistRedis->exists('zset:gain_tracking_number')) {
            $package_number = $this->persistRedis->zRange('zset:gain_tracking_number', $start, $end, true);
        }
        return $package_number;
    }

    /** 删除跟踪号的集合
     * @param $number
     */
    public function delGainTrackingNumber($number)
    {
        $this->persistRedis->zRem('zset:gain_tracking_number', $number);
    }

    /**
     * 加入获取物流商发货状态的集合
     * @param $number
     * @param int $time
     */
    public function setCarrierSend($number, $time = 0)
    {
        if (empty($time)) {
            $time = time();
        }
        $this->persistRedis->zAdd($this->_zset_carrier_send, $time, $number);
    }

    /** 获取物流商发货状态的集合
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getCarrierSend($start = 0, $end = 50)
    {
        $package_number = [];
        if ($this->persistRedis->exists($this->_zset_carrier_send)) {
            $package_number = $this->persistRedis->zRange($this->_zset_carrier_send, $start, $end, true);
        }
        return $package_number;
    }

    /** 删除物流商发货状态的集合
     * @param $number
     */
    public function delCarrierSend($number)
    {
        $this->persistRedis->zRem($this->_zset_carrier_send, $number);
    }

    /**
     * 物流商下单任务列
     * @param int $order_package_id
     * @param int|boolean $delivery
     * @param int $deadline
     * @param int $warehouse_id
     * @return boolean
     */
    public function createOrder($order_package_id, $delivery, $deadline, $warehouse_id, $userId = 0, $carrierCode = '')
    {
        switch ($carrierCode) {
            case 'Gucang':
            case 'Wangji':
            case 'fourpx':
            case 'Iml':
                (new UniqueQueuer(ThirdLogisticsUploadQueue::class))->push($order_package_id);
                break;
            case 'Lazada':
                (new UniqueQueuer(LazadaLogisticsUploadQueue::class))->push($order_package_id);
                break;
            case 'shopee':
                (new UniqueQueuer(ShopeeLogisticsUploadQueue::class))->push($order_package_id);
                break;
            default:
                (new UniqueQueuer(LogisticsUploadQueue::class))->push($order_package_id);
                break;
        }
        $confirm = $delivery ? 1 : 0;
        $this->redis->sEtex("task:logisticsupload:" . $order_package_id, 172800, json_encode(['confirm' => $confirm, 'warehouse_id' => $warehouse_id, 'userId' => $userId]));
        return true;
    }

    /**
     * 获取物流商下单包裹列表
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getCreateOrderIds()
    {
        $orderPackageId = (new UniqueQueuer($this->_zset_create_order))->pop();
        return $orderPackageId;
    }

    /**
     * 获取单据是否交运
     * @param string $order_package_id
     * @return array
     */
    public function getCreateOrderConfirm($order_package_id)
    {
        $info = json_decode($this->redis->get("task:logisticsupload:" . $order_package_id), true);
        return $info ? $info : [];
    }

    /**
     * 物流商下单包裹删除
     * @param string $order_package_id
     * @return boolean
     */
    public function delCreateOrder($order_package_id)
    {
        $this->redis->del("task:logisticsupload:" . $order_package_id);
        return true;
    }

    /**
     * 包裹是否在物流商下单集合中
     * @param string $order_package_id
     * @return boolean
     */
    public function isCreateOrder($order_package_id)
    {
        $exist = false;
        do {
            if ($exist = (new UniqueQueuer(LogisticsUploadQueue::class))->exist($order_package_id)) {
                break;
            }
            if ($exist = (new UniqueQueuer(ThirdLogisticsUploadQueue::class))->exist($order_package_id)) {
                break;
            }
            if ($exist = (new UniqueQueuer(LazadaLogisticsUploadQueue::class))->exist($order_package_id)) {
                break;
            }
            $exist = (new UniqueQueuer(ShopeeLogisticsUploadQueue::class))->exist($order_package_id);
        } while(false);

        return $exist;
    }

    /**
     * 添加包裹id到交运集合
     * @param string $order_package_id
     * @return boolean
     */
    public function addPackageConfirm($order_package_id, $deadline)
    {
        $this->persistRedis->zAdd('zset:package:confirm', $deadline, $order_package_id);
        return true;
    }

    /**
     * 从交运集合中删除包裹id
     * @param string $order_package_id
     * @return boolean
     */
    public function delPackageConfirm($order_package_id)
    {
        $this->persistRedis->zRem('zset:package:confirm', $order_package_id);
        return true;
    }

    /**
     * 获取交运列表
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getPackageConfirmList($start, $end)
    {
        $ids = $this->persistRedis->zRange('zset:package:confirm', $start, $end, true);
        return $ids ? $ids : [];
    }

    /**
     * 包裹是否在交运列表中
     * @param string $order_package_id
     * @return boolean
     */
    public function isPackageConfirm($order_package_id)
    {
        if ($this->persistRedis->zScore('zset:package:confirm', $order_package_id) !== false) {
            return true;
        }

        return false;
    }

    /**
     * 记录历史包裹面单信息
     * @param $shipping_number
     * @param $package_id
     */
    public function recordLabelNumber($shipping_number, $package_id)
    {
        $key = $this->_history_package_shipping;
        if (!$this->persistRedis->hExists($key, $shipping_number)) {
            $this->persistRedis->hSet($key, $shipping_number, $package_id);
        }
    }

    /**
     * 删除历史包裹面单信息
     * @param $package_id
     */
    public function delLabelNumber($package_id)
    {
        $key = $this->_history_package_shipping;
        if ($this->persistRedis->exists($key)) {
            $labelData = $this->persistRedis->hGetAll($key);
            foreach ($labelData as $label => $packageId) {
                if ($package_id == $packageId) {
                    $this->persistRedis->hDel($key, $label);
                }
            }
        }
    }

    /**
     * 读取记录历史包裹面单信息，获取包裹id信息
     * @param $shipping_number
     * @return int|string
     */
    public function readLabelNumber($shipping_number)
    {
        $package_id = 0;
        $key = $this->_history_package_shipping;
        if ($this->persistRedis->hExists($key, $shipping_number)) {
            $package_id = $this->persistRedis->hGet($key, $shipping_number);
        }
        return $package_id;
    }

    /**
     * 加入需获取物流商发货记录
     * @param $number
     */
    public function setHaveCarrierSend($number)
    {
        $this->persistRedis->hSet($this->_hash_have_carrier_send, $number, $number);
    }

    /**
     * 获取需物流商发货记录
     * @param $number
     * @return array|string
     */
    public function isExistsHaveCarrierSend($number)
    {
        if ($this->persistRedis->hExists($this->_hash_have_carrier_send,$number)) {
            return true;
        }
        return false;
    }

    /** 删除需物流商发货记录
     * @param $number
     */
    public function delHaveCarrierSend($number)
    {
        $this->persistRedis->hDel($this->_hash_have_carrier_send, $number);
    }

    /**
     * 加入需获取物流商物流费用
     * @param $number
     */
    public function setHaveWeightFee($number)
    {
        $this->persistRedis->hSet($this->_hash_have_weight_fee, $number, $number);
    }

    /**
     * 获取需物流商物流费用
     * @param $number
     * @return array|string
     */
    public function isExistsHaveWeightFee($number)
    {
        if ($this->persistRedis->hExists($this->_hash_have_weight_fee,$number)) {
            return true;
        }
        return false;
    }

    /** 删除需物流商物流费用
     * @param $number
     */
    public function delHaveWeightFee($number)
    {
        $this->persistRedis->hDel($this->_hash_have_weight_fee, $number);
    }

    /**
     * 记录申请取消包裹的信息
     * @param $package_id
     */
    public function setPackingApplyCancel($package_id)
    {
        $this->persistRedis->hSet($this->_package_apply_cancel, $package_id, $package_id);
    }

    /**
     * 检查包裹是否申请取消
     * @param $package_id
     * @return bool
     */
    public function isPackingApplyCancel($package_id)
    {
        if ($this->persistRedis->hExists($this->_package_apply_cancel, $package_id)) {
            return true;
        }
        return false;
    }

    /**
     * 删除包裹申请的记录
     * @param $package_id
     */
    public function delPackingApplyCancel($package_id)
    {
        $this->persistRedis->hDel($this->_package_apply_cancel, $package_id);
    }
}