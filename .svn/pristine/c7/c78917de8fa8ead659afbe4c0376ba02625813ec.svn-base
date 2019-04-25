<?php
namespace app\publish\queue;

use app\common\cache\Cache;
use org\EbayXml;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\publish\service\EbayListingCommonHelper;
use app\index\service\EbayAccountService;
use app\publish\helper\ebay\EbayPublish as EbayPublishHelper;

/**
 * Rondaful
 * 18-01-29
 * ebay获取指定Item消费队列
 */

class EbayItemOperationQueue extends SwooleQueueJob
{
    private $accountId = null;
    private $itemId = null;
    static $accounts = [];
    private $accountService = null;
    protected static $maxRunnerCount = 2000;

    public function getName(): string {
        return '处理ebay Notification';
    }

    public function getDesc(): string {
        return '处理ebay Notification';
    }

    public function getAuthor(): string {
        return 'zhaibin';
    }
    
    public static function swooleTaskMaxNumber():int
    {
        return 40;
    }
    
    public function init()
    {
        $this->accountService = new EbayAccountService();
    }
    
    public function execute()
    {
        try {
            $key = $this->params;

            if (!is_string($key)) {
                return false;
            }

            $xml = Cache::store('EbayNotification')->getXml($key);

            if (empty($xml)) {
                return false;
                // throw new Exception('Ebay通知XML的键:'. $key. ' 不存在，或xml为空');
            }

            $xml = $this->getContent($xml);

            $response = new EbayXml($xml);
            if (!$response || !isset($response->xml) || !isset($response->xml['soapenvEnvelope']['soapenvBody']['GetItemResponse'])) {
                return false;
            }
            $result =  $response->xml['soapenvEnvelope']['soapenvBody']['GetItemResponse'];
            $item = $result['Item'];
            $accountId = $this->getAccountId($result['RecipientUserID']);
            if (!$accountId) {
                throw new Exception('不识别账号名称'. $result['RecipientUserID']);
            }
            $this->accountId = $accountId;
            $handler = new EbayListingCommonHelper($this->accountId);
            $res = $handler->syncEbayListing($item);
            $handler->syncListingData($res,0,$result['NotificationEventName']);
            //重上逻辑
//            $notificationName = $result['NotificationEventName'];
//            if (in_array($notificationName, ['ItemUnsold', 'ItemSold'])) {
//                (new EbayPublishHelper())->autoRelist($res['listing']['item_id'], $notificationName);
//            }

            Cache::handler()->del($key);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getFile()."|".$e->getLine()."|".$e->getMessage());
        }
    }
    
    private function getAccountId($name)
    {
        if (isset(self::$accounts[$name])) {
            return self::$accounts[$name];
        }
        $id = $this->accountService->getIdByName($name);
        self::$accounts[$name] = $id;
        return $id;
    }
    
    private function getContent($content)
    {
        $content = str_replace(['soapenv:Envelope', 'soapenv:Body'], ['soapenvEnvelope', 'soapenvBody'], $content);
        return $content;
    }
}
