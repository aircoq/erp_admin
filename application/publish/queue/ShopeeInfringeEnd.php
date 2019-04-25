<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2018/11/5
 * Time: 15:58
 */

namespace app\publish\queue;


use app\common\model\Channel;
use app\common\model\Goods;
use app\common\model\shopee\ShopeeProduct;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\internalletter\service\InternalLetterService;

class ShopeeInfringeEnd extends SwooleQueueJob
{
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }

    public function getName():string
    {
        return 'shopee商品侵权下架';
    }

    public function getDesc():string
    {
        return 'shopee商品侵权下架';
    }

    public function getAuthor():string
    {
        return 'wlw2533';
    }

    public  function execute()
    {
//        参数格式
//        $data  = [
//            'tort_id'=>$row['tort_id'],//侵权id
//            'goods_id'=>$row['goods_id'],//商品id
//            'ban_shop_id'=>explode(',',$row['ban_shop_id']),//不用下架的店铺id
//            'notice_channel'=>$row[''],//需要通知的渠道id
//            'reason'=>$row['reason']//原因
//        ];
        $params = $this->params;
        if ($params['channel_id'] != 9 && !in_array(9,$params['notice_channel'])) {
            return false;
        }
        $wh['goods_id'] = $params['goods_id'];
        $wh['account_id'] = ['not in', $params['ban_shop_id']];
        $wh['item_id'] = ['neq',0];
        $wh['status'] = 1;
        $listingItemIds = ShopeeProduct::where($wh)->column('id,account_id,create_id','item_id');
        if (empty($listingItemIds)) {
            return false;
        }
        //判断是否需要下架
        if ($params['channel_id'] == 9) {//需要下架
            $itemIds = array_keys($listingItemIds);
            //先设置下架类型
            ShopeeProduct::update(['end_type'=>2],['item_id'=>['in',$itemIds]]);
            //推入下架队列
            $backWriteData = [
                'goods_id' => $params['goods_id'],
                'goods_tort_id' => $params['tort_id'],
                'channel_id' => 9,
                'status' => 0,
            ];
            foreach ($listingItemIds as $itemId => $listing) {
                $qData = [
                    'item_id' => $itemId,
                    'account_id' => $listing['account_id'],
                    'tort_id' => $params['tort_id']
                ];
                (new UniqueQueuer(ShopeeDeleteItemQueue::class))->push($qData);
                $backWriteData['listing_id'] = $listing['id'];
                $backWriteData['item_id'] = $itemId;
                (new UniqueQueuer(\app\goods\queue\GoodsTortListingQueue::class))->push($backWriteData);//回写
            }
        }

        $userIds = [];
        foreach ($listingItemIds as $itemId => $listing) {
            $userIds[] = $listing['create_id'];//记录创建者
        }
        $userIds = array_unique($userIds);
        $userIds = array_filter($userIds,function ($a) {
            return $a>0;
        });
        $userIds = array_values($userIds);
        if (empty($userIds)) {
            return false;
        }
        //发送钉钉消息
        $spu = Goods::where('id',$params['goods_id'])->value('spu');
        $channel = Channel::column('name','id');
        $internalLetter = [
            'receive_ids' => $userIds,
            'title' => '侵权下架',
            'content' => 'SPU:'.$spu.'因'.$params['reason'].'原因已在'.$channel[$params['channel_id']].'平台已下架，请及时处理对应平台。',
            'type' => 13,
            'dingtalk' => 1
        ];
        InternalLetterService::sendLetter($internalLetter);
    }

}