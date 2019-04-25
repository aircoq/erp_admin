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
class AttributeValue extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /** 保存映射
     * @param array $data
     * @param array $attribute_id
     */
    public function saveData(array $data,$attribute_id)
    {       
        $lists = [];
        foreach($data as $list) {
            $list['create_time']  = time();
            $list['attribute_id'] = $attribute_id;
            $lists[] = $list;
        }
        
        $this->allowField(true)->saveAll($lists);
    }
    
    /**
     * 属性数据
     * @param array $data 属性值数据
     * @param type $attribute_id
     */
    public function updateData(array $data)
    {
        $this->allowField(true)->saveAll($data);
    }
    
    /**
     * 删除属性数据
     */
    public function deleteData(array $data, $attribute_id)
    {
        foreach($data as $list) {
            $this->where(['id' => $list['id'], 'attribute_id' => $attribute_id])->delete();
        }
    }
}