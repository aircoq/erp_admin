<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-1-7
 * Time: 上午9:56
 */

namespace app\publish\queue;


use app\common\model\joom\JoomProduct;
use app\common\service\CommonQueuer;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\publish\service\JoomPublishHelper;
use service\joom\JoomApi;
use think\Exception;
use app\common\exception\QueueException;

use app\report\queue\StatisticByPublishSpuQueue;
use app\common\service\ChannelAccountConst;

class JoomQueueJob extends SwooleQueueJob
{
    protected static $priority=self::PRIORITY_HEIGHT;
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }
    public function getName():string
    {
        return 'joom刊登队列';
    }
    public function getDesc():string
    {
        return 'joom刊登队列';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public  function execute()
    {
        set_time_limit(0);
        try{
            $id = $this->params;

            if($id)
            {
                $map['status']=['<>',1];
                $where['id']=['=',$id];

                $product= JoomProduct::where($where)
                    ->with(['info','variants'=>function($query)use($map){$query->order('price ASC')->where($map);},'shop'=>function($query){$query->field('id,access_token,code')
                    ->whereNotNull('access_token');}])
                    ->find();
                $platformStatus = (new \app\goods\service\GoodsHelp())->getPlatformForChannel($product['goods_id'],7);
                if (!$platformStatus) {
                    throw new Exception('商品在该平台已禁止上架');
                }


                //如果定时刊登时间为0，或者定时刊登时间小于等于当前时间，则满足刊登条件，执行刊登
                if($product && $product['cron_time']<=time())
                {
                    $product = is_object($product)?$product->toArray():$product;

                    $info = $product['info'];
                    $product = array_merge($product,$info);
                    $variants=$product['variants'];
                    //存在没有刊登的数据
                    if(!empty($variants) && !empty($product['shop']['access_token']))
                    {
                        $helper = new JoomPublishHelper();
                        $config['id']=$product['shop']['id'];
                        $config['access_token']=$product['shop']['access_token'];
                        $api = JoomApi::instance($config)->loader('Product');
                        $productId=$helper->addVarints($product,$variants,$api);
                        //刊登成功,同步线上数据
                        if($productId)
                        {

                            //刊登成功后push到"SPU上架实时统计队列"
                            $param = [
                                'channel_id' => ChannelAccountConst::channel_Joom,
                                'account_id' => $config['id'],
                                'shelf_id' => $product['create_id'],
                                'goods_id' => $product['goods_id'],
                                'times'    => 1, //实时=1
                                'quantity' => count($variants),//SKU的数量
                                'dateline' => time()
                            ];
                            (new CommonQueuer(StatisticByPublishSpuQueue::class))->push($param);

                            (new UniqueQueuer(JoomRsyncListing::class))->push($productId);
                        }
                    }else{
                        throw new QueueException("帐号id:[{$product['shop']['id']}]数据错误");
                    }
                }else{
                    throw new QueueException("数据不存在或者不满足刊登条件");
                }
            }else{
                throw new QueueException("数据为空");
            }
        }catch (Exception $exp) {
            throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
        }catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
}