<?php
namespace app\common\model;

use erp\ErpModel;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class OrderAddress extends ErpModel
{

    /**
     * 订单
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
        $this->query('set names utf8mb4');
    }

    /** 地址id转字符串
     * @param $value
     * @return string
     */
    public function getIdAttr($value)
    {
        if(is_numeric($value)){
            $value = $value.'';
        }
        return $value;
    }

    /** 获取字段名称
     * @param $field
     * @return string
     */
    public function fieldName($field)
    {
        $fieldName = [
            'consignee' => '收货人',
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