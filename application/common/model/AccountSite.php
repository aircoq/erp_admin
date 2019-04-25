<?php

namespace app\common\model;

use app\common\model\AccountCompany;
use app\common\service\Common;
use think\Model;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/4/9
 * Time: 17:46
 */
class AccountSite extends Model
{


    //账号状态 0 未分配,1 运营中,2 回收中,3 冻结中,4 申诉中,5已回收 ，6已作废
    const status_not_allocated = 0;
    const status_in_operation = 1;
    const status_in_recovery = 2;
    const status_frozen = 3;
    const status_in_appeal = 4;
    const status_recycle = 5;
    const status_cancellation = 6;

    const STATUS = [
        AccountSite::status_not_allocated => '未分配',
        AccountSite::status_in_operation => '运营中',
        AccountSite::status_in_recovery => '回收中',
        AccountSite::status_frozen => '冻结中',
        AccountSite::status_in_appeal => '申诉中',
        AccountSite::status_recycle => '已回收',
        AccountSite::status_cancellation => '已作废',
    ];


    /**
     * 基础账号信息
     */
    protected function initialize()
    {
        parent::initialize();
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

    public function getList($id, $field = '*')
    {
        $where = [
            'base_account_id' => $id,
        ];
        return $this->where($where)->field($field)->select();
    }

    public function add($data,$userId = 0,$old = [])
    {
        if(!$userId){
            $user = Common::getUserInfo();
            $userId = $user['user_id'] ?? 0;
        }
        if(!$old){
            $old = $this->isHas($data);
        }
        if ($old) {
            if($old['site_status'] == $data['site_status']){
                return false;
            }
            $save = [
                'update_time' => time(),
                'updater_id' => $userId,
                'site_status' => $data['site_status'],
            ];
            $this->save($save, ['id' => $old['id']]);
            return $old;
        }
        $data['creator_id'] = $userId;
        $data['create_time'] = time();
        (new AccountSite())->allowField(true)->isUpdate(false)->save($data);
        return false;
    }

    public function isHas($data)
    {
        $where = [
            'base_account_id' => $data['base_account_id'],
            'account_code' => $data['account_code'],
        ];
        return $this->where($where)->find();
    }

}