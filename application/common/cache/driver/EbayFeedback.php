<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Created by tanbin.
 * Date: 2017/09/11
 * Time: 11:44
 */
class EbayFeedback extends Cache
{
    
    /**
     * 评价操作日志
     * @param unknown $key
     * @param array $data
     */
    public function addLeaveFeedbackLogs($key, $data = [])
    {
        $this->redis->hSet('hash:EbayLeaveFeedbackLogs', $key, json_encode($data));
    }
    
    /**
     * 评价操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getLeaveFeedbackLogs($key)
    {
        if ($this->redis->hExists('hash:EbayLeaveFeedbackLogs', $key)) {
            return true;
        }
        return false;
    }
    
    /**
     * 回评操作日志
     * @param unknown $key
     * @param array $data
     */
    public function addRespondFeedbackLogs($key, $data = [])
    {
        $this->redis->hSet('hash:EbayRespondFeedbackLogs', $key, json_encode($data));
    }
    
    /**
     * 回评操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getRespondFeedbackLogs($key)
    {
        if ($this->redis->hExists('hash:EbayRespondFeedbackLogs', $key)) {
            return true;
        }
        return false;
    }
    

    
}
