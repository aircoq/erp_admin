<?php
namespace app\common\model\amazon;
use think\Model;
use think\Loader;
use think\Db;
class AmazonPublishProductJson extends Model{

    public function saveProductJson($data){
        $row = $this->getByProductId($data['product_id']);
        if(is_object($row) && $row){
            if($this->where(['id' => $row->id])->update($data)){
                return $row['id'];
            }else{
                return 0;
            }
        }else{
            if($this->insert($data)){
                return $this->getLastInsID();
            }else{
                return 0;
            }
        }
    }

    public function getByProductId($productId,$fields='id'){
        return  $this->field($fields)->where(array('product_id' => $productId))->find();
    }
}