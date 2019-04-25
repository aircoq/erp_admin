<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/29
 * Time: 9:17
 */
class Attribute extends Model
{
    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /** 判断记录是否存在
     * @param $attribute_id
     * @return bool
     */
    public function hasData($attribute_id)
    {
        $result = $this->where(['id' => $attribute_id])->select();
        if(empty($result)){
            return false;
        }
        return true;
    }
}