<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/6/8
 * Time: 20:01
 */

namespace app\carrier\service;


use app\common\model\aliexpress\AliexpressSellerAddress;
use erp\AbsServer;
use think\Exception;

class AliSellerAddressServer extends AbsServer
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new AliexpressSellerAddress();
    }

    /**
     * 保存卖家地址信息
     * @param $addressData
     * @param $accountId
     * @return bool
     * @throws Exception
     */
    public function saveSellerAddress(array $addressData,int $accountId)
    {
        if(empty($addressData))
        {
            throw new Exception('没有任何数据');
        }
        $saveData = [];
        foreach($addressData as $k=>$address){
            $temp = $address;
            $temp['account_id'] = $accountId;
            $temp['street_address'] = $address['streetAddress'];
            $temp['member_type'] = $address['memberType'];
            $temp['address_id'] = $address['addressId'];
            $temp['trademanage'] = $address['trademanageId'];
            $temp['is_default'] = $address['isDefault'];
            $temp['is_need_to_update'] = $address['isNeedToUpdate'];
            unset($temp['streetAddress'],$temp['memberType'],$temp['addressId'],$temp['trademanageId'],$temp['isDefault'],$temp['isNeedToUpdate']);
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
    public function getSellerAddress(int $accountId,string $type='')
    {
        $arr_address_types = AliexpressSellerAddress::MEMBER_TYPE;
        if(!empty($type)){
            if(!in_array($type,$arr_address_types)){
                throw new Exception('错误的地址类型');
            }
        }
        $type = AliexpressSellerAddress::getTypeByDisplayName($type);
        $where = [
            'account_id'=>$accountId,
            'member_type'=>$type,
        ];
        $list = $this->model->where($where)->field('address_id,member_type,name,country,province,city')->select();
        $arr_address = [];
        if(!empty($list)){
            foreach ($list as $item){
                $address = [
                    'address_id'=>$item['address_id'],
                    'name'=>$item['name'],
                    'country'=>$item['country'],
                    'province'=>$item['province'],
                    'city'=>$item['city']
                ];
                if(!empty($type)){
                    $arr_address[] = $address;
                }else{
                    if(isset($arr_address_types[$item['member_type']])){
                        $key = $arr_address_types[$item['member_type']];
                        $arr_address[$key][] = $address;
                    }
                }

            }
        }
        return $arr_address;
    }
}