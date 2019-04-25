<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Node as NodeModel;
/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/12/7
 * Time: 17:45
 */
class Node extends Cache
{
    private $cacheName = 'cache:node';

    /** 获取所有节点
     * @return array $result : 返回所有节点列表
     */
    public function getNode($id = 0)
    {
        if($this->redis->exists($this->cacheName)){
            $result = json_decode($this->redis->get($this->cacheName), true);
            if(!empty($result)){
                if(!empty($id)){
                    return isset($result[$id]) ? $result[$id] : [];
                }
                return $result;
            }
        }
        //查表
        $result = NodeModel::select();
        $newResult = [];
        foreach ($result as $k => $v) {
            $newResult[$v['id']] = $v;
        }
        $this->redis->set($this->cacheName, json_encode($newResult));
        return $result;
    }

    /**
     * 删除所有节点
     */
    public function delete()
    {
        if ($this->redis->exists($this->cacheName)) {
            return $this->redis->del($this->cacheName);
        }
        return false;
    }
    
}

