<?php

namespace app\common\model;

use app\common\service\Common;
use app\index\service\AccountApplyService;
use think\Cache;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/12/5
 * Time: 11:47
 */
class AccountApplyLog extends Model
{
    const add = 0;
    const update = 1;
    const delete = 2;


    const allMeg = [
        'initiate_time' =>	'发起日期',
        'initiate_man' =>	'负责人id',
        'initiate_man_name' =>	'负责人姓名',
        'channel_id' =>	'渠道id【平台id】',
        'site_code' =>	'站点',
        'breed' =>	'品种',
        'status' =>	'状态',
        'company_id' =>	'所属公司',
        'email' =>	'注册邮箱',
        'email_password' =>	'邮箱密码',
        'account_name' =>	'账号全称【店铺名】',
        'password' =>	'账号密码【店铺密码】',
        'address_billing' =>	'地址账单',
        'grant_receipt' =>	'收款证明',
        'type_msg' =>	'审核失败备注',
        'fulfill_time' =>	'完成日期',
        'account_code' =>	'账号简称',
        'account_show_id' =>	'账号ID',
        'collection_msg' => '收款账号数组',
        'credit_card' => '信用卡',
        'collection_account' => '收款账号',
        'collection_type' => '收款账号平台',
        'collection_email' => '收款账号邮箱',
        'collection_user' => '收款方',
        'phone_id' => '手机号',
        'server_id' => '服务器',
        'register_id' => '注册人',
        'register_ip' => '注册ip'
    ];


    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public static function getLog($id)
    {
        $list = (new AccountApplyLog())->where('apply_id',$id)->order('id desc')->select();
        foreach ($list as &$v){
            $v['remark'] = json_decode($v['remark'], true);
        }
        return $list;
    }

    /**
     * 新增日志
     * @param $apply_id
     * @param $type
     * @param $newData
     * @param  $oldData
     * @param  $msg
     * @return false|int
     */
    public static function addLog($apply_id, $type, $newData=[],$oldData=[],$msg = '')
    {
        $userInfo = Common::getUserInfo();
        $temp['apply_id'] = $apply_id;
        $temp['type'] = $type;
        $temp['operator_id'] = $userInfo['user_id'] ?? 0;
        $temp['operator'] = $userInfo['realname'] ?? '';
        $remark = '';
        unset($newData['update_time']);
        unset($newData['updater_id']);
        unset($newData['create_time']);
        unset($newData['creator_id']);
        switch ($type) {
            case self::add:
                $remark = self::getRemark($newData,$oldData);
                break;
            case self::update:
                $remark = self::getRemark($newData,$oldData);
                break;
            case self::delete:
                $remark[] = '删除资料';
                break;
        }
        if($msg){
            $remark[] = $msg;
        }
        if(!$remark){
            return false;
        }
        $temp['remark'] = json_encode($remark,JSON_UNESCAPED_UNICODE);
        $temp['data'] = json_encode($newData, JSON_UNESCAPED_UNICODE);
        $temp['create_time'] = time();
        return (new AccountApplyLog())->allowField(true)->isUpdate(false)->save($temp);
    }

    public static function getRemark($newData,$oldData)
    {
        $remarks = [];
        foreach($newData as $key => $new){
            $remark = '';
            if($key == 'collection_msg') {
                $newData[$key] = json_decode($newData[$key], true);
                if(isset($oldData[$key]) ) {
                    $oldData[$key] = json_decode($oldData[$key] , true);
                }
            }
            if(isset($oldData[$key]) ){

                if($oldData[$key] != $newData[$key]){
                    $remark .= '修改:'. self::getValue($key,$oldData[$key]) .'-->'.self::getValue($key,$newData[$key]);
                }
            }elseif($new) {
                $remark .= '增加:' . self::getValue($key, $newData[$key]);
            }
            if($remark){
                $remarks[] = "【".self::allMeg[$key]."】" .$remark;
            }

        }
        return $remarks;
    }

    public static function getValue($key,$vlave)
    {
        $msg = '';
        if(!$vlave){
            return $msg;
        }
        switch ($key){
            case 'status':
                $allStatus = AccountApply::STATUS;
                $msg = $allStatus[$vlave] ?? '';
                break;
            case 'fulfill_time':
                $msg = self::showTime($vlave);
                break;
            case 'initiate_time':
                $msg = self::showTime($vlave);
                break;
            case 'company_id':
                $msg = (new AccountCompany())->where('id' ,$vlave)->value('company') ?? $vlave;
                break;
            case 'email_id':
                $msg = (new Email())->where('id' ,$vlave)->value('email') ?? $vlave;
                break;
            case 'phone_id':
                $msg = (new Phone())->where('id' ,$vlave)->value('phone') ?? $vlave;
                break;
            case 'collection_msg':
                $msg = self::getCollectionMsg($vlave);
                break;
            case 'password' :
            case 'email_password':
                $msg = '***';
                break;
            default:
                $msg = $vlave;
        }
        return $msg;
    }

    public static function getCollectionMsg($vlave){
        $msg = '';
        if(!$vlave){
            return $msg;
        }
        $allMeg = [
            'collection_account' => '收款账号',
            'collection_type' => '收款账号平台',
            'collection_email' => '收款账号邮箱',
            'collection_user' => '收款方',
        ];
        foreach ($vlave as $k1=>$v1){
            if($k1 == 0){
                $k1 = '';
            }
            foreach ($v1 as $k=>$v) {
                if($v){
                    $msg .= "(" . $allMeg[$k] . $k1 . ")" . $v;
                }
            }
        }
        return $msg;
    }

    public static function showTime($time)
    {
        $msg = 0;
        if($time){
            $msg = date('Y-m-d H:i:s',$time);
        }
        return $msg;
    }


}