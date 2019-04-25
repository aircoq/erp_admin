<?php

namespace app\common\model\aliexpress;

use think\Model;

class AliexpressIssueSolution extends Model
{
    protected $autoWriteTimestamp = true;
    
    protected $dateFormat = false;

    /**
     * 纠纷方案创建时间修改器(Aliexpress返回时间与北京时间相差15小时)
     * @param $value
     * @return mixed
     */
    public function setGmtCreateAttr($value)
    {
        if(!$value){
            return $value;
        }else{
            return ($value+54000);
        }
    }
    //方案修改时间
    public function setGmtModifiedAttr($value)
    {
        if(!$value){
            return $value;
        }else{
            return ($value+54000);
        }
    }
    //方案达成时间
    public function setReachedTimeAttr($value)
    {
        if(!$value){
            return $value;
        }else{
            return ($value+54000);
        }
    }
    //买家接受时间
    public function setBuyerAcceptTimeAttr($value)
    {
        if(!$value){
            return $value;
        }else{
            return ($value+54000);
        }
    }
    //卖家接受时间
    public function setSellerAcceptTimeAttr($value)
    {
        if(!$value){
            return $value;
        }else{
            return ($value+54000);
        }
    }

}
