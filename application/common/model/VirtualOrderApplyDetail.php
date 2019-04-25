<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/9/29
 * Time: 9:33
 */

namespace app\common\model;

use think\Model;
use app\common\cache\Cache;
use erp\ErpModel;

class VirtualOrderApplyDetail extends ErpModel
{
    const STATUS = [
        0=>'未处理',
        1=>'已处理',
        2=>'已作废',
        3=>'处理中'
    ];

    const TYPE = [
        1 => '内部刷单',
        2 => '外包刷单',
        3 => '国外刷单'
    ];

    //运单类型  1-FBA 2-FBM
    const SHIPPING_TYPE = [
        '' => '',
        0 => '',
        1 => 'FBA',
        2 => 'FBM',
    ];

    public function getStatusTxtAttr($value,$data){
        return self::STATUS[$data['status']];
    }

    public function getThumbAttr($value,$data){
        if($data['thumb']){
            return $data['thumb'];
        }
        $aSku = Cache::store('Goods')->getSkuInfo($data['sku_id']);
        return isset($aSku['thumb'])?$aSku['thumb']:'';
    }

    public function getTypeAttr($value)
    {
        return self::TYPE[$value];
    }

    public function getUserName($user_id)
    {
        $user = Cache::store('user')->getOneUser($user_id);
        if ($user) {
            return $user['realname'];
        }
        return '';
    }

    public function add($data){
        if(isset($data['time_quantity'])){
            unset($data['time_quantity']);
        }

        if(isset($data['status_txt'])){
            unset($data['status_txt']);
        }

        return $this->insert($data);
    }

}