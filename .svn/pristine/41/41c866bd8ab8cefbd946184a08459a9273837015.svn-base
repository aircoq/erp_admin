<?php


namespace app\index\service;

use app\common\model\AccountApplyLog as Model;

class AccountApplyLog extends BaseLog
{
    protected $fields = [
        'channel_id' => '平台',
        'status' => '状态',
        'phone_id' => '手机',
        'creator_id' => '创建人',
        'updater_id' => '更新人',
        'register_id' => '注册人',
        'register_time' => '注册时间',
        'register_ip' => '注册ip',
        'fulfill_time' => '完成时间',
        'company_id' => '所属公司',
        'server_id' => '服务器',
        'audit_data' => '审核资料',
        'is_kyc' => '是否触发',
        'main_shop' => 'ebay主店铺',
        'shop_category' => '店铺品类',
        'remark' => '备注',
        'reg_result' => '注册结果',
        'account_name' => '帐号全称',
        'password' => '密码',
        'shop_name' => '店铺名',
        'account_code' => '帐号简称',
        'site_code' => '所属站点',
        'email_id' => '邮箱地址',
        'credit_card_id' => '信用卡',
        'collection_account' => '收款帐号',
        'collection_type' => '收款平台',
        'collection_email' => '收款帐号邮箱',
        'collection_name' => '收款方'
    ];

    public function __construct()
    {
        $this->model = new Model();
    }

    protected $tableField = [
        'id' => 'apply_id',
        'remark' => 'remark',
        'operator_id' => 'operator_id',
        'operator' => 'operator'
    ];

    public function add($remark = '')
    {
        if ($remark) {
            $remark = ',' . $remark;
        }
        $list = [];
        $list['type'] = '帐号注册资料';
        $list['val'] = $remark;
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function addDetail($name)
    {

        $list = [];
        $list['type'] = '帐号';
        $list['val'] = $name;
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function addCollection($name)
    {
        $list = [];
        $list['type'] = '收款帐户';
        $list['val'] = $name;
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function mdfDetail($name, $old, $new)
    {
        $name = "账号信息:".$name;
        return $this->mdf($name, $old, $new);
    }

    public function mdfCollection($name, $old, $new)
    {
        $name = "账号信息:".$name;
        return $this->mdf($name, $old, $new);
    }

    public function delDetail($name)
    {
        $list = [];
        $list['type'] = '帐号';
        $list['val'] = $name;
        $list['data'] = [];
        $list['exec'] = 'del';
        $this->LogData[] = $list;
        return $this;
    }

    public function submitAudit($remark)
    {
        if ($remark) {
            $remark = '备注：' . $remark;
        }
        $list = [];
        $list['type'] = '审核';
        $list['val'] = $remark;
        $list['data'] = [];
        $list['exec'] = 'submit';
        $this->LogData[] = $list;
        return $this;
    }

    public function submitAndPush($remark)
    {
        if ($remark) {
            $remark = '备注：' . $remark;
        }
        $list = [];
        $list['type'] = '审核,并推送到帐号资料';
        $list['val'] = $remark;
        $list['data'] = [];
        $list['exec'] = 'submit';
        $this->LogData[] = $list;
        return $this;
    }

    public function successPush()
    {
        $list = [];
        $list['type'] = '';
        $list['val'] = ',推送到帐号基础资料';
        $list['data'] = [];
        $list['exec'] = '注册成功';
        $this->LogData[] = $list;
        return $this;
    }

    public function success()
    {
        $list = [];
        $list['type'] = '';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = '注册成功';
        $this->LogData[] = $list;
        return $this;
    }

    public function cancel($remark){
        $list = [];
        $list['type'] = '';
        $list['val'] = $remark;
        $list['data'] = [];
        $list['exec'] = 'invalid';
        $this->LogData[] = $list;
        return $this;
    }

    public function submitRegister()
    {
        $list = [];
        $list['type'] = '注册信息';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'submit';
        $this->LogData[] = $list;
        return $this;
    }

    public function saveRegister()
    {
        $list = [];
        $list['type'] = '注册信息';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'save';
        $this->LogData[] = $list;
        return $this;
    }

    public function delCollection($name)
    {
        $list = [];
        $list['type'] = '收款账户';
        $list['val'] = $name;
        $list['data'] = [];
        $list['exec'] = 'del';
        $this->LogData[] = $list;
        return $this;
    }


    public function mdf($name, $old, $new)
    {
        $data = $this->mdfData($old, $new);
        $info = [];
        foreach ($data as $key) {
            $row = [];
            $row['old'] = $old[$key];
            $row['new'] = $new[$key];
            $info[$key] = $row;
        }
        $this->mdfItem($name, $info);
        return $this;
    }

    protected function mdfData($old, $new)
    {
        $data = [];
        foreach ($new as $key => $v) {
            if (in_array($key, array_keys($this->fields))) {
                if ($v != $old[$key]) {
                    $data[] = $key;
                }
            }
        }
        return $data;
    }

    protected function mdfItem($name, $info)
    {
        $list = [];
        $list['type'] = $name;
        $list['val'] = '';
        $list['data'] = $info;
        $list['exec'] = 'mdf';
        $this->LogData[] = $list;
    }

}