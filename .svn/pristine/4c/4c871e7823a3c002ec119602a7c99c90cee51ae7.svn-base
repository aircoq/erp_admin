<?php
namespace app\customerservice\validate;

use think\Validate;

class AliInboxValidate extends Validate
{   
    protected $rule = [
        ['channel_id','require'],
        ['content','require'],
        ['id','require'],
        ['rank','require|in:0,1,2,3,4,5'],
        ['level','require|in:1,2,3,4,5,6,7'],
        ['code','require'],
        ['type','require'],
        ['order_id','require'],
    ];
    
    protected $message = [
        'channel_id'    => ['require' => '通道ID必须'],
        'content'       => ['require' => '消息内容必须'],
        'id'            => 'ID必须',
        'rank'          => ['require' => '评分必须', 'in' => '无效的标签值'],
        'level'         => ['require' => '评分必须', 'in' => '无效的level值'],
        'code'          => 'code必须',
        'type'          => 'type必须',
        'order_id'      => ['require' => '订单ID必须'],
    ];

    protected $scene = [
        'replay'  => ['channel_id','content'],
        'rank'    => ['id','rank'],
        'level'   => ['id','level'],
        'tmp'     => ['id','code','type'],   //获取模板内容
        'order_replay' => ['order_id','content'],
    ];
}