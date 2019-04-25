<?php
namespace app\common\model\amazon;


use think\Model;
use think\Db;


class amazonCategoryAttributeMap extends Model
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

    /**
     * @title 保存EXCEL与XSD属性映射记录
     * @param $data
     * @return int
     * @throws Exception
     */
    public function saveAttributeMap($data){
        $row = $this->getByXsdAttrId($data['cat_id'],$data['site'],$data['xsd_attr_id'],'id');
        $data['last_update_time'] = time();
        if(is_object($row) && $row!= null){
            if($this->where(['id' => $row['id']])->update($data)){
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






    public function getByXsdAttrId($xsdCatId,$site,$xsdAttrId,$fields = '*'){
        return $this->field($fields)->where(['cat_id' => $xsdCatId,'site' => $site,'xsd_attr_id' => $xsdAttrId])->find();
    }

    public function deleteByCategoryId($xsdCatId,$site,$time){
        return $this->where(['cat_id' => $xsdCatId,'site' => $site,'last_update_time' => array('lt',$time)])->delete();
    }




}