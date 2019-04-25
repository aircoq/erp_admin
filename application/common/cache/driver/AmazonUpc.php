<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\publish\queue\AmazonUpcPoolQueuer;
use phpDocumentor\Reflection\Types\Mixed_;
use think\Db;
use think\Exception;

class AmazonUpc extends Cache
{
    const cacheUpcPrefix = 'task:';

    private $max = 4000;

    private $poolKey = '';
    private $poolMaxKey = '';
    private $lockKey = '';

    public function __construct() {
        parent::__construct();
        $this->poolKey = self::cacheUpcPrefix. 'amazon:upcs-pool-important';
        $this->poolMaxKey = self::cacheUpcPrefix. 'amazon:upcs-pool-max';
        $this->lockKey = self::cacheUpcPrefix. 'amazon:upcs-lock:';
        $max = $this->redis->get($this->poolMaxKey);
        if ($max > 0) {
            $this->max = $max;
        }
    }

    public function setAutoNumber($max)
    {
        $this->redis->set($this->poolMaxKey, $max);
        $this->max = $max;
    }

    public function getAutoNumber()
    {
        return $this->max;
    }

    /**
     * 获取的UPC做一个记录，保存下来，确保24小时；
     * @param $upcs
     * @return bool
     */
    public function upcRecord($upcs)
    {
        if (empty($upcs)) {
            return true;
        }

        $key = self::cacheUpcPrefix. 'amazon:upcs-'. date('Y-m-d-H');

        if (is_string($upcs)) {
            $this->redis->sAdd($key, $upcs);
        } else if (is_array($upcs)) {
            $this->redis->sAddArray($key, $upcs);
        } else {
            throw new Exception('未知UPC数据类型');
        }

        //有效期一天，结果是第二天仍有可能查到这个数值；
        $this->redis->expire($key, 3600);
        return true;
    }

    /**
     * 确保24小时内获取的UPC没有重复的；存在true,不存在false;
     * @param $upc
     * @return bool
     */
    public function checkUpcRecord($upc)
    {
        $yesterKey = self::cacheUpcPrefix. 'amazon:upcs-'. date('Y-m-d-H', time() - 360);
        $key = self::cacheUpcPrefix. 'amazon:upcs-'. date('Y-m-d-H');

        //昨天的KEY存在，且，这个元素也在这个集合里面，返回true；
        if ($this->redis->exists($yesterKey) && $this->redis->sIsMember($yesterKey, $upc)) {
            return true;
        }

        //今天的KEY存在，且，这个元素也在今天的集合里面，返回true;
        if ($this->redis->exists($key) && $this->redis->sIsMember($key, $upc)) {
            return true;
        }

        //以上两重验证，说明不存在，为false;
        return false;
    }

    /**
     * 增加UPC到UPC池；
     * @param $upcs Mixed
     * @return int
     */
    public function addUpcToPool($upcs, $returnNumber = false)
    {
        if (empty($upcs)) {
            return 0;
        }
        if (is_string($upcs)) {
            $upcs = [$upcs];
        }
        if ($returnNumber) {
            $old = $this->redis->sCard($this->poolKey);
            $this->redis->sAddArray($this->poolKey, $upcs);
            $num = $this->redis->sCard($this->poolKey) - $old;
            return $num;
        } else {
            $this->redis->sAddArray($this->poolKey, $upcs);
            return true;
        }
    }

    /**
     * 拿取池里的UPC；
     * @param $num
     * @return array
     */
    public function getUpcFromPool($num)
    {
        $upcs = [];
        $count = 0;
        while ($count < $num) {
            $upc = $this->redis->sPop($this->poolKey);
            //没有了；
            if (empty($upc)) {
                break;
            }
            if (!$this->checkUpcRecord($upc)) {
                $count++;
                $upcs[] = $upc;
            }
        }

        //数量不够下载了去抓；
        if ($this->getPoolUpcTotal() < $this->max/2) {
            (new UniqueQueuer(AmazonUpcPoolQueuer::class))->push($this->max);
        }

        //记录一下返回的UPC；
        $this->upcRecord($upcs);

        return $upcs;
    }


    /**
     * 返回UPC池里的UPC各数；
     * @return int
     */
    public function getPoolUpcTotal() {
        $total = $this->redis->sCard($this->poolKey);
        if (empty($total)) {
            return 0;
        }
        return $total;
    }


    public function getUpcLock($user_id, $second = 5)
    {
        $key = $this->lockKey. $user_id;
        $value = 1;
        $result = $this->redis->get($key);

        if ($result == $value) {
            return false;
        } else {
            return $this->redis->set($key, $value, 5);
        }
    }
}