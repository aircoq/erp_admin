<?php
namespace app\common\model;

use erp\ErpModel;
use think\Model;
/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/12/7
 * Time: 14:20
 */
class PackageReturn extends ErpModel
{
    /**
     * 包裹退回
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public function scopeOrder(Query $query, $params)
    {
        $query->where('__TABLE__.channel_account_id', 'in', $params);
    }

    public function scopeChannel(Query $query, $params)
    {
        $query->where('__TABLE__.channel_id', 'in', $params);
    }


}