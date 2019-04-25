<?php

/**
 * Description of AliexpressWindow
 * @datetime 2017-7-5  15:35:55
 * @author joy
 */

namespace app\common\model\aliexpress;
use app\common\traits\ModelFilter;
use erp\ErpModel;

class AliexpressAccountHealthList extends ErpModel
{

    use ModelFilter;

    public function scopeOrder(\erp\ErpQuery $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.account_id', 'in', $params);
        }
    }

    protected $resultSetType = 'collection';

    public  function initialize() {
        parent::initialize();
    }

    public  function account()
    {
        return $this->hasOne(AliexpressAccount::class,'id','account_id');
    }
}
