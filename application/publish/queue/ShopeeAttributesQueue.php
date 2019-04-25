<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-8-20
 * Time: 上午11:48
 */

namespace app\publish\queue;


use app\common\model\shopee\ShopeeSite;
use app\common\service\SwooleQueueJob;
use app\publish\helper\shopee\ShopeeHelper;
use think\Exception;

class ShopeeAttributesQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'shopee分类属性队列';
    }

    public function getDesc(): string
    {
        return 'shopee分类属性队列';
    }

    public function getAuthor(): string
    {
        return 'wlw2533';
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $params = $this->params;
            list($site_id,$category_id) = explode("|",$params);
            $country = ShopeeSite::where(['id'=>$site_id])->value('code');
            $res = (new ShopeeHelper())->syncAttributes($country, $category_id);
            if ($res !== true) {
                throw new Exception($res);
            }
        }catch (Exception $exp){
            throw new Exception("{$exp->getMessage()}");
        }
    }
}