<?php
/**
 * Created by PhpStorm.
 * Date: 2017/11/27
 * Time: 16:41
 */
namespace app\common\model\amazon;
use think\Model;
use think\Loader;
use think\Db;

class AmazonXsdVariantMap extends Model
{
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function saveVariantMap($data){
        $row = $this->getByCatId($data['category_id'],$data['variant_id'],$data['xsd_id'],'id');

        if(is_object($row) && $row != null){
            return $this->where(['id' => $row['id']])->update($data);
        }else{
            return $this->insert($data);
        }
    }


    public function getVarinatsByCatId($categoryId,$variantId,$fields='*'){
        return $this->field($fields)->where(['category_id' => $categoryId,'variant_id' => $variantId])->select();
    }


    public function getByCatId($categoryId,$variantId,$xsdId,$fields='*'){

        return $this->field($fields)->where(['category_id' => $categoryId,'variant_id' => $variantId,'xsd_id' => $xsdId])->find();

    }


}