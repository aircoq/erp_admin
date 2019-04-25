<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/8
 * Time: 14:16
 */

namespace app\common\model\oberlo;


use think\Model;

class OberloOrderDetail extends Model
{
    public function add($detail,$is_update=false)
    {
        if(!$detail)
        {
            return false;
        }
        if(!$is_update)
        {
            $this->insert($detail);
        }else{
            unset($detail['create_time']);
            $this->where("oid", $detail['oid'])->where("order_item_id",$detail['order_item_id'])->update($detail);
        }
    }
}