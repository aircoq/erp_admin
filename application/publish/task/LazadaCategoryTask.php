<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-22
 * Time: 下午2:05
 */

namespace app\publish\task;


use app\common\model\lazada\LazadaSite;
use app\index\service\AbsTasker;
use app\publish\helper\lazada\LazadaHelper;
use think\Exception;

class LazadaCategoryTask extends AbsTasker
{
    public function getName()
    {
       return 'lazada分类';
    }

    public function getDesc()
    {
        return 'lazada分类';
    }

    public function getCreator()
    {
        return 'thomas';
    }

    public function getParamRule()
    {
       return [];
    }

    public function execute()
    {
       set_time_limit(0);
       try {
           $siteCodes = LazadaSite::column('id, code');
           $res = (new LazadaHelper())->syncCategoriesByCountry(1, 'th');
           print_r($res);
           exit;
//           $res = (new LazadaHelper())->syncCategoriesByCountry(2, 'my');  5114  5050
//           $res = (new LazadaHelper())->syncCategoriesByCountry(3, 'id');
//           $res = (new LazadaHelper())->syncCategoriesByCountry(4, 'ph');
//           $res = (new LazadaHelper())->syncCategoriesByCountry(5, 'sg');
//           $res = (new LazadaHelper())->syncCategoriesByCountry(6, 'vn');
//           exit;
           foreach ($siteCodes as $siteId => $siteCode) {
               $res = (new LazadaHelper())->syncCategoriesByCountry($siteId, $siteCode);
               if ($res !== true) {
                   echo $res."\n";
               } else {
                   echo "sync category completely\n";
               }
           }
       } catch (Exception $exp) {
           throw new Exception($exp->getMessage());
       }
    }
}