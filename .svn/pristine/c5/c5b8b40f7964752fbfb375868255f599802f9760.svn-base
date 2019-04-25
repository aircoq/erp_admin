<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/5
 * Time: 9:49
 */

namespace app\common\model;

use think\Model;

class SupplierActionLog extends Model
{
    const FIELDS = [
        'account_name' => '开户名,',
        'company_name' => '公司名,',
        'code' => '代码,',
        'level' => '供应商等级,',
        'type' => '企业形式,',
        'invoice' => '发票类型,',
        'legal' => '法人代表,',
        'system_name' => '系统名称,',
        'business_license' => '营业执照名称,',
//        'business_code' => '营业执照编号,',
        'balance_type' => '结算方式,',
        'balance_remark' => '结算方式备注,',
        'introduce' => '介绍,',
        'pay_type' => '支付方式,',
        'transaction_type' => '交易类型,',
        'bank' => '开户行,',
        'bank_account' => '开户行账号,',
        'paypal' => 'PayPal帐号,',
        'alipay' => '支付宝账号,',
        'contacts2' => '联系人2,',
        'contacts2_job' => '联系人2的职务,',
        'contacts_job' => '联系人1职务,',
        'contacts' => '联系人1,',
        'email' => '邮箱,',
        'province_id' => '省份,',
        'city_id' => '城市,',
        'area_id' => '地区,',
        'address' => '详细地址,',
        'zipcode' => '邮编,',
        'tel' => '固定电话,',
        'qq' => 'QQ1,',
        'qq2' => 'QQ2,',
        'mobile' => '联系电话,',
        'mobile2' => '联系人2联系电话,',
        'ww' => '阿里旺旺账号,',
        'weixin' => '微信,',
        'remark' => '备注,',
        'link' => '供应商链接,',
        'auto_payment_request' => '自动生成付款申请单,',
//        'source' => '来源,',
        'purchaser_id' => '采购员,',
        'public_accounts' => '对公账号,',
        'public_accounts_name' => '户名(对公),',
        'public_accounts_bank' => '对公账号开户行,',
        'public_bank_city' => '开户行所在市(对公),',
        'public_bank_retained_contact_way' => '银行留存联系方式(对公),',
        'private_accounts' => '对私账号,',
        'private_accounts_name' => '对私户名,',
        'private_accounts_bank' => '对私账号开户行,',
        'new_reason' => '新增理由,',
        'categorys' => '商品分类,',
        'opening_bank_city' => '开户行所在市(对私),',
        'opening_id_card' => '开户身份证号(对私),',
        'online_shop_name' => '网上店铺全称,',
        'bank_retained_contact_way' => '银行留存联系方式(对私),',
        'payment_effective_time' => '账期生效日期,',
        'payment_communicator' => '谈账期人,',
        'supply_chain_specialist_id' => '供应链专员,',
        'delivery_day' => '交货时间,',
        'public_bank_address' => '开户行支行具体地址(对公),',
        'public_swift_address' => 'SWIFT ADDRESS(对公),',
        'public_cnaps' => 'CNAPS(对公),',
        'private_bank_address' => '开户支行具体地址(对私),',
        'private_swift_address' => 'SWIFT ADDRESS(对私),',
        'private_cnaps' => 'CNAPS(对私),',
        'default_payment_method' => '默认付款方式,',
        'ali_account' => '阿里账号,',
    ];
    /**
     * 操作类型获取器
     *
     * @param $value
     *
     * @return string
     */
    public function getStatusAttr($value)
    {
        if (empty($value)) return '';
        $type = [
            1 => '新增',
            2 => '编辑',
            3 => '审核',
            4 => '停用',
            5 => '删除',
            6 => '变更',
        ];
        return $type[$value];
    }

    /** 创建时间获取器
     *
     * @param $value
     *
     * @return string
     */
    public function getCreateTimeAttr($value)
    {
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', $value);
        }
        return $value;
    }

    /** 更新时间获取器
     *
     * @param $value
     *
     * @return string
     */
    public function getUpdateTimeAttr($value)
    {
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', $value);
        }
        return $value;
    }
}
