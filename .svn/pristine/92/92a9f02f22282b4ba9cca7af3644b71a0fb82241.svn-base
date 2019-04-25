<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: Reece
 * Date: 2018/12/22
 * Time: 14:39
 */
class SupplierStatisticReport extends Model
{

    public static function statisticPurchase($supplierId, $poInfo)
    {
        $data = self::where(['supplier_id'=>$supplierId])->find();
        $purchaseMoney = round($poInfo['payable_amount']*$poInfo['rate'], 4);
        if($data){
            if($poInfo['create_time'] > $data->last_purchase_time){
                $data->last_purchase_time = $poInfo['create_time'];
            }
            $data->count_purchase_times += 1;
            $data->count_purchase_money = round($data->count_purchase_money+$purchaseMoney, 4);
            $data->save();
        }else{
            $model = new self();
            $model->isUpdate(false)->save([
                'supplier_id' => $supplierId,
                'last_purchase_time' => $poInfo['create_time'],
                'count_purchase_times' => 1,
                'count_purchase_money' => $purchaseMoney
            ]);
        }
    }

    public static function statisticSpuQty($supplierId, $spuQty)
    {
        $data = self::where(['supplier_id'=>$supplierId])->find();
        if($data){
            $data->count_spu_qty += $spuQty;
            $data->save();
        }else{
            (new self())->isUpdate(false)->save([
                'supplier_id' => $supplierId,
                'count_spu_qty' => $spuQty
            ]);
        }
    }
}