<?php
namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/1/26
 * Time: 14:46
 */
class AccountPhoneHistory extends Model
{

    /**
     * 基础账号信息
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public static function add($accountId,$phone)
    {
        if(!$accountId || !$phone){
            return false;
        }
        $save = [
            'account_id' => $accountId,
            'phone' => $phone,
            'create_time' => time(),
        ];
        return (new AccountPhoneHistory())->isUpdate(false)->save($save);
    }

    public static function getLog($accountId)
    {
        $log =  (new AccountPhoneHistory())->where('account_id',$accountId)->order('id desc')->column('phone');
        return $log ? $log : [];
    }

}