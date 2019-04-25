<?php


namespace app\index\validate;


use think\Validate;
use app\common\model\Phone as Model;

class Phone extends Validate
{
    protected $rule = [
        'id' => 'require',
        'phone' => 'require',
        'operator' => 'require',
        'creator_id' => 'require',
        'create_time' => 'require',
        'status' => 'require',
        'reg_id' => 'require',
        'reg_time' => 'require',
    ];
    protected $message = [
        'id.require' => 'id不能为空',
        'phone.require' => '手机号码不能为空',
        'phone.unique' => '手机号码已存在',
        'operator.require' => '运营商不能为空',
        'operator.number' => '运营商类型为整形',
        'creator_id.require' => '创建人不能为空',
        'creator_id.number' => '创建人类型为整形',
        'create_time.require' => '创建时间不能为空',
        'create_time.number' => '创建时间类型为整形',
        'status.require' => '状态不能为空',
        'status.number' => '状态类型为整形',
        'reg_id.require' => '注册人不能为空',
        'reg_id.number' => '注册人类型为整形',
        'reg_time.require' => '注册时间不能为空',
        'reg_time.number' => '注册时间类型为整形',
        'operator.in'=>'运营商只能为中国移动、联通、或电信',
        'status.in'=>'状态只能为启用和停用',
    ];

    protected $scene = [
        'insert'=>[
            'phone'=>'require|unique:Phone,phone',
            'operator'=>'require|number|in:1,2,3' ,
            'status'=>'require|in:0,1',
            'creator_id'=>'require|number',
            'create_time'=>'require|number',
            'reg_id'=>'require|number',
            'reg_time'=>'require|number'
        ],
        'update'=>[
            'phone'=>'unique:Phone,phone',
            'operator'=>'number|in:1,2,3',
            'reg_id'=>'number',
            'reg_time'=>'number',
            'status'=>'number|in:1,0',
        ]
    ];
}