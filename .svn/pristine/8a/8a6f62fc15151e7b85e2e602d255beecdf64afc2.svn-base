<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-25
 * Time: 下午5:29
 */

namespace app\common\model;


use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;

class WishTest extends ErpModel
{
    use ModelFilter;
    protected $field = true;

    protected static $associatedDeleteCallbacks = [
        \app\index\controller\Test::class
    ];

//    protected function filter($fclass, $scope, $params)
//    {
//    }

    public function scopeDepartment(Query $query, $params)
    {
    }

    public function getIsUploadAttr($attr)
    {
        return true;
    }

    public function setIsUploadAttr($attr)
    {
        dump_detail($attr);
        return $attr;
    }
}