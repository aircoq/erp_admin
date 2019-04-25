<?php

namespace app\common\model;

use app\common\model\AccountCompany;
use think\Model;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/8/22
 * Time: 17:46
 */
class Account extends Model
{


    //0-新增   3-已交接  4-已回收 5-已作废  //1-已申请 2-已成功 移除
    const status_new = 0;
//    const status_apply = 1;
//    const status_succeed = 2;
    const status_connect = 3;
    const status_recycle = 4;
    const status_cancellation = 5;

    const STATUS = [
        Account::status_new => '新增',
//        Account::status_apply => '已申请',
//        Account::status_succeed => '已成功',
        Account::status_connect => '已交接',
        Account::status_recycle => '已回收',
        Account::status_cancellation => '已作废',
    ];


    /**
     * 基础账号信息
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 检测渠道账号是否已经绑定过这个服务器了
     * @param $channel_id
     * @param $account_id
     * @param $server_id
     * @return array|bool|false|\PDOStatement|string|Model
     */
    public function isHas($channel_id, $server_id, $account_id = 0)
    {
        if (!empty($account_id)) {
            $where['id'] = ['<>', $account_id];
        }
        $where['channel_id'] = ['=', $channel_id];
        $where['server_id'] = ['=', $server_id];
        $result = $this->where($where)->find();
        if (empty($result)) {   //不存在
            return false;
        }
        return $result;
    }

    /**
     * 获取状态名称
     * @param $status
     * @return string
     */
    public function statusName($status)
    {
        $remark = self::STATUS;
        if (isset($remark[$status])) {
            return $remark[$status];
        }
        return '';
    }

    public function company()
    {
        return $this->belongsTo(AccountCompany::class, 'company_id', 'id');
    }
    public function phone()
    {
        return $this->hasOne(Phone::class,'id','phone_id');
    }
    public function server(){
        return $this->hasOne(Server::class,'id','server_id');
    }


    public function getCompanyNameAttr($value, $data)
    {
        if($data['company_id']){
            if($this->company){
                return $this->company->company;
            }
        }
        return '';
    }
}