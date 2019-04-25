<?php
namespace app\common\cache\driver;

use app\common\cache\Cache;
use app\common\model\Supplier as SupplierModel;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/01/09
 * Time: 17:45
 */
class Supplier extends Cache
{
    /** 获取供应商
     * @param $id
     * @return array
     */
    public function getSupplier($id = 0)
    {
        $hashKey = 'hash:supplier';
        // $this->redis->del($hashKey);
        if($this->redis->hExists($hashKey, $id)){
            $result = json_decode($this->redis->hGet($hashKey,$id), true);
            $result['balanceTypeText'] = $this->getBalanceTypeText($result['balance_type']);
            return $result;
        }
        //查表
        $supplierModel = new SupplierModel();
        $supplier = $supplierModel->field(true)->where(['id'=>$id])->find();
        if(!$supplier){
            // $this->delSupplier($id);
            return [];
        }
        $supplierArr = $supplier->toArray();
        $supplierArr['balanceTypeText'] = $this->getBalanceTypeText($supplierArr['balance_type']);
        $this->redis->hSet($hashKey,$id, json_encode($supplierArr));
        return $supplierArr;
    }

    /** 获取供应商名称
     * @param $id
     * @return array
     */
    public function getSupplierName($id = 0)
    {
        $name = '';
        $supplier = $this->getSupplier($id);
        if($supplier){
            $name = $supplier['company_name'];
        }
        return $name;
    }

    /**
     * 更新供应商
     * @param number $id
     * @param array $data
     * @return boolean
     */
    function updateSupplier($id = 0){
        $key = 'hash:supplier';
        if ($id) {
            //查表
            $supplierModel = new SupplierModel();
            $supplier = $supplierModel->field(true)->where(['id'=>$id])->find();
            $supplierArr = $supplier->toArray();
            $supplierArr['balanceTypeText'] = $this->getBalanceTypeText($supplierArr['balance_type']); 
            $this->redis->hset($key, $id, json_encode($supplierArr));
            return true;
        }
        return true;
    }
    

    /**
     * 删除缓存
     * @param unknown $supplier_id
     */
    public function delSupplier($supplier_id)
    {
        $hashKey = 'hash:supplier';
        return  $this->redis->del($hashKey, $supplier_id);
    }
    
    
    /**结算方式的文本*/
    public function getBalanceTypeText($balanceType){
        $balance = (new SupplierModel())->getBalance();
        $list = array_combine(array_column($balance, 'label'),array_column($balance, 'name'));
        return isset($list[$balanceType]) ? $list[$balanceType] : '';
    }


}
