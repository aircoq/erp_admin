<?php


namespace app\index\validate;


use think\Validate;

class RegisterCompany extends Validate
{
    protected $rule = [
        'corporation' => 'require',
        'corporation_identification' => 'require',
        'id_date_st' => 'require',
        'id_date_nd' => 'require',
        'corporation_id_front' => 'require',
        'corporation_id_contrary' => 'require',
        'corporation_address_zip' => 'require',
        'ic_agent' => 'require',
        'legal_remark' => 'require',
        'type' => 'require',
        'source' => 'require',
        'company' => 'require',
        'company_registration_number' => 'require',
        'business_term_st' => 'require',
        'business_term_nd' => 'require',
        'company_time' => 'require',
        'company_address_zip' => 'require',
        'charter_url' => 'require',
        'corporate_settlement' => 'require',
        'agency_settlement' => 'require',
        'has_seal' => 'require',
        'settlement_remark' => 'require',
        'status' => 'require',
        'creator_id' => 'require',
        'create_time' => 'require',
        'updater_id' => 'require',
        'update_time' => 'require'
    ];
    protected $message = [
        'corporation.require' => '公司法人为必填项',
        'corporation_identification.require' => '法人身份证号为必填项',
        'id_date_st.require' => '身份证有效期起始日期为必填项',
        'id_date_nd.require' => '身份证有效期结束日期为必填项',
        'corporation_id_front.require' => '身份证正面照为必填项',
        'corporation_id_contrary.require' => '身份证背面照为必填项',
        'ic_agent.require' => '工商代理为必填项',
        'creator_id.require' => '创建人不能为空',
        'create_time.require' => '创建时间不能为空',
        'updater_id.require' => '更新人不能为空',
        'update_time.require' => '更新时间不能为空',
        'type.require' => '公司类型为必填项',
        'company.require' => '公司名称为必填项',
        'company.unique' => '公司名称已存在',
        'charter_url.require' => '营业执照为必填项',
        'corporate_settlement.require' => '法人结账为必填项',
        'agency_settlement.require' => '代理结账为必填项',
        'has_seal.require' => '是否有公章为必填项',
    ];
    protected $scene = [
        'add_legalInfo' => [
            'corporation' => 'require',
            'corporation_identification' => 'require',
            'id_date_st' => 'require',
            'corporation_id_front' => 'require',
            'corporation_id_contrary' => 'require',
            'ic_agent' => 'require',
            'creator_id' => 'require',
            'create_time' => 'require'
        ],
        'update_legal' => [
            'updater_id' => 'require',
            'update_time' => 'require'
        ],
        'add_company' => [
            'type' => 'require',
            'company' => 'require',
            'updater_id' => 'require',
            'update_time' => 'require'
        ],
        'saveCharter' => [
            'charter_url' => 'require',
            'updater_id' => 'require',
            'update_time' => 'require',
        ],
        'wait_settle' => [
            'corporate_settlement' => 'require',
            'agency_settlement' => 'require',
            'has_seal' => 'require'
        ]

    ];

}