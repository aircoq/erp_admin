<?php
namespace app\common\model;

use erp\ErpModel;
use app\common\traits\ModelFilter;
use think\db\Query;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class Channel  extends ErpModel
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
    public function scopeGoodsTort(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.name', 'in', $params);
        }else{
            $query->where('__TABLE__.name','-1');
        }
    }
}