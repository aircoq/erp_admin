<?php
namespace app\common\model;

use think\Model;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class Category extends Model
{
    /**
     * 初始化
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    public function getIdAttr($value) 
    {
        return $value;
    }
    /**
     * 获取分类映射信息
     */
     public function getMap()
     {
         return $this->hasMany('CategoryMap','category_id','id');
     }

    /** 查看分类是否有儿子
     * @param int $category_id
     * @return bool
     */
    public function hasChild($category_id = 0)
    {
        $data = $this->where(['pid' => $category_id])->select();
        if(empty($data)){   //没有儿子
            return false;
        }
        return true;
    }

    /** 获取指定分类内容
     * @param $category
     * @param $data
     * @return array
     */
    public function getCategory($category,$data)
    {
        $result = [];
        $temp = [];
        foreach($data as $k => $v){
            $temp[$k] = [
                'channel_id' => $v['channel_id'],
                'channel_category_id' => $v['channel_category_id'],
            ];
        }
        $result[0] = $category;
        $result[0]['platform'] = $temp;
        return $result;
    }

    /** 判断记录是否存在
     * @param $category_id
     * @return bool
     */
    public function hasData($category_id)
    {
        $result = $this->where(['id' => $category_id])->select();
        if(empty($result)){
            return false;
        }
        return true;
    }

    /**获取指定分类所有子分类
     * @param $pid
     * @return array
     */
    public function getAllChilds($pid,$cateIdsAll=[])
    {
        $cateIds = [$pid];
        $cates = $this->where(['pid'=>$pid])->select();
        foreach($cates as $k => $v){
            if(count($this->where(['pid'=>$v['id']])->select())){
                $cateIds[] += $this->getAllChilds($v['id'],$cateIdsAll);
            }else{
                $cateIds[] = $v['id']; 
            }
        }
        return $cateIdsAll+$cateIds;
    }

}