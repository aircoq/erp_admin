<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-3-12
 * Time: 上午10:45
 */

namespace app\publish\validate;


use think\Validate;

class CollectValidate extends Validate
{
    protected $rule=[
        'id'=>'require|integer|gt:0',
        'account_id'=>'require',
        'goods_id'=>'require|integer|gt:0',
        'channel_id'=>'require|integer|gt:0',
        'claim_channel'=>'require'
    ];
    protected $message=[
        'account_id.require'=>'账号account_id必须',
        'account_id.array'=>'账号account_id数据类型为数组',

        'id.require'=>'采集商品id必须',
        'id.gt'=>'采集商品id大于0',
        'id.integer'=>'采集商品id只能是整数',

        'goods_id.require'=>'关联商品goods_id必须',
        'goods_id.gt'=>'关联商品goods_id必须大于0',
        'goods_id.integer'=>'关联商品goods_id只能是整数',

        'channel_id.require'=>'认领平台channel_id必须',
        'channel_id.gt'=>'认领平台channel_id必须大于0',
        'channel_id.integer'=>'认领平台channel_id只能是整数',
    ];
    protected $scene=[
        'claim'=>['id','goods_id','account_id','channel_id'],
    ];
    public function claim($params)
    {
        $this->check($params,$this->rule,'claim');
        if($this->error)
        {
            return $this->error;
        }
    }
}