<?php

/**
 * Description of AliexpressWindow
 * @datetime 2017-7-5  15:35:55
 * @author joy
 */

namespace app\common\model\aliexpress;
use think\Model;
class AliexpressWindow extends Model{
    protected $resultSetType = 'collection';
    public  function initialize() {
        parent::initialize();
    }
    public  function account()
    {
        return $this->hasOne(AliexpressAccount::class,'id','account_id');
    }
    public function user()
    {
        return $this->hasManyThrough(\app\common\model\User::class, \app\common\model\ChannelUserAccountMap::class, 'account_id', 'seller_id', 'id');
    }
}
