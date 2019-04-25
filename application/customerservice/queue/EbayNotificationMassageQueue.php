<?php
namespace app\customerservice\queue;

use org\EbayXml;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Exception;
use app\index\service\EbayAccountService;
use app\customerservice\service\EbayMessageHelp;

/**
 * Rondaful
 * 18-01-29
 * ebay获取指定message消费队列
 */
class EbayNotificationMassageQueue extends SwooleQueueJob
{
    static $accounts = [];
    private $accountService = null;
    
    public function getName(): string {
        return '处理ebay Notification MemberMessage';
    }

    public function getDesc(): string {
        return '处理ebay Notification MemberMessage';
    }

    public function getAuthor(): string {
        return '冬';
    }
    
    public static function swooleTaskMaxNumber():int
    {
        return 8;
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
                throw new Exception('Ebay通知XML的键:'. $key. ' 不存在，或xml为空');
            }

            $xml = $this->getContent($xml);

            $response = new EbayXml($xml);

            if (!$response || !isset($response->xml) || !isset($response->xml['soapenvEnvelope']['soapenvBody']['GetMemberMessagesResponse'])) {
                Cache::handler()->del($key);
                return false;
            }
            $result =  $response->xml['soapenvEnvelope']['soapenvBody']['GetMemberMessagesResponse'];
            //ebay帐号名称；
            $accountName = $result['RecipientUserId']?? $result['RecipientUserID'] ?? '';
            $accountId = $this->getAccountId($accountName);
            if (!$accountId) {
                throw new Exception('不识别账号名称：'. $accountName);
            }
            $result = $response->xml['soapenvEnvelope']['soapenvBody']['GetMemberMessagesResponse']['MemberMessage']['MemberMessageExchange'];
            $dataArr = isset($result['Question'])? [$result] : $result;
            $help = new EbayMessageHelp();
            $help->notificationMemberMessage($dataArr, $accountId, $accountName);
            Cache::handler()->del($key);
            return true;
        } catch (Exception $e) {
            Cache::handler()->del($key);
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
