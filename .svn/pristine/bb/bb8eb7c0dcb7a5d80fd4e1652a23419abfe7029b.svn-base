<?php
namespace app\common\validate;

use \think\Validate;

/**
 *
 * Date: 2017/02/27
 * Time: 16:32
 */
class ShippingMethod extends Validate
{

    protected $rule = [
        ['carrier_id', 'require|number', '物流商是必填的！！|物流商为整形'],
        ['code', 'require', '物流代码不能为空'],
        ['warehouse_id', 'number|requireNotWith:country_code', '仓库为整形|国家编码为空，仓库为必须'],
        ['country_code', 'requireNotWith:warehouse_id', '仓库为空，国家编码为必须'],
        ['weight', 'require|number', '重量为必须的|重量为整形'],
        ['system_code', 'require|unique:ShippingMethod,carrier_id^system_code', '物流系统代码为必须的|物流系统代码不能重复'],
        ['shortname', 'require', '邮寄方式简称不能为空'],
        ['shipping_method_id', 'require|number', '邮寄方式ID是必填的！！|邮寄方式ID为整形'],
        ['from_shipping_id', 'require|number', '来源邮寄方式ID是必填的！！| 来源邮寄方式ID为整形'],
        ['to_shipping_id', 'require|number', '目标邮寄方式ID是必填的！！|目标邮寄方式ID为整形'],
    ];
    
    protected $scene = [
        'add'  => ['carrier_id', 'code','system_code','shortname'],
        'edit' => ['carrier_id', 'code'],
        'dictionary' => ['warehouse_id'=>'requireNotWith:country_code', 'country_code'],
        'trial'      => ['weight', 'country_code' => 'require|alpha'],
        'lists'      => ['carrier_id'=> 'number', 'warehouse_id' => 'require|number'],
        'copy' => ['from_shipping_id','to_shipping_id'],
        'sequence_number' => ['carrier_id','shipping_method_id']
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