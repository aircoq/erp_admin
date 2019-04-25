<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class Packing extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 检测材料是否存在
     * @param int $id
     * @return bool
     */
    public function isHas($id = 0)
    {
        $result = $this->where(['id' => $id])->find();
        if(empty($result)){   //不存在
            return false;
        }
        return true;
    }

    /** 更新是判断title是否重复
     * @param $title
     * @param $id
     * @return bool
     */
    public function isRepeat($title,$id)
    {
        $where = "title ='".$title."' and id != ".$id;
        $result = $this->where($where)->find();
        if(empty($result)){   //不存在
            return true;
        }
        return false;
    }

    /** 修改的规则
     * @return array
     */
    public function rule()
    {
         $rule = [
            ['weight','number','重量必须为数字'],
            ['width','number','宽度必须为数字'],
            ['height','number','高度必须为数字'],
            ['depth','number','深度必须为数字'],
            ['supplier_id','number','供应商必须为数字'],
            ['currency_id','number','货币必须为数字'],
            ['cost_price','number','成本价必须为数字'],
         ];
        return $rule;
    }
}