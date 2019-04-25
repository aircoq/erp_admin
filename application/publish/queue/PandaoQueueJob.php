<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-2
 * Time: 上午11:57
 */

namespace app\publish\queue;


use app\common\exception\QueueException;
use app\common\model\pandao\PandaoProduct;
use app\common\service\SwooleQueueJob;
use app\publish\service\PandaoApiService;
use app\publish\service\PandaoService;
use service\pandao\PandaoApi;
use think\Exception;

use app\report\queue\StatisticByPublishSpuQueue;
use app\common\service\ChannelAccountConst;

class PandaoQueueJob extends SwooleQueueJob
{

    public function getName(): string
    {
        return 'pandao刊登队列';
    }

    public function getDesc(): string
    {
        return 'pandao刊登队列';
    }

    public function getAuthor(): string
    {
        return 'joy';
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $id = $this->params;
            if($id)
            {
                $map['status']=['<>',1];
                $where['a.id']=['=',$id];

                $product= PandaoProduct::where($where)->alias('a')->join('pandao_product_info b','a.id=b.id','LEFT')
                    ->with(['variants'=>function($query)use($map){$query->order('price ASC')->where($map);},'account'=>function($query){$query->field('id,access_token,code')->whereNotNull('access_token');}])
                    ->find();
                $platformStatus = (new \app\goods\service\GoodsHelp())->getPlatformForChannel($product['goods_id'],8);
                if (!$platformStatus) {
                    throw new Exception('商品在该平台已禁止上架');
                }



                //如果定时刊登时间为0，或者定时刊登时间小于等于当前时间，则满足刊登条件，执行刊登
                if($product && $product['cron_time']<=time())
                {
                    $product = is_object($product)?$product->toArray():$product;

                    $variants=$product['variants'];

                    //存在没有刊登的数据
                    if(!empty($variants) && !empty($product['account']['access_token']))
                    {

                        $config['id']=$product['account']['id'];
                        $config['access_token']=$product['account']['access_token'];
                        //$api = PandaoApi::instance($config)->loader('Product');
                        $productId=PandaoApiService::addVarints($product,$variants,$config);

                        //刊登成功,同步线上数据
                        if($productId)
                        {
                            if(isset($product['create_id']) && $product['create_id'])
                            {
                                $uid = $product['create_id'];
                                $queryWhere=[
                                    'create_id'=>['=',$uid],
                                    'new_data'=>['=','发布商品'],
                                    'product_id'=>['=',$productId],
                                    'status'=>['=',1],
                                ];
                                $log=[
                                    'create_id'=>$uid,
                                    'type'=>PandaoService::TYPE['online'],
                                    'new_data'=>'发布商品',
                                    'old_data'=>'',
                                    'product_id'=>$productId,
                                    'create_time'=>$product['create_time'],
                                    'run_time'=>time(),

                                ];
                                (new PandaoService())->ActionLog($log,$queryWhere);


                                //刊登成功后push到"SPU上架实时统计队列"-pan
                                $param = [
                                    'channel_id' => ChannelAccountConst::channel_Pandao,
                                    'account_id' => $config['id'],
                                    'shelf_id' => $product['create_id'],
                                    'goods_id' => $product['goods_id'],
                                    'times'    => 1, //实时=1
                                    'quantity' => count($variants),//SKU的数量
                                    'dateline' => time()
                                ];
                                (new CommonQueuer(StatisticByPublishSpuQueue::class))->push($param);


                            }

                            //(new UniqueQueuer(WishRsyncListing::class))->push($productId);
                        }
                    }
                }
            }
        }catch (Exception $exp) {
            throw new QueueException($exp->getMessage());
        }
    }
}