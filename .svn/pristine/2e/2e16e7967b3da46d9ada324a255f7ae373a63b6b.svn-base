<?php
namespace app\api\controller;

use app\common\cache\Cache;
use think\Request;
use app\common\controller\Base;
use app\common\service\CommonQueuer;
use app\publish\queue\EbayItemOperationQueue;

/**
 * @title ebay通知接口
 * Class EbayNotification
 * @package app\api\controller
 */
class  EbayNotification extends Base
{

    private $xml = '';

    /**
     * @var array ['queue' ＝> 要执行的队列完整类名，必须是带有命名空间的完整类名， 'events'＝>执行些队列的数组]
     */
    private $operationQueue = [
        [
            'type' => 'listing',
            'queue' => 'app\publish\queue\EbayItemOperationQueue',
            'events' => ['ItemAddedToWatchList', 'ItemClosed', 'ItemListed', 'ItemLost', 'ItemRemovedFromWatchList', 'ItemRevised', 'ItemSold', 'ItemUnsold', 'ItemWon'],
        ],
        [
            'type' => 'message',
            'queue' => 'app\customerservice\queue\EbayNotificationMassageQueue',
            'events' => ['AskSellerQuestion'],
        ],
        [
            'type' => 'message',
            'queue' => 'app\customerservice\queue\EbayNotificationMyMassageQueue',
            'events' => ['MyMessagesM2MMessage', 'MyMessageseBayMessage'],
        ],
        [
            'type' => 'order',
            'queue' => 'app\order\queue\EbayNotificationTransactions',
            'events' => ['AuctionCheckoutComplete', 'CheckoutBuyerRequests', 'EndOfAuction', 'FixedPriceTransaction']
        ],
        [
            'type' => 'listing',
            'queue' => 'app\publish\queue\EbayNtfFixedPriceTransaction',
            'events' => ['FixedPriceTransaction']
        ],
        [
            'type' => 'return',
            'queue' => 'app\customerservice\queue\EbayNotificationReturnQueue',
            'events' => ['ReturnCreated', 'ReturnWaitingForSellerInfo', 'ReturnSellerInfoOverdue', 'ReturnShipped', 'ReturnDelivered', 'ReturnRefundOverdue', 'ReturnClosed', 'ReturnEscalated']
        ],
    ];
    /**
     * @title ebay 接收接口
     * @author rondaful
     * @package app\api\controller
     * @method POST
     * @url /api/ebay/notification
     * @noauth
     */
    public function item(Request $request)
    {
        try {
            $this->xml = $request->getInput();
            $cache = Cache::store('EbayNotification');
            $event = $this->getEvent();

            //事件区取失败，返回false;
            if (empty($event)) {
                $cache->saveXml($this->xml, 'noEvent');
                return json(['result' => true, 'message' => 'ok']);
            }

            //匹配预设的通知处理设置，调用对应的队列；
            foreach ($this->operationQueue as $operation) {
                if (in_array($event, $operation['events'])) {
                    $key = $cache->saveXml($this->xml, $operation['type']);
                    if ($key) {
                        (new CommonQueuer($operation['queue']))->push($key);
                    }
                    return json(['result' => true, 'message' => 'ok']);
                }
            }
            //用来接收保存,不在接收队列里面的通知类型；
            $cache->saveXml($this->xml, $event);
            return json(['result' => true, 'message' => 'ok']);
        } catch (\Exception $e) {
            return json(['result' => true, 'message' => 'ok']);
        }
    }

    /**
     * 匹配出通知类型；
     * @return bool|string
     */
    private function getEvent()
    {
        preg_match('@<NotificationEventName>([^<>]*)</NotificationEventName>@', $this->xml, $data);
        if(empty($data[1])) {
            return false;
        }
        return trim($data[1]);
    }
}

