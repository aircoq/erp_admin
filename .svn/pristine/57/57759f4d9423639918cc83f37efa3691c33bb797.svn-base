<?php
/**
 * Created by PhpStorm.
 * User: panguofu
 * Date: 2018/11/2
 * Time: 下午2:08
 */

namespace app\publish\queue;

use app\common\model\wish\WishWaitUploadProductVariant;
use app\common\service\CommonQueuer;
use app\common\model\Channel;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\listing\service\WishListingHelper;
use think\Exception;
use app\common\exception\QueueException;
use app\listing\service\WishItemUpdateService;
use app\common\model\wish\WishWaitUploadProduct;

use app\internalletter\service\InternalLetterService;
use app\goods\service\GoodsHelp;
use app\common\service\ChannelAccountConst;


class WishInfringeEnd extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'wish侵权下架';
    }

    public function getDesc(): string
    {
        return 'wish侵权下架......';
    }

    public function getAuthor(): string
    {
        return '潘多拉';
    }

    public function execute()
    {

        $params = $this->params;
        if ($params) {

            if ($params['channel_id'] != ChannelAccountConst::channel_wish
                && (!in_array(ChannelAccountConst::channel_wish, $params['notice_channel']) || $params['type']==1)) {
                return false;
            }

            $goods_id = $params['goods_id'];
            $ban_shop_id = $params['ban_shop_id'];

            if ($params['channel_id'] == ChannelAccountConst::channel_wish) {//需要下架
                //推入下架队列
                $backWriteData = [
                    'goods_id' => $params['goods_id'],
                    'tort_id' => $params['tort_id'],
                    'channel_id' => ChannelAccountConst::channel_wish,
                    'status' => 0,
                ];

                if ($params['type']) {//SKU停售
                    $varWh = [
                        'sku_id' => $params['sku_id'],
                        'status' => 1,
                        'enabled' => 1,
                        'product_id' => ['<>',0],
                        'variant_id' => ['<>',0],
                    ];
                    $field = 'pid,product_id,variant_id';
                    $variantIds = WishWaitUploadProductVariant::where($varWh)->column($field,'variant_id');
                    if ($variantIds) {
                        foreach ($variantIds as $id => $variantId) {
                            $backWriteData['listing_id'] = $variantId['pid'];
                            $backWriteData['item_id'] = $id;
                            (new CommonQueuer(\app\goods\queue\GoodsTortListingQueue::class))->push($backWriteData);//回写
                            $res = (new WishListingHelper())->variantOnOff($id,'disableVariant',$params['create_id'],0,'停售');
                            if ($res['result'] === false) {
                                throw new Exception($res['message']);
                            }
                        }
                    }
                } else {//侵权下架
                    $wh = [
                        'product_id' => ['<>',0],
                        'publish_status' => 1,
                    ];
                    if ($ban_shop_id) {
                        $wh['account_id'] = ['not in',$ban_shop_id];
                    }
                    $products = WishWaitUploadProduct::where($wh)->column('uid,product_id','id');
                    foreach ($products as $id => $pid) {
                        $backWriteData['listing_id'] = $id;
                        $backWriteData['item_id'] = $pid['product_id'];
                        (new UniqueQueuer(\app\goods\queue\GoodsTortListingQueue::class))->push($backWriteData);//回写
                    }
                    (new WishListingHelper())->disableInableAction(array_column($products,'product_id'),'disableProduct',$params['create_id'],'下架',0,'');
                }
            }
            if ($params['type']) {
                return;
            }

            $user_ids = array_unique(array_column($products,'product_id'));
            if (count($user_ids) > 0) {
                $goodsBase = (new GoodsHelp())->getBaseInfo($goods_id);
                $title = $goodsBase['spu'] . '侵权下载通知';
                $channel = Channel::column('name', 'id');
                foreach ($user_ids as $k => $user_id) {
                    if (!$user_id) {
                        unset($user_ids[$k]);
                    }
                }

                $internalLetter = [
                    'receive_ids' => $user_ids,
                    'title' => $title,
                    'content' => 'SPU:' . $goodsBase['spu'] . '因' . $params['reason'] . '原因已在' . $channel[$params['channel_id']] . '平台已下架，请及时处理对应平台。',
                    'type' => 13,
                    'dingtalk' => 1
                ];
                InternalLetterService::sendLetter($internalLetter);
            }


        } else {
            throw new QueueException("数据为空");
        }
    }


    /***
     * 发送钉钉通知
     * @param $receive_ids
     * @param $title
     * @param $content
     * @param $type
     */
//    private function sendDingNotice($receive_ids, $title, $content, $type)
//    {
//
//        $InternalLetterService = new InternalLetterService();
//        $params = [
//            'receive_ids' => $receive_ids,
//            'title' => $title,
//            'content' => $content,
//            'type' => $type
//        ];
//        $InternalLetterService->sendLetter($params);
//
//
//    }

}