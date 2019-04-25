<?php

namespace app\publish\queue;

use app\common\cache\driver\EbayAccount;
use app\publish\helper\ebay\EbayPublish;
use service\ebay\EbayApi;
use app\common\service\SwooleQueueJob;
use think\Db;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingImage;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbayListingVariation;
use app\publish\service\EbayListingCommonHelper;

/**
 * Rondaful
 * 17-12-19
 * ebay获取指定Item消费队列
 */

class EbayGetItemQueue extends SwooleQueueJob
{
    private $accountId = null;
    private $cache = null;
    private $sites = [];
    private $itemId = null;
    private $helper = null;

    protected $maxFailPushCount = 3;

    public function getName(): string {
        return 'ebay获取指定Item';
    }

    public function getDesc(): string {
        return 'ebay获取指定Item';
    }

    public function getAuthor(): string {
        return 'zhaibin';
    }
    
    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }
    
    public function init()
    {
        $this->cache = Cache::store('ebayRsyncListing');
    }
    
    public function execute()
    {
        try {
            // 从队列中获取待更新的ebay账号
            list($accountId, $itemId) = explode(',', $this->params);
            if (!$accountId || !$itemId) {
                throw new Exception('账号id不能为空');
            }
            $this->accountId = $accountId;
            $this->itemId = $itemId;
            $this->helper = new EbayListingCommonHelper($accountId);
            #$this->processingResults();
            $this->processingResults2($itemId);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getFile()."|".$e->getLine()."|".$e->getMessage());
        }
    }

    public function processingResults2($itemId=0){
        try {
            $acInfo = Cache::store('EbayAccount')->getAccountById($this->accountId);
            $tokenArr = json_decode($acInfo['token'], true);
            $token = trim($tokenArr[0]) ? $tokenArr[0] : $acInfo['token'];
            $ebayApi = $this->createApi($acInfo, $token); // 创建API对象
            $xml = $this->createXml($token);
            $resText = $ebayApi->createHeaders()->__set("requesBody", $xml)->sendHttpRequest2();
            if (isset($resText['GetItemResponse'])) {
                $response = $resText['GetItemResponse'];
                if ($response['Ack'] == "Success" || $response['Ack'] == 'Warning') {
                    $listingData = $this->helper->syncEbayListing($response['Item']);
                    $this->helper->syncListingData($listingData);
                } else {
                    $errMsg = EbayPublish::dealEbayApiError($response);
                    if (isset($errMsg[17])) {//错误码17表示listing已被删除，更新本地状态
                        EbayPublish::updateListingStatusWithErrMsg('ended',0,['item_id'=>$itemId]);
                    } else {
                        throw new Exception($response['Errors']['ShortMessage']);
                    }
                }
            } else {
                throw new Exception('执行时报错,没有获取到GetSellerListResponse节点');
            }

        } catch (Exception $e) {
            throw new Exception($e->getFile()."|".$e->getLine()."|".$e->getMessage());
        }
    }

    public function processingResults()
    {
        $acInfo = Cache::store('EbayAccount')->getAccountById($this->accountId);
        $tokenArr = json_decode($acInfo['token'], true);
        $token = trim($tokenArr[0]) ? $tokenArr[0] : $acInfo['token'];
        $ebayApi = $this->createApi($acInfo, $token); // 创建API对象
        $xml = $this->createXml($token);
        $resText = $ebayApi->createHeaders()->__set("requesBody", $xml)->sendHttpRequest2();
        if (isset($resText['GetItemResponse'])) {
            $response = $resText['GetItemResponse'];
            if ($response['Ack'] == "Success") {
                $this->syncEbayListing($response['Item']);
            } else {
                throw new Exception($response['Errors']['ShortMessage']);
            }
        } else {
            throw new Exception('执行时报错,没有获取到GetSellerListResponse节点');
        }
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
        $config['verb'] = 'GetItem';
        $config['appMode'] = 0;
        $config['account_id'] = $acInfo['id'];
        return new EbayApi($config);
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
        $listing['reserve_price'] = $list['ReservePrice'] ?? 0.00; // 保留价
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
        $itemSpecifics = [];
        if (isset($list['ItemSpecifics']) && $list['ItemSpecifics']) {
            $speNameValues = isset($list['ItemSpecifics']['NameValueList']['Name']) ? [$list['ItemSpecifics']['NameValueList']] : $list['ItemSpecifics']['NameValueList'];
            foreach($speNameValues as $nameValue) {
                array_push($itemSpecifics, ['name' => $nameValue['Name'], 'value' => $nameValue['Value'], 'source' => $nameValue['Source']]);
            }
        }
        $detail['specifics'] = json_encode($itemSpecifics);
        // ItemCompatibilityCount
        if (isset($list['ItemCompatibilityCount'])) {
            $detail['compatibility_count'] = $list['ItemCompatibilityCount'];
        }
        $compatibilityList = [];
        if (isset($list['ItemCompatibilityList']) && $list['ItemCompatibilityList']) {
            $compaList = isset($list['ItemCompatibilityList']['Compatibility']['NameValueList']) ? [$list['ItemCompatibilityList']['Compatibility']] : $list['ItemCompatibilityList']['Compatibility'];
            foreach($compaList as $compatibility) {
                $nameValueList = [];
                foreach($compatibility['NameValueList'] as $nameValue) {
                    if (!$nameValue) {
                        continue;
                    }
                    array_push($nameValueList, ['name' => $nameValue['Name'], 'value' => $nameValue['Value']]);
                }
                $note = isset($compatibility['CompatibilityNotes']) ? $compatibility['CompatibilityNotes'] : '';
                array_push($compatibilityList, ['name_value_list' => $nameValueList, 'compatibility_notes' => $note]);
            }
        }
        $detail['compatibility'] = json_encode($compatibilityList);
        $excludeLocations = [];
        if (isset($list["ShippingDetails"])) {
            $ShippingDetails = $list["ShippingDetails"];
            if (isset($ShippingDetails["PaymentInstructions"])) {
                $detail["payment_instructions"] = $ShippingDetails["PaymentInstructions"]; // 付款说明
            } else {
                $detail["payment_instructions"] = "";
            }
            if (isset($ShippingDetails["ExcludeShipToLocation"])) { // 不送达地区
                $excludeLocations = json_encode($ShippingDetails["ExcludeShipToLocation"]);
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
        $variationPics = [];
        if (isset($list["Variations"])) {
            $variations = isset($list["Variations"]["Variation"][0]) ? $list["Variations"]["Variation"] : array($list["Variations"]["Variation"]);
            $i = 0;
            foreach ($variations as $ia) {
                $vs[$i]["v_sku"]=$ia["SKU"];
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
        
        if (isset($list['Variations']) && isset($list['Variations']['Pictures'])) {
            $pictureDetail = $list['Variations']['Pictures'];
            $name = $pictureDetail['VariationSpecificName'];
            foreach($pictureDetail['VariationSpecificPictureSet'] as $set) {
                $value = $set['VariationSpecificValue'];
                if (!isset($set['PictureURL']) || !$set['PictureURL']) {
                    continue;
                }
                $vaPicList =  is_array($set['PictureURL']) ? $set['PictureURL'] : [$set['PictureURL']];
                foreach($vaPicList as $k => $img) {
                    $image['name'] = $name;
                    $image['value'] = $value;
                    $image['main'] = 0;
                    $image['main_de'] = 1;
                    $image['url'] = $img;
                    array_push($variationPics, $image);
                    unset($image);
                }
            }
        }
        
        $detail['shipping'] = json_encode($shipping);
        $detail['international_shipping'] = json_encode($internationalShipping);
        $detail['buyer_requirment_details'] = json_encode($buererRequiments);
        $listingData = [
            "listing" => $listing,
            "images" => $images,
            "detail" => $detail,
            "variations" => $vs,
            'variationPics' => $variationPics
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
        $cacheInfo = $this->cache->getProductCache($this->accountId, $listingData['listing']['item_id']);
        if (!$cacheInfo && !$cacheInfo = $listingModel->
            where(['item_id' => $listingData['listing']['item_id'],'draft'=>0])
            ->field('id')->find()) {
            return false;
        }

        $id = $cacheInfo['id'];
        $listingModel->where(['id'=>$id])->update($listingData['listing']);
        $postData = ['specifics' => $listingData['detail']['specifics']];
        isset($listingData['detail']['compatibility_count']) ? $postData['compatibility_count'] = $listingData['detail']['compatibility_count'] : '';
        isset($listingData['detail']['compatibility']) ? $postData['compatibility'] = $listingData['detail']['compatibility'] : '';
        $settingModel->where(['id' => $id])->update($postData);

        #同步变体
        foreach ($listingData['variations'] as $variation) {
            $variationModel = new EbayListingVariation();
            if (isset($variations[$variation['unique_code']])) {
                $variationModel->allowField(true)->where(['id' => $variations[$variation['unique_code']]])->update($variation);
            } else {
                $variation['listing_id'] = $id;
                $variationModel->allowField(true)->save($variation);
            }
        }

        Db::startTrans();
        try {
            #同步主图
            $imageModel = new EbayListingImage();
            $mainImgsDb = [];
            $mainIds = [];
            $mainImgsList = EbayListingImage::where(['listing_id' => $id])->field('id,eps_path')->select();
            foreach($mainImgsList as $mainImg){
                $mainImgsDb[$mainImg['eps_path']] = $mainImg['id'];
                $mainIds[] = $mainImg['id'];
            }
            $saveIds = [];
            foreach($listingData['images'] as $k => $img){
                if(!isset($mainImgsDb[$img])){#新增
                    $insImg['listing_id'] = $id;
                    $insImg['sku'] = isset($listingData['listing']['sku']) ? $listingData['listing']['sku'] : "";
                    $insImg['thumb'] = $img;
                    $insImg['eps_path'] = $img;
                    $insImg['sort'] = $k;
                    $insImg['status'] = 3;
                    $insImg['detail'] = 0;
                    $insImg['main'] = 1;
                    $insImg['update_time'] = time();
                    $imageModel->insertGetId($insImg);
                }else{#同步
                    $saveIds[] = $mainImgsDb[$img];
                    $imageModel->where(['id'=>$mainImgsDb[$img]])->update(['main'=>1,'sort'=>$k]);
                }
            }
            $upIds = array_diff($mainIds,$saveIds);
            $imageModel->where(['id'=>['in',$upIds]])->update(['main'=>0]);
            $whDel['main'] = 0;
            $whDel['main_de'] = 0;
            $whDel['detail'] = 0;
            $whDel['id'] = ['in',$upIds];
            $imageModel->where($whDel)->delete();

            #同步多属性图片
            if($listingData['listing']['variation'] && $listingData['variationPics']){
                $name = $listingData['variationPics'][0]['name'];
                $variationList = EbayListingVariation::where(['listing_id' => $id])->field('id,variation')->select();
                foreach ($variationList as $variationItem) {
                    $nameValues = json_decode($variationItem['variation'], true);
                    $variations[$nameValues[$name]] = $variationItem['id'];
                }

                $imageList = EbayListingImage::where(['listing_id' => $id])->field('id,eps_path')->select();
                foreach ($imageList as $image) {
                    $images[$image['eps_path']] = $image['id'];
                }
            
                $k = count($images);
                foreach ($listingData['variationPics'] as $v) {
                    if ($v['main'] && isset($variations[$v['value']])) {
                        (new EbayListingVariation())->where(['id' => $variations[$v['value']]])->update(['thumb' => $v['url']]);
                    }
                    if (!isset($images[$v['url']])) {
                        $imgArr['listing_id'] = $id;
                        $imgArr['sku'] = isset($listingData['listing']['sku']) ? $listingData['listing']['sku'] : "";
                        $imgArr['thumb'] = $v['url'];
                        $imgArr['eps_path'] = $v['url'];
                        $imgArr['sort'] = $k++;
                        $imgArr['status'] = 3; // 已上传至eps                
                        $imgArr['detail'] = 0;
                        $imgArr['main_de'] = isset($v['main_de'])?$v['main_de']:0;
                        $imgArr['name'] = $v['name'];
                        $imgArr['value'] = $v['value'];
                        $imgArr['update_time'] = time();
                        $imageModel->insertGetId($imgArr);
                        unset($imgArr);
                    } else {
                        $imageModel->where(['id' => $images[$v['url']]])->update(['main_de'=>1,'name' => $v['name'], 'value' => $v['value']]);
                    }
                }
            }
            Db::commit();
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    /*
     * title 创建获取在线Item信息的xml
     * @param token 账号秘钥
     * @return string
     */
    public function createXml($token)
    {
        $requesBody = '<?xml version="1.0" encoding="utf-8"?>';
        $requesBody .= '<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $requesBody .= '<RequesterCredentials>';
        $requesBody .= '<eBayAuthToken>' . $token . '</eBayAuthToken>';
        $requesBody .= '</RequesterCredentials>';
        $requesBody .= '<ItemID>'. $this->itemId . '</ItemID>';
        $requesBody .= '<ErrorLanguage>en_US</ErrorLanguage>';
        $requesBody .= '<WarningLevel>High</WarningLevel>';
        $requesBody .= '<DetailLevel>ReturnAll</DetailLevel>';
        $requesBody .= '<IncludeItemCompatibilityList>true</IncludeItemCompatibilityList>';
        $requesBody .= '<IncludeItemSpecifics>true</IncludeItemSpecifics>';
        $requesBody .= '<IncludeTaxTable>true</IncludeTaxTable>';
        $requesBody .= '<IncludeWatchCount>true</IncludeWatchCount>';
        $requesBody .= '</GetItemRequest>';
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
