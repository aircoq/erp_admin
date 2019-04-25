<?php

namespace app\common\model\aliexpress;

use think\Model;
use think\Db;
use think\Exception;
class AliexpressMsgDetail extends Model
{
    protected $autoWriteTimestamp = true;

    //消息类别
    const TYPE_PRODUCT  = 1;    //产品
    const TYPE_ORDER    = 2;    //订单
    const TYPE_MEMBER   = 3;    //
    const TYPE_STORE    = 4;    //

    const MESSAGE_TYPE  = [
        self::TYPE_PRODUCT  => 'product',
        self::TYPE_ORDER    => 'order',
        self::TYPE_MEMBER   => 'member',
        self::TYPE_STORE    => 'store'
    ];
    
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    protected function setMessageTypeAttr($value)
    {
        $message_type = array_flip(self::MESSAGE_TYPE);
        return isset($message_type[$value])?$message_type[$value]:0;
    }
    
}

