<?php
namespace app\common\validate;

use \think\Validate;

/**
 * @author wangwei
 * @date 2018-10-12 16:38:39
 */
class LocalStocking extends Validate
{
    protected $rule = [
        ['warehouse_id', 'require', '请选择备货仓库！'],
        ['channel_id', 'require', '请选择备货平台！'],
        ['start_time', 'require', '请指定活动开始时间！'],
        ['end_time', 'require', '请指定活动结束时间！'],
    ];
}