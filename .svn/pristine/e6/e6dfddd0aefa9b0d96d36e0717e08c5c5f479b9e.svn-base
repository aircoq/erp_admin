<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-24
 * Time: 上午9:40
 */

namespace app\system\cache;


use app\common\cache\Cache;

class Release extends Cache
{
    protected $db = 3;
    public function getVersion()
    {
        if($version = $this->persistRedis->hGet('hash:version', 'info')){
            return json_decode($version, true);
        }else{
            return false;
        }
    }

    public function setVersion($version)
    {
        $version = json_encode($version);
        $this->persistRedis->hSet("hash:version", 'info', $version);
    }
}