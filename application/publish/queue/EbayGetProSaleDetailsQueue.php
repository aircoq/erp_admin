<?php
namespace app\publish\queue;

use service\ebay\EbayApi;
use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use think\Exception;

/**
 * Rondaful
 * 17-12-20
 * ebay获取折扣
 */

class EbayGetProSaleDetailsQueue extends SwooleQueueJob
{
    private $accountId = null;
    
    public function getName(): string {
        return 'ebay获取账号折扣';
    }

    public function getDesc(): string {
        return 'ebay获取账号折扣';
    }

    public function getAuthor(): string {
        return 'zhaibin';
    }
    
    public function execute()
    {
        try {    
            // 从队列中获取待更新的ebay账号
            $aid = $this->params;
            if (!$aid) {
                throw new Exception('账号id不能为空');
            }
            $this->accountId = $aid;
            $this->processingResults();
            return true;
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage());
        }
    }

    public function processingResults()
    {
        $acInfo = Cache::store('EbayAccount')->getAccountById($this->accountId);
        $tokenArr = json_decode($acInfo['token'], true);
        $token = trim($tokenArr[0]) ? $tokenArr[0] : $acInfo['token'];
        $ebayApi = $this->createApi($acInfo, $token); // 创建API对象
        do {
            $xml = $this->createXml($token);
            $resText = $ebayApi->createHeaders()->__set("requesBody", $xml)->sendHttpRequest2();
            if (isset($resText['GetPromotionalSaleDetailsResponse'])) {
                $response = $resText['GetPromotionalSaleDetailsResponse'];
                if ($response['Ack'] == "Success") {
                    if (!$response['PromotionalSaleDetails']) {
                        break;
                    }
                    $promotions = isset($response['PromotionalSaleDetails']['PromotionalSale']['PromotionalSaleID']) ? [$response['PromotionalSaleDetails']['PromotionalSale']] : $response['PromotionalSaleDetails']['PromotionalSale'];
                    foreach($promotions as $promotion) {
                        $this->handle($promotion);
                    }
                } else {
                    throw new Exception($response['Errors']['ShortMessage']);
                }
            } else {
                throw new Exception('执行时报错,没有获取到GetPromotionalSaleDetailsResponse节点');
            }
        } while (false);

        return true;
    }

    /*
     * title 实例化API对象
     * @param list listing详细信息
     * @param account_id 销售账号ID
     * @return $ebayApi obj
     */

    private function createApi(&$acInfo, $token)
    {
        $config['devID'] = $acInfo['dev_id'];
        $config['appID'] = $acInfo['app_id'];
        $config['certID'] = $acInfo['cert_id'];
        $config['userToken'] = $token;
        $config['compatLevel'] = 957;
        $config['siteID'] = 0;
        $config['verb'] = 'GetPromotionalSaleDetails';
        $config['appMode'] = 0;
        $config['account_id'] = $acInfo['id'];
        return new EbayApi($config);
    }
    
    /*
     * title 同步折扣信息
     * @param array $promotion listing详细信息
     * @return boolean
     */
    public function handle($promotion)
    {
        $proDetail['discount_type'] = $promotion['DiscountType'];
        $proDetail['discount_value'] = $promotion['DiscountValue'];
        $proDetail['start_time'] = $promotion['PromotionalSaleStartTime'];
        $proDetail['end_time'] = $promotion['PromotionalSaleEndTime'];
        $proDetail['type'] = $promotion['PromotionalSaleType'];
        $proDetail['promotion_id'] = $promotion['PromotionalSaleID'];
        $proDetail['name'] = $promotion['PromotionalSaleName'];
        $proDetail['status'] = $promotion['Status'];
        $proDetail['ItemIdArr'] = is_array($promotion['PromotionalSaleItemIDArray']['ItemID']) ? $promotion['PromotionalSaleItemIDArray']['ItemID'] : [$promotion['PromotionalSaleItemIDArray']['ItemID']];
        \think\Log::write($proDetail);
    }

    /*
     * title 创建获取折扣的xml
     * @param token 账号秘钥
     * @return string
     */
    public function createXml($token)
    {
        $requesBody = '<?xml version="1.0" encoding="utf-8"?>';
        $requesBody .= '<GetPromotionalSaleDetailsRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $requesBody .= '<RequesterCredentials>';
        $requesBody .= '<eBayAuthToken>' . $token . '</eBayAuthToken>';
        $requesBody .= '</RequesterCredentials>';
        $requesBody .= '<ErrorLanguage>en_US</ErrorLanguage>';
        $requesBody .= '<WarningLevel>High</WarningLevel>';
        $requesBody .= '<DetailLevel>ReturnAll</DetailLevel>';
        $requesBody .= '</GetPromotionalSaleDetailsRequest>';
        return $requesBody;
    }
}
