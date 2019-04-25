<?php

/**
 * Description of EbayModelPromotion
 * @datetime 2017-6-27  14:12:43
 * @author joy
 */

namespace app\common\model\ebay;
use app\common\model\AutoCompleteModel;
use app\common\traits\Account;
use app\index\service\EbayAccountService;
use think\Model;
class EbayModelPromotion extends AutoCompleteModel {
    protected $autoWriteTimestamp = true;
    protected $createId = 'creator_id';
    protected $updateId = 'updator_id';
    public  function initialize() {
        parent::initialize();
    }
    public function account()
    {
        return $this->hasOne(EbayAccount::class,'id','ebay_account');
    }

    public function setStartDateAttr($value)
    {
        if (strpos((string)$value,'-') !== false) {
            return strtotime($value);
        }
        return $value;
    }

    public function setEndDateAttr($value)
    {
        if (strpos((string)$value,'-') !== false) {
            return strtotime($value);
        }
        return $value;
    }
}
