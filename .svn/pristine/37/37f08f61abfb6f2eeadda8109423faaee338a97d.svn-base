<?php
namespace app\common\cache\driver;
use app\common\cache\Cache;
use think\Db;
use app\common\model\Config;

class ConfigParams extends Cache
{
    private $cacheName = 'cache:Config';

    /** 获取配置参数
     * @param string $param
     * @return array|bool|false|mixed|\PDOStatement|string|\think\Model
     */
    public function getConfig($param = '')
    {
        if (empty($param)) {
            return false;
        }
        if ($param) {
            if ($this->redis->hexists($this->cacheName, $param)) {
                $result = $this->redis->hget($this->cacheName, $param);
                return json_decode($result, true);
            }
        }
        if (is_numeric($param)) {
            $where['id'] = $param;
        } else {
            $where['name'] = $param;
        }
        $configModel = new Config();
        $result = $configModel->field(true)->where($where)->find();
        $this->redis->hmset($this->cacheName, [$result['id'] => json_encode($result), $result['name'] => json_encode($result)]);
        return $result;
    }

    /**
     * 删除
     * @param int $id
     */
    public function delete($id = 0)
    {
        if(empty($id)){
            $this->redis->del($this->cacheName);
        }else{
            $result = $this->getConfig($id);
            if ($result) {
                $name = $result['name'];
                $this->redis->hdel($this->cacheName, $id);
                $this->redis->hdel($this->cacheName, $name);
            }
        }
    }
    
}