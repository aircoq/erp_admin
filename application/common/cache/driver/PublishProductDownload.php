<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-13
 * Time: 上午11:49
 */

namespace app\common\cache\driver;


use app\common\cache\Cache;

class PublishProductDownload extends Cache
{
    private $key="publish:product:download";
    private $_field="publish:download:field";
    /**
     * 查看是否在导出状态
     * @param $key
     * @return bool
     */
    public function isExport($key)
    {
        if ($this->redis->exists($this->key . $key)) {
            return true;
        }
        return false;
    }
    public function setExport($key)
    {
        $this->redis->set($this->key . $key, 1, 10);
    }
    public function delExport($key)
    {
        $this->redis->del($this->key . $key);
    }

    public function exists($name){
       return  $this->redis->hExists($this->key,$name);
    }
    public function deleteCacheData($name){
        return $this->redis->hDel($this->key,$name);
    }
    public function getCacheData($name){
        return $this->redis->hGet($this->key,$name);
    }
    public function setCacheData($name,$data){
        $this->redis->hSet($this->key,$name,$data);
    }
    public function setFields($name,$fields){
        $this->redis->hSet($this->_field,$name,$fields);
    }
    public function getFields($name){
        $this->redis->hGet($this->_field,$name);
    }

}