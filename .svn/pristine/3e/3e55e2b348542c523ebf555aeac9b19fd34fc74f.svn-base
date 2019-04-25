<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-10-14
 * Time: 下午2:15
 */

namespace app\listing\queue;
use app\common\cache\Cache;
use app\common\cache\driver\WishListing;
use app\common\exception\QueueException;
use app\common\exception\TaskException;
use app\common\model\wish\WishActionLog;
use app\common\service\SwooleQueueJob;
use app\listing\controller\Wish;
use app\listing\service\WishItemUpdateService;
use app\publish\queue\WishQueue;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\model\wish\WishWaitUploadProductVariant;
use think\Db;
use service\wish\WishApi;
use app\listing\service\WishListingHelper;
use app\common\service\Twitter;
use think\Exception;

class WishListingUpdateQueue extends  SwooleQueueJob{
    const PRIORITY_HEIGHT = 10;
    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }
    public function getName():string
    {
        return 'wish同步修改了的Listing (队列)';
    }
    public function getDesc():string
    {
        return 'wish同步修改了的Listing(队列)';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public  function execute()
    {
        try {
            $id = $this->params;
            if ($id)
            {
                $product = (new WishActionLog())
                    ->with(['product'=>function($query){$query->field('id,accountid,product_id')->with(['account'=>function($query){$query->field('id,access_token,code');}]);}])
                    ->where('id','=',$id)->find();
                if($product)
                {
                    $status = $product->getData('status');
                    $product = $product->toArray();

                    if($status!=1)
                    {
                        $api = $product['type'];
                        switch ($api)
                        {
                            case 1:
                                WishItemUpdateService::updateProduct($product);
                                break;
                            case 2:
                                WishItemUpdateService::updateVariant($product);
                                break;
                            case 3:
                                WishItemUpdateService::enableProduct($product);
                                break;
                            case 4:
                                WishItemUpdateService::disableProduct($product);
                                break;
                            case 5:
                                WishItemUpdateService::enableVariant($product);
                                break;
                            case 6:
                                WishItemUpdateService::disableVariant($product);
                                break;
                            case 7:
                                WishItemUpdateService::updateInventory($product);
                                break;
                            case 8:
                                WishItemUpdateService::updateShipping($product);
                                break;
                            case 9:
                                WishItemUpdateService::updateMultiShipping($product);
                                break;
                            case 10:
                                WishItemUpdateService::updateQipaMultiShipping($product);
                                break;
                            case 12:
                                WishItemUpdateService::disableWishExpress($product);
                                break;
                            default:
                                break;
                        }
                    }
                }

            }
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
}