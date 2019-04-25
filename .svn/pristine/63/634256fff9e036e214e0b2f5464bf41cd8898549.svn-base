<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-1
 * Time: 下午2:48
 */

namespace app\publish\task;


use app\common\model\shopee\ShopeeCategory;
use app\index\service\AbsTasker;
use think\Exception;

class ShopeeCategoryLevelAnalyse extends AbsTasker
{
    public function getName()
    {
       return 'shopee分类层次分析';
    }

    public function getDesc()
    {
        return 'shopee分类层次分析';
    }

    public function getCreator()
    {
        return 'joy';
    }

    public function getParamRule()
    {
        return [];
    }

    public function execute()
    {
       set_time_limit(0);
       try{
           $page = 1;$pageSize=50;
           do{
               $categorys = ShopeeCategory::page($page,$pageSize)->order('category_id','DESC')->select();
               if(empty($categorys)){
                   break;
               }else{
                   ++$page;
                    $this->analyse($categorys);
               }
           }while(count($categorys) == $pageSize);
       }catch (Exception $exp){
           throw new Exception("{$exp->getMessage()}");
       }
    }
    private function analyse($categorys){
        foreach ($categorys as $category){
            $this->getLevel($category);
        }
    }
    private function getLevel($category,$parentCategory=[],$level=1){
        if($level==1){
            $parentCategory = ShopeeCategory::where('category_id',$category['parent_id'])->field('parent_id,category_name,category_id')->find();
        }else{
            $parentCategory = ShopeeCategory::where('category_id',$parentCategory['parent_id'])->field('parent_id,category_name,category_id')->find();
        }
        //查找父分类,如果存在，则层次加１,如果不存在，则更新level
        if($parentCategory){
            ++$level;
            $this->getLevel($category,$parentCategory,$level);
        }else{
            dump($level);
            ShopeeCategory::where('category_id',$category['category_id'])->setField('level',$level);
        }
    }
}