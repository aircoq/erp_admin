<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/29
 * Time: 9:17
 */
class AttributeGroup extends Model
{
    /**
     * 初始化数据
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    public function categoryAttribute()
    {
        return $this->belongsToMany('CategoryAttribute');
    }

    /** 查看分类的属性组是否已经存在了
     * @param $name
     * @param $category_id
     * @return bool
     */
    public function isHas($name,$category_id)
    {
        $result = $this->where(['name' => $name,'category_id' => $category_id])->select();
        if(empty($result)){  //不存在
            return false;
        }
        return true;
    }
    
    /** 查看分类的属性组名是否存在
     * @param $group_id
     * @param $category_id
     * @return bool
     */
    public function check($group_id,$category_id)
    {
        $result = $this->where(['id' => $group_id,'category_id' => $category_id])->select();
        if(empty($result)){  //不存在
            return false;
        }
        return true;
    }
    
    /** 查看分类的属性组名是否存在
     * @param $name
     * @param $category_id
     * @return bool
     */
    public function checkName($name,$category_id, $group_id)
    {
        $result = $this->where(['category_id' => $category_id, 'name' => $name])->select();
        if(empty($result)){  //不存在
            return false;
        }
        
        if ($result[0]->id == $group_id) {
            return false;
        }
        
        return true;
    }

    /** 保存数据，并且返回id
     * @param array $data
     * @param $category_id
     * @return mixed
     */
    public function saveData(array $data,$category_id)
    {
        if($this->isHas($data['name'],$category_id)){
            //存在
           $result = $this->where(['name' => $data['name'],'category_id' => $data['category_id']])->select();
           return $result[0]['id'];
        }
        $this->allowField(true)->isUpdate(false)->save($data);
        return $this->id;
    }
}