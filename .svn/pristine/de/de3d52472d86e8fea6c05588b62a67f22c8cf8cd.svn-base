<?php

namespace app\common\validate;

use think\Validate;

/**
 * @desc 申报单号 验证类
 * @author lan 
 * @date 2018-02-03 16:06:11
 */
class DefectiveGoodsDeclare extends Validate
{

    protected $rule = [
        ['number','require|unique:DefectiveGoodsDeclare,number','申报单号不能为空！|申报单号已存在'],

    ];

}
