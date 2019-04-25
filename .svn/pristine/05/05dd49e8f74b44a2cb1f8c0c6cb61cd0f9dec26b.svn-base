<?php
namespace app\common\model;

use think\Model;
use app\common\cache\Cache;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/29
 * Time: 9:17
 */
class CategoryAttribute extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /** 关联查询属性组表记录
     * @return \think\model\Relation
     */
    public function attributeGroup()
    {
        return $this->hasOne('AttributeGroup','id','group_id');
    }

    /** 保存数据
     * @param array $data
     * @param $category_id
     * @param int $group_id
     * @throws \think\Exception
     */
    public function saveData(array $data,$category_id,$group_id = 0)
    {
        //删除之前分组所有记录
         $this->where(['category_id' => $category_id, 'group_id' => $group_id])->delete();
        foreach($data as $k => $v){
            $temp[$k]['category_id'] = $category_id;
            $temp[$k]['group_id'] = $group_id;
            $value_id = [];
            if (is_array($v['attribute_value'])) {
                foreach($v['attribute_value'] as $key => $value){
                    array_push($value_id,$value['attribute_value_id']);
                }
            }
            $temp[$k]['value_range'] = json_encode($value_id);
            $temp[$k]['sku'] = $v['sku'];
            $temp[$k]['gallery'] = $v['gallery'];
            $temp[$k]['required'] = $v['required'];
            $temp[$k]['attribute_id'] = $v['attribute_id'];
        }
        if(isset($temp) && !empty($temp)){
            $this->allowField(true)->insertAll($temp);
        }
    }
    
    /** 保存质检数据
     * @param array $data
     * @param $category_id
     * @param int $group_id
     * @throws \think\Exception
     */
    public function saveQcData($data,$category_id,$group_id = 0,$delQc=false)
    {
        foreach($data as $v){
            if($delQc){
                $temp['is_qc'] = 0;
                $temp['is_qc_required'] = 0;
                $temp['check_tool'] = '';
                $temp['qc_group_id']=0;
            }else{
                $temp['is_qc'] = 1;
                $temp['is_qc_required'] = $v['is_qc_required'];
                $temp['check_tool'] = json_encode($v['check_tool']);
                $temp['qc_group_id']=$group_id;
            }            
            $where['category_id']=$category_id;
            $where['attribute_id']=$v['attribute_id'];            
            $this->allowField(true)->where($where)->update($temp);
        }
    }
}