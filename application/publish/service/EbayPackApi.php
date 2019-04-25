<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/5/24
 * Time: 18:25
 */

namespace app\publish\service;

use app\common\cache\Cache;
use app\common\model\ebay\EbayModelSale;
use app\common\model\ebay\EbayModelStyle;
use app\common\model\ebay\EbayListingImage;
use app\publish\helper\ebay\EbayPublish as EbayPublishHelper;
use app\publish\helper\ebay\EbayPublish;
use service\ebay\EbayApi;
use app\goods\service\GoodsImage;
use app\publish\service\EbayConstants as Constants;
use think\Exception;


class EbayPackApi
{
    private $token;
    private $verb;
    private $helper;
    private const BOOL = ['False', 'True'];


    public function __construct()
    {
        $this->helper = new EbayListingCommonHelper();
    }

    /**
     * 封装发送ebay call
     * @param array $accountInfo
     * @param array $xmlInfo
     * @param string $verb
     * @param int $siteId
     * @return mixed
     * @throws Exception
     */
    public function sendEbayApiCall(array $accountInfo, array $xmlInfo, string $verb, int $siteId=0)
    {
        try {
            $ebayApi = $this->createApi($accountInfo, $verb, $siteId);
            $xml = $this->createXml($xmlInfo);
            $res = $ebayApi->createHeaders()->__set('requesBody', $xml)->sendHttpRequest2();
            return $res;
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @param array $accountInfo
     * @param string $verb
     * @param int $siteId
     * @return EbayApi
     * @throws Exception
     */
    public function createApi(array $accountInfo, string $verb, int $siteId=0)
    {
        try {
            $token = json_decode($accountInfo['token'], true);
            $token = isset($token[0]) ? $token[0] : $accountInfo['token'];
            $config['devID'] = $accountInfo['dev_id'];
            $config['appID'] = $accountInfo['app_id'];
            $config['certID'] = $accountInfo['cert_id'];
            $config['compatLevel'] = Constants::EBAY_API_VERSION;
            $config['userToken'] = $token;
            $config['siteID'] = $siteId;
            $config['verb'] = $verb;
            $this->token = $token;
            $this->verb = $verb;
            return new EbayApi($config);

        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function createXml( $data) : string
    {
        try {
            $verb = $this->verb;
            $listvar_en = Constants::LISTVAR_EN;
            $xml = '<?xml version="1.0" encoding="utf-8"?>';
            $xml .= '<' . $verb . 'Request xmlns="urn:ebay:apis:eBLBaseComponents">';
            switch ($verb) {
                case 'UploadSiteHostedPictures':
                    $xml .= '<ExternalPictureURL>'.$data.'</ExternalPictureURL>';
                    $xml .= '<PictureSet>Supersize</PictureSet>';
                    break;
                case 'AddItem':
                case 'VerifyItem':
                case 'AddFixedPriceItem':
                case 'VerifyAddFixedPriceItem':
                    $list = $data['list'];
                    $set = $data['set'];
                    $xml .= '<Item>';
//                    $xml .= '<OutOfStockControl>True</OutOfStockControl>';
                    $xml .= '<ApplicationData>' . ($set['application_data']??'rondaful') . '</ApplicationData>';
                    $xml .= '<AutoPay>' . self::BOOL[$list['autopay']] . '</AutoPay>';
                    //议价
                    $xml .= '<BestOfferDetails>';
                    $xml .= '<BestOfferEnabled>' . self::BOOL[$list['best_offer']] . '</BestOfferEnabled>';
                    $xml .= '</BestOfferDetails>';
                    if ($list['best_offer'] && ($set['auto_accept_price']!='0.00' || $set['minimum_accept_price']!='0.00')) {
                        $xml .= '<ListingDetails>';
                        if ($set['auto_accept_price']!='0.00') {
                            $xml .= '<BestOfferAutoAcceptPrice>' . $set['auto_accept_price'] . '</BestOfferAutoAcceptPrice>';
                        }
                        if ($set['minimum_accept_price']!='0.00') {
                            $xml .= '<MinimumBestOfferPrice>' . $set['minimum_accept_price'] . '</MinimumBestOfferPrice>';
                        }
                        $xml .= '</ListingDetails>';
                    }

                    $imgs = EbayPublish::seperateImgs($data['imgs']);
                    //描述
                    $styleInfo['style'] = $list['mod_style'];
                    $styleInfo['sale'] = $list['mod_sale'];
                    $styleInfo['description'] = $set['description'];
                    $styleInfo['title'] = $list['title'];
                    $styleInfo['imgs'] = $imgs;
                    $description = $this->applyStyleTemplate($styleInfo);
                    $xml .= '<Description><![CDATA[' . $description . ']]></Description>';

                    //买家限制
                    $xml .= '<DisableBuyerRequirements>' .(($list['disable_buyer']^1) ? 'true': 'false') . '</DisableBuyerRequirements>';
                    if ($list['disable_buyer']) {
                        $brd = (new EbayPublishHelper())->buyerRequirementJsonToArray($set['buyer_requirment_details']);
                        $brd = $brd[0]??$brd;
                        $xml .= '<BuyerRequirementDetails>';
//                        $xml .= '<LinkedPayPalAccount>' . self::BOOL[$brd['link_paypal']] . '</LinkedPayPalAccount>';
                        if ($brd['violations']) {
                            $xml .= '<MaximumBuyerPolicyViolations>';
                            $xml .= '<Count>' . $brd['violations_count'] . '</Count>';
                            $xml .= '<Period>' . $brd['violations_period'] . '</Period>';
                            $xml .= '</MaximumBuyerPolicyViolations>';
                        }
                        if ($brd['requirements']) {
                            $xml .= '<MaximumItemRequirements>';
                            $xml .= '<MaximumItemCount>' . $brd['requirements_max_count'] . '</MaximumItemCount>';
                            $xml .= '<MinimumFeedbackScore>' . $brd['requirements_feedback_score'] . '</MinimumFeedbackScore>';
                            $xml .= '</MaximumItemRequirements>';
                        }
                        if ($brd['strikes']) {
                            $xml .= '<MaximumUnpaidItemStrikesInfo>';
                            $xml .= '<Count>' . $brd['strikes_count'] . '</Count>';
                            $xml .= '<Period>' . $brd['strikes_period'] . '</Period>';
                            $xml .= '</MaximumUnpaidItemStrikesInfo>';
                        }
                        if (isset($brd['minimum_feedback']) && $brd['minimum_feedback']) {
                            $xml .= '<MinimumFeedbackScore>' . $brd['minimum_feedback_score'] . '</MinimumFeedbackScore>';
                        }
                        $xml .= '<ShipToRegistrationCountry>' . ($brd['registration'] ? 'true' : 'false') . '</ShipToRegistrationCountry>';
                        $xml .= '</BuyerRequirementDetails>';

                    }

                    $xml .= '<DispatchTimeMax>' . $list['dispatch_max_time'] . '</DispatchTimeMax>'; //备货时间
                    //汽车兼容信息
                    $compatibilityXML = '';
                    if (intval($set['compatibility_count']) > 0) {
                        $compatibility = (new EbayPublishHelper())->compatibilityJsonToArray($set['compatibility']);
                        $compatibilityXML .= '<ItemCompatibilityList>';
                        $compatibilityXML .= '<Type>NameValue</Type>';
                        foreach ($compatibility as $comp) {
                            unset($comp['id']);
                            unset($comp['isCheck']);
                            $compatibilityXML .= '<Compatibility>';
                            $compatibilityXML .= '<CompatibilityNotes>' . $comp['notes'] . '</CompatibilityNotes>';
                            unset($comp['notes']);
                            foreach ($comp as $k => $v) {
                                $compatibilityXML .= '<NameValueList>';
                                $compatibilityXML .= '<Name>' . ucfirst($k) . '</Name>';
                                $compatibilityXML .= '<Value><![CDATA[' .$v. ']]></Value>';
                                $compatibilityXML .= '</NameValueList>';
                            }
                            $compatibilityXML .= '</Compatibility>';
                        }
                        $compatibilityXML .= '</ItemCompatibilityList>';
                    }
                    $xml .= $compatibilityXML;

                    $xml .= '<ListingDuration>' . $listvar_en['listingDuration'][$list['listing_duration']] . '</ListingDuration>';//刊登周期

                    if ($list['listing_enhancement']) {
                        $xml .= '<ListingEnhancement>BoldTitle</ListingEnhancement>';//样式增强
                    }
                    $xml .= '<ListingType>' . $listvar_en['listingType'][$list['listing_type']] . '</ListingType>';//出售方式

                    //商品所在地
                    $xml .= '<Location>' . $list['location'] . '</Location>';
                    if (!empty($set['postal_code'])) {
                        $xml .= '<PostalCode>' . $set['postal_code'] . '</PostalCode>';
                    }
                    $xml .= '<Country>' . $list['country'] . '</Country>';

                    //物品状况
                    $xml .= '<ConditionID>' . $set['condition_id'] . '</ConditionID>';
                    if (!empty($set['condition_description'])) {
                        $xml .= '<ConditionDescription>' . $set['condition_description'] . '</ConditionDescription>';
                    }
                    //付款
//                    $payMethods = json_decode($set['payment_method'], true);
//                    foreach ($payMethods as $payMethod) {
//
//                        if ($payMethod == 'PayPal') {
//
//                        }
//                    }
                    $xml .= '<PaymentMethods>PayPal</PaymentMethods>';
                    $xml .= '<PayPalEmailAddress>' . $list['paypal_emailaddress'] . '</PayPalEmailAddress>';

                    //网站 货币
                    $siteInfo = Cache::store('EbaySite')->getSiteInfoBySiteId($list['site']);
                    $xml .= '<Site>' . $siteInfo['country'] . '</Site>';
                    $xml .= '<Currency>' . $siteInfo['currency'] . '</Currency>';//货币

                    //自提
                    if ($set['local_pickup']) {
                        if ($list['site'] == 0) {//实体店提货 适用于美国站点
                            $xml .= '<PickupInStoreDetails>';
                            $xml .= '<EligibleForPickupInStore>true</EligibleForPickupInStore>';
                            $xml .= '</PickupInStoreDetails>';
                        } else if (intval($list['site']) == 3 || intval($list['site']) == 15 || intval($list['site']) == 77) {
                            //click & collect自提 适用于英国，澳洲，德国
                            $xml .= '<PickupInStoreDetails>';
                            $xml .= '<EligibleForPickupDropOff>true</EligibleForPickupDropOff>';
                            $xml .= '</PickupInStoreDetails>';
                        }
                    }

                    //刊登图片
                    $publishImgs = $imgs['publishImgs'];//刊登图
                    $xml .= '<PictureDetails>';
                    $xml .= '<GalleryType>' . $listvar_en['pictureGallery'][$list['picture_gallery']] . '</GalleryType>';
                    foreach ($publishImgs as $publishImg) {
                        $xml .= '<PictureURL>';
                        $xml .=  $publishImg['eps_path'];
                        $xml .= '</PictureURL>';
                    }
                    $xml .= '</PictureDetails>';

                    //分类
                    $xml .= '<PrimaryCategory>';
                    $xml .= '<CategoryID>' . $list['primary_categoryid'] . '</CategoryID>';
                    $xml .= '</PrimaryCategory>';
                    if (!empty($list['second_categoryid'])) {
                        $xml .= '<SecondaryCategory>';
                        $xml .= '<CategoryID>' . $list['second_categoryid'] . '</CategoryID>';
                        $xml .= '</SecondaryCategory>';
                    }
                    $xml.='<CategoryMappingAllowed>true</CategoryMappingAllowed>';

                    //物品详情
                    $xml .= '<ProductListingDetails>';
                    $xml .= '<BrandMPN>';
                    $xml .= '<Brand>' . $set['brand'] . '</Brand>';
                    !empty($set['mpn']) && $xml .= '<MPN>' . $set['mpn'] . '</MPN>';
                    $xml .= '</BrandMPN>';
                    if ($list['variation'] == 0) {
                        //单属性时加在这里，多属性时列在子产品里
                        isset($set['ean']) && $xml .= '<EAN>' . $set['ean'] . '</EAN>';
                        $xml .= '<ISBN>' . $set['isbn'] . '</ISBN>';
                        isset($set['upc']) && $xml .= '<UPC>' . $set['upc'] . '</UPC>';
                    }
                    $xml .= '</ProductListingDetails>';
                    if ($list['variation'] == 0) {
                        //可售量
                        $xml .= '<Quantity>' . $list['quantity'] . '</Quantity>';

                        //一口价
                        $xml .= '<StartPrice>' . $list['start_price'] . '</StartPrice>';
                    }
                    //平台SKU，不论单属性多属性均设置
                    if (!empty($list['listing_sku'])) {
                        $xml .= '<SKU>' . $list['listing_sku'] . '</SKU>';
                    }
                    //拍卖
                    if ($list['listing_type'] == 2) {
                        $xml .= '<BuyItNowPrice currencyID="'.$siteInfo['currency'].'">'.$list['buy_it_nowprice'].'</BuyItNowPrice>';
                        $xml .= '<ReservePrice currencyID="'.$siteInfo['currency'].'">'.$list['reserve_price'].'</ReservePrice>';
                    }

                    //退货  折旧费和节假日延期被舍弃了
                    if ($set['return_policy'] == 1) {
                        $xml .= '<ReturnPolicy>';
                        if (!empty($set['return_description'])) {
                            $xml .= '<Description>' . $set['return_description'] . '</Description>';
                        }
                        $xml .= '<RefundOption>' . $set['return_type'] . '</RefundOption>';
                        $xml .= '<ReturnsAcceptedOption>' . $listvar_en['returnPolicy'][$set['return_policy']] . '</ReturnsAcceptedOption>';
                        $xml .= '<ReturnsWithinOption>' . $listvar_en['returnTime'][$list['return_time']] . '</ReturnsWithinOption>';
                        $xml .= '<ShippingCostPaidByOption>' . $listvar_en['returnShippingOption'][$set['return_shipping_option']] . '</ShippingCostPaidByOption>';
                        $xml .= '</ReturnPolicy>';
                    }

                    //物流设置
                    $xml .= '<ShippingDetails>';

                    //不运送地区
                    if ($set['custom_exclude'] == 1) {
                        $xml .= '<ExcludeShipToLocation>none</ExcludeShipToLocation>';//运送至所有国家
                    } else if ($set['custom_exclude'] == 3 && !empty($set['exclude_location'])) {
                        $excludeLocation = json_decode($set['exclude_location'], true);
                        foreach ($excludeLocation as $v) {
                            $xml .= '<ExcludeShipToLocation>' . $v . '</ExcludeShipToLocation>';
                        }
                    }
                    //国际运输方式
                    $internationalShippings = json_decode($set['international_shipping'], true);
                    foreach ($internationalShippings as $k => $interShipping) {
                        //优先级没有使用字段记录，使用存储时的索引代替,取值1-5
                        $xml .= '<InternationalShippingServiceOption>';
                        $xml .= '<ShippingService><![CDATA[' . $interShipping['shipping_service'] . ']]></ShippingService>';
                        $xml .= '<ShippingServiceAdditionalCost>' . $interShipping['shipping_service_additional_cost'] . '</ShippingServiceAdditionalCost>';
                        $xml .= '<ShippingServiceCost>' . $interShipping['shipping_service_cost'] . '</ShippingServiceCost>';
                        $xml .= '<ShippingServicePriority>' . ($k + 1) . '</ShippingServicePriority>';
                        if (is_array($interShipping['shiptolocation'])) {
                            foreach ($interShipping['shiptolocation'] as $shipLocation) {
                                $xml .= '<ShipToLocation>' . $shipLocation . '</ShipToLocation>';
                            }
                        } else if (!empty($interShipping['shiptolocation'])) {
                            $xml .= '<ShipToLocation>' . $interShipping['shiptolocation'] . '</ShipToLocation>';
                        }
                        $xml .= '</InternationalShippingServiceOption>';
                    }
                    //税 仅支持US, US Motors(site 0) sites
                    if ($list['site'] == 0) {
                        $xml .= '<SalesTax>';
                        $xml .= '<SalesTaxPercent>' . number_format(floatval($list['sales_tax']), 3) . '</SalesTaxPercent>';
                        $xml .= '<SalesTaxState>' . $list['sales_tax_state'] . '</SalesTaxState>';
                        $xml .= '<ShippingIncludedInTax>' . self::BOOL[$list['shipping_tax']] . '</ShippingIncludedInTax>';
                        $xml .= '</SalesTax>';
                    }
                    //国内运输
                    $shippings = json_decode($set['shipping'], true);
                    foreach ($shippings as $k => $shipping) {
                        $xml .= '<ShippingServiceOptions>';
                        $xml .= '<ShippingService><![CDATA[' . $shipping['shipping_service'] . ']]></ShippingService>';
//                        if (!intval($shipping['shipping_service_cost'])) {
//                            $xml .= '<FreeShipping>true</FreeShipping>';
//                        }
                        $xml .= '<ShippingServiceAdditionalCost>' . $shipping['shipping_service_additional_cost'] . '</ShippingServiceAdditionalCost>';
                        $xml .= '<ShippingServiceCost>' . $shipping['shipping_service_cost'] . '</ShippingServiceCost>';
                        $xml .= '<ShippingServicePriority>' . ($k + 1) . '</ShippingServicePriority>';
                        if (floatval($shipping['extra_cost'])>0) {
                            $xml .= '<ShippingSurcharge>' . $shipping['extra_cost'] . '</ShippingSurcharge>';
                        }
                        $xml .= '</ShippingServiceOptions>';
                    }

                    $xml .= '</ShippingDetails>';
                    //运送地区
                    $shipLocations = json_decode($set['ship_location'], true);
                    if (is_array($shipLocations)) {
                        foreach ($shipLocations as $shipLocation) {
                            $xml .= '<ShipToLocations>' . $shipLocation . '</ShipToLocations>';
                        }
                    } else if (!empty($shipLocations)) {
                        $xml .= '<ShipToLocations>' . $shipLocations . '</ShipToLocations>';
                    }
                    //店铺分类
                    if ($list['store_category_id']) {
                        $xml .= '<Storefront>';
                        $xml .= '<StoreCategoryID>' . $list['store_category_id'] . '</StoreCategoryID>';
//                        $xml .= '<StoreCategoryName><![CDATA[' . $list['store_name'] . ']]></StoreCategoryName>';
                        if ($list['store_category2_id']) {
                            $xml .= '<StoreCategory2ID>' . $list['store_category2_id'] . '</StoreCategory2ID>';
//                            $xml .= '<StoreCategory2Name><![CDATA[' . $list['second_store_name'] . ']]></StoreCategory2Name>';
                        }
                        $xml .= '</Storefront>';
                    }
                    //标题
                    $xml .= '<Title><![CDATA[' . $list['title'] . ']]></Title>';
                    !empty($list['sub_title']) && $xml .= '<SubTitle>' . $list['sub_title'] . '</SubTitle>';

                    //拥有的特性
                    $specifics = json_decode($set['specifics'], true);//specifics格式为['custom'=>0, 'attr_name'=>'Brand', 'attr_value'=>'Does not apply'],[],[]
                    $list['variation'] && $varians = $data['varians'];
                    if ($list['variation']) {
                        $varkey = array_keys(json_decode($varians[0]["variation"], true));
                        foreach ($specifics as $k => $v) {
                            if (in_array($v['attr_name'], $varkey)) {
                                unset($specifics[$k]);
                            }
                        }
                    }
                    if (count($specifics)) {
                        $specXml = '';
                        foreach ($specifics as $specific) {
                            $specXml .= '<NameValueList>';
                            $specXml .= '<Name><![CDATA[' . $specific['attr_name'] . ']]></Name>';
                            $attrValue = empty($specific['attr_value']) ? '' : $specific['attr_value'];
                            if (is_array($attrValue)) {
                                foreach ($attrValue as $atrVal) {
                                    $specXml .= '<Value><![CDATA[' . $atrVal . ']]></Value>';

                                }
                            } else {
                                $specXml .= '<Value><![CDATA[' . $attrValue . ']]></Value>';
                            }
                            $specXml .= '</NameValueList>';
                        }
                        if ($specXml) {
                            $xml .= '<ItemSpecifics>' . $specXml . '</ItemSpecifics>';
                        }
                    }

                    //多属性
                    if ($list['variation']) {
                        $xml .= '<Variations>';
                        $xml .= '<Pictures>';
                        $keys = array_keys(json_decode($varians[0]['variation'], true));
                        $varImg = empty($set['variation_image']) ? $keys[0] : $set['variation_image'];
                        $skuImgs = $imgs['skuImgs']??[];
                        $attrValues = array_keys($skuImgs);
                        $xml .= '<VariationSpecificName><![CDATA[' . $varImg . ']]></VariationSpecificName>';
                        foreach ($attrValues as $attrValue) {
                            $xml .= '<VariationSpecificPictureSet>';
                            $xml .= '<VariationSpecificValue><![CDATA[' . $attrValue . ']]></VariationSpecificValue>';
                            foreach ($skuImgs as $value => $skuImg) {
                                if ($value == $attrValue) {
                                    foreach ($skuImg as $si) {
                                        $xml .= '<PictureURL>';
                                        $xml .= $si['eps_path'];
                                        $xml .= '</PictureURL>';
                                    }
                                }
                            }
                            $xml .= '</VariationSpecificPictureSet>';
                        }
                        $xml .= '</Pictures>';
                        $varSpecifics = [];
                        foreach ($varians as $k => $varian) {
                            $xml .= '<Variation>';
                            $xml .= '<Quantity>' . $varian['v_qty'] . '</Quantity>';
                            $xml .= '<SKU>' . $varian['channel_map_code'] . '</SKU>';
                            $xml .= '<StartPrice>' . $varian['v_price'] . '</StartPrice>';
                            $xml .= '<VariationProductListingDetails>';
                            $xml .= '<EAN>' . $varian['ean'] . '</EAN>';
                            $xml .= '<ISBN>' . $varian['isbn'] . '</ISBN>';
                            $xml .= '<UPC>' . $varian['upc'] . '</UPC>';
                            $xml .= '</VariationProductListingDetails>';
                            $varNameValue = json_decode($varian['variation'], true);
                            $xml .= '<VariationSpecifics>';
                            foreach ($varNameValue as $name => $value) {
                                $xml .= '<NameValueList>';
                                $xml .= '<Name><![CDATA[' . $name . ']]></Name>';
                                $xml .= '<Value><![CDATA[' . $value . ']]></Value>';
                                $xml .= '</NameValueList>';
                                $varSpecifics[$name][] = trim($value);
                            }
                            $xml .= '</VariationSpecifics>';
                            $xml .= '</Variation>';
                        }
                        foreach ($varSpecifics as $key => $varSpecific) {
                            $varSpecifics[$key] = array_unique($varSpecific);
                        }
                        $xml .= '<VariationSpecificsSet>';
                        foreach ($varSpecifics as $k => $varSpec) {
                            $xml .= '<NameValueList>';
                            $xml .= '<Name><![CDATA[' . $k . ']]></Name>';
                            foreach ($varSpec as $value) {
                                $xml .= '<Value><![CDATA[' . $value . ']]></Value>';
                            }
                            $xml .= '</NameValueList>';
                        }
                        $xml .= '</VariationSpecificsSet>';
                        $xml .= '</Variations>';
                    }
                    $xml .= '</Item>';
                    break;
                case 'ReviseInventoryStatus':
                    foreach ($data['newVal'] as $newVal) {
                        $xml .= '<InventoryStatus>';
                        $xml .= '<ItemID>'.$data['listing']['item_id'].'</ItemID>';
                        isset($newVal['quantity']) && $xml .= '<Quantity>'.$newVal['quantity'].'</Quantity>';
                        $xml .= '<SKU>'.$newVal['listing_sku'].'</SKU>';
                        isset($newVal['price']) && $xml .= '<StartPrice>'.$newVal['price'].'</StartPrice>';
                        $xml .= '</InventoryStatus>';
                    }
                    break;
                case 'ReviseFixedPriceItem':
                case 'ReviseItem':
                    $listingInfo = $data['listing'];
                    $newVal = $data['newVal'];
                    $accountInfo = $data['accountInfo'];
                    if (isset($newVal['imgs']) || isset($newVal['style']['imgs'])) {
                        $imgs = EbayPublish::seperateImgs($newVal['imgs']??$newVal['style']['imgs']);
                    }


                    $xml .= '<Item>';
                    $xml .= '<ItemID>'.$listingInfo['item_id'].'</ItemID>';
                    //描述
                    if (isset($newVal['style'])) {
                        $styleInfo['style'] = $newVal['style']['mod_style'];
                        $styleInfo['sale'] = $newVal['style']['mod_sale'];
                        $styleInfo['description'] = $newVal['style']['description'];
                        $styleInfo['title'] = $newVal['style']['title'];
                        $styleInfo['imgs'] = $imgs;

                        $description = $this->applyStyleTemplate($styleInfo);
                        $xml .= '<Description><![CDATA[' . $description . ']]></Description>';
                        $xml .= '<DescriptionReviseMode>Replace</DescriptionReviseMode>';
                        $xml .= '<Title><![CDATA['.$newVal['style']['title'].']]></Title>';
                    }
                    //副标题
                    isset($newVal['sub_title']) && $xml .= '<SubTitle><![CDATA['.$newVal['sub_title'].']]></SubTitle>';

                    //商品所在地
                    isset($newVal['location']) && $xml .= '<Location>'.$newVal['location'].'</Location>';
                    isset($newVal['country']) && $xml .= '<Country>'.$newVal['country'].'</Country>';
                    isset($newVal['postal_code']) && $xml .= '<PostalCode>' . $newVal['postal_code'] . '</PostalCode>';
                    //退货政策
                    if (isset($newVal['return'])) {
                        $return = $newVal['return'];
                        $xml .= '<ReturnPolicy>';
                        if (!empty($return['return_description'])) {
                            $xml .= '<Description>' . $return['return_description'] . '</Description>';
                        }
                        $xml .= '<RefundOption>' . $return['return_type'] . '</RefundOption>';
                        $xml .= '<ReturnsAcceptedOption>' . $listvar_en['returnPolicy'][$return['return_policy']] . '</ReturnsAcceptedOption>';
                        $xml .= '<ReturnsWithinOption>' . $listvar_en['returnTime'][$return['return_time']] . '</ReturnsWithinOption>';
                        $xml .= '<ShippingCostPaidByOption>' . $listvar_en['returnShippingOption'][$return['return_shipping_option']] . '</ShippingCostPaidByOption>';
                        $xml .= '</ReturnPolicy>';
                    }
                    //卖家限制
                    isset($newVal['disable_buyer']) && $xml .= '<DisableBuyerRequirements>'.self::BOOL[($newVal['disable_buyer']^1)].'</DisableBuyerRequirements>';
                    if (isset($newVal['disable_buyer']) && $newVal['disable_buyer'] == 1) {
                        $brd = $newVal['buyer_requirement'];
//                            $brd = $brd[0];
                        $xml .= '<BuyerRequirementDetails>';
                        $xml .= '<LinkedPayPalAccount>' . self::BOOL[$brd['link_paypal']] . '</LinkedPayPalAccount>';
                        if ($brd['violations']) {
                            $xml .= '<MaximumBuyerPolicyViolations>';
                            $xml .= '<Count>' . $brd['violations_count'] . '</Count>';
                            $xml .= '<Period>' . $brd['violations_period'] . '</Period>';
                            $xml .= '</MaximumBuyerPolicyViolations>';
                        }
                        if ($brd['requirements']) {
                            $xml .= '<MaximumItemRequirements>';
                            $xml .= '<MaximumItemCount>' . $brd['requirements_max_count'] . '</MaximumItemCount>';
                            $xml .= '<MinimumFeedbackScore>' . $brd['requirements_feedback_score'] . '</MinimumFeedbackScore>';
                            $xml .= '</MaximumItemRequirements>';
                        }
                        if ($brd['strikes']) {
                            $xml .= '<MaximumUnpaidItemStrikesInfo>';
                            $xml .= '<Count>' . $brd['strikes_count'] . '</Count>';
                            $xml .= '<Period>' . $brd['strikes_period'] . '</Period>';
                            $xml .= '</MaximumUnpaidItemStrikesInfo>';
                        }
                        if ($brd['minimum_feedback']??'') {
                            $xml .= '<MinimumFeedbackScore>' . $brd['minimum_feedback_score'] . '</MinimumFeedbackScore>';
                        }
                        $xml .= '<ShipToRegistrationCountry>' . self::BOOL[$brd['registration']] . '</ShipToRegistrationCountry>';
                        $xml .= '</BuyerRequirementDetails>';
                    }
                    //备货时间
                    isset($newVal['dispatch_max_time']) && $xml .= '<DispatchTimeMax>' . $newVal['dispatch_max_time'] . '</DispatchTimeMax>'; //备货时间
                    //自提
                    if (isset($newVal['local_pickup'])) {
                        $site = $listingInfo['site'];
                        if ($site == 0) {//实体店提货 适用于美国站点
                            $xml .= '<PickupInStoreDetails>';
                            $xml .= '<EligibleForPickupInStore>'.self::BOOL[$newVal['local_pickup']].'</EligibleForPickupInStore>';
                            $xml .= '</PickupInStoreDetails>';
                        } else if (intval($site) == 3 || intval($site) == 15 || intval($site) == 77) {
                            //click & collect自提 适用于英国，澳洲，德国
                            $xml .= '<PickupInStoreDetails>';
                            $xml .= '<EligibleForPickupDropOff>'.self::BOOL[$newVal['local_pickup']].'</EligibleForPickupDropOff>';
                            $xml .= '</PickupInStoreDetails>';
                        }
                    }
                    isset($newVal['autopay']) && $xml .= '<AutoPay>'.self::BOOL[$newVal['autopay']].'</AutoPay>';//立即付款
                    //议价
                    if (isset($newVal['best_offer'])) {
                        $xml .= '<BestOfferDetails>';
                        $xml .= '<BestOfferEnabled>'.self::BOOL[$newVal['best_offer']].'</BestOfferEnabled>';
                        $xml .= '</BestOfferDetails>';
                        if ($newVal['best_offer']) {
                            $xml .= '<ListingDetails>';
                            isset($newVal['auto_accept_price']) && $xml .= '<BestOfferAutoAcceptPrice>'.$newVal['auto_accept_price'].'</BestOfferAutoAcceptPrice>';
                            isset($newVal['minimum_accept_price']) && $xml .= '<MinimumBestOfferPrice>'.$newVal['minimum_accept_price'].'</MinimumBestOfferPrice>';
                            $xml .= '</ListingDetails>';
                        }
                    }
                    if (isset($newVal['epid'])) {
                        $xml .= '<ProductListingDetails>';
                        $xml .= '<ProductReferenceID>'.$newVal['epid'].'</ProductReferenceID>';
                        $xml .= '</ProductListingDetails>';
                    }

                    //单属性价格库存,listing_sku
                    if ($listingInfo['variation'] == 0) {
                        isset($newVal['start_price']) && $xml .= '<StartPrice>' . $newVal['start_price'] . '</StartPrice>';
                        isset($newVal['listing_sku']) && $xml .= '<SKU>'.$newVal['listing_sku'].'</SKU>';
                    }
                    if ($listingInfo['listing_type'] == 2) {
                        isset($newVal['buy_it_nowprice']) && $xml .= '<BuyItNowPrice>'.$newVal['buy_it_nowprice'].'</BuyItNowPrice>';
                        isset($newVal['reserve_price']) && $xml .= '<ReservePrice>'.$newVal['reserve_price'].'</ReservePrice>';
                    }
                    isset($newVal['quantity']) && $xml .= '<Quantity>' . $newVal['quantity'] . '</Quantity>';

                    //物流不运送地区
                    if (isset($newVal['shipping']) || isset($newVal['international_shipping']) || isset($newVal['exclude_location'])) {
                        //物流设置
                        $xml .= '<ShippingDetails>';
                        //不运送地区
                        if (isset($newVal['exclude_location'])) {
                            if (empty($newVal['exclude_location'])) {
                                $xml .= '<ExcludeShipToLocation>none</ExcludeShipToLocation>';
                            } else {
                                foreach ($newVal['exclude_location'] as $v) {
                                    if (empty($v)) continue;
                                    $xml .= '<ExcludeShipToLocation>' . $v . '</ExcludeShipToLocation>';
                                }
                            }
                        }
                        //国际运输方式
                        if (isset($newVal['international_shipping'])) {
                            foreach ($newVal['international_shipping'] as $k => $interShipping) {
                                //优先级没有使用字段记录，使用存储时的索引代替,取值1-5
                                $xml .= '<InternationalShippingServiceOption>';
                                $xml .= '<ShippingService>' . $interShipping['shipping_service'] . '</ShippingService>';
                                $xml .= '<ShippingServiceAdditionalCost>' . $interShipping['shipping_service_additional_cost'] . '</ShippingServiceAdditionalCost>';
                                $xml .= '<ShippingServiceCost>' . $interShipping['shipping_service_cost'] . '</ShippingServiceCost>';
                                $xml .= '<ShippingServicePriority>' . ($k + 1) . '</ShippingServicePriority>';
                                if (isset($interShipping['shiptolocation'])&&is_array($interShipping['shiptolocation'])) {
                                    foreach ($interShipping['shiptolocation'] as $shipLocation) {
                                        $xml .= '<ShipToLocation>' . $shipLocation . '</ShipToLocation>';
                                    }
                                } else if (!empty($interShipping['shiptolocation'])) {
                                    $xml .= '<ShipToLocation>' . $interShipping['shiptolocation'] . '</ShipToLocation>';
                                }
                                $xml .= '</InternationalShippingServiceOption>';
                            }
                        }
                        //国内运输
                        if (isset($newVal['shipping'])) {
                            foreach ($newVal['shipping'] as $k => $shipping) {
                                $xml .= '<ShippingServiceOptions>';
                                $xml .= '<ShippingService>' . $shipping['shipping_service'] . '</ShippingService>';
    //                        if (!intval($shipping['shipping_service_cost'])) {
    //                            $xml .= '<FreeShipping>true</FreeShipping>';
    //                        }
                                $xml .= '<ShippingServiceAdditionalCost>' . $shipping['shipping_service_additional_cost'] . '</ShippingServiceAdditionalCost>';
                                $xml .= '<ShippingServiceCost>' . $shipping['shipping_service_cost'] . '</ShippingServiceCost>';
                                $xml .= '<ShippingServicePriority>' . ($k + 1) . '</ShippingServicePriority>';
                                if ($shipping['extra_cost'] > 0.01) {
                                    $xml .= '<ShippingSurcharge>' . $shipping['extra_cost']. '</ShippingSurcharge>';
                                }
                                $xml .= '</ShippingServiceOptions>';
                            }
                        }
                        $xml .= '</ShippingDetails>';
                    }

                    //店铺分类
                    if (isset($newVal['storeCategoryId']) || isset($newVal['storeCategory2Id'])) {
                        $xml .= '<Storefront>';
                        isset($newVal['storeCategory2Id']) && $xml .= '<StoreCategory2ID>'.$newVal['storeCategory2Id'].'</StoreCategory2ID>';
    //                        $xml .= '<StoreCategory2Name> string </StoreCategory2Name>';
                        isset($newVal['storeCategoryId']) && $xml .= '<StoreCategoryID>'.$newVal['storeCategoryId'].'</StoreCategoryID>';
    //                        $xml .= '<StoreCategoryName> string </StoreCategoryName>';
                        $xml .= '</Storefront>';
                    }

                    //橱窗图片
                    if ((isset($newVal['imgs']) && $newVal['publishImgs']==1) || isset($newVal['picture_gallery'])) {

                        $pictureGallery = isset($newVal['picture_gallery']) ? $newVal['picture_gallery'] : $listingInfo['picture_gallery'];
                        $xml .= '<PictureDetails>';
                        $xml .= '<GalleryType>' . $listvar_en['pictureGallery'][$pictureGallery] . '</GalleryType>';
                        if (isset($newVal['imgs'])) {
                            $publishImgs = $imgs['publishImgs'];
                            foreach ($publishImgs as $publishImg) {
                                $xml .= '<PictureURL>';
                                $xml .= $publishImg['eps_path'];
                                $xml .= '</PictureURL>';
                            }
                        }
                        $xml .= '</PictureDetails>';
                    }
                    //付款选项
                    if (isset($newVal['payMethod'])) {
                        $payMethods = $newVal['payMethod'];
                        $paypalEmail = $newVal['paypal'];
                        foreach ($payMethods as $payMethod) {
                            $xml .= '<PaymentMethods>' . $payMethod . '</PaymentMethods>';
                            if ($payMethod == 'PayPal') {
                                $xml .= '<PayPalEmailAddress>' . $paypalEmail . '</PayPalEmailAddress>';
                            }
                        }
                    }
                    //拥有的特性
                    if (isset($newVal['specifics'])) {
                        $specifics = $newVal['specifics'];//
                        if ($listingInfo['variation'] && isset($newVal['varian'])) {
                            $vars = array_values($newVal['varian']);
                            $varkey = array_keys($vars[0]["variation"]);
                            foreach ($specifics as $k => $v) {
                                if (in_array($v['attr_name'], $varkey)) {
                                    unset($specifics[$k]);
                                }
                            }
                        }
                        if (count($specifics)) {
                            $specXml = '';
                            foreach ($specifics as $specific) {
                                $specXml .= '<NameValueList>';
                                $specXml .= '<Name><![CDATA[' . $specific['attr_name'] . ']]></Name>';
                                $attrValue = empty($specific['attr_value']) ? '' : $specific['attr_value'];
                                if (is_array($attrValue)) {
                                    foreach ($attrValue as $atrVal) {
                                        $specXml .= '<Value><![CDATA[' . $atrVal . ']]></Value>';

                                    }
                                } else {
                                    $specXml .= '<Value><![CDATA[' . $attrValue . ']]></Value>';
                                }
                                $specXml .= '</NameValueList>';
                            }
                            if ($specXml) {
                                $xml .= '<ItemSpecifics>' . $specXml . '</ItemSpecifics>';
                            }
                        }
                    }

                    //多属性
                    if ($listingInfo['variation']) {
                        if (isset($newVal['variants'])) {
                            $xml .= '<Variations>';
                            $xml .= '<Pictures>';
                            $varImg = $newVal['variation_image'];
                            $skuImgs = $imgs['skuImgs'];
                            $attrValues = array_values($skuImgs);
                            $xml .= '<VariationSpecificName><![CDATA[' . $varImg . ']]></VariationSpecificName>';
                            foreach ($attrValues as $attrValue) {
                                $xml .= '<VariationSpecificPictureSet>';
                                $xml .= '<VariationSpecificValue><![CDATA[' . $attrValue . ']]></VariationSpecificValue>';
                                foreach ($skuImgs[$attrValue] as $skuImg) {
                                    $xml .= '<PictureURL>';
                                    $xml .= $skuImg['eps_path'];
                                    $xml .= '</PictureURL>';
                                }
                                $xml .= '</VariationSpecificPictureSet>';
                            }
                            $xml .= '</Pictures>';
                            $varSpecifics = [];
                            foreach ($newVal['variants'] as $k => $varian) {
                                $xml .= '<Variation>';
                                $xml .= '<Quantity>' . $varian['v_qty'] . '</Quantity>';
                                $xml .= '<SKU>' . $varian['channel_map_code'] . '</SKU>';
                                $xml .= '<StartPrice>' . $varian['v_price'] . '</StartPrice>';
                                $varNameValue = $varian['variation'];
                                $xml .= '<VariationSpecifics>';
                                foreach ($varNameValue as $name => $value) {
                                    $xml .= '<NameValueList>';
                                    $xml .= '<Name><![CDATA[' . $name . ']]></Name>';
                                    $xml .= '<Value><![CDATA[' . $value . ']]></Value>';
                                    $xml .= '</NameValueList>';
                                    $varSpecifics[$name][] = trim($value);
                                }
                                $xml .= '</VariationSpecifics>';
                                $xml .= '</Variation>';
                            }
                            //需要删除的变体
                            if (isset($newVal['delVars'])) {
                                foreach ($newVal['delVars'] as $delVar) {
                                    $xml .= '<Variation>';
                                    $xml .= '<Delete>True</Delete>';
                                    $xml .= '<Quantity>' . $delVar['v_qty'] . '</Quantity>';
                                    $xml .= '<SKU>' . $delVar['channel_map_code'] . '</SKU>';
                                    $xml .= '<StartPrice>' . $delVar['v_price'] . '</StartPrice>';
                                    $varNameValue = $delVar['variation'];
                                    $xml .= '<VariationSpecifics>';
                                    foreach ($varNameValue as $name => $value) {
                                        $xml .= '<NameValueList>';
                                        $xml .= '<Name><![CDATA[' . $name . ']]></Name>';
                                        $xml .= '<Value><![CDATA[' . $value . ']]></Value>';
                                        $xml .= '</NameValueList>';
                                    }
                                    $xml .= '</VariationSpecifics>';
                                    $xml .= '</Variation>';
                                }
                            }
                            foreach ($varSpecifics as $key => $varSpecific) {
                                $varSpecifics[$key] = array_unique($varSpecific);
                            }
                            $xml .= '<VariationSpecificsSet>';
                            foreach ($varSpecifics as $k => $varSpecific) {
                                $xml .= '<NameValueList>';
                                $xml .= '<Name><![CDATA[' . $k . ']]></Name>';
                                foreach ($varSpecific as $value) {
                                    $xml .= '<Value><![CDATA[' . $value . ']]></Value>';
                                }
                                $xml .= '</NameValueList>';
                            }
                            $xml .= '</VariationSpecificsSet>';
                            $xml .= '</Variations>';
                        }
                    }
                    $xml .= '</Item>';
                    break;
                case 'GetCategories':
                    if (isset($data['parent_id'])) {
                        $xml .= '<CategoryParent>'.$data['parent_id'].'</CategoryParent>';
                    }
                    $xml .= '<CategorySiteID>'.$data['site_id'].'</CategorySiteID>';
                    if (isset($data['level_limit'])) {
                        $xml .= '<LevelLimit>'.$data['level_limit'].'</LevelLimit>';
                    }
                    $xml .= '<DetailLevel>ReturnAll</DetailLevel>';
                    break;
                case 'GetCategoryFeatures':
                    $xml .= '<CategoryID>'.$data['category_id'].'</CategoryID>';
//                    $xml .= '<AllFeaturesForCategory>True</AllFeaturesForCategory>';
//                    foreach ($data['feature_id'] as $feature) {
                    $xml .= '<FeatureID>'.$data['feature_id'].'</FeatureID>';
//                    }
                    $xml .= '<ViewAllNodes>True</ViewAllNodes>';
                    $xml .= '<DetailLevel>ReturnAll</DetailLevel>';
                    break;
                case 'GeteBayDetails':
                    $xml .= '<DetailName>'.$data['detail_name'].'</DetailName>';
                    break;
                case 'GetItem':
                    $xml .= '<IncludeItemSpecifics>true</IncludeItemSpecifics>';
                    $xml .= '<IncludeItemCompatibilityList>true</IncludeItemCompatibilityList>';
                    $xml .= '<IncludeWatchCount>true</IncludeWatchCount>';
                    $xml .= '<DetailLevel>ReturnAll</DetailLevel>';
                    $xml .= '<ItemID>'.$data['item_id'].'</ItemID>';
                    break;
                case 'GetCategorySpecifics':
                    foreach ($data['category_id'] as $categoryId) {
                        $xml .= '<CategoryID>'.$categoryId.'</CategoryID>';
                    }
                    break;
                case 'EndItem':
                    $xml .= '<EndingReason>OtherListingError</EndingReason>';
                    $xml .= ' <ItemID>'.$data['item_id'].'</ItemID>';
                    break;
                case 'GetOrderTransactions':
                    $xml .= '<IncludeFinalValueFees>True</IncludeFinalValueFees>';
                    $xml .= '<ItemTransactionIDArray>';
                    $xml .= '<ItemTransactionID>';
                    $xml .= '<ItemID>'.$data['item_id'].'</ItemID>';
//                    $xml .= '<OrderLineItemID></OrderLineItemID>';
                    $xml .= '<SKU>'.$data['sku'].'</SKU>';
//                    $xml .= '<TransactionID>'..'</TransactionID>';
                    $xml .= '</ItemTransactionID>';
                    $xml .= '</ItemTransactionIDArray>';
//                    $xml .= '<OrderIDArray>';
//                    $xml .= '<OrderID></OrderID>';
//                    $xml .= '</OrderIDArray>';
                    $xml .= '<DetailLevel>ReturnAll</DetailLevel>';
                    break;
                case 'GetItemTransactions':
                    $xml .= '<IncludeVariations>'.$data['includeVariations'].'</IncludeVariations>';
                    $xml .= '<ItemID>'.$data['itemID'].'</ItemID>';
                    $xml .= '<TransactionID>'.$data['transactionID'].'</TransactionID>';
                    $xml .= '<DetailLevel>ReturnAll</DetailLevel>';
                    break;
                case 'GetSellerDashboard':
                    break;
                case 'GetSuggestedCategories':
                    $xml .= '<Query>'.$data['query'].'</Query>';
                    break;
                case 'GetUser':
//                    $xml .= '<IncludeFeatureEligibility>True</IncludeFeatureEligibility>';
                    break;
                case 'RelistItem':
                    $xml .= '<Item>';
                    $xml .= '<ItemID>'.$data['item_id'].'</ItemID>';
                    $xml .= '<Site>'.$data['country'].'</Site>';
                    $xml .= '</Item>';
                    break;
                case 'GetSellerList':
                    $xml .= '<Pagination>';
                    $xml .= '<EntriesPerPage>'.$data['Pagination']['EntriesPerPage'].'</EntriesPerPage>';
                    $xml .= '<PageNumber>'.$data['Pagination']['PageNumber'].'</PageNumber>';
                    $xml .= '</Pagination>';
                    $xml .= '<StartTimeFrom>'.$data['StartTimeFrom'].'</StartTimeFrom>';
                    $xml .= '<StartTimeTo>'.$data['StartTimeTo'].'</StartTimeTo>';
                    break;
                    
            }
            $xml .= '<ErrorLanguage>zh_CN</ErrorLanguage>';
            $xml .= '<WarningLevel>High</WarningLevel>';
            $xml .= '<RequesterCredentials>';
            $xml .= '<eBayAuthToken>'.$this->token.'</eBayAuthToken>';
            $xml .= '</RequesterCredentials>';
            $xml .= '</'.$verb.'Request>';
            return $xml;
        } catch (Exception $e) {
            throw new Exception($e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
        } catch (\Exception $e) {
            throw new Exception($e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
        }
    }



    /**
     *应用风格模板
     * @param array $data
     * @return string
     * @throws Exception
     */
    private function applyStyleTemplate(array $data) : string
    {
        try {
            $style = [];
            $sale = [];
            $desc = $data['description'];
            $newDesc = '';
            if (!empty($data['style'])) {
                $style = EbayModelStyle::get($data['style']);
                $style = empty($style) ? [] : $style->toArray();
            }
            if (!empty($data['sale'])) {
                $sale = EbayModelSale::get($data['sale']);
                $sale = empty($sale) ? [] : $sale->toArray();
            }

            $publishImgs = $data['imgs']['publishImgs'];
            $detailImgs = $data['imgs']['detailImgs'];
            $pdImgs = array_merge($publishImgs,$detailImgs);

            /************************替换描述中的图片地址**************************************************************/
            //替换掉其中的本地图片
            $desc = preg_replace_callback('/http(s)?:\/\/((14\.118\.130\.19)|(img\.rondaful\.com))(:\d+)?(\/\w+)+\.((jpg)|(gif)|(bmp)|(png))/',
                function($matches) use ($pdImgs){
                    foreach ($pdImgs as $pdImg) {
                        if ('http://img.rondaful.com/'.$pdImg['path'] == $matches[0]
                            || 'https://img.rondaful.com/'.$pdImg['path'] == $matches[0]) {
                            return $pdImg['eps_path'];//将图片地址替换为eps地址
                        }
                    }
            }, $desc);
            /**********************************************************************************************************/


            if($style){
                $newDesc = str_replace('[DESCRIBE]',$desc, $style['style_detail']);
                $newDesc = str_replace('[TITLE]',$data['title'], $newDesc);
            }
            if($sale){
                $payment = trim($sale['payment']) ? $sale['payment'] : "";
                $Shipping = trim($sale['delivery_detail']) ? $sale['delivery_detail'] : "";
                $Terms = trim($sale['terms_of_sales']) ? $sale['terms_of_sales'] : "";
                $About = trim($sale['about_us']) ? $sale['about_us'] : "";
                $Contace = trim($sale['contact_us']) ? $sale['contact_us'] : "";

                $newDesc = str_replace('[Payment]',$payment, $newDesc);//付款
                $newDesc = str_replace('[Shipping]',$Shipping, $newDesc);//提货
                $newDesc = str_replace('[Terms of Sale]',$Terms, $newDesc);//销售条款
                $newDesc = str_replace('[About Me]',$About, $newDesc);//关于我们
                $newDesc = str_replace('[Contact Us]',$Contace, $newDesc);//联系我们
            }
            $imgsStr = '';
            $i=1;
            $k=1;
            foreach ($publishImgs as $publishImg) {//橱窗图
                $imgsStrNum = '<img src='.$publishImg['eps_path'].'>';
                $newDesc = str_replace("[IMG".$i."]", $imgsStrNum, $newDesc);
                $i++;
            }
            foreach ($detailImgs as $detailImg) {//详情图
                $imgsStr .= '<img src='.$detailImg['eps_path'].'>';
                $imgDetail = '<img src='.$detailImg['eps_path'].'>';
                $newDesc = str_replace("[TIMG".$k."]",$imgDetail,$newDesc);
                $k++;
            }
            //如果实际图片数量比占位符数量少，把未使用的占位符清除掉
            while ($i <= (Constants::MAX_MAIN_IMG_NUM) || $k <= (Constants::MAX_DETAIL_IMG_NUM)){
                if ($i <= (Constants::MAX_MAIN_IMG_NUM)) {
                    $newDesc = str_replace("[IMG".$i++."]","",$newDesc);
                }
                if ($k <= (Constants::MAX_DETAIL_IMG_NUM)) {
                    $newDesc = str_replace("[TIMG".$k++."]","",$newDesc);
                }
            }
            //快捷替换。直接把PICTURE占位符替换成所有的详情描述图。PICTURE与TIMG占位符不应该共存
            $newDesc = str_replace("[PICTURE]", $imgsStr, $newDesc);
            return empty($newDesc) ? $desc : $newDesc;
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }


    
    /**
     * 上传图片到EPS
     * @param array $imgs
     * @param array $accountInfo
     * @throws Exception
     */
    public function updateListingUploadImages(array &$imgs, array $accountInfo)
    {
        try {
            $verb = 'UploadSiteHostedPictures';
            $needUploadImgs = [];
            //过滤已上传的图片
            foreach ($imgs as $k => &$img) {
                if (empty($img['eps_path']) && strpos($img['path'],'https://i.ebayimg.com/') === false) {
                    $needUploadImgs[$k] = $img;
                } else if (strpos($img['path'],'https://i.ebayimg.com/') !== false) {
                    $img['eps_path'] = $img['path'];
                }
            }
            if (empty($needUploadImgs)) {
                return;//没有需要上传的
            }
            //有需要上传的图片，检查是否有图片服务器地址
            foreach ($needUploadImgs as &$needUploadImg) {
                if (empty($needUploadImg['ser_path'])) {
                    $needUploadImg['ser_path'] = GoodsImage::getThumbPath($needUploadImg['path'], 0, 0, $accountInfo['code'], true);
                }
            }
            $api = $this->createApi($accountInfo, $verb);

            for ($i=0; $i<5; $i++) {//最多上传5次
                $xml = [];
                foreach ($needUploadImgs as $k => $needUploadImg) {
                    if (empty($needUploadImg['eps_path'])) {
                        $xml[$k] = $this->createXml($needUploadImg);
                    }
                }
                if (empty($xml)) break;
                $response = $api->createHeaders()->sendHttpRequestMulti($xml);
                foreach ($response as $key => $res) {
                    if (!is_array($res)) continue;
                    $r = (new EbayDealApiInformation())->dealWithApiResponse($verb, $res);
                    if ($r['result'] == true) {
                        $needUploadImgs[$key]['eps_path'] = $r['data'];
                    }
                }
            }
            //更新原图片数组
            foreach ($needUploadImgs as $k => $needUploadImg) {
                $imgs[$k] = $needUploadImg;
            }
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @param int $listId
     * @param array $accountInfo
     * @param int $siteId
     * @return array
     */
//    public function uploadImgToEPS(int $listId, $accountInfo, int $siteId=0)
//    {
//        set_time_limit(0);
//        try {
//            $errorMsg = [];
//            $imgs = (new EbayPublishHelper())->getListingUniqueImgs($listId);
//            $verb = 'UploadSiteHostedPictures';
////            $ebayApi = new EbayPackApi();
//            $ebayDealRes = new EbayDealApiInformation();
//            $apiObj = $this->createApi($accountInfo, $verb);
//            $tolCount = (new EbayListingImage())->where(['listing_id' => $listId])->count();//所有图片数量
//            $i = 0;
//            $j = 0;
//            for (; $i<5; $i++) {
//                $xmls = [];
//                foreach ($imgs as $k => $img) {
//                    $imgs[$k] = is_array($img) ? $img : $img->toArray();
//                    if (empty($img['eps_path']) && strpos($img['path'],'https://i.ebayimg.com/')!==false) {
//                        $img['eps_path'] = $img['path'];
//                        $img['status'] = 3;
//                        $img['message'] = '';
//                        EbayListingImage::update($img);
//                        continue;
//                    }
//                    if (empty($img['eps_path']) || $img['status'] != 3) {
//                        $xmls[$k] = $this->createXml($imgs[$k]);
//                        $j++;
//                    }
//                    if ($j == 10 || ($k == count($imgs)-1)) {//带宽限制，10个一组上传
//                        $updateImgs = [];
//                        $response = $apiObj->createHeaders()->sendHttpRequestMulti($xmls);
//                        if (empty($response)) {
//                            continue;
//                        }
//                        foreach ($response as $key => $res) {
//                            if (!is_array($res)) continue;
//                            $r = $ebayDealRes->dealWithApiResponse($verb, $res);
//
//                            if ($r['result'] == true) {
//                                $imgs[$key]['eps_path'] = $r['data'];
//                                $imgs[$key]['status'] = 3;
//                                $imgs[$key]['message'] = '';
//                                $errorMsg[$key] = '';
//                            } else {
//                                $imgs[$key]['message'] = json_encode($r['data']);
//                                $errorMsg[$key] = json_encode($r['data']);
//                            }
//                            $updateImgs[] = $imgs[$key];
//                        }
//                        $imgModel = new EbayListingImage();
//                        $imgModel->saveAll($updateImgs);
//                        $j = 0;
//                    }
//                }
//                //具有相同服务器地址的同步更新
//                $syncImgs = [];
//                $unUploadImgs = (new EbayListingImage())->field(true)->where(['listing_id'=>$listId, 'status'=>['neq', 3]])->select();
//                foreach ($unUploadImgs as $k => $unUploadImg) {
//                    $unUploadImg = $unUploadImg->toArray();
//                    foreach ($imgs as $img) {
//                        if ($img['status'] == 3 && $unUploadImg['ser_path'] == $img['ser_path']) {
//                            $unUploadImg['status'] = 3;
//                            $unUploadImg['eps_path'] = $img['eps_path'];
//                            $unUploadImg['message'] = '';
//                            $syncImgs[] = $unUploadImg;
//                        }
//                    }
//                }
//                (new EbayListingImage())->saveAll($syncImgs);
//
//                $count = (new EbayListingImage())->where(['listing_id' => $listId, 'status' => 3])->count();//上传成功的图片数量
//                $imgs = (new EbayListingImage())->field(true)->where(['listing_id' => $listId, 'status' => ['neq', 3]])->select();
//                if ($tolCount == $count) {
//                    break;
//                }
//            }
//            if ($i == 5) {
//                throw new Exception('图片上传失败,信息:'.json_encode($errorMsg));
//            }
//        } catch(Exception $e) {
//            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
//        } catch (\Exception $e) {
//            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
//        }
//    }
    
    public function uploadImgsToEps(&$imgs,$accountInfo,$siteId=0)
    {
        $verb = 'UploadSiteHostedPictures';
        $message = '';
        try {
            $api = $this->createApi($accountInfo, $verb, $siteId);
        } catch (\Exception $e) {
            return ['result' => false, 'message' => $e->getMessage()];
        }
        try {
            $needUploadImgs = [];
            foreach ($imgs as &$img) {
                if (empty($img['eps_path']) && strpos($img['path'],'https://i.ebayimg.com')===false) {
                    $needUploadImgs[$img['path']] = $img;
                } elseif (empty($img['eps_path']) && strpos($img['path'],'https://i.ebayimg.com')!==false) {
                    $img['epa_path'] = $img['path'];
                }

            }
            if (empty($needUploadImgs)) {
                return ['result' => true];
            }

            for ($i = 0; $i < 5; $i++) {//最多上传5次
                $xml = [];
                $count = count($needUploadImgs);
                $uploadOver = 0;
                for ($j = 0; $j < ceil($count / 10); $j++) {//10个一组
                    $imgNum = 0;
                    foreach ($needUploadImgs as $k => $needUploadImg) {
                        if (empty($needUploadImg['eps_path'])) {
                            try {
                                $xml[$k] = $this->createXml($needUploadImg['ser_path']);
                                $imgNum++;
                            } catch (\Exception $e) {
                                return ['result' => false, 'message' => $e->getMessage()];
                            }
                        }
                        if ($imgNum == 10) {
                            break;
                        }
                    }
                    if (empty($xml)) {
                        $uploadOver = 1;
                        break;
                    }
                    $response = $api->createHeaders()->sendHttpRequestMulti($xml);
                    foreach ($response as $key => $res) {
                        $r = (new EbayDealApiInformation())->dealWithApiResponse($verb, $res);
                        if ($r['result'] === false) {
                            $message = $r['message'];
                            continue;
                        }
                        $needUploadImgs[$key]['eps_path'] = $r['data'];
                    }
                }
                if ($uploadOver) {
                    break;
                }
            }
            foreach ($imgs as &$img) {
                if (isset($needUploadImgs[$img['path']])) {
                    $img['eps_path'] = $needUploadImgs[$img['path']]['eps_path'] ?? '';
                }
            }
            if ($i >= 5) {
                return ['result' => false, 'message' => empty($message) ? '图片上传失败' : $message];
            } else {
                return ['result'=>true];
            }
        } catch (\Exception $e) {
            return ['result' => false, 'message' =>  '图片上传失败:'. $e->getMessage()];
        }
    }


    /*******************************************************************************************************************
     *                      Trading API
     ******************************************************************************************************************/

    /**
     *  下架 Item 打包
     * @param array $data
     * @return array
     * @throws Exception
     */
    public static function endItem($data)
    {
        $params = [];
        $params['EndingReason'] = $data['ending_reason']??'' ? $data['ending_reason'] : 'OtherListingError';
        if (!($data['item_id']??0)) {
            throw new Exception('下架时，Item ID必须设置');
        }
        $params['ItemID'] = $data['item_id'];
        return $params;
    }
}
