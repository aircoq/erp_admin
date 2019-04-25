<?php
namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/15
 * Time: 14:07
 */
class InvoiceAddress extends Model
{
    /**
     * 订单发票
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 获取字段名称
     * @param $field
     * @return string
     */
    public function fieldName($field)
    {
        $fieldName = [
            'recipient' => '收件人名称',
            'country_code' => '国家编码',
            'area_info' => '地区信息',
            'city' => '城市名称',
            'province' => '省/州名称',
            'address' => '详情地址',
            'address2' => '详情地址2',
            'zipcode' => '邮编',
            'tel' => '电话',
            'mobile' => '手机',
            'email' => '邮箱'
        ];
        if(isset($fieldName[$field])){
            return $fieldName[$field];
        }
        return '';
    }
}