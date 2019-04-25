<?php

namespace app\common\model\aliexpress;

use think\Model;

class AliexpressIssueProcess extends Model
{

    /**
     * 纠纷过程创建时间修改器(Aliexpress返回时间与北京时间相差15小时)
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

}
