<?php
namespace app\publish\queue;

use service\ebay\EbayApi;
use app\common\service\SwooleQueueJob;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingImage;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbayListingVariation;

/**
 * 曾绍辉
 * 17-12-25
 * ebay获取在线listing消费队列
 */

class EbayGetSellerEventsQueuer extends SwooleQueueJob
{
    private $accountId = null;
    private $startTime = null;
    private $endTime = null;
    private $startFormat = null;
    private $endFormat = null;
    private $cache = null;
    private $accountCache = null;
    private $defaultTime = 86400 * 2;
    private $sites = [];
    
    public function getName(): string {
        return 'ebay获取账号Listing事件';
    }

    public function getDesc(): string {
        return 'ebay获取账号Listing事件';
    }

    public function getAuthor(): string {
        return 'zhaibin';
    }
    
    public function init()
    {
        $this->cache = Cache::store('ebayRsyncListing');
        $this->accountCache = Cache::store('EbayAccount');
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
        $acInfo = $this->accountCache->getAccountById($this->accountId);
        $tokenArr = json_decode($acInfo['token'], true);
        $token = trim($tokenArr[0]) ? $tokenArr[0] : $acInfo['token'];
        $this->handleTime();
        $ebayApi = $this->createApi($acInfo, $token); // 创建API对象
        do {
            $xml = $this->createXml($token);
            $resText = $ebayApi->createHeaders()->__set("requesBody", $xml)->sendHttpRequest2();
            if (isset($resText['GetSellerEventsResponse'])) {
                $response = $resText['GetSellerEventsResponse'];
                if ($response['Ack'] == "Success") {
                    if(!$response['ItemArray']) {
                        break;
                    }
                    $itemArray = isset($response['ItemArray']['Item']['ItemID']) ? [$response['ItemArray']['Item']] : $response['ItemArray']['Item'];
                    foreach($itemArray as $item) {
                        $this->syncEbayListing($item);
                    }
                } else {
                    throw new Exception($response['Errors']['ShortMessage']);
                }
            } else {
                throw new Exception('执行时报错,没有获取到GetSellerListResponse节点');
            }
        } while (false);
        
        $this->setSyncListingTime();
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
        $config['verb'] = 'GetSellerEvents';
        $config['appMode'] = 0;
        $config['account_id'] = $acInfo['id'];
        return new EbayApi($config);
    }

    /*
     * 处理时间
     */
    private function handleTime()
    {
        $syncInfo = $this->accountCache->getListingEventsSyncTime($this->accountId);
        $format = 'Y-m-d\TH:i:s.000\Z';
        $time = time();
        if ($syncInfo || ($time - $syncInfo < 3600*47)) {
            $this->startTime = $syncInfo;
            $this->startFormat = gmdate($format, $this->startTime - 1*3600);
        } else {
            $this->startTime = time() - $this->defaultTime;
            $this->startFormat = gmdate($format, $this->startTime);
        }
        $this->endTime = $time - 3*60;
        $this->endFormat = gmdate($format, $this->endTime);        
        return true;
    }

    /*
     * title 缓存数据
     * @return boolean
     */
    public function setSyncListingTime()
    {
       return $this->accountCache->setListingEventsSyncTime($this->accountId, $this->endTime);
    }

    /**
     * 获取ebay站点id
     * @param string $code
     * @return int
     * @throw exception
     */
    public function getSiteIdByCode($code)
    {
        if (isset($this->sites[$code])) {
            return $this->sites[$code];
        }        
        $siteInfo = Cache::store('ebaySite')->getSiteInfoByCode($code);
        if ($siteInfo) {
            $this->sites[$code] = $siteInfo['siteid'];
            return $siteInfo['siteid'];
        } else {
            throw new Exception('此站点'. $code . '不存在');
        }
    }
    
