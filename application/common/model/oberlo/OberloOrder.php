<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/8
 * Time: 14:13
 */

namespace app\common\model\oberlo;


use app\common\cache\Cache;
use think\Db;
use think\Exception;
use think\Model;

class OberloOrder extends Model
{
   public function addAll($orders)
   {
       if(!$orders)
       {
           return false;
       }
       foreach ($orders as $order)
       {
           $existOrder = $this->where("order_id",$order['order_id'])->find();
           $cacheOrder = Cache::store("OberloOrder")->filterOrder($order['order_id']);
           if($cacheOrder && !$existOrder)  //说明其他队列正在插入此订单，不做处理
           {
              continue;
           }
           if($existOrder)
           {
               $order['id'] = $existOrder['id'];
               unset($order['create_time']);
           }
           $this->add($order);
       }
   }

   public function add($order)
   {
      $orderDetail = new OberloOrderDetail();
      $items = $order['items'];
      unset($order['items']);
      Db::startTrans();
      try{
          if(!isset($order['id']))
          {
              $id = $this->insertGetId($order);
              foreach ($items as $item)
              {
                  $item['oid'] = $id;
                  $orderDetail->add($item);
              }
          }else{
              $this->where("id", $order['id'])->update($order);
              foreach ($items as $item)
              {
                  $item['oid'] = $order['id'];
                  $orderDetail->add($item,true);
              }
          }
          Db::commit();
      }catch (Exception $e)
      {
          Db::rollback();
          throw new Exception($e->getMessage());
      }

   }

}