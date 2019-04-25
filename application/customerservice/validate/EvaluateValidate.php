<?php
namespace app\customerservice\validate;

use think\Validate;

class EvaluateValidate extends Validate
{   
    protected $rule = [
        ['id','require'],
        ['ids','require'],
        ['score','require|in:1,2,3,4,5'],
        ['content','require|max:1000'],
        ['order_id','require'],
        ['is_random','require|in:0,1'],
        ['tmp_id','require'],
    ];
    
    protected $message = [
        'score'  => ['require' => '评分必须', 'in' => '无效的分值'],
        'id' => 'ID必须',
        'content' => ['require' => '评价内容必须', 'max' => '评价内容长度不能超过1000'],
        'order_id' => '订单ID必须',
    ];

    protected $scene = [
        'evaluate'  => ['id','score','content'],
        'append'    => ['id','content'],
        'all'       => ['score','content'],
        'batch'     => ['ids','score','content'],
        'tmp1'      => ['order_id','is_random'],   //随机获取模板内容
        'tmp2'      => ['order_id','is_random','tmp_id'],   //指定获取模板内容
        'evaluate_order'=>['order_id','content','score'],
    ];
}