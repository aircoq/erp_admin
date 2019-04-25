<?php
namespace app\common\model\amazon;

use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;

class AmazonPublishTask extends ErpModel {
    use ModelFilter;

    private $filterAccount = [];
    /**
     * listing过滤
     * @param Query $query
     * @param $params
     */
    public function scopeListing(Query $query, $params)
    {
        $this->filterAccount = array_merge($params, $this->filterAccount);
        if(!empty($params))
        {
            $query->where('__TABLE__.account_id','in', $this->filterAccount);
        }
    }

    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

}