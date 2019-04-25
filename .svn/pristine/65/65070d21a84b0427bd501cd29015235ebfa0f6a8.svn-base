<?php
namespace app\common\model;

use think\Model;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/28
 * Time: 20:09
 */
class AfterServiceReason extends Model
{
    /**
     * 订单原因
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 查看是否存在
     * @param $id
     * @return bool
     */
    public function isHas($id)
    {
        $result = $this->where(['id' => $id])->find();
        if (!empty($result)) {
            return true;
        }
        return false;
    }
}