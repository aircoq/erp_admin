<?php
/**
 * Created by PhpStorm.
 * User: dhb5861
 * Date: 2019/1/7
 * Time: 18:37
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;

class EbaySettlemen extends Cache
{
    private $intervalKey = "cache:ebay_settlement_intval";

    public function setInterval($time)
    {
        $this->redis->set($this->intervalKey,$time);
    }

    public function getInterval()
    {
        return $this->redis->get($this->intervalKey);
    }
}