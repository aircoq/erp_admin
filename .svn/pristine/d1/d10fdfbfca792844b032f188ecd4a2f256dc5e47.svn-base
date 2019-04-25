<?php

namespace app\publish\queue;

/**
 * 曾绍辉
 * 18-3-17
*/
use app\common\service\CommonQueueJob;
use app\common\exception\TaskException;
use think\Db;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\cache\driver;
use app\index\service\AbsTasker;
use app\common\service\UniqueQueuer;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use think\Exception;

class ebayProductStatusQueue extends SwooleQueueJob
{
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }

    public function getName():string
    {
        return '同步ebaylisting本地产品状态';
    }

    public function getDesc():string
    {
        return '同步ebaylisting本地产品状态';
    }

    public function getAuthor():string
    {
        return 'zengsh';
    }

    public  function execute()
    {
        try{
            $modEbayListing = new EbayListing();
            $modEbayListingVar = new EbayListingVariation();
            $params = json_decode($this->params,true);
            if($params['type']==1){#产品状态
                $modEbayListing->where(['goods_id'=>$params['id']])->update(['sale_status'=>$params['status']]);
            }else if($params['type']==2){#子产品状态
                $modEbayListingVar->where(['sku_id'=>$params['id']])->update(['sku_status'=>$params['status']]);
            }
        }catch(Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }
}