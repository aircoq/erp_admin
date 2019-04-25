<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2018/11/1
 * Time: 10:15
 */

namespace app\publish\queue;


use app\common\model\Channel;
use app\common\model\ebay\EbayActionLog;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\Goods;
use app\common\model\GoodsSku;
use app\common\service\CommonQueuer;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\internalletter\service\InternalLetterService;
use app\listing\queue\EbayEndItemQueue;
use app\publish\helper\ebay\EbayPublish;
use app\publish\service\EbayListingService;

class EbayInfringeEnd extends SwooleQueueJob
{
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }

    public function getName():string
    {
        return '商品系统推送ebay平台下架队列';
    }

    public function getDesc():string
    {
        return '商品系统推送ebay平台下架队列';
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
//            'channel_id'=>$row['channel_id']//渠道id
//            'create_id'=>$user_id//操作人id
//            'type'=>1//下架类型，0侵权，1停售
//            'sku_id'=>14234//要下架的sku id
//        ];
        $params = $this->params;
        if ($params['channel_id'] != 1 && (!in_array(1,$params['notice_channel']) || $params['type'] == 1)) {
            //没有涉及到本平台，而且侵权通知平台不包含本平台或是停售推送
            return;
        }
        //获取listing信息
        $wh['goods_id'] = $params['goods_id'];
        !empty($params['ban_shop_id']) && $wh['account_id'] = ['not in', $params['ban_shop_id']];
        $wh['draft'] = 0;
        $wh['item_id'] = ['neq',0];
        $wh['listing_status'] = ['in',[3,5,6,7,8,9,10]];
        $field = 'id,realname,variation,account_id,site,item_id,listing_sku,local_sku';
        $listingItemIds = EbayListing::where($wh)->column($field, 'id');
        if (empty($listingItemIds)) {
            return;
        }

        //判断是否需要下架
        if ($params['channel_id'] == 1) {//需要下架
            //推入下架队列
            $backWriteData = [
                'goods_id' => $params['goods_id'],
                'goods_tort_id' => $params['tort_id'],
                'channel_id' => 1,
                'status' => 0,
            ];
            if ($params['type']) {//停售的需要区分单多属性
                $sListingItemIds = [];//记录单属性信息
                $varListingItemIds = [];//记录多属性信息
                foreach ($listingItemIds as $lid => $listingItemId) {
                    if ($listingItemId['variation']) {//多属性
                        $varListingItemIds[$lid] = $listingItemId;
                    } else {//单属性
                        $sListingItemIds[$lid] = $listingItemId;
                    }
                }
                $listingItemIds = $sListingItemIds;//单属性的下架
                //多属性的库存调0
                $varWh = [
                    'sku_id' => $params['sku_id'],
                    'listing_id' => ['in',array_keys($varListingItemIds)],
                ];
                $variants = EbayListingVariation::where($varWh)->column('id,channel_map_code,listing_id','id');
                $data = [];
                foreach ($variants as $variant) {
                    $data[] = [
                        'item_id' => $varListingItemIds[$variant['listing_id']]['item_id']??0,
                        'listing_sku' => $variant['channel_map_code'],
                        'account_id' => $varListingItemIds[$variant['listing_id']]['account_id']??0,
                        'site' => $varListingItemIds[$variant['listing_id']]['site']??0,
                        'remark' => '停售调0',
                        'quantity' => 0,
                    ];
                    $backWriteData['listing_id'] = $variant['listing_id'];
                    $backWriteData['sku_id'] = $params['sku_id'];
                    $backWriteData['item_id'] = $varListingItemIds[$variant['listing_id']]['item_id']??0;
                    (new CommonQueuer(\app\goods\queue\GoodsTortListingQueue::class))->push($backWriteData);//回写
                }
                if ($data) {
                    (new EbayListingService($params['create_id']))->updatePriceQty($data);
                }
            }

            $log = [
                'new_val' => [
                    'tort_id' => $params['tort_id'],
                    'end_type' => $params['type'] ? 3 : 2,
                    'goods_id' => $params['goods_id'],
                    ],
                'remark' => $params['type'] ? '停售下架' : '侵权下架',
                'old_val' => '',
                'create_id' => $params['create_id'],
                'api_type' => 4,//下架
            ];
            if ($params['type']) {//停售下架
                $sku = GoodsSku::where('id',$params['sku_id'])->value('sku');
            }
            foreach ($listingItemIds as $listing) {
                if ($params['type'] && $listing['local_sku']!=$sku) {//多属性按单属性上架时，如果sku不对应，不下架
                    continue;
                }
                //下架走日志
                $log['item_id'] = $listing['item_id'];
                $log['account_id'] = $listing['account_id'];
                $log['listing_sku'] = $listing['listing_sku'];
                $log['new_val']['listing_id'] = $listing['id'];
                $log['new_val']['item_id'] = $listing['item_id'];
                $log['new_val'] = json_encode($log['new_val']);
                EbayActionLog::create($log);
                $logId = (new EbayActionLog())->getLastInsID();
                EbayListing::update(['listing_status'=>EbayPublish::PUBLISH_STATUS['inEndQueue']],['item_id'=>$listing['item_id']]);
                (new UniqueQueuer(EbayEndItemQueue::class))->push($logId);
                $backWriteData['listing_id'] = $listing['id'];
                $backWriteData['item_id'] = $listing['item_id'];
                (new CommonQueuer(\app\goods\queue\GoodsTortListingQueue::class))->push($backWriteData);//回写
            }

        }
        if ($params['type']) {
            return;//停售的不通知
        }

        $userIds = [];
        foreach ($listingItemIds as $itemId => $listing) {
            $userIds[] = $listing['realname'];//记录创建者
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