    /*
     * title 同步listing信息
     * @param array $item listing详细信息
     * @return boolean
     */
    public function syncEbayListing($item)
    {
        $listing = [];
        $detail = [];
        $itemId = $item['ItemID'];
        if (isset($item['BestOfferDetails'])) { // 议价
            $listing['best_offer'] = $item['BestOfferDetails']['BestOfferEnabled'] == 'true' ? 1 : 0;
        }
        isset($item['BuyItNowPrice']) ? $listing['buy_it_nowprice'] = $item['BuyItNowPrice'] : ''; // auction 拍卖价
        isset($item['Currency']) ? $listing['currency'] = $item['Currency'] : ''; // 币种
        isset($item['HitCount']) ? $listing['hit_count'] = $item['HitCount'] : ''; // hitCount
        isset($item['HitCounter']) ? $listing['hit_counter'] = $this->getHitcounter($item['HitCounter']) : ''; // HitCounter
        if (isset($item['ListingDetails'])) {
            $listing['start_date'] = strtotime($item['ListingDetails']['StartTime']);
            $listing['end_date'] = strtotime($item['ListingDetails']['EndTime']);
        }        
        if (isset($item['SellingStatus'])) {
            isset($item['SellingStatus']['ListingStatus']) ? $listing['listing_status'] = $this->getListingStatus($item['SellingStatus']['ListingStatus']) : '';
            isset($item['SellingStatus']['QuantitySold']) ? $listing['sold_quantity'] = $item['SellingStatus']['QuantitySold'] : '';
            isset($item['SellingStatus']['BidCount']) ? $detail['bit_count'] = $item['SellingStatus']['BidCount'] : '';
            isset($item['SellingStatus']['CurrentPrice']) ? $detail['current_price'] = $item['SellingStatus']['CurrentPrice'] : '';
        }
        isset($item['Title']) ? $listing['title'] = $item['Title'] : ''; // 标题
        isset($item['Quantity']) ? $listing['quantity'] = $item['Quantity'] : ''; // 上架数量
        isset($item['WatchCount']) ? $listing['watch_count'] = $item['WatchCount'] : ''; // 收藏量
        
        $listing['update_date'] = time();

        $variationDetails = [];
        if (isset($item['Variations'])) {
            $vars = isset($item['Variations']['Variation'][0]) ? $item['Variations']['Variation'] : [$item['Variations']['Variation']];
            foreach ($vars as $var) {
                isset($var['StartPrice']) ? $ian['v_price'] = $var['StartPrice'] : '';
                isset($var['Quantity']) ? $ian['v_qty'] = $var['Quantity'] : '';
                isset($var['SellingStatus']['QuantitySold']) ? $ian['v_sold'] = $var['SellingStatus']['QuantitySold'] : '';
                // 多属性
                if (isset($var['VariationSpecifics'])) {
                    $spec = isset($var['VariationSpecifics']['NameValueList'][0]) ?
                            $var['VariationSpecifics']['NameValueList'] : [$var['VariationSpecifics']['NameValueList']];
                    foreach ($spec as $sp) {
                        $temp[$sp['Name']] = $sp['Value'];
                    }
                    $ian['variation'] = json_encode($temp);
                    $ian['unique_code'] = md5($ian['variation']);
                }
                array_push($variationDetails, $ian);
                unset($ian);
            }
        }
        
        $listingModel = new EbayListing();
        $settingModel = new EbayListingSetting();
        $cacheInfo = $this->cache->getProductCache($this->accountId, $itemId);
        if (!$cacheInfo && !$cacheInfo = $listingModel->where(['item_id' => $itemId])->field('id')->find()) {
            return false;
        } else {
            $id = $cacheInfo['id'];
            $variationList = EbayListingVariation::where(['listing_id' => $id])->field('id,unique_code')->select();
            foreach ($variationList as $variationItem) {
                $variations[$variationItem['unique_code']] = $variationItem['id'];
            }
        }
        Db::startTrans();
        try {
            if ($listing) {
                $listingModel->allowField(true)->where('id', $id)->update($listing);
            }
            if ($detail) {
                $settingModel->allowField(true)->where('id', $id)->update($detail);
            }
            foreach($variationDetails as $detail) {
                $variationModel = new EbayListingVariation();
                if (isset($variations[$detail['unique_code']])) {
                    $variationModel->allowField(true)->where('id', $variations[$detail['unique_code']])->update($detail);
                } else {
                    $detail['listing_id'] = $id;
                    $variationModel->allowField(true)->save($detail);
                }
            }
            Db::commit();
            $this->cache->setProductCache($this->accountId, $itemId,['update_date'=>time(),'id'=>$id]);
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
        
    }

    /*
     * title 创建获取在线listing信息的xml
     * @param token 账号秘钥
     * @return string
     */
    public function createXml($token)
    {
        $requesBody = '<?xml version="1.0" encoding="utf-8"?>';
        $requesBody .= '<GetSellerEventsRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $requesBody .= '<RequesterCredentials>';
        $requesBody .= '<eBayAuthToken>' . $token . '</eBayAuthToken>';
        $requesBody .= '</RequesterCredentials>';
        $requesBody .= '<ErrorLanguage>en_US</ErrorLanguage>';
        $requesBody .= '<WarningLevel>High</WarningLevel>';
        $requesBody .= '<DetailLevel>ReturnAll</DetailLevel>';
        $requesBody .= '<ModTimeFrom>' . $this->startFormat . '</ModTimeFrom>';
        $requesBody .= '<ModTimeTo>' . $this->endFormat . '</ModTimeTo>';
        $requesBody .= '<HideVariations>false</HideVariations>';
        $requesBody .= '<IncludeVariationSpecifics>true</IncludeVariationSpecifics>';
        $requesBody .= '<IncludeWatchCount>true</IncludeWatchCount>';
        $requesBody .= '</GetSellerEventsRequest>';
        return $requesBody;
    }
    
    private function getHitcounter($hitCounter)
    {
        $counters = ['NoHitCounter' => 1, 'BasicStyle' => 1, 'HiddenStyle' => 2, 'RetroStyle' => 3, 'GreedLED' => 4, 'Hidden' => 5, 'HonestyStyle' => 6];
        
        if (isset($counters[$hitCounter])) {
            return $counters[$hitCounter];
        }
        return 0;
    }
    
    private function getListingStatus($status)
    {
        $statuses = ["Active" => 3, 'Completed' => 11, 'Custom' => 12, 'CustomCode' => 12, 'Ended' => 11];
        if (isset($statuses[$status])) {
            return $statuses[$status];
        }
        
        return 3;
    }
}
