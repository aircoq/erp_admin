<?php
namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\ChannelBuyerAddress;
use app\index\validate\BuyerAddressValidate;
use think\Db;
use think\Exception;

/** 买家地址管理
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/8/4
 * Time: 19:48
 */
class BuyerAddressService
{
    protected $channelBuyerAddressModel;
    protected $validate;

    public function __construct()
    {
        if (is_null($this->channelBuyerAddressModel)) {
            $this->channelBuyerAddressModel = new ChannelBuyerAddress();
        }
        $this->validate = new BuyerAddressValidate();
    }

    /** 获取地址列表
     * @param $channel_buyer_id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function addressList($channel_buyer_id)
    {
        $where['channel_buyer_id'] = ['eq', $channel_buyer_id];
        $serverList = $this->channelBuyerAddressModel->field(true)->where($where)->select();
        $result = [
            'data' => $serverList
        ];
        return $result;
    }

    /** 更新买家地址信息
     * @param array $address
     * @param $id
     */
    public function update(array $address, $id)
    {
        if(!$this->validate->check($address)){
            throw new JsonErrorException($this->validate->getError(),500);
        }
        try {
            $this->channelBuyerAddressModel->where(['id' => $id])->update($address);
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /** 新增买家地址
     * @param array $address
     * @return mixed
     */
    public function add(array $address)
    {
        if(!$this->validate->check($address)){
            throw new JsonErrorException($this->validate->getError(),500);
        }
        try {
            $this->channelBuyerAddressModel->allowField(true)->isUpdate(false)->save($address);
            $address_id = $this->channelBuyerAddressModel->id;
            return $address_id;
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /** 删除买家地址信息
     * @param $id
     */
    public function delete($id)
    {
        try {
            $this->channelBuyerAddressModel->where(['id' => $id])->delete();
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /** 设置为默认地址
     * @param $id
     * @param $channel_buyer_id
     */
    public function defaultAddress($id, $channel_buyer_id)
    {
        try {
            $this->channelBuyerAddressModel->where(['channel_buyer_id' => $channel_buyer_id])->update(['is_default' => 0]);
            $this->channelBuyerAddressModel->where(['id' => $id])->update(['is_default' => 1]);
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }
}