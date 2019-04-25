<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\FinanceInfo as FinanceInfoModel;

/**
 * Created by PhpStorm.
 * User: WCG
 * Date: 2017/01/09
 * Time: 17:45
 */
class FinanceInfo extends Cache
{
    /** 获取供应商
     * @param $id
     * @return array
     */
    public function getFinanceInfo($id=0)
    {
        if($this->redis->exists('cache:FinanceInfo')){
            if(!empty($id)){
                $result = $this->redis->zrangebyscore('cache:FinanceInfo',$id,$id);
                if(isset($result[0]) && !empty($result[0])){
                    return json_decode($result[0],true);
                }else{
                    return [];
                }
            }
            $data = $this->redis->zrange('cache:FinanceInfo',0,-1);
            $result = [];
            foreach($data as $k => $v){
                array_push($result,json_decode($v,true));
            }
            return $result;
        }
        //查表
        $model = new FinanceInfoModel();
        $result = $model::field('*')->select();
        foreach($result as $k => $v){
            $this->redis->zadd('cache:FinanceInfo',$v['id'],json_encode($v));
        }
        if(!empty($id)){
            $result = $this->redis->zrangebyscore('cache:FinanceInfo',$id,$id);
            if(isset($result[0]) && !empty($result[0])){
                return json_decode($result[0],true);
            }else{
                return [];
            }
        }
        return $result;
    }
}
