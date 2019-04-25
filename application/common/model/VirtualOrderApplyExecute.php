<?php
/**
 * Created by PhpStorm.
 * User: starzhan
 * Date: 2017/9/29
 * Time: 9:34
 */

namespace app\common\model;

use think\Model;

class VirtualOrderApplyExecute extends Model
{
    const STATUS =[
        0=>'初始',
        1=>'已提交',
        2=>'已作废',
    ];
    public function getStatusTxtAttr($value,$data){
        return self::STATUS[$data['status']];
    }
}