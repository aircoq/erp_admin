<?php
namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/4
 * Time: 17:10
 */
class GoodsAttribute extends Model
{
    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    
    /**
     * 属性值是否存在
     */
    public function check($where)
    {
        $info = $this->get($where);
        if (empty($info)) {
            return false;
        }
        return true;
    }
    
}