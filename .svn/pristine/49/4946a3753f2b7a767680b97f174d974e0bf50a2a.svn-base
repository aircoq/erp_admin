<?php
namespace app\common\model\amazon;
use think\Model;
use think\Loader;
use think\Db;

class AmazonPublishProductAttach extends Model{
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function saveProductAttach($data){
        $row = $this->getByProductId($data['product_id'],$data['attribute_id'],$data['attr_name']);
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

    public function getByProductId($productId,$attributeId,$attrName,$fields='*'){
        return  $this->field('id')->where(array('product_id' => $productId,'attr_name' => $attrName,'attribute_id' => $attributeId))->find();
    }
}