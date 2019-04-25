<?php
namespace app\common\model;

use app\common\cache\Cache;
use erp\ErpModel;
use think\Model;
/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2019/1/12
 * Time: 14:20
 */
class PackageReturnStorageDetail extends ErpModel
{
    /**
     * 包裹退回入库商品详情表
     */
    protected function initialize()
    {
        parent::initialize();
    }

    public static function add($data)
    {
        return (new PackageReturnStorageDetail())->isUpdate(false)->save($data);
    }

    public static function getDetailByReturnId($id)
    {
        $reData = [];
        $where['package_return_id'] = $id;
        $feild = 'sku_id,sku_price,quantity,defective_quantity,good_quantity';
        $list =  (new PackageReturnStorageDetail())->field($feild)->where($where)->select();
        foreach ($list as $value){
            $skuInfo = Cache::store('goods')->getSkuInfo($value['sku_id']);
            $reData[] = [
                'sku' => $skuInfo['sku'] ?? $value['sku_id'],
                'spu_name' => $skuInfo['spu_name'] ?? '',
                'thumb' => $skuInfo['thumb'] ?? '',
                'sku_price' => $value['sku_price'],
                'quantity' => $value['quantity'],
                'defective_quantity' => $value['defective_quantity'],
                'good_quantity' => $value['good_quantity'],
            ];
        }
        return $reData;
    }

}