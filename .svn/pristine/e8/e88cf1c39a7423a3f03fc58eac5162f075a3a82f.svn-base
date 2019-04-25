<?php
namespace app\common\validate;

use \think\Validate;

/**
 *
 * Date: 2018/12/21
 * Time: 16:32
 */
class ShippingAddress extends Validate
{

    protected $rule = [
        ['type', 'require|number', '类型是必填的！！|类型为整形'],
        ['name', 'require', '名称是必填的！'],
        ['company', 'require', '公司是必填的'],
        ['country', 'require', '国家是必填的'],
        ['state', 'require', '省州是必填的'],
        ['city', 'require', '城市是必填的'],
        ['district', 'require', '地区是必填的'],
        ['street', 'require', '街道是必填的'],
        ['phone', 'require', '电话是必填的'],
        ['mobile', 'require', '手机是必填的'],
        ['email', 'require', '邮件是必填的'],
        ['address_name','require','地址信息是必填的']

    ];
    protected $scene = [
        'save'  => ['address_name'],
        'update' => ['id'],
        'read' => [ 'id'],
    ];

    
    /**
     * 验证某个字段没有值的情况下必须
     * @access protected
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则
     * @param array     $data  数据
     * @return bool
     */
    protected function requireNotWith($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        if (empty($val)) {
            return !empty($value);
        } else {
            return true;
        }
    }

}