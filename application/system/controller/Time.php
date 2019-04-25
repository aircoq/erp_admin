<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-4-6
 * Time: 上午11:29
 */

namespace app\system\controller;


use app\common\controller\Base;

/**
 * @module 系统内部
 * @title 时间
 */
class Time extends Base
{
    /**
     * @title 获取当前系统时间
     * @url /system/time
     */
    public function time()
    {
        return json(millisecond());
    }
}