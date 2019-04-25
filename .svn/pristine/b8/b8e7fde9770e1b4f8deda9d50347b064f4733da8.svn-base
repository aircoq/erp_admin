<?php

namespace app\common\model;

use app\common\service\Common;
use app\index\service\AccountCompanyService;
use app\common\cache\Cache;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/11/26
 * Time: 11:47
 */
class AccountCompanyLog extends Model
{
    const add = 0;
    const update = 1;
    const delete = 2;


    const allMeg = [
        'company' =>				'公司名称',
        'company_type' =>				'公司类型',
        'company_registration_number' =>				'公司注册号',
        'corporation' =>				'公司法人',
        'collection_account' =>				'收款账号',
        'credit_card' =>				'信用卡',
        'vat' =>				'VAT（%）',
        'company_time' =>				'公司成立时间',
        'company_address_zip' =>				'公司注册地址',
        'corporation_address_zip' =>				'法人地址邮编',
        'charter_url' =>				'营业执照图片内容',
        'corporation_id_front' =>				'法人身份证正面图片内容',
        'corporation_id_contrary' =>				'法人身份证反面图片内容',
        'corporation_id' =>				'法人身份证号',
        'status' =>				'状态',
        'channel' =>				'允许平台数组',
        'phone' =>				'公司电话',
        'register_time' =>				'公司注册时间',
        'type' =>				'公司类型',
        'source' =>				'资料来源',
        'vat_data'=>            'vat税率',
        'vat_attachment'=>       'vat附件',
        'open_bank_account'=>       '公户帐号',
        'open_date'=>       '开户日期',
        'open_bank'=>       '开户银行',
        'open_licence'=>       '开户许可证',
        'id_date_nd'=>       '身份证有效期结束时间',
        'id_date_st'=>       '身份证有效期起始时间',
        'business_term_st'=>       '营业期限开始时间',
        'business_term_nd'=>       '营业期限结束时间',
        'corporation_identification'=>'法人身份证号',
        'images'=>'图片资料'
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
        $list = (new AccountCompanyLog())->where('company_id',$id)->order('id desc')->select();
        foreach ($list as &$v){
            $v['remark'] = json_decode($v['remark'], true);
        }
        return $list;
    }

    /**
     * 新增日志
     * @param $company_id
     * @param $type
     * @param $newData
     * @param  $oldData
     * @param  $msg
     * @return false|int
     */
    public static function addLog($company_id, $type, $newData=[],$oldData=[],$msg = '')
    {
        $userInfo = Common::getUserInfo();
        $temp['company_id'] = $company_id;
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
        return (new AccountCompanyLog())->allowField(true)->isUpdate(false)->save($temp);
    }

    public static function getRemark($newData,$oldData)
    {
        $remarks = [];
        foreach($newData as $key => $new){
            $remark = '';
             if(isset($oldData[$key]) ){
                 if($oldData[$key] != $new){
                     $remark .= '修改:'. self::getValue($key,$oldData[$key]) .'-->'.self::getValue($key,$new);
                 }
             }elseif($new){
                 $remark .= '增加:'.self::getValue($key,$new);
             }
             if($remark){
                 $remarks[] = "【".self::allMeg[$key]."】" .$remark;
             }
        }
        return $remarks;
    }

    public static function getValue($key,$value)
    {
        switch ($key){
            case 'status':
                $msg = $value == 1 ? '关闭' : '开启';
                break;
            case 'company_time':
                $msg = date('Y-m-d',$value);
                break;
            case 'type':
                $all = AccountCompany::TYPE;
                $msg = $all[$value];
                break;
            case 'source':
                $all = AccountCompany::SOURCE;
                $msg = $all[$value];
                break;
            case 'register_time':
                $msg = date('Y-m-d',$value);
                break;
            case 'channel':
                $msg = '';
                $channel = (new AccountCompanyService())->placeToChannel($value);
                $allName = Cache::store('Channel')->getChannelName(null);
                if(is_array($channel)){
                    foreach ($channel as $v){
                        $msg .=  $allName[$v]. ',';
                    }
                }else{
                    $msg = '全部';
                }
                break;
            case 'vat_data':
                return "略";
                break;
            case 'vat_attachment':
                return "略";
                break;
            default:
                $msg = $value;
        }
        return $msg;
    }
}