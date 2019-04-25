<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/11/23
 * Time: 14:32
 */

namespace app\common\model;

use app\common\cache\Cache;
use erp\ErpModel;
use app\warehouse\service\ShippingMethod;
use app\warehouse\service\PackageCollection as ServicePackageCollection;

class PackageCollection extends ErpModel
{
    /**
     * 集包中
     */
    const STATUS_COLLECTING = 0;
    /**
     * 待复核
     */
    const STATUS_WAIT_CHECK = 1;
    /**
     * 待交接
     */
    const STATUS_WAIT_TRANSFER = 2;
    /**
     * 已交接
     */
    const STATUS_TRANSFER_ED = 3;
    /**
     * 复核失败
     */
    const STATUS_CHECK_FAIL = 4;
    /**
     * 已作废
     */
    const STATUS_CANCEL = 5;
    /**
     * 以出库
     */
    const STATUS_OUT = 6;
    /**
     * 部分出库
     */
    const STATUS_PART_OUT = 7;
    /**
     * 单号前缀
     */
    const PRE_FIX_CODE = 'G';
    /**
     * 状态对应文本
     */
    const STATUS_TEXT = [
        self::STATUS_COLLECTING => '集包中',
        self::STATUS_WAIT_CHECK => '待复核',
        self::STATUS_WAIT_TRANSFER => '待交接',
        self::STATUS_TRANSFER_ED => '已交接',
        self::STATUS_CHECK_FAIL => '复核失败',
        self::STATUS_CANCEL => '已作废',
        self::STATUS_OUT => '已出库',
        self::STATUS_PART_OUT => '部分出库'
    ];

    public function getCodeAttr($value)
    {
        return self::PRE_FIX_CODE . $value;
    }

    public function setCodeAttr($value)
    {
        $value = str_replace(self::PRE_FIX_CODE, '', $value);
        return $value;
    }

    public function getTypeAttr($value, $data)
    {
        $result = Cache::store('packing')->getPackageList();
        return isset($result[$data['packing_id']]) ? $result[$data['packing_id']]['name'] : '';
    }

    public function getCollectWeightAttr($value, $data)
    {
        $result = Cache::store('packing')->getPackageList();
        return isset($result[$data['packing_id']]) ? $result[$data['packing_id']]['weight'] : '0';
    }

    public function getStatusTxtAttr($value, $data)
    {
        return isset(self::STATUS_TEXT[$data['status']]) ? self::STATUS_TEXT[$data['status']] : '';
    }

    public function getCreatorAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['creator_id']);
        return $user ? $user['realname'] : '';
    }

    public function getHandoverAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['handover_id']);
        return $user ? $user['realname'] : '';
    }

    public function getWeigherAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['weight_id']);
        return $user ? $user['realname'] : '';
    }

    public function getDeleterAttr($value, $data)
    {
        $user = Cache::store('user')->getOneUser($data['deleter_id']);
        return $user ? $user['realname'] : '';
    }

    public function getShippingMethodAttr($value, $data)
    {
        return Cache::store('shipping')->getShippingName($data['shipping_method_id']);
    }

    public function detail()
    {
        return $this->hasMany(PackageCollectionDetail::class, 'package_collection_id', 'id');
    }

    public function getBotherShippingAttr($value, $data)
    {
        $ShippingMethod = new ShippingMethod();
        $result = [];
        if ($data['bother_shipping_id']) {
            $aShippingId = explode(',', $data['bother_shipping_id']);
            foreach ($aShippingId as $id) {
                $carrieInfo = $ShippingMethod->getCarrierByShippingId($id);
                $aShipping = ShippingMethod::getShippingById($id);
                $row = [];
                $row['shipping_id'] = $id;
                $row['shipping_name'] = $carrieInfo['name'] . ">>" . $aShipping['shortname'];
                $result[] = $row;
            }
        }else{
            $ServicePackageCollection = new ServicePackageCollection();
           $ret =  $ServicePackageCollection->getBotherShippingCache($data['id']);
           if($ret){
               foreach ($ret as $id=>$qty){
                   $carrieInfo = $ShippingMethod->getCarrierByShippingId($id);
                   $aShipping = ShippingMethod::getShippingById($id);
                   $row = [];
                   $row['shipping_id'] = $id;
                   $row['shipping_name'] = $carrieInfo['name'] . ">>" . $aShipping['shortname'];
                   $result[] = $row;
               }
           }
        }
        return $result;
    }

    /**
     * @title 获取类型列表
     * @return array
     * @author starzhan <397041849@qq.com>
     */
    public static function getTypeList()
    {
        $result = [];
        $ret = Cache::store('packing')->getPackageList();
        foreach ($ret as $k => $v) {
            $row = [
                'value' => $v['id'],
                'label' => $v['name'],
                'weight' => $v['weight'],
            ];
            $result[] = $row;
        }
        return $result;
    }
}