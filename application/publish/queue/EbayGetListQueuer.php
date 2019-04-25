<?php
namespace app\publish\queue;

use app\common\model\ebay\EbayAccount;
use service\ebay\EbayApi;
use app\common\service\SwooleQueueJob;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingImage;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbayListingVariation;
use app\common\service\UniqueQueuer;
use app\publish\queue\EbayGetItemQueue;

/**
 * 曾绍辉
 * 17-8-25  
 * ebay获取在线listing消费队列
 */

class EbayGetListQueuer extends SwooleQueueJob
{
    private $accountId = null;
    private $page = 1;
    private $startTime = null;
    private $endTime = null;
    private $startFormat = null;
    private $endFormat = null;
    private $cache = null;
    private $accountCache = null;
    private $defaultTime = 86400 * 118; // 86400 * 120
    private $stepTime = 86400 * 5;
    private $pageSize = 2;
    private $sites = [];
    private $continue = true;
    
    public function getName(): string {
        return 'ebay获取账号在线listing';
    }

    public function getDesc(): string {
        return 'ebay获取账号在线listing';
    }

    public function getAuthor(): string {
        return 'zhaibin';
    }
    
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }
    
    public function init()
    {
        $this->cache = Cache::store('ebayRsyncListing');
        $this->accountCache = Cache::store('EbayAccount');
    }
    
    public function execute()
    {
        set_time_limit(0);
        try {    
            // 从队列中获取待更新的ebay账号
            $aid = $this->params;
            if (!$aid) {
                throw new Exception('账号id不能为空');
            }
            $this->accountId = $aid;
            $this->continue = true;
            $this->processingResults();
            return true;
        } catch (Exception $exp) {
            throw new Exception($exp->getMessage());
        }
    }

    public function processingResults()
    {
        $acInfo = $this->accountCache->getAccountById($this->accountId);
        $acInfo = EbayAccount::where('id',$this->accountId)->find();
        if($acInfo){
            $acInfo= $acInfo->toArray();
        }else{
            throw new Exception("账号不存在");
        }
        $tokenArr = json_decode($acInfo['token'], true);
        $token = trim($tokenArr[0]) ? $tokenArr[0] : $acInfo['token'];
        $this->handleTime();
        $ebayApi = $this->createApi($acInfo, $token); // 创建API对象
        $hasMore = false;
        $i = 0;
        do {
            Cache::handler()->set('ebay:list:runing', json_encode(['account' => $this->accountId, 'time' => date('Y-m-d H:i:s'), 'page' => $this->page]));
            $xml = $this->createXml($token);
            $resText = $ebayApi->createHeaders()->__set("requesBody", $xml)->sendHttpRequest2();
            dump($resText);die;
            if (isset($resText['GetSellerListResponse'])) {
                $response = $resText['GetSellerListResponse'];
                if ($response['Ack'] == "Success") {
                    if ($response['HasMoreItems'] == 'false') {
                        $this->page = 1;
                        $hasMore = false;
                        $this->setSyncListingTime();
                    } else {
                        $this->page++;
                        $hasMore = true;
                        $this->setSyncListingTime();
                    }
                    if (!$response['ItemArray']) {
                        break;
                    }
                    $itemArray = isset($response['ItemArray']['Item']['ItemID']) ? [$response['ItemArray']['Item']] : $response['ItemArray']['Item'];
                    foreach($itemArray as $item) {
                        $this->syncEbayListing($item);
                    }
                } else {
                    $message = $response['Errors']['ShortMessage'];
                    if (strpos($message, 'Invalid page number') !== false) {
                        $this->page = 1;
                        $this->setSyncListingTime();
                    }
                    throw new Exception($message);
                }
            } else {
                throw new Exception('执行时报错,没有获取到GetSellerListResponse节点');
            }
            $i++;
        } while ($hasMore || $this->continue);
        
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
        $config['verb'] = 'GetSellerList';
        $config['appMode'] = 0;
        $config['account_id'] = $acInfo['id'];
        return new EbayApi($config);
    }

    /*
     * 处理时间
     */
    private function handleTime()
    {
        $syncInfo = $this->accountCache->getListingSyncTime($this->accountId);
        $format = 'Y-m-d\TH:i:s.000\Z';
        $time = time();
        if ($syncInfo && (!isset($syncInfo['page']) || $syncInfo['page'] == 1)) {
            $this->startTime = $syncInfo['endTime'];
            $this->startFormat = gmdate($format, $this->startTime);
            if ($this->startTime + $this->stepTime > $time) {
                $this->endTime = $time;
                $this->continue = false;
            } else {
                $this->endTime = $this->startTime + $this->stepTime;
            }
        } else if ($syncInfo) {
            $this->startTime = $syncInfo['startTime'];
            $this->startFormat = gmdate($format, $this->startTime);
            $this->endTime = $syncInfo['endTime'];
            $this->page = $syncInfo['page'];
        } else {
            $this->startTime = $time - $this->defaultTime;
            $this->startFormat = gmdate($format, $this->startTime);
            $this->endTime = $this->startTime + $this->stepTime;
        }
        $this->endFormat = gmdate($format, $this->endTime);
        
        return true;
    }

    /*
     * title 缓存数据
     * @return boolean
     */
    public function setSyncListingTime()
    {
        $data = [
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'page' => $this->page
        ];
       return $this->accountCache->setListingSyncTime($this->accountId, $data);
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
        $siteInfo = Cache::store('ebaySite')->getSiteInfoByCode($code, 'country');
        if ($siteInfo) {
            $this->sites[$code] = $siteInfo['siteid'];
            return $siteInfo['siteid'];
        } else {
            throw new Exception('此站点'. $code . '不存在');
        }
    }
    
    /*
     * title 同步listing信息
     * @param list listing详细信息
     * @return boolean
     */
    public function syncEbayListing($list)
    {
        $listing['account_id'] = $this->accountId;
        $listing['site'] = $this->getSiteIdByCode($list['Site']);
        $listing['item_id'] = $list['ItemID'];
        $listing['listing_sku'] = $list['SKU'] ?? '';
        $listing['currency'] = $list['Currency'];
        // 判断是否为多属性产品
        if (isset($list['Variations'])) {
            $listing['variation'] = 1;
        } else {
            $listing['variation'] = 0;
        }
        // listing基本信息
        $listing['paypal_emailaddress'] = isset($list['PayPalEmailAddress']) ? $list['PayPalEmailAddress'] : "";
        $listing['primary_categoryid'] = $list['PrimaryCategory']['CategoryID'];
        $listing['second_categoryid'] = isset($list['SecondCategory']) ? $list['SecondCategory']['CategoryID'] : 0;
        $listing['quantity'] = $list['Quantity'] ?? 0;
        $listing['sold_quantity'] = intval($list["SellingStatus"]["QuantitySold"]);
        $listing['buy_it_nowprice'] = $list["BuyItNowPrice"]; // 一口价
        $listing['start_price'] = $list["StartPrice"]; // 起始价
        $listing['reserve_price'] = isset($list['ReservePrice']) ? $list['ReservePrice'] : 0.00; // 保留价
        $listing['img'] = isset($list["PictureDetails"]["GalleryURL"]) ? $list["PictureDetails"]["GalleryURL"] : "";
        $listing['title'] = $list['Title']; // 标题
        $listing['sub_title'] = $list['SubTitle'] ?? ''; // 副标题
        $listing['hit_count'] = isset($list['HitCount']) ? $list['HitCount'] : 0; // 点击量
        $listing['hit_counter'] = $this->getHitcounter($list['HitCounter']);
        $listing['watch_count'] = isset($list['WatchCount']) ? $list['WatchCount'] : 0; //收藏量
        $listing['listing_type'] = $this->getListingType($list['ListingType']); // 刊登类型
        $detail['description'] = $list['Description']; // 描述
        $listing['store_category_id'] = isset($list['Storefront']['StoreCategoryID']) ? intval($list['Storefront']['StoreCategoryID']) : 0;
        $listing['store_category2_id'] = isset($list['Storefront']['StoreCategory2ID']) ? intval($list['Storefront']['StoreCategory2ID']) : 0;
        $detail['condition_id'] = isset($list['ConditionID']) ? $list['ConditionID'] : '';
        $detail['condition_description'] = isset($list['ConditionDescription']) ? $list['ConditionDescription'] : "";
        $listing['start_date'] = strtotime($list['ListingDetails']['StartTime']);
        $listing['end_date'] = strtotime($list['ListingDetails']['EndTime']);
        $listing['listing_status'] = $this->getListingStatus($list['SellingStatus']['ListingStatus']);
        $listing['autopay'] = $list['AutoPay'] == 'false' ? 0 : 1;
        $listing['private_listing'] = $list['PrivateListing'] == 'false' ? 0 : 1;
        $listing['picture_gallery'] = isset($list["PictureDetails"]["PictureURL"]) ? $this->getGallery($list['PictureDetails']['GalleryType']) : 0;
        $images = array();
        if (isset($list["PictureDetails"]["PictureURL"])) {
            $images = is_array($list["PictureDetails"]["PictureURL"]) ? $list["PictureDetails"]["PictureURL"] : array($list["PictureDetails"]["PictureURL"]);
        }
        $listing['listing_enhancement'] = isset($list['ListingEnhancement']) ? 1 : 0;
        // 缺少是否开启BestOffer的判断
        $detail['auto_accept_price'] = $list['ListingDetails']['BestOfferAutoAcceptPrice'] ?? 0.00;
        $detail['minimum_accept_price'] = $list['ListingDetails']['MinimumBestOfferPrice'] ?? 0.00;
        // 拍卖信息
        $detail['bid_count'] = $list['SellingStatus']['BidCount'] ?? 0;
        $detail['bid_increment'] = $list['SellingStatus']['BidIncrement'] ?? 0.00;
        $detail['current_price'] = $list['SellingStatus']['CurrentPrice'] ?? 0.00;
        if (isset($list['SellingStatus']['PromotionalSaleDetails'])) {
           // 待处理
        }
        // 运输
        $internationalShipping = []; // 国际运输方式
        $shipping = []; // 国内运输方式
        $detail = []; // listing详情
        $listing["location"] = $list["Location"]; // 商品所在地
        $listing["country"] = $list["Country"]; // 发货国家代码
        $listing['listing_duration'] = $this->getListingDuration($list['ListingDuration']); // 刊登天数        
        $detail['application_data'] = isset($list['ApplicationData']) ? $list['ApplicationData'] : ""; // 应用名称
        $listing["dispatch_max_time"] = isset($list["DispatchTimeMax"]) ? intval($list["DispatchTimeMax"]) : 0; // 发货处理时间(dispatch_time_max)
        $detail["payment_method"] = json_encode($list['PaymentMethods']); // 付款方式

        #类目属性
        $itemSpecifics = [];
        if(isset($list['ItemSpecifics']['NameValueList'])){
            $NameValueList = isset($list['ItemSpecifics']['NameValueList'][0])?$list['ItemSpecifics']['NameValueList']:[$list['ItemSpecifics']['NameValueList']];
            foreach($NameValueList as $spec){
                $tempSpec['attr_name'] = $spec['Name'];
                $tempSpec['attr_value'] = $spec['Value'];
                $tempSpec['custom'] = 0;
                $itemSpecifics[] = $tempSpec;
                unset($tempSpec);
            }
        }
        $detail['specifics'] = json_encode($itemSpecifics);

        $excludeLocations = [];
        if (isset($list["ShippingDetails"])) {
            $ShippingDetails = $list["ShippingDetails"];
            if (isset($ShippingDetails["PaymentInstructions"])) {
                $detail["payment_instructions"] = $ShippingDetails["PaymentInstructions"]; // 付款说明
            } else {
                $detail["payment_instructions"] = "";
            }
            if (isset($ShippingDetails["ExcludeShipToLocation"])) { // 不送达地区
                $excludeLocations = $ShippingDetails["ExcludeShipToLocation"];
            }
            if (isset($ShippingDetails["ShippingServiceOptions"])) {// 国内运输
                $ship = isset($ShippingDetails["ShippingServiceOptions"][0]) ? $ShippingDetails["ShippingServiceOptions"] : array($ShippingDetails["ShippingServiceOptions"]);
                foreach ($ship as $ksh => $vsh) {
                    $shipping[$ksh]['shipping_service'] = $vsh['ShippingService'];
                    $shipping[$ksh]['shipping_service_cost'] = isset($vsh['ShippingServiceCost']) ? $vsh['ShippingServiceCost'] : 0;
                    $shipping[$ksh]['shipping_service_additional_cost'] = isset($vsh['ShippingServiceAdditionalCost']) ? $vsh['ShippingServiceAdditionalCost'] : 0;
                    $shipping[$ksh]['shipping_service_priority'] = isset($vsh['ShippingServicePriority']) ? $vsh['ShippingServicePriority'] : 0;
                    $shipping[$ksh]['expedited_service'] = isset($vsh['ExpeditedService']) ? ($vsh['ExpeditedService'] == "true" ? 1 : 0) : 0;
                    $shipping[$ksh]['shipping_time_min'] = isset($vsh['ShippingTimeMin']) ? $vsh['ShippingTimeMin'] : 0;
                    $shipping[$ksh]['shipping_time_max'] = isset($vsh['ShippingTimeMax']) ? $vsh['ShippingTimeMax'] : 0;
                    $shipping[$ksh]['extra_cost'] = isset($vsh['ShippingSurcharge'])?$vsh['ShippingSurcharge']:0;
                    $shipping[$ksh]['free_shipping'] = isset($vsh['FreeShipping']) ? ($vsh['FreeShipping'] == "true" ? 1 : 0) : 0;

                }
            }

            if (isset($ShippingDetails["InternationalShippingServiceOption"])) { // 国际运输
                $InternationalShippingServiceOption = isset($ShippingDetails["InternationalShippingServiceOption"][0]) ? $ShippingDetails["InternationalShippingServiceOption"] : array($ShippingDetails["InternationalShippingServiceOption"]);
                $i = 0;
                foreach ($InternationalShippingServiceOption as $in) {
                    $internationalShipping[$i]["shipping_service"] = $in["ShippingService"];
                    if (isset($in["ShippingServiceAdditionalCost"])) {
                        $internationalShipping[$i]["shipping_service_additional_cost"] = $in["ShippingServiceAdditionalCost"];
                    }
                    if (isset($in["ShippingServiceCost"])) {
                        $internationalShipping[$i]["shipping_service_cost"] = $in["ShippingServiceCost"];
                    }
                    $internationalShipping[$i]["shipping_service_priority"] = $in["ShippingServicePriority"];
                    $ShipToLocation = is_array($in["ShipToLocation"]) ? implode(",", $in["ShipToLocation"]) : $in["ShipToLocation"];
                    $internationalShipping[$i]["shiptolocation"] = $ShipToLocation;
                    $i++;
                }
            }
        }
        $detail['exclude_location'] = json_encode($excludeLocations);
        $shipToLocation = isset($list['ShipToLocations']) ? $list['ShipToLocations'] : [];
        $detail['ship_location'] = json_encode($shipToLocation);
        // 退换货政策
        if (isset($list["ReturnPolicy"])) {
            $ReturnPolicy = $list["ReturnPolicy"];
            // 退款详情
            if (isset($ReturnPolicy["Description"])) {
                $detail["return_description"] = $ReturnPolicy["Description"];
            } else {
                $detail["return_description"] = "";
            }
            // 退款方式
            if (isset($ReturnPolicy["RefundOption"])) {
                $detail["return_type"] = $ReturnPolicy["RefundOption"];
            }
            // 退款天数
            $listing["return_time"] = isset($ReturnPolicy["ReturnsWithinOption"]) ? $this->getReturnTime($ReturnPolicy["ReturnsWithinOption"]) : 0;
            // 运费承担方
            if (isset($ReturnPolicy["ShippingCostPaidByOption"])) {
                $detail["return_shipping_option"] = $ReturnPolicy["ShippingCostPaidByOption"] == "Buyer" ? 0 : 1;
            }
            // 是否支持退换货
            $detail["return_policy"] = 1;
        }else {
            $detail["return_policy"] = 0;
        }

        // 买家限制
        $buererRequiments = [];
        if (isset($list["BuyerRequirementDetails"])) {
            // paypal限制 绑定paypal的限制
            $BuyerRequirementDetails = $list["BuyerRequirementDetails"];
            if (isset($BuyerRequirementDetails["LinkedPayPalAccount"])) {
                $buererRequiments["link_paypal"] = $BuyerRequirementDetails["LinkedPayPalAccount"] == "true" ? 1 : 0;
            } else {
                $buererRequiments["link_paypal"] = 0;
            }

            // 未付款限制 弃标案显示
            if (isset($BuyerRequirementDetails["MaximumUnpaidItemStrikesInfo"])) {
                // 次数
                $buererRequiments["strikes_count"] = isset($BuyerRequirementDetails["MaximumUnpaidItemStrikesInfo"]["Count"]) ? $BuyerRequirementDetails["MaximumUnpaidItemStrikesInfo"]["Count"] : 0;
                // 时限
                $buererRequiments["strikes_period"] = isset($BuyerRequirementDetails["MaximumUnpaidItemStrikesInfo"]["Period"]) ? $BuyerRequirementDetails["MaximumUnpaidItemStrikesInfo"]["Period"] : "";
                $buererRequiments["strikes"] = 1;
            } else {
                $buererRequiments["strikes"] = 0;
            }

            // 违反ebay政策相关
            if (isset($BuyerRequirementDetails["MaximumBuyerPolicyViolations"])) {
                // 次数
                $buererRequiments["violations_count"] = isset($BuyerRequirementDetails["MaximumBuyerPolicyViolations"]["Count"]) ? $BuyerRequirementDetails["MaximumBuyerPolicyViolations"]["Count"] : 0;
                // 时限
                $buererRequiments["violations_period"] = isset($BuyerRequirementDetails["MaximumBuyerPolicyViolations"]["Period"]) ? $BuyerRequirementDetails["MaximumBuyerPolicyViolations"]["Period"] : "";
                $buererRequiments["violations"] = 1;
            } else {
                $buererRequiments["violations"] = 0;
            }

            // 限制条件
            if (isset($BuyerRequirementDetails["MaximumItemRequirements"])) {
                $buererRequiments["requirements_max_count"] = isset($BuyerRequirementDetails["MaximumItemRequirements"]["MaximumItemCount"]) ? $BuyerRequirementDetails["MaximumItemRequirements"]["MaximumItemCount"] : 0;
                if (isset($BuyerRequirementDetails["MaximumItemRequirements"]["MinimumFeedbackScore"])) {
                    $buererRequiments["minimum_feedback"] = 1;
                    $buererRequiments['minimum_feedback_score'] = $BuyerRequirementDetails["MaximumItemRequirements"]["MinimumFeedbackScore"];
                }
                $buererRequiments["requirements"] = 1;
            } else {
                $buererRequiments["requirements"] = 0;
            }

            // 信用限制
            if (isset($BuyerRequirementDetails["MinimumFeedbackScore"])) {
                $buererRequiments["credit"] = 1;
                if (isset($BuyerRequirementDetails["MinimumFeedbackScore"]))
                    $buererRequiments["requirements_feedback_score"] = $BuyerRequirementDetails["MinimumFeedbackScore"];
            }else {
                $buererRequiments["credit"] = 0;
            }

            // 不在我的配送地
            if (isset($BuyerRequirementDetails["ShipToRegistrationCountry"])) {
                $buererRequiments["registration"] = $BuyerRequirementDetails["ShipToRegistrationCountry"] == "true" ? 1 : 0;
            } else {
                $buererRequiments["registration"] = 0;
            }
            $listing["disable_buyer"] = 1;
        } else {
            $listing["disable_buyer"] = 0;
        }

        $vs = array();
        if (isset($list["Variations"])) {
            $variations = isset($list["Variations"]["Variation"][0]) ? $list["Variations"]["Variation"] : array($list["Variations"]["Variation"]);
            $i = 0;
            foreach ($variations as $ia) {
                // $vs[$i]["v_sku"]=$ia["SKU"];
                $vs[$i]['channel_map_code'] = $ia['SKU'];
                $vs[$i]["v_price"] = $ia["StartPrice"];
                $vs[$i]["v_qty"] = $ia["Quantity"] ?? 0;
                $vs[$i]["v_sold"] = intval($ia["SellingStatus"]["QuantitySold"]);
                if (isset($ia["VariationProductListingDetails"]["UPC"]))
                    $vs[$i]["upc"] = $ia["VariationProductListingDetails"]["UPC"];
                if (isset($ia["VariationProductListingDetails"]["ISBN"]))
                    $vs[$i]["isbn"] = $ia["VariationProductListingDetails"]["ISBN"];
                if (isset($ia["VariationProductListingDetails"]["EAN"]))
                    $vs[$i]["ean"] = $ia["VariationProductListingDetails"]["EAN"];
                $Specifics = isset($ia["VariationSpecifics"]["NameValueList"][0]) ? $ia["VariationSpecifics"]["NameValueList"] : array($ia["VariationSpecifics"]["NameValueList"]);
                $temp = array();
                foreach ($Specifics as $val) {
                    $temp[$val["Name"]] = $val["Value"];
                }
                $vs[$i]["variation"] = json_encode($temp);
                $vs[$i]['unique_code'] = md5($vs[$i]['variation']);
                $i++;
            }
        }
        
        $detail['shipping'] = json_encode($shipping);
        $detail['international_shipping'] = json_encode($internationalShipping);
        $detail['buyer_requirment_details'] = json_encode($buererRequiments);
        $listingData = [
            "listing" => $listing,
            "images" => $images,
            "detail" => $detail,
            "variations" => $vs
        ];
        // 同步数据库
        $this->syncListingData($listingData);
        return true;
    }

    /*
     * title 同步listing数据
     * @param listingData listing信息
     */
    public function syncListingData(&$listingData)
    {
        $variations = [];
        $images = [];
        $listingModel = new EbayListing();
        $settingModel = new EbayListingSetting();
        $addFlag = false;
        $cacheInfo = $this->cache->getProductCache($this->accountId, $listingData['listing']['item_id']);
        if (!$cacheInfo && !$cacheInfo = $listingModel->where(['item_id' => $listingData['listing']['item_id']])->field('id')->find()) {
            $id = 0;
            $addFlag = true;
        } else {
            $id = $cacheInfo['id'];
            $variationList = EbayListingVariation::where(['listing_id' => $id])->field('id,unique_code')->select();
            foreach ($variationList as $variationItem) {
                $variations[$variationItem['unique_code']] = $variationItem['id'];
            }
            $imageList = EbayListingImage::where(['listing_id' => $id])->field('id,eps_path')->select();
            foreach($imageList as $image) {
                $images[$image['eps_path']] = $image['id'];
            }
        }
        
        Db::startTrans();
        try {
            // Listing
            if ($id) {
                $listingModel->allowField(true)->where(['id' => $id])->update($listingData['listing']);
                $settingModel->allowField(true)->where(['id' => $id])->update($listingData['detail']);
            } else {
                $listingModel->allowField(true)->save($listingData['listing']);
                $id = $listingModel->id;
                $listingData['detail']['id'] = $id;
                $settingModel->allowField(true)->save($listingData['detail']);
            }
            // 变体
            foreach ($listingData['variations'] as $variation) {
                $variationModel = new EbayListingVariation();
                if (isset($variations[$variation['unique_code']])) {
                    $variationModel->allowField(true)->where(['id' => $variations[$variation['unique_code']]])->update($variation);
                } else {
                    $variation['listing_id'] = $id;
                    $variationModel->allowField(true)->save($variation);
                }
            }
            // 图片
            $k = count($images);
            foreach ($listingData['images'] as $v) {
                if (!isset($images[$v])) {
                    $imgArr['listing_id'] = $id;
                    
                    $imgArr['sku'] = isset($listingData['listing']['sku']) ? $listingData['listing']['sku'] : "";
                    $imgArr['thumb'] = $v;
                    $imgArr['eps_path'] = $v;
                    $imgArr['main'] = 1 ;
                    $imgArr['sort'] = $k++;
                    $imgArr['status'] = 3; // 已上传至eps                
                    $imgArr['detail'] = 0;
                    $imgArr['update_time'] = time();
                    $imageModel = new EbayListingImage();
                    #$imageModel->allowField(true)->save($imgArr);
                    $imageModel->insertGetId($imgArr);
                    unset($imgArr);
                }
            }
            Db::commit();
            $addFlag ? (new UniqueQueuer(EbayGetItemQueue::class))->push($this->accountId . ',' . $listingData['listing']['item_id']) : '';
            $this->cache->setProductCache($this->accountId, $listingData['listing']['item_id'],['update_date'=>time(),'id'=>$id]);
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
        $requesBody .= '<GetSellerListRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $requesBody .= '<RequesterCredentials>';
        $requesBody .= '<eBayAuthToken>' . $token . '</eBayAuthToken>';
        $requesBody .= '</RequesterCredentials>';
        $requesBody .= '<ErrorLanguage>en_US</ErrorLanguage>';
        $requesBody .= '<WarningLevel>High</WarningLevel>';
        $requesBody .= '<DetailLevel>ReturnAll</DetailLevel>';
        $requesBody .= '<StartTimeFrom>' . $this->startFormat . '</StartTimeFrom>';
        $requesBody .= '<StartTimeTo>' . $this->endFormat . '</StartTimeTo>';
        $requesBody .= '<IncludeVariations>true</IncludeVariations>';
        $requesBody .= '<Pagination>';
        $requesBody .= '<PageNumber>' . $this->page . '</PageNumber>';
        $requesBody .= '<EntriesPerPage>' . $this->pageSize . '</EntriesPerPage>';
        $requesBody .= '</Pagination>';
        $requesBody .= '</GetSellerListRequest>';
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
    
    private function getListingType($type)
    {
        $durations = ['FixedPriceItem' => 1, 'Chinese' => 2];
        if (isset($durations[$type])) {
            return $durations[$type];
        }
        return 1;
    }
    
    private function getListingDuration($duration)
    {
        $durations = ['GTC' => 1, 'Days_1' => 2, 'Days_3' => 3, 'Days_5' => 4, 'Days_7' => 5, 'Days_10' => 6, 'Days_30' => 7];
        if (isset($durations[$duration])) {
            return $durations[$duration];
        }
        
        return 1;
    }
    
    private function getListingStatus($status)
    {
        $statuses = ["Active" => 3, 'Completed' => 11, 'Custom' => 12, 'CustomCode' => 12, 'Ended' => 11];
        if (isset($statuses[$status])) {
            return $statuses[$status];
        }
        
        return 3;
    }
    
    private function getReturnTime($time)
    {
        $times = ['Days_14' => 1, 'Days_30' => 2, 'Days_60' => 3, 'Months_1' => 4];
        if (isset($times[$time])) {
            return $times[$time];
        }
        
        return 0;
    }
    
    private function getGallery($gallery)
    {
        $galleries = ['None' => 0, 'Gallery' => 1, 'Featured' => 2, 'Plus' => 3];
        if (isset($galleries[$gallery])) {
            return $galleries[$gallery];
        }
        
        return 0;
    }
}
