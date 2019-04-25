<?php

/**
 * Description of EbayCommonTrans
 * @datetime 2017-6-27  13:52:55
 * @author joy
 */

namespace app\common\model\ebay;
use app\common\model\AutoCompleteModel;
use think\Model;
class EbayCommonTrans extends AutoCompleteModel
{
    protected $autoWriteTimestamp = true;//自动写入时间戳
    protected $createId = 'creator_id';//创建人id字段名
    protected $updateId = 'updator_id';//更新人id字段名

    public  function initialize() {
        parent::initialize();
    }
    public  function detail()
    {
        return $this->hasMany(EbayCommonTransDetail::class, 'trans_id', 'id');
    }
}
