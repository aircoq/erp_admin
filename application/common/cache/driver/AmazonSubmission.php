<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/11/5
 * Time: 11:44
 */
class AmazonSubmission extends Cache
{
    private $consumeTime = 120;
    private $max = 12;
    private $key = 'task:amazon:submission:';
    
    /** 获取属性信息
     * @param accountId 账号id
     * @param string $order_number amazon订单id
     * @param array $data
     * @return array|mixed
     */
    public function getSubmission($accountId)
    {
        $key = $this->getSubmissionKey($accountId);
        $info = $this->redis->hGetAll($key);
        if (!$info) {
            return true;
        }
        if ($info['number'] < $this->max) {
            return true;
        }
        $length = time() - $info['start'];
        $releaseCount = intval($length / $this->consumeTime);
        if ($releaseCount) {
            return true;
        }
        
        return false;
    }
    
    public function setSubmission($accountId)
    {
        $key = $this->getSubmissionKey($accountId);
        $info = $this->redis->hGetAll($key);
        $start = time();
        $number = 1;
        if ($info) {
            $length = time() - $info['start'];
            $releaseCount = intval($length / 120);
            if ($releaseCount >= $info['number']) {
                
            } else {
                $start = $info['start'] + $releaseCount * 120;
                $number = $info['number'] - $releaseCount + 1;
            }
        }
        
        $this->redis->hSet($key, 'start', $start);
        $this->redis->hSet($key, 'number', $number);
        return true;
    }
    
    private function getSubmissionKey($accountId)
    {
        return $this->key . $accountId;
    }
}
