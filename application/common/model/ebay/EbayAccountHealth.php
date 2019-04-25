<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/7/21
 * Time: 16:23
 */

namespace app\common\model\ebay;


use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;

class EbayAccountHealth extends ErpModel
{
    use ModelFilter;
    public function scopeEbayAccountHealth(Query $query,$params)
    {
        if(!empty($params)){
            $query->where('__TABLE__.account_id','in',$params);
        }
    }

    public function scopeDepart(Query $query,$params)
    {
        if(!empty($params)){
            $query->where('__TABLE__.account_id','in',$params);
        }
    }

}