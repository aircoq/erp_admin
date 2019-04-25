<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/24
 * Time: 9:23
 */

namespace app\publish\queue;


use app\common\exception\QueueException;
use app\common\service\CommonQueuer;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\listing\queue\WishExpressQueue;
use app\listing\queue\WishRsyncListing;
use app\listing\service\WishListingHelper;
use app\publish\service\WishPublishHelper;
use service\wish\WishApi;
use app\common\model\wish\WishWaitUploadProduct;
use think\Exception;
use app\report\queue\StatisticByPublishSpuQueue;
use app\common\service\ChannelAccountConst;

class WishQueueJob extends SwooleQueueJob
{
    protected static $priority = self::PRIORITY_HEIGHT;

    public static function swooleTaskMaxNumber(): int
    {
        return 4;
    }

    public function getName(): string
    {
        return 'wish刊登队列';
    }

    public function getDesc(): string
    {
        return 'wish刊登队列';
    }

    public function getAuthor(): string
    {
        return 'joy';
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            $id = $this->params;

            if ($id) {
                $map['status'] = ['<>', 1];
                $where['a.id'] = ['=', $id];

                $product = WishWaitUploadProduct::where($where)->alias('a')->join('wish_wait_upload_product_info b', 'a.id=b.id', 'LEFT')
                    ->with(['variants' => function ($query) use ($map) {
                        $query->order('price ASC')->where($map);
                    }, 'account' => function ($query) {
                        $query->field('id,access_token,code')->whereNotNull('access_token');
                    }])
                    ->find();


                //如果定时刊登时间为0，或者定时刊登时间小于等于当前时间，则满足刊登条件，执行刊登
                if ($product && $product['cron_time'] <= time()) //if (true)
                {
                    $product = is_object($product) ? $product->toArray() : $product;

                    $variants = $product['variants'];
                    //存在没有刊登的数据
                    if (!empty($variants) && !empty($product['account']['access_token'])) {
                        $helper = new WishPublishHelper();
                        $config['id'] = $product['account']['id'];
                        $config['access_token'] = $product['account']['access_token'];
                        $api = WishApi::instance($config)->loader('Product');
                        $productId = $helper->addVarints($product, $variants, $api);

                        //刊登成功,同步线上数据
                        if ($productId) {
                            if (isset($product['uid']) && $product['uid']) {
                                $uid = $product['uid'];
                                $queryWhere = [
                                    'create_id' => ['=', $uid],
                                    'new_data' => ['=', '发布商品'],
                                    'product_id' => ['=', $productId],
                                    'status' => ['=', 1],
                                ];
                                $log = [
                                    'create_id' => $uid,
                                    'type' => WishListingHelper::TYPE['online'],
                                    'new_data' => '发布商品',
                                    'old_data' => '',
                                    'product_id' => $productId,
                                    'create_time' => $product['addtime'],
                                    'run_time' => time(),

                                ];
                                (new WishListingHelper())->wishActionLog($log, $queryWhere);
                            }


                            //刊登成功后push到"SPU上架实时统计队列"
                            $param = [
                                'channel_id' => ChannelAccountConst::channel_wish,
                                'account_id' => $config['id'],
                                'shelf_id' => $product['uid'],
                                'goods_id' => $product['goods_id'],
                                'times'    => 1,                //实时=1
                                'quantity' => count($variants), //SKU的数量
                                'dateline' => time()
                            ];
                            (new CommonQueuer(StatisticByPublishSpuQueue::class))->push($param);

                            (new UniqueQueuer(WishRsyncListing::class))->push($productId);
                            (new CommonQueuer(WishExpressQueue::class))->push(['account_id' => $config['id'], 'product_id' => $productId]);
                        }
                    }
                }
            }
        } catch (\Exception $exp) {
            throw new QueueException($exp->getMessage());
        } catch (\Throwable $exp) {
            throw new QueueException($exp->getMessage());
        }
    }
}