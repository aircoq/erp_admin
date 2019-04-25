<?php
namespace app\common\model\amazon;

use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;

class AmazonAccountHealthList extends ErpModel
{

    use ModelFilter;
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function scopeAmazonAccountHealth(Query $query,$params)
    {
        if(!empty($params)){
            $query->where('__TABLE__.amazon_account_id','in',$params);
        }
    }

    public function scopeDepart(Query $query,$params)
    {
        if(!empty($params)){
            $query->where('__TABLE__.amazon_account_id','in',$params);
        }
    }

}
