<?php
namespace app\common\model\amazon;


use think\Model;
use think\Db;


class amazonCategoryAttributeValueXsd extends Model
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
    public function saveCategoryAttributeValue($data){
        $row = $this->getByAttirbuteValue($data['attribute_id'],$data['value'],'id');;
        $data['last_update_time'] = time();

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


    /**获取一条类目记录
     * @param $fid
     * @param $categoryName
     * @param string $fields
     */
    public function getByAttirbuteValue($attributeId,$value,$fields="*"){
        return  $this->field($fields)->where(array('attribute_id' => $attributeId,'value' => $value))->find();
    }
}