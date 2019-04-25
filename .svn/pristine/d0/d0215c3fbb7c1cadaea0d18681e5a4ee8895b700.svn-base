<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-10-16
 * Time: 下午2:51
 */

namespace app\listing\queue;

use app\common\exception\QueueException;
use app\common\model\aliexpress\AliexpressActionLog;
use app\common\service\SwooleQueueJob;
use app\listing\service\AliexpressItemService;

class AliexpressListingUpdateQueue extends  SwooleQueueJob{
    public function getName():string
    {
        return '速卖通同步修改了的Listing (队列)';
    }
    public function getDesc():string
    {
        return '速卖通同步修改了的Listing(队列)';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

    public  function execute()
    {
        try {
            $id = $this->params;
            if ($id)
            {
                $product = (new AliexpressActionLog())
                    ->with(['product'=>function($query){$query->field('id,account_id,product_id')->with(['account'=>function($query){$query->field('id,access_token');}]);}])
                    ->where('id','=',$id)->find();

                if($product->getData('status')!=1)
                {

                    $api = $product['type'];
                    $product = $product->toArray();
                    switch ($api)
                    {
                        case 1:
                            AliexpressItemService::editSimpleProductFiled($product);
                            break;
                        case 2:
                            AliexpressItemService::editSingleSkuPrice($product);
                            break;
                        case 3:
                            AliexpressItemService::editSingleSkuStock($product);
                            break;
                        case 4:
                            AliexpressItemService::editAeProduct($product);
                            break;
                        case 5:
                            AliexpressItemService::onlineAeProduct($product);
                            break;
                        case 6:
                            AliexpressItemService::offlineAeProduct($product);
                            break;
                        case 7:
                            AliexpressItemService::renewExpire($product);
                            break;
                        case 8:
                            AliexpressItemService::setGroups($product);
                            break;
                        case 9:
                            AliexpressItemService::RsyncListing($product);
                            break;
                        case 10:
                            AliexpressItemService::editTemplate($product);
                        default:
                            break;
                    }
                }

            }
        }catch (QueueException $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

}