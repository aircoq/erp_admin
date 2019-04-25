<?php
namespace app\customerservice\queue;

use app\common\service\UniqueQueuer;
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
class EbayNotificationReturnQueue extends SwooleQueueJob
{
    static $accounts = [];
    private $accountService = null;
    
    public function getName(): string {
        return '处理ebay Notification Return';
    }

    public function getDesc(): string {
        return '处理ebay Notification Return';
    }

    public function getAuthor(): string {
        return '冬';
    }
    
    public static function swooleTaskMaxNumber():int
    {
        return 2;
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
            if (!$response || !isset($response->xml) || empty($response->xml['soapenvEnvelope']['soapenvBody']['NotificationEvent']['ReturnId'])) {
                return false;
            }
            $result =  $response->xml['soapenvEnvelope']['soapenvBody']['NotificationEvent'];
            //ebay帐号名称；
            $accountName = $result['RecipientUserId']?? $result['RecipientUserID'] ?? '';
            $accountId = $this->getAccountId($accountName);
            if (!$accountId) {
                throw new Exception('不识别账号名称：'. $accountName);
            }
            $returnId = $result['ReturnId'];
            //把参数放进队列去下载；
            (new UniqueQueuer(EbayReturnByIdQueue::class))->push(['account_id' => $accountId, 'return_id' => $returnId]);
            //(new EbayReturnByIdQueue(['account_id' => $accountId, 'return_id' => $returnId]))->execute();
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
