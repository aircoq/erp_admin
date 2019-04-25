<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-4-18
 * Time: 下午5:38
 */

namespace app\common\model;


use erp\ErpModel;

class ConfigParams extends ErpModel
{
    public function getParamsAttr($attr)
    {
        if(is_string($attr)){
            $attr = json_decode($attr);
        }
        return $attr;
    }
}