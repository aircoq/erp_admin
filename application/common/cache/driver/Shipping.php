<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\ShippingMethod;
use app\common\model\ShippingMethodDetail;
use app\common\model\ShippingMethodArriveLocation;
use app\common\model\ShippingMethodChannel;
use app\common\model\ShippingMethodStageFee;
use app\common\model\ShippingChannel;
use app\common\exception\BadParamException;
use think\Db;

class Shipping extends Cache
{
    const shippingKey = "cache:Shipping";
    private $detailKey = 'hash:ShippingDetail';
    private $channelKey = 'hash:ShippingChannel';

    const cachePrefix = 'table';
    private $tablePrefix = self::cachePrefix . ':shipping_method:';


    /**
     * 判断key是否存在
     * @param $key
     * @return bool
     */
    private function isExists($key)
    {
        if ($this->redis->exists($key)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断域是否存在
     * @param $key
     * @param $field
     * @return bool
     */
    private function isFieldExists($key, $field)
    {
        if ($this->redis->hExists($key, $field)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置值
     * @param $key
     * @param $field
     * @param $value
     */
    private function setData($key, $field, $value)
    {
        if (!$this->isFieldExists($key, $field)) {
            $this->redis->hSet($key, $field, $value);
        }
    }


    /** 获取物流方式信息
     * @param $id
     * @return array|mixed
     */
    private function readShipping($id)
    {
        $result = [];
        $shippingMethodInfo = (new ShippingMethod())->where(['id' => $id])->field(true)->find();
        if($shippingMethodInfo) {
            $result = $shippingMethodInfo->getData();
            $channels =  $this->getChannelCarrier($id);
            $result['channels'] = json_encode($channels);
            $key = $this->tablePrefix . $id;
            foreach ($result as $k => $v) {
                $this->setData($key, $k, $v);
            }
            $result['channels'] = $channels;
        }
        return $result;
    }

    /**
     * 获取物流方式信息
     * @param int $id
     * @return array|mixed
     */
    public function getShipping($id)
    {
        $key = $this->tablePrefix . $id;
        if ($this->isExists($key)) {
            $shippingInfo = $this->redis->hGetAll($key);
            if(!isset($shippingInfo['status'])){
                $this->delShipping($id);
                $shippingInfo = $this->readShipping($id);
            }else{
                $shippingInfo['channels'] = isset($shippingInfo['channels']) ? json_decode($shippingInfo['channels'], true) : array();
            }
        } else {
            $shippingInfo = $this->readShipping($id);
        }
        return $shippingInfo;
    }

    /**
     * 删除缓存
     * @param int $id
     */
    public function delShipping($id)
    {
        $key = $this->tablePrefix . $id;
        $this->redis->del($key);

    }

    /**
     * 获取渠道物流商id
     * @param int $shipping_method_id
     * @return array
     */
    private function getChannelCarrier($shipping_method_id)
    {
        $result = [];
        $lists = ShippingMethodChannel::where(['shipping_method_id' => $shipping_method_id])->field('channel_id,upload_carrier,tracking_url,shipping_service,upload_number_type,origin_country_code')->select();
        foreach($lists as $list) {
            $result[$list['channel_id']] = $list->toArray();
        }

        return $result;
    }

    /**
     * 获取渠道物流信息
     * @param int $shipping_method_id
     * @param int $channel_id
     * @return array
     */
    public function getChannelShipping($shipping_method_id, $channel_id)
    {
        $result = [];
        $shippingMethodInfo = $this->getShipping($shipping_method_id);
        if ($shippingMethodInfo && isset($shippingMethodInfo['channels'][$channel_id])) {
            $result = $shippingMethodInfo['channels'][$channel_id];
        }

        return $result;
    }

    /**
     * 获取物流名称
     * @param int $id
     * @return string
     */
    public function getShippingName($id)
    {
        if (!$id) {
            return '';
        }
        try {
            $info = $this->getShipping($id);
            $name = param($info, 'shortname');
        } catch (BadParamException $ex) {
            $name = '';
        }
        return $name;
    }

    public function getShippingClass($shippingId)
    {
        $shipping = $this->redis->hGet(static::shippingKey, $shippingId);
        if($shipping){
            return json_decode($shipping);
        }else{
            $shipping = ShippingMethod::get($shippingId);
            $this->redis->hSet(static::shippingKey, $shippingId, json_encode($shipping));

            return $shipping;
        }
    }

    /**
     * 获取启用运输方式的详情
     * @param int $shipping_method_id
     * @throws BadParamException
     * @return array
     */
    public function getShippingDetail($shipping_method_id)
    {
        $result = [];
        if ($this->redis->hExists($this->detailKey, $shipping_method_id)) {
            $result = json_decode($this->redis->hGet($this->detailKey, $shipping_method_id), true);
        } else {
            // 详情
            $shippingDetails = ShippingMethodDetail::where(['shipping_method_id' => $shipping_method_id])->select();
            if (!$shippingDetails) {
                return [];
                // throw new BadParamException('物流缺少运费详情');
            }
            foreach ($shippingDetails as $shippingDetail) {
                $result[$shippingDetail->id] = $shippingDetail->toArray();
                $result[$shippingDetail->id]['locations'] = [];
                $result[$shippingDetail->id]['stages']   = [];
            }
            $shipping_method_detail_ids = array_keys($result);
            // 分段详情
            $shippingDetailStages = ShippingMethodStageFee::where(['shipping_method_detail_id' => ['in', $shipping_method_detail_ids]])->select();
            foreach ($shippingDetailStages as $stage) {
                $result[$stage['shipping_method_detail_id']]['stages'][] = $stage->toArray();
            }
            // 详情地区
            $shippingDetailLocations = ShippingMethodArriveLocation::where(['shipping_method_detail_id' => ['in', $shipping_method_detail_ids]])->select();
            foreach ($shippingDetailLocations as $location) {
                $result[$location['shipping_method_detail_id']]['locations'][] = $location['country_code'];
                $result[$location['shipping_method_detail_id']]['country_remote'][$location['country_code']] = $location['is_remote'];
            }
            $result = array_values($result);
            $this->redis->hSet($this->detailKey, $shipping_method_id, json_encode($result));
        }

        return $result;
    }

    /**
     * 删除运输方式cache
     * @param int $shipping_method_id
     * @return boolean
     */
    public function delShippingDetail($shipping_method_id)
    {
        return $this->redis->hDel($this->detailKey, $shipping_method_id);
    }

    /**
     * 获取物流名称（拼接物流商）
     * @param int $shipping_method_id
     * @return string
     */
    public function getFullShippingName($shipping_method_id)
    {
        $name = '';
        $shippingMethod = $this->getShipping($shipping_method_id);
        if($shippingMethod){
            $carrier = Cache::store('carrier')->getCarrier($shippingMethod['carrier_id']);
            $name = $carrier ? $carrier['fullname'].'>>'.$shippingMethod['shortname'] : $shippingMethod['shortname'];
        }
        return $name;
    }

    public function getCarrierCode($shippingId)
    {
        $code = '';
        $shippingMethod = $this->getShipping($shippingId);
        if($shippingMethod){
            $carrier = Cache::store('carrier')->getCarrier($shippingMethod['carrier_id']);
            $code = $carrier ? $carrier['index'] : '';
        }
        return $code;
    }

    /**
     * 获取邮寄方式可发货平台
     * @param int $shipping_method_id
     * @throws BadParamException
     * @return array
     */
    public function getShippingChannel($shipping_method_id)
    {
        $result = [];
        if ($this->redis->hExists($this->channelKey, $shipping_method_id)) {
            $result = json_decode($this->redis->hGet($this->channelKey, $shipping_method_id), true);
        } else {
            $ShippingChannels= (new ShippingChannel())
                ->field('channel_id, use_site, type, content')
                ->where(['shipping_method_id' => $shipping_method_id])
                ->select();
            if (!$ShippingChannels) {
                return [];
            }
            foreach ($ShippingChannels as $channel) {
                $channel = $channel->toArray();
                $channel['content'] = json_decode($channel['content'], true);
                $result[$channel['channel_id']] = $channel;
                //开启平台配置
                if($channel['use_site']){
                    $content = [];
                    foreach($channel['content'] as $value){
                        $content[$value['site_code']] = $value;
                    }
                    $result[$channel['channel_id']]['content'] = $content;
                }
            }
            $this->redis->hSet($this->channelKey, $shipping_method_id, json_encode($result));
        }
        return $result;
    }

    /**
     * @desc 物流发货平台
     * @param int $shipping_method_id
     * @return boolean
     */
    public function delShippingChannel($shipping_method_id)
    {
        return $this->redis->hDel($this->channelKey, $shipping_method_id);
    }

}