<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 18:38
 */

namespace app\common\model\daraz;


use app\common\cache\Cache;
use think\Db;
use think\Exception;
use think\Model;

class DarazOrder extends Model
{
    public function addAll($orders)
    {
       if(!$orders)
       {
           return false;
       }
       foreach ($orders as $singleOrder)
       {
           $order_id = $singleOrder['order']['order_id'];
           $exist_order = $this->where("order_id", $order_id)->find();
           if(Cache::store("DarazOrder")->hasOrder($order_id) && !$exist_order)  //缓存存在，数据库不存在，防止插入重复数据
           {
               continue;
           }
           if($exist_order)
           {
               if($exist_order['updated_at'] == $singleOrder['order']['updated_at'])  ////最后更新时间与数据库一直，不用再更新
               {
                   continue;
               }
               $singleOrder['order']['id'] = $exist_order['id'];
           }
           $this->add($singleOrder);
       }
    }

    public function add($order)
    {
        $darazOrderDetailModel = new DarazOrderDetail();
        Db::startTrans();
        try{
            Db::query("set names utf8mb4");
            if(!isset($order['order']['id'])) //插入
            {
                $id = $this->allowField(true)->insertGetId($order['order']);
                foreach ($order['items'] as $item)
                {
                    $item['oid'] = $id;
                    $darazOrderDetailModel->allowField(true)->insert($item);
                }
            }else{
                $id = $order['order']['id'];
                unset($order['order']['create_time']);
                unset($order['order']['id']);
                $this->allowField(true)->where("id",$id)->update($order['order']);
                foreach ($order['items'] as $item)
                {
                    unset($item['create_time']);
                    $darazOrderDetailModel->where(['oid' => $id, 'order_id' => $item['order_id'], 'order_item_id'=>$item['order_item_id']])->update($item);
                }
            }
            Db::commit();
        }catch (Exception $e)
        {
            Db::rollback();
        }
    }

}