<?php

namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\service\UniqueQueuer;
use app\goods\queue\GoodsTortListingQueue;
use app\listing\service\AliexpressListingHelper;
use app\common\cache\Cache;
use app\listing\service\AliexpressItemService;
use app\publish\service\AliProductHelper;

class AliExpressInfringeEnd extends  SwooleQueueJob{

    public function getName():string
    {
        return 'AliExpress侵权(批量sku)下架队列';
    }

    public function getDesc():string
    {
        return 'AliExpress侵权(批量sku)下架队列';
    }

    public function getAuthor():string
    {
        return 'hao';
    }


    public function init()
    {
    }

    public static function swooleTaskMaxNumber():int
    {
        return 40;
    }

    public function execute()
    {
        try{
            set_time_limit(0);
            $job = $this->params;

            if($job){

                //如果选择店铺,则去除选择的店铺
                $where = [];
                if(isset($job['ban_shop_id']) && $job['ban_shop_id']){

                    $accountIds = implode(',', $job['ban_shop_id']);
                    $where['account_id'] = ['not in', $accountIds];
                }


               if(isset($job['type']) && $job['type']) {
                    //批量下架
                    return (new AliProductHelper())->skuOffline($job);
                }

                //侵权下架
                $this->tortOffLine($where, $job);

            }

        }catch (QueueException $exp){
            throw  new QueueException($exp->getMessage().$exp->getFile().$exp->getLine());
        }
    }



    /**
     *侵权下架
     *
     */
    public function tortOffLine($where, $job)
    {
        //上架,刊登成功
        $where['product_status_type'] = ['=', 1];
        $where['product_id'] = ['>', 0];
        $where['goods_id'] = ['=', $job['goods_id']];

        $model = new AliexpressProduct();
        $list = $model->field('id,goods_id,product_id,account_id,salesperson_id,goods_spu')->where($where)->select();

        //1.查询侵权下架的数据
        if($list){

            foreach ($list as $key => $val){

                $data = [
                    'goods_id'=> $val['goods_id'],//商品id
                    'goods_tort_id'=> $job['tort_id'],//侵权下架id
                    'listing_id'=>$val['id'],//listing_id
                    'channel_id'=> 4,//平台id
                    'item_id'=> $val['product_id'],//平台listing唯一码
                    'status'=>'0'//状态 0 待下架   1 下架成功 2 下架失败
                ];

                //初始化回写
                (new UniqueQueuer(GoodsTortListingQueue::class))->push($data);

                //写入缓存
                Cache::handler()->set('AliExpressTortoffLine:'.$val['product_id'], \GuzzleHttp\json_encode($data));

                //发送钉钉,写入侵权下架队列
                $this->offLineQueue($val, $job);
            }
        }

        return;
    }

    /**
     *发送钉钉,写入侵权下架队列
     *type 13侵权下架
     */
    public function offLineQueue($val, $job)
    {

        //发送钉钉消息
        $content = '产品侵权了,可能会下架';
        AliexpressItemService::sendTortOffineLetter($val, $content);

        //如果channel_id为速卖通平台,则下架,否则只是发送消息,不下架
        if($job['channel_id'] == 4){
            //写入下架队列
            (new AliexpressListingHelper())->onOffLineProductLog($val['product_id'],0,'offline',0,$job['reason']);
        }
    }
}