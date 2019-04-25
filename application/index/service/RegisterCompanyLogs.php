<?php


namespace app\index\service;

use app\common\cache\Cache;
use app\common\model\RegisterCompanyLogs as Model;

class RegisterCompanyLogs extends BaseLog
{
    protected $fields = [
        'corporation' => '法人',
        'corporation_identification' => '法人身份证号',
        'id_date_st' => '身份证有效期起始时间',
        'id_date_nd' => '身份证有效期结束时间',
        'corporation_id_front' => '身份证正面照',
        'corporation_id_contrary' => '身份证背面照片',
        'corporation_address_zip' => '法人地址+邮编',
        'ic_agent' => '工商代理',
        'legal_remark' => '法人信息备注',
        'type' => '公司类型',
        'source' => '资料来源',
        'company' => '公司名称',
        'company_registration_number' => '公司注册号',
        'business_term_st' => '营业期限开始时间',
        'business_term_nd' => '营业期限结束时间',
        'company_time' => '公司成立时间',
        'company_address_zip' => '公司注册地址+邮编',
        'charter_url' => '营业执照地址',
        'corporate_settlement' => '法人结算',
        'agency_settlement' => '代理结账',
        'has_seal' => '是否有公章',
        'settlement_remark' => '结账备注',
        'status' => '状态'
    ];

    public function __construct()
    {
        $this->model = new Model();
    }

    protected $tableField = [
        'id' => 'reg_company_id',
        'remark' => 'remark',
        'operator_id' => 'operator_id',
        'operator' => 'operator'
    ];

    public function add($name)
    {
        $list = [];
        $list['type'] = '用户';
        $list['val'] = $name;
        $list['data'] = [];
        $list['exec'] = 'add';
        $this->LogData[] = $list;
        return $this;
    }

    public function saveLegalInfo($remark = '')
    {
        if($remark){
            $remark = ','.$remark;
        }
        $list = [];
        $list['type'] = '法人信息';
        $list['val'] = $remark;
        $list['data'] = [];
        $list['exec'] = 'save';
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

    public function submitLegalInfo($remark='')
    {
        if($remark){
            $remark = ','.$remark;
        }
        $list = [];
        $list['type'] = '给工商代理审批';
        $list['val'] = $remark;
        $list['data'] = [];
        $list['exec'] = 'submit';
        $this->LogData[] = $list;
        return $this;
    }

    public function invalid($val = '')
    {
        $list = [];
        $list['type'] = '';
        $list['val'] = $val;
        $list['data'] = [];
        $list['exec'] = 'invalid';
        $this->LogData[] = $list;
        return $this;
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

    public function agree_ic_agent()
    {
        $list = [];
        $list['type'] = '工商代理';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = 'agree';
        $this->LogData[] = $list;
    }

    public function disagree_ic_agent($val)
    {
        $list = [];
        $list['type'] = '工商代理';
        $list['val'] = '未通过原因:' . $val;
        $list['data'] = [];
        $list['exec'] = 'disagree';
        $this->LogData[] = $list;
    }

    public function saveCompanyInfo($remark = '')
    {
        if($remark){
            $remark = ','.$remark;
        }
        $list = [];
        $list['type'] = '公司信息';
        $list['val'] = $remark;
        $list['data'] = [];
        $list['exec'] = 'save';
        $this->LogData[] = $list;
        return $this;
    }

    public function disagree_licence($val)
    {
        $list = [];
        $list['type'] = '待批执照';
        $list['val'] = '未通过原因:' . $val;
        $list['data'] = [];
        $list['exec'] = 'disagree';
        $this->LogData[] = $list;
    }

    public function pullCompanyData()
    {
        $list = [];
        $list['type'] = '公司资料库';
        $list['val'] = '';
        $list['data'] = [];
        $list['exec'] = '推送到';
        $this->LogData[] = $list;
    }

    public function waitSettle($remark='')
    {
        if($remark){
            $remark = ','.$remark;
        }
        $list = [];
        $list['type'] = '';
        $list['val'] = $remark;
        $list['data'] = [];
        $list['exec'] = '待领取公章';
        $this->LogData[] = $list;
    }

    public function finish($remark)
    {
        if($remark){
            $remark = ','.$remark;
        }
        $list = [];
        $list['type'] = '';
        $list['val'] = $remark;
        $list['data'] = [];
        $list['exec'] = '注册完成';
        $this->LogData[] = $list;
    }

    public function saveSettlement($remark = '')
    {
        if($remark){
            $remark = ','.$remark;
        }
        $list = [];
        $list['type'] = '结账信息';
        $list['val'] = $remark;
        $list['data'] = [];
        $list['exec'] = 'save';
        $this->LogData[] = $list;
        return $this;
    }
}