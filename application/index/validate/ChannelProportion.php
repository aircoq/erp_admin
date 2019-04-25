<?php


namespace app\index\validate;


use think\Validate;

class ChannelProportion extends Validate
{
    protected $rule = [
        'channel_id'=>'require',
        'department_id'=>'require',
        'product_proportion'=>'require',
        'profit_in'=>'require',
        'profit_out'=>'require',
        'product_count'=>'require',
        'create_time'=>'require',
        'create_id'=>'require',
        'update_time'=>'require',
        'update_id'=>'require'
    ];

    protected $message = [
        'channel_id.require'=>'渠道id不能为空',
        'channel_id.number'=>'渠道id为整型',
        'department_id.require'=>'部门id不能为空',
        'department_id.number'=>'部门id为整型',
        'product_proportion.require'=>'产品数占比不能为空',
        'product_proportion.number'=>'产品数占比为整型',
        'profit_in.require'=>'预计利润率本部不能为空',
        'profit_in.number'=>'预计利润率本部为整型',
        'profit_out.require'=>'预计利润率外部不能为空',
        'profit_out.number'=>'预计利润率外部为整型',
        'product_count.require'=>'随机抽取产品数不能为空',
        'product_count.number'=>'随机抽取产品数为整型',
        'create_time.require'=>'创建时间不能为空',
        'create_time.number'=>'创建时间为整型',
        'create_id.require'=>'创建人不能为空',
        'create_id.number'=>'创建人为整型',
        'update_time.require'=>'更新时间不能为空',
        'update_time.number'=>'更新时间为整型',
        'update_id.require'=>'更新人d不能为空',
        'update_id.number'=>'更新人为整型',
    ];

    protected $scene = [
        'insert'=>[
            'channel_id'=>'require|number',
            'department_id'=>'require|number',
            'product_proportion'=>'require|number',
            'profit_in'=>'require|number',
            'profit_out'=>'require|number',
            'product_count'=>'require|number',
            'create_time'=>'require|number',
            'create_id'=>'require|number'
        ],
        'update'=>[
            'channel_id'=>'number',
            'department_id'=>'number',
            'product_proportion'=>'number',
            'profit_in'=>'number',
            'profit_out'=>'number',
            'product_count'=>'number',
            'update_time'=>'require|number',
            'update_id'=>'require|number'
        ]

    ];

}