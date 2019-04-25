<?php
namespace app\common\model\amazon;


use think\Model;
use think\Db;
use app\common\model\amazon\AmazonCategoryXsd;

class amazonCategoryAttributeXsd extends Model
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

    /**保存一条记录
     * @param $data
     * @return int|string
     */
    public function saveCategoryAttribute($data){
        $row = $this->getByAttirbuteName($data['category_id'],$data['f_id'],$data['name'],'id');
        $data['last_update_time'] = time();
        if(is_object($row) && $row){
            if($this->where(['id' => $row->id])->update($data)){
                return $row->id;
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







    /**获取一条类目记录
     * @param $fid
     * @param $categoryName
     * @param string $fields
     */
    public function getByAttirbuteName($categoryId,$fid,$name,$fields="*"){
        return  $this->field($fields)->where(array('category_id' => $categoryId,'f_id' => $fid,'name' => $name))->find();
    }


    public function getListByAttributeName($categoryId,$name,$fields='*'){
        return  $this->field($fields)->where(array('category_id' => $categoryId,'name' => $name))->select();
    }

    public function getListByFid($fid,$attrName,$fields='*'){
        return $this->field($fields)->where(array('f_id' => $fid,'name' => $attrName))->select();
    }


    public function getBaseAttributeList(){
        $xsdModel = new AmazonCategoryXsd();
        $catInfo = $xsdModel->field('id')->where(['category_name' => 'Product'])->find();
        if($catInfo){
            $conditions = ['category_id' => $catInfo['id'],'is_has_children' => 0];
            return $this->field("*")->where($conditions)->order('id')->select();
        }else{
            return array();
        }
    }


    public function getCommonAttributeListByCategoryId($categoryId){
        return  $this->field('id,name,is_has_children,is_select,is_public,is_sku,is_requried,is_hand,sites,is_match_excel')->where(['category_id' => $categoryId,'is_has_children' => 0])->select();
    }


    public function getVariantAttributeListByCategoryId($categoryId){
      $data = $this->field('id,name,is_has_children,is_select,is_public,is_sku,is_requried,is_hand,sites,is_match_excel')->where(['category_id' => $categoryId,'is_has_children' => 0,'name' => 'VariationTheme'])->select();
      return $data;
    }

}