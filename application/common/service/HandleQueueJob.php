<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-3
 * Time: 下午7:22
 */

namespace app\common\service;


interface HandleQueueJob
{
    /**
     * @doc 生产者
     */
    public function production();

    /**
     * @doc 消费者
     */
    public function consumption();
}