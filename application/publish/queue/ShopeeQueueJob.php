<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 18-5-28
 * Time: 下午5:56
 */

namespace app\publish\queue;


use app\common\exception\QueueException;
use app\common\model\shopee\ShopeeProduct;
use app\common\service\SwooleQueueJob;
use app\publish\helper\shopee\ShopeeHelper;
use app\publish\service\ShopeeApiService;
use think\Exception;

class ShopeeQueueJob extends SwooleQueueJob
{
    const PRIORITY_HEIGHT = 10;

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    public function getName(): string
    {
        return 'shopee刊登队列';
    }

    public function getDesc(): string
    {
        return 'shopee刊登队列';
    }

    public function getAuthor(): string
    {
        return 'wlw2533';
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $id = $this->params;
            ShopeeProduct::update(['publish_status'=>2], ['id'=>$id]);
            $res = (new ShopeeHelper())->addItem($id);
            if ($res !== true) {
                throw new Exception($res);
            }
//            if($id)
//            {
//                $map['publish_status']=['<>',1];
//                $where['a.id']=['=',$id];
//                $where['a.publish_status']=['<>',1];
//
//                $product= ShopeeProduct::where($where)->alias('a')->join('shopee_product_info b','a.id=b.id','LEFT')
//                    ->with(['variants'=>function($query)use($map){$query->order('price ASC')->where($map);},'account'])
//                    ->find();
//
//                //如果定时刊登时间为0，或者定时刊登时间小于等于当前时间，则满足刊登条件，执行刊登
//                if($product && $product['cron_time']<=time())
//                {
//                    $product = is_object($product)?$product->toArray():$product;
//
//                    $variants=$product['variants'];
//
//                    //存在没有刊登的数据
//                    if(!empty($variants) && !empty($product['account']['key']))
//                    {
//                        $config=$product['account'];
//                        ShopeeApiService::postProduct($product,$variants,$config);
//                    }
//                }
//            }
        }catch (Exception $exp) {
            throw new QueueException($exp->getMessage());
        }
    }
}