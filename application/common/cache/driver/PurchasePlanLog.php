<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\PurchasePlanLog as PurchasePlanLogModel;

/**
 * 采购计划
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/01/09
 * Time: 17:45
 */
class PurchasePlanLog extends Cache
{
    /** 获取供应商
     * @param $id
     * @return array
     */
    public function getPurchasePlaLog($id=0)
    {
        if(self::$redis->exists('cache:PurchasePlanLog')){
            if(!empty($id)){
                $result = self::$redis->zrangebyscore('cache:PurchasePlanLog',$id,$id);
                if(isset($result[0]) && !empty($result[0])){
                    return json_decode($result[0],true);
                }else{
                    return [];
                }
            }
            $data = self::$redis->zrange('cache:PurchasePlanLog',0,-1);
            $result = [];
            foreach($data as $k => $v){
                array_push($result,json_decode($v,true));
            }
            return $result;
        }
        //查表
        $model = new PurchasePlanLogModel();
        $result = $model::field('*')->select();
        foreach($result as $k => $v){
            self::$redis->zadd('cache:PurchasePlanLog',$v['id'],json_encode($v));
        }
        if(!empty($id)){
            $result = self::$redis->zrangebyscore('cache:PurchasePlanLog',$id,$id);
            if(isset($result[0]) && !empty($result[0])){
                return json_decode($result[0],true);
            }else{
                return [];
            }
        }
        return $result;
    }
}
