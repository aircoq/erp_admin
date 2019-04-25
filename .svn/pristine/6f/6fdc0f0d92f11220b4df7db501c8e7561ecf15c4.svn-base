<?php
namespace app\common\model;
use think\Model;

/**
 * Created by Netbeans.
 * User: libaimin
 * Date: 2019/3/12
 * Time: 17:24
 */
class SinglePackageSku extends Model
{
    /**F
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public static function add($add)
    {
        $add['create_time'] = time();
        return (new SinglePackageSku())->allowField(true)->isUpdate(false)->save($add);
    }
}