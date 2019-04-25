<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-13
 * Time: 上午10:36
 */

namespace app\publish\queue;


use app\common\exception\QueueException;
use app\common\model\joom\JoomActionLog;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\publish\service\JoomItemUpdateService;
class JoomListingUpdateQueue extends SwooleQueueJob
{
    protected static $priority=self::PRIORITY_HEIGHT;
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }
    public function getName():string
    {
        return 'joom在线listing更新队列';
    }
    public function getDesc():string
    {
        return 'joom在线listing更新队列';
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
                $product = (new JoomActionLog())
                    ->with(['product'=>function($query){$query->field('id,account_id,shop_id,product_id')->with(['shop'=>function($query){$query->field('id,access_token,code');}]);}])
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
                                JoomItemUpdateService::updateProduct($product);
                                break;
                            case 2:
                                JoomItemUpdateService::updateVariant($product);
                                break;
                            case 3:
                                JoomItemUpdateService::enableProduct($product);
                                break;
                            case 4:
                                JoomItemUpdateService::disableProduct($product);
                                break;
                            case 5:
                                JoomItemUpdateService::enableVariant($product);
                                break;
                            case 6:
                                JoomItemUpdateService::disableVariant($product);
                                break;
                            case 7:
                                JoomItemUpdateService::updateInventory($product);
                                break;
                            case 8:
                                JoomItemUpdateService::updateShipping($product);
                                break;
                            case 9:
                                JoomItemUpdateService::updateMultiShipping($product);
                                break;
                            case 10:
                                JoomItemUpdateService::updateQipaMultiShipping($product);
                                break;
                            default:
                                break;
                        }
                    }
                }else{
                    throw new QueueException("数据不存在");
                }
            }
        } catch (\Exception $exp){
            throw new Exception("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
}