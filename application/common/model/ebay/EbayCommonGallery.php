<?php

/**
 * Description of EbayCommonGallery
 * @datetime 2017-6-27  14:20:12
 * @author joy
 */

namespace app\common\model\ebay;
use app\common\model\AutoCompleteModel;
use think\Model;
class EbayCommonGallery extends AutoCompleteModel
{
    protected $autoWriteTimestamp = true;//自动写入时间戳
    protected $createId = 'creator_id';//创建人id字段名
    protected $updateId = 'updator_id';//更新人id字段名

    public  function initialize() {
        parent::initialize();
    }
}
