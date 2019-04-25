<?php

/**
 * Description of WishHistoryHealthData
 * @datetime 2017-7-12  11:56:51
 * @author joy
 */

namespace app\common\model\wish;
use think\Model;
class WishHistoryHealthData extends Model{
    public  function initialize() {
        parent::initialize();
    }
    public  function account()
    {
        return $this->hasOne(WishAccount::class, 'id', 'account_id', [], 'LEFT');
    }
}
