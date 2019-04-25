<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Allocation as AllocationModel;


/**
 * Created by tanbin.
 * User: PHILL
 * Date: 2017/06/21
 * Time: 11:44
 */
class Allocation extends Cache
{
    /** 获取属性信息
     * @param string $order_code 调拨单号
     * @param array $data
     * @return array|mixed
     */
    public function allocationOrderCode($order_code, $data = [])
    { 
        //Cache::handler()->del('hash:AllocationOrderCode'); //删除
        
        $key = 'hash:AllocationOrderCode';
        if ($data) {
            $this->redis->hset($key, $order_code, json_encode($data));
            return true;
        }
        $result = json_decode($this->redis->hget($key, $order_code), true);
        if($result){
            return $result;
        }
      
        //从数据库中获取值        
        $info = AllocationModel::field('id')->where(['order_code' => $order_code])->find();         
      
        if($info){
            $data = [
                'id'  => $info['id']
            ];
            $this->redis->hset($key, $order_code, json_encode($data));
            return $data;
        }
        
        return [];
    }


}
