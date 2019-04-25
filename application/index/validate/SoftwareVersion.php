<?php


namespace app\index\validate;


use think\Validate;

class SoftwareVersion extends Validate
{
    protected $rule = [
        'version' => 'require',
        'remark' => 'require',
        'status' => 'require',
        'creator_id' => 'require',
        'create_time' => 'require',
        'upgrade_address' => 'require'
    ];
    protected $message =[
        'version.require'=>'版本号是必须的',
        'remark.require'=>'备注不能为空',
        'status.require'=>'状态不能为空',
        'status.in'=>'状态的值仅为0,1',
        'creator_id.require'=>'创建人不能为空',
        'create_time.require'=>'创建时间不能为空',
        'upgrade_address.require'=>'更新地址不能为空',

    ];

    protected $scene = [
        'insert'=>[
            'version'=>'require',
            'remark'=>'require',
            'status'=>'require|in:0,1',
            'upgrade_address'=>'require',
            'creator_id'=>'require',
            'create_time'=>'require',
            'upgrade_address'=>'require'
        ]
    ];


}