<?php
namespace app\common\model\amazon;
use think\Model;
use think\Loader;
use think\Db;
class AmazonPublishProductVariant extends Model{
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function saveVariant($data){
        $row = $this->getByRealSku($data['real_sku'],$data['product_id']);
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

    public function getByRealSku($realSku,$productId,$fields='*'){
        return  $this->field($fields)->where(array('product_id' => $productId,'real_sku' => $realSku))->find();
    }

}