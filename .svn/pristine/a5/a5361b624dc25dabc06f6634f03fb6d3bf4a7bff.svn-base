<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/6/8
 * Time: 20:01
 */

namespace app\carrier\service;


use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\aliexpress\AliexpressSellerAddress;
use app\common\model\aliexpress\AliexpressShippingAddress;
use erp\AbsServer;
use think\Exception;

class AliSellerAddressService extends AbsServer
{
    private $shippingAddressModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new AliexpressSellerAddress();
        $this->shippingAddressModel = new AliexpressShippingAddress();
    }

    /**
     * 保存卖家地址信息
     * @param $addressData
     * @param $accountId
     * @return bool
     * @throws Exception
     */
    public function saveSellerAddress(array $addressData, int $accountId)
    {
        if (empty($addressData)) {
            throw new Exception('没有任何数据');
        }
        $saveData = [];
        foreach ($addressData as $k => $address) {
            $temp = $address;
            $temp['account_id'] = $accountId;
            $temp['trademanage'] = $address['trademanage_id'];
            unset($temp['trademanage_id']);
            $saveData[] = $temp;
        }
        $this->model->saveAddress($saveData);
        return true;
    }

    /**
     * 获取卖家地址信息
     * @param int $accountId 平台账号ID
     * @param string $type 地址类型:sender,pickup,refund
     * @return array
     * @throws Exception
     */
    public function getSellerAddress(int $accountId, string $type = '')
    {
        $arr_address_types = AliexpressSellerAddress::MEMBER_TYPE;
        if (!empty($type)) {
            if (!in_array($type, $arr_address_types)) {
                throw new Exception('错误的地址类型');
            }
        }
        $type = AliexpressSellerAddress::getTypeByDisplayName($type);
        $where = [
            'account_id' => $accountId,
        ];
        if (!empty($type)) {
            $where['member_type'] = $type;
        }
        $list = $this->model->where($where)->field('address_id,member_type,name,country,province,city,county,street,street_address')->select();
        $arr_address = [];
        if (!empty($list)) {
            foreach ($list as $item) {
                $address = [
                    'address_id' => $item['address_id'],
                    'name' => $item['name'],
                    'country' => $item['country'],
                    'province' => $item['province'],
                    'city' => $item['city'],
                    'county' => $item['county'],
                    'street' => $item['street'],
                    'street_address' => $item['street_address']
                ];
                if (!empty($type)) {
                    $arr_address[] = $address;
                } else {
                    if (isset($arr_address_types[$item['member_type']])) {
                        $key = $arr_address_types[$item['member_type']];
                        $arr_address[$key][] = $address;
                    }
                }

            }
        }
        return $arr_address;
    }

    /**
     * 获取速卖通卖家账号地址信息
     * @param int $accountId
     * @param int $shippingId
     * @return array
     */
    public function getAliSellerAddress(int $accountId = 0, int $shippingId)
    {

        $arr_address_types = AliexpressSellerAddress::MEMBER_TYPE;
        $where = [];
        if (!empty($accountId)) {
            $where['account_id'] = $accountId;
        }
        $list = $this->model->where($where)->field('account_id,address_id,member_type,name,country,province,city')->select();
        $selected = $this->shippingAddressModel->field('sender_id,refund_id,pickup_id')
            ->where(['shipping_method_id' => $shippingId])->column('refund_id, pickup_id, sender_id', 'account_id');
        $arr_address = [];
        if (!empty($list)) {
            foreach ($list as $item) {
                /*$selected = $this->shippingAddressModel->field('sender_id,refund_id,pickup_id')
                    ->where(['account_id'=>$item['account_id'],'shipping_method_id'=>$shippingId])->find();
                $arr_address[$item['account_id']]['default_sender'] = isset($selected['sender_id'])&&!empty($selected['sender_id'])?$selected['sender_id']:'';
                $arr_address[$item['account_id']]['default_refund'] = isset($selected['refund_id'])&&!empty($selected['refund_id'])?$selected['refund_id']:'';
                $arr_address[$item['account_id']]['default_pickup'] = isset($selected['pickup_id'])&&!empty($selected['pickup_id'])?$selected['pickup_id']:''*/;
                $arr_address[$item['account_id']]['default_sender'] = isset($selected[$item['account_id']]) && !empty($selected[$item['account_id']]['sender_id']) ? $selected[$item['account_id']]['sender_id'] : '';
                $arr_address[$item['account_id']]['default_refund'] = isset($selected[$item['account_id']]) && !empty($selected[$item['account_id']]['refund_id']) ? $selected[$item['account_id']]['refund_id'] : '';
                $arr_address[$item['account_id']]['default_pickup'] = isset($selected[$item['account_id']]) && !empty($selected[$item['account_id']]['pickup_id']) ? $selected[$item['account_id']]['pickup_id'] : '';

                $address = [
                    'address_id' => $item['address_id'],
                    'name' => $item['name'],
                    'country' => $item['country'],
                    'province' => $item['province'],
                    'city' => $item['city'],
                ];
                if (isset($arr_address_types[$item['member_type']])) {
                    $key = $arr_address_types[$item['member_type']];
                    $arr_address[$item['account_id']][$key][] = $address;
                }
            }
        }
        $account_list = Cache::store('AliexpressAccount')->getTableRecord();
        $result = [];
        foreach ($account_list as $key => $account) {
            if (isset($account['is_invalid']) && $account['is_invalid'] && $account['is_authorization']) {
                $temp = isset($arr_address[$account['id']]) ? $arr_address[$account['id']] : [
                    'default_sender' => '',
                    'default_refund' => '',
                    'default_pickup' => '',
                    'pickup' => [],
                    'refund' => [],
                    'sender' => []
                ];
                $temp['account_id'] = $account['id'];
                $temp['account_code'] = $account['code'];
                $result[$account['id']] = $temp;
            }
        }
        if (!empty($accountId)) {
            return $result[$accountId];
        }
        return array_values($result);

    }
}