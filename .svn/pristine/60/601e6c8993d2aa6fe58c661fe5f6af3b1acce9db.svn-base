<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Created by 冬.
 * User: PHILL
 * Date: 2016/11/5
 * Time: 11:44
 */
class EbayNotification extends Cache
{
    public $preKey = 'task:ebay:notification:xml:';
    public $preAutoKey = 'task:ebay:notification:auto-increment:';

    public $redisInc = 0;

    public $autoKey = '';


    /**
     * 保存帐号自增ID
     * @param $type string 类别前辍
     * @param $xml
     * @return bool|string
     */
    public function saveXml($xml, $type = '')
    {
        $type = trim($type, ' :');
        if (!empty($type)) {
            $type = $type. ':';
        }
        $key = $this->preKey . $type. date('ymd:'). $this->getAutoIncrement();
        if (!$key) {
            return false;
        }
        $result = $this->redis->setex($key, 60 * 60, $xml);
        if ($result) {
            return $key;
        }

        return false;
    }

    public function getXml($key)
    {
        return (string)$this->redis->master()->get($key);
    }

    /**
     * 获取帐号保存的自增唯一
     * @param $account_id
     * @return bool|string
     */
    private function getAutoIncrement()
    {
        $key = $this->preAutoKey. date('Y-m-d');
        $int = $this->persistRedis->incr($key);
        if ($int == 1) {
            $this->persistRedis->expire($key, 3600*25);
        }

        return $int;
    }
    
}
