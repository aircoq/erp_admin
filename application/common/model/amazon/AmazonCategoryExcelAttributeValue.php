<?php
namespace app\common\model\amazon;

use think\Model;
use think\Loader;
use think\Db;

class amazonCategoryExcelAttributeValue extends Model
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
     * @title 保存EXCLE属性值记录
     * @param $data
     * @return int|string
     * @throws Exception
     */
    public function saveExcelAttributeValue($data){
        $row = $this->getByExcelAttrValueName($data['excel_attr_id'],$data['name'],'id');
        $data['last_update_time'] = time();
        if(is_object($row) && $row != null){
            return $this->where(['id' => $row->id])->update($data);
        }else{
            return $this->insert($data);
        }
    }


    public function saveExcelAttributeValueByXsdAttrId($data,$site){
        $row = $this->getByExcelAttrValueByXsdAttrId($data['xsd_attr_id'],$site,$data['name'],'id');
        $data['last_update_time'] = time();
        if(is_array($row) && $row){
            return $this->where(['id' => $row->id])->update($data);
        }else{
            return $this->insert($data);
        }
    }

    public function getByExcelAttrValueByXsdAttrId($attrId,$site,$name,$fields='*'){
        return  $this->field($fields)->where(['xsd_attr_id' => $attrId,'site' => $site,'name' => $name])->find();
    }


    public function getByExcelAttrValueName($attrId,$name,$fields='*'){
        return  $this->field($fields)->where(['excel_attr_id' => $attrId,'name' => $name])->find();
    }


    public function deleteByUpdateTime($excelAttrId,$time){
        return  $this->where(['excel_attr_id' => $excelAttrId,'last_update_time' => array('lt',$time)])->delete();
    }

}