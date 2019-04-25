<?php
/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/6/8
 * Time: 19:32
 */

namespace app\common\model\aliexpress;


use think\Model;

class AliexpressSellerAddress extends Model
{
    protected $autoWriteTimestamp = true;

    const TYPE_SENDER = 1;  //发货地址
    const TYPE_PICKUP = 2;  //揽收地址
    const TYPE_REFUND = 3;  //退货地址

    const MEMBER_TYPE = [
        self::TYPE_SENDER => 'sender',
        self::TYPE_PICKUP => 'pickup',
        self::TYPE_REFUND => 'refund'
    ];

    public static function getTypeByDisplayName(string $displayName) :int
    {
        $arr_types = self::MEMBER_TYPE;
        $arr_types = array_flip($arr_types);
        return isset($arr_types[$displayName])?$arr_types[$displayName]:0;
    }

    public function saveAddress($addressData)
    {
        foreach($addressData as $address){
            $addModel = $this->where(['account_id'=>$address['account_id'], 'address_id'=>$address['address_id']])->find();
            if(empty($addModel)){
                $this->allowField(true)->create($address);
            }else{
                $addModel->allowField(true)->save($address, ['id' => $addModel->id]);
            }
        }
    }

    protected function setMemberTypeAttr($value)
    {
        $type = array_flip(self::MEMBER_TYPE);
        return isset($type[$value])?$type[$value]:0;
    }
    
    private function getMemberType($str)
    {
        $id = 0;
        switch ($str) {
            case 'sender':
                $id = 1;
            break;
            case 'pickup':
                $id = 2;
            break;
            case 'refund':
                $id = 3;
            break;     
        }
        
        return $id;
    }

    public function account()
    {
        return $this->hasOne(AliexpressAccount::class,'account_id','id');
    }


}