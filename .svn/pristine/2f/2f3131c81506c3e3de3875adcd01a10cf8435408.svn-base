<?php
namespace app\common\model\ebay;

use app\common\traits\ModelFilter;
use erp\ErpModel;

class OeNumber extends ErpModel
{
    use ModelFilter;
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public function scopeOeList(Query $query,$params)
    {
        if(!empty($params)){
            $query->where('__TABLE__.creator_id','in',$params);
        }
    }

    public function scopeEbayDepart(Query $query,$params)
    {
        if(!empty($params)){
            $query->where('__TABLE__.creator_id','in',$params);
        }
    }

    /**
     * 修改
     * @param  array $data [description]
     * @return [type]       [description]
     */
    public function edit(array $data, array $where)
    {
        return $this->allowField(true)->save($data, $where);
    }
}