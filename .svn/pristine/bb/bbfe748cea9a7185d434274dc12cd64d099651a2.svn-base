<?php
namespace app\common\model\aliexpress;
use think\Model;
/**
 * Created by ZendStudio.
 * User: Hot-Zr
 * Date: 2017年3月24日 
 * Time: 09:29:29
 */
class AliexpressCategoryAttrVal extends Model
{
    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public static function getNameById($id)
    {
        $model = self::field('name_zh')->find(['id'=>$id]);
        return empty($model)?false:$model->name_zh;
    }
}