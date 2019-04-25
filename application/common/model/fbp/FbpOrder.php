<?php

namespace app\common\model\fbp;

use think\Model;

class FbpOrder extends Model
{
    public function detail()
    {
        return $this->hasMany(FbpOrderDetail::class,'fbp_order_id','id','left')->field('*');
    }

    public function source()
    {
        return $this->hasMany(FbpOrderSourceDetail::class,'fbp_order_id','id','left')->field('*');
    }
}