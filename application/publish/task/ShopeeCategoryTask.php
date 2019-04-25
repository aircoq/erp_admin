<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-22
 * Time: 下午2:05
 */

namespace app\publish\task;


use app\common\model\shopee\ShopeeSite;
use app\index\service\AbsTasker;
use app\publish\helper\shopee\ShopeeHelper;
use think\Exception;

class ShopeeCategoryTask extends AbsTasker
{
    public function getName()
    {
       return 'shopee分类';
    }

    public function getDesc()
    {
        return 'shopee分类';
    }

    public function getCreator()
    {
        return 'wlw2533';
    }

    public function getParamRule()
    {
       return [];
    }

    public function execute()
    {
       set_time_limit(0);
       try{
           $siteCodes = ShopeeSite::column('code');
           foreach ($siteCodes as $siteCode){
               $res = (new ShopeeHelper())->syncCategoriesByCountry($siteCode);
               if ($res !== true) {
                   throw new Exception($res);
               }
           }
       }catch (Exception $exp){
           throw new Exception($exp->getMessage());
       }
    }
}