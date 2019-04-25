<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-22
 * Time: 下午2:05
 */

namespace app\publish\task;


use app\common\model\shopee\ShopeeCategory;
use app\index\service\AbsTasker;
use app\publish\helper\shopee\ShopeeHelper;
use app\publish\queue\shopeeCategoryTranslateQueue;
use app\common\service\UniqueQueuer;
use think\Exception;
use app\publish\service\ShopeeService;


class ShopeeCategoryTranslateTask extends AbsTasker
{
    public function getName()
    {
       return 'shopee分类翻译';
    }

    public function getDesc()
    {
        return 'shopee分类翻译';
    }

    public function getCreator()
    {
        return 'hao';
    }

    public function getParamRule()
    {
       return [];
    }

    public function execute()
    {
       set_time_limit(0);
       try{

           //越南，泰国，印尼
           $where = [
               'site_id' => ['in', [1,4,7]],
               'category_name_en' => ['=', '']];

           $page = 1;
           $pageSize = 100;

           do{
               $categoryObj = new ShopeeCategory();

               $categorys = $categoryObj->field('id,category_name, category_name_en, site_id')->where($where)->page($page++, $pageSize)->select();

               if(empty($categorys)) {
                return;
               }

               $shopeeService = new ShopeeService;
               $shopeeService->categoryNameEn($categoryObj, $categorys);

           }while(count($categorys) == $pageSize);

           true;
       }catch (Exception $exp){
           throw new Exception($exp->getMessage());
       }
    }
}