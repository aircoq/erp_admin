<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;

/**
 * Created by tanbin.
 * Date: 2017/09/11
 * Time: 11:44
 */
class EbayDispute extends Cache
{
    
    /**
     * 添加纠纷-Cancel操作日志
     * @param unknown $key
     * @param array $data
     */
    public function addOperateCancelLogs($key, $data = [])
    {
        $this->redis->hSet('hash:EbayOperateCancelLogs', $key, json_encode($data));
    }
    
    /**
     * 获取纠纷-Cancel操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getOperateCancelLogs($key)
    {
        if ($this->redis->hExists('hash:EbayOperateCancelLogs', $key)) {
            return true;
        }
        return false;
    }
    
    /**
     * 添加纠纷-Return操作日志
     * @param unknown $key
     * @param array $data
     */
    public function addOperateReturnLogs($key, $data = [])
    {
        $this->redis->hSet('hash:EbayOperateReturnLogs', $key, json_encode($data));
    }
    
    /**
     * 获取纠纷-Return操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getOperateReturnLogs($key)
    {
        if ($this->redis->hExists('hash:EbayOperateReturnLogs', $key)) {
            return true;
        }
        return false;
    }
    
    
    
    /**
     * 添加纠纷-Case操作日志
     * @param unknown $key
     * @param array $data
     */
    public function addOperateCaseLogs($key, $data = [])
    {
        $this->redis->hSet('hash:EbayOperateCaseLogs', $key, json_encode($data));
    }
    
    /**
     * 获取纠纷-Case操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getOperateCaseLogs($key)
    {
        if ($this->redis->hExists('hash:EbayOperateCaseLogs', $key)) {
            return true;
        }
        return false;
    }
    
    
    
    /**
     * 添加纠纷-Inquir操作日志
     * @param unknown $key
     * @param array $data
     */
    public function addOperateInquirLogs($key, $data = [])
    {
        $this->redis->hSet('hash:EbayOperateInquirLogs', $key, json_encode($data));
    }
    
    /**
     * 获取纠纷-Inquir操作日志
     * @param unknown $key
     * @param array $data
     */
    public function getOperateInquirLogs($key)
    {
        if ($this->redis->hExists('hash:EbayOperateInquirLogs', $key)) {
            return true;
        }
        return false;
    }
    
    
}
