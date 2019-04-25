<?php
namespace app\common\model\aliexpress;
use think\Model;
class AliexpressCategory extends Model
{
    protected $autoWriteTimestamp=false;
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }
    
    public function getCategoryIdAttr($value) 
    {
        return $value;
    }
    
    /**
     * @info 根据一个分类返回他的最顶层大类
     * @param unknown $intCategoryId
     */
    public function getTheMostPareantCategory($intCategoryId)
    {
        $objCategory = self::where('category_id',$intCategoryId)->find();
        if(!empty($objCategory)&&$objCategory->category_pid != 0)
        {
            return self::getTheMostPareantCategory($objCategory->category_pid);
        }
        else
        {
            return $objCategory;
        }
    }

    /**
     * 获取一个分类的所有父级
     * @param int $categoryId
     * @param array $arrCategoryIds
     * @return array
     */
    public static function getAllParent($categoryId,&$arrCategoryIds=[])
    {
        $arrCategory = self::where(['category_id'=>$categoryId])->field('category_pid,category_id,category_name_zh,required_size_model')->find();

        if($arrCategory){
              $arrCategory = $arrCategory->toArray();

            array_unshift($arrCategoryIds,['category_id'=>$categoryId,'category_name'=>$arrCategory['category_name_zh'],'required_size_model'=>$arrCategory['required_size_model']]);
            if($arrCategory['category_pid']!=0){
                self::getAllParent($arrCategory['category_pid'],$arrCategoryIds);
            }
        }

        return $arrCategoryIds;
    }
    /*
    public function getCategoryNameZhAttr($value,$data)
    {

        if($data['category_pid']>0)
        {

           $parentCategory = self::getAllParent($data['category_pid']);
           if ($parentCategory)
           {
               foreach ($parentCategory as $item)
               {
                   $value=$value.">>".$item['category_name'];
               }
           }

           return $value;
        }else{
            return $value;
        }
    }
    */
    
    public function getCategoryNameAttr($value)
    {
       return unserialize($value);
    }
    /**
     * 获取所有子分类
     * @param $cate
     * @param $pid
     * @return array
     */
    public static function getChildsByPid($pid,&$return=[])
    {
       $model = new self();
       $categorys = $model->field('category_id,category_pid,category_isleaf,category_name_zh')->where('category_pid',$pid)->select();
       if($categorys){
           foreach ($categorys as $category){
                $return[] = $category->toArray();
                self::getChildsByPid($category['category_id'],$return);
           }
       }
       return $return;
    }

    /**
     * 获取所有子分类
     * @param $cate
     * @param $pid
     * @return array
     */
    Public function getChilds($cate,$pid)
    {
        $arr = array();
        foreach ($cate as $v) 
        {
            if($v['category_pid'] == $pid)
            {
                $arr[] = $v;
                $arr = array_merge($arr,self::getChilds($cate,$v['category_id']));
            }
        }
        return $arr;
    }


    /**
     * 关联模型分类属性
     */
    public function attribute()
    {
        return $this->hasMany(AliexpressCategoryAttr::class,'category_id','category_id');
    }
}