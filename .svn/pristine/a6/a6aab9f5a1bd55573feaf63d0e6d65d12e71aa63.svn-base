<?php
namespace app\publish\task;
/**
 * 曾绍辉
 * 17-4-18
 * ebay刊登产品
*/

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use service\ebay\EbayApi;
use think\Db;
use think\cache\driver;
use app\publish\queue\EbayQueuer;
use app\publish\task\EbayUploadImgs;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingImage;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\ebay\EbayModelSale;
use app\common\model\ebay\EbayModelStyle;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbaySite;
use app\common\model\GoodsSku; 
use app\common\model\GoodsSkuMap;
use app\common\service\EbayCommonVariables;
use app\goods\service\GoodsPublishMapService;
use think\Exception;
use app\common\exception\TaskException;
use app\publish\task\EbaySetPromotionalSaleListings;

class EbayPublishItem extends AbsTasker
{
    private $token;
    public function getName()
    {
        return "ebay刊登产品";
    }
    
    public function getDesc()
    {
        return "ebay刊登产品";
    }
    
    public function getCreator()
    {
        return "曾绍辉";
    }
    
    public function getParamRule()
    {
        return [];
    }
    
    
    public function execute()
    {
        try {
            self::ebayPublish();
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    public function ebayPublish()
    {
        try {
            set_time_limit(0);
            #获取刊登数据
            $rows = $this->getPublishRows();
            if (!$rows)
                return;#没有待刊登数据,停止执行

            if ($rows['list']['listing_type'] == 1) {#固定价格
                $verb = "AddFixedPriceItem";
            } else if ($rows['list']['listing_type'] == 2) {#拍卖
                $verb = "AddItem";
            }
            $acInfo = Cache::store('EbayAccount')->getTableRecord($rows['list']['account_id']);
            if (!$acInfo) {
                $acInfo = (new EbayAccount())->where(['id' => $rows['list']['account_id']])->find();
            }
            $tokenArr = json_decode($acInfo['token'], true);
            $token = trim($tokenArr[0]) ? $tokenArr[0] : $acInfo['token'];

            $config['userToken'] = $token;
            $config['compatLevel'] = 957;
            $config['siteID'] = $rows['list']['site'];
            $config['verb'] = $verb;
            $config['appMode'] = 0;
            $config['account_id'] = $acInfo['id'];
            $ebayApi = new EbayApi($config);
            $xml = $this->createXml($rows, $token);
            // echo $xml;die;
            $resText = $ebayApi->createHeaders()->__set("requesBody", $xml)->sendHttpRequest2();
            // echo "<pre>";
            // print_r($resText);

            if ($rows['list']['listing_type'] == 1) {#固定价格
                $reponse = isset($resText['AddFixedPriceItemResponse']) ? $resText['AddFixedPriceItemResponse'] : [];
            } else if ($rows['list']['listing_type'] == 2) {#拍卖
                $reponse = isset($resText['AddItemResponse']) ? $resText['AddItemResponse'] : [];
            }
            if (!empty($reponse)) {#处理返回结果
                if ($reponse['Ack'] == "Failure") {#刊登失败
                    $errStr = $reponse['Errors'];
                    if (isset($errStr[0])) {
                        $error = $errStr;
                    } else {
                        $error = array($errStr);
                    }

                    $msg = array();
                    foreach ($error as $val) {
                        if ($val["SeverityCode"] == "Error") {
                            $msg[] = $val["ErrorCode"] . "：" . $val["LongMessage"];
                        }
                    }

                    if (empty($msg)) {
                        $up["message"] = json_encode($errStr);
                    } else {
                        $up["message"] = implode("\n", $msg);
                    }
                    $up["listing_status"] = 4;

                } else if ($reponse['Ack'] == "Success") {#刊登成功
                    $up['listing_status'] = 3;
                    $up['listing_code'] = "Active";
                    $up['item_id'] = $reponse['ItemID'];
                    $up['start_date'] = date("Y-m-d H:i:s");
                    $fee = $this->getFees($reponse['Fees']);
                    $up['insertion_fee'] = $fee['insertion_fee'];
                    $up['listing_fee'] = $fee['listing_fee'];

                    #关联本地产品
                    if ($rows['list']['spu'])
                        GoodsPublishMapService::update(1, $rows['list']['spu'], $rows['list']['account_id']);
                } else if ($reponse['Ack'] == "Warning") {#刊登成功，有警告
                    $errStr = $reponse['Errors'];
                    if (isset($errStr[0])) {
                        $error = $errStr;
                    } else {
                        $error = array($errStr);
                    }
                    $wMsg = array();
                    foreach ($error as $val) {
                        if ($val["SeverityCode"] == "Warning") {
                            $wMsg[] = $val["ErrorCode"] . "：" . $val["LongMessage"];
                        }
                    }
                    if (empty($wMsg)) {
                        $upSet["message"] = json_encode($errStr);
                    } else {
                        $upSet["message"] = implode("\n", $wMsg);
                    }

                    $up['listing_status'] = 3;
                    $up['listing_code'] = "Active";
                    $up['item_id'] = $reponse['ItemID'];
                    $up['start_date'] = time();
                    $fee = $this->getFees($reponse['Fees']);
                    $up['insertion_fee'] = $fee['insertion_fee'];
                    $up['listing_fee'] = $fee['listing_fee'];

                    #关联本地产品
                    if ($rows['list']['spu'])
                        GoodsPublishMapService::update(1, $rows['list']['spu'], $rows['list']['account_id']);
                } else {
                    $up['listing_status'] = 4;
                    $up["message"] = "未知错误";
                }
                //促销设置
                if ($up['listing_status'] == 3) {
                    // 刊登成功后，应用促销设置
                    if (!empty($rows['list']['mod_promotion'])) {//使用了促销模板
                        (new EbaySetPromotionalSaleListings())->SetPromotionalSaleListings($rows['list']['mod_pormotion'],
                                                                                           $rows['list']['account_id'],
                                                                                           $rows['list']['site'],
                                                                                           $up['item_id']);
                    }
                }
                // #保存记录刊登数据
                (new EbayListing())->where(['id' => $rows['list']['id']])->update($up);
            }
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    public function createXml($data,$token="")
    {
        try {
            if ($token == "")
                $token = $this->token;
            $list = $data['list'];#listing基础信息
            $set = $data['set'];#listing基础设置
            $Variables = (new EbayCommonVariables())->__get('variables');
            $xml = "<Item>\n";
            $xml .= "<OutOfStockControl>True</OutOfStockControl>\n";
            $xml .= "<ApplicationData>{$set['application_data']}</ApplicationData>\n";#应用名称
            $xml .= "<CategoryMappingAllowed>true</CategoryMappingAllowed>\n";
            $xml .= "<Country>{$list['country']}</Country>\n";
            if ($list['autopay']) {#是否要求立即付款
                $xml .= "<AutoPay>True</AutoPay>\n";
            }
            #描述
            $description = $this->replaceDescription($set['description'], $data['images'], $list['title'],
                                                     $list['mod_style'], $list['mod_sale']);
            $xml .= "<Description><![CDATA[" . $description . "]]></Description>\n";#描述
            #$xml.="<Description><![CDATA[".$set['description']."]]></Description>\n";#描述
            $xml .= "<DispatchTimeMax>{$list['dispatch_max_time']}</DispatchTimeMax>\n";#发货处理时间
            $xml .= "<ListingDuration>{$Variables['listingDuration'][$list['listing_duration']]}</ListingDuration>\n";#上架时间
            $xml .= "<ListingType>{$Variables['listingType'][$list['listing_type']]}</ListingType>\n";#销售方式
            if (trim($set['postal_code'])) {
                $xml .= "<PostalCode>{$set['postal_code']}</PostalCode>\n";#邮编
            }

            #是否自提 0否1是
            if ($set['local_pickup']) {
                if (intval($list['site']) == 0) {
                    $xml .= "<PickupInStoreDetails>";
                    $xml .= "<EligibleForPickupInStore>true</EligibleForPickupInStore>";#实体店提货 适用于美国站点
                    $xml .= "</PickupInStoreDetails>";
                } else if (intval($list['site']) == 3 || intval($list['site']) == 15 || intval($list['site']) == 77) {
                    $xml .= "<PickupInStoreDetails>";
                    $xml .= "<EligibleForPickupDropOff>true</EligibleForPickupDropOff>";#click & collect自提 适用于英国，澳洲，德国
                    $xml .= "</PickupInStoreDetails>";
                }
            }

            $xml .= "<Location>{$list['location']}</Location>\n";#发货地址
            $pMthods = json_decode($set['payment_method'], true);
            if (is_array($pMthods)) {
                foreach ($pMthods as $pm) {
                    $xml .= "<PaymentMethods>{$pm}</PaymentMethods>\n";#付款方式
                }
            } else {
                $xml .= "<PaymentMethods>{$pMthods}</PaymentMethods>\n";#付款方式
            }

            $xml .= "<PayPalEmailAddress>{$list['paypal_emailaddress']}</PayPalEmailAddress>\n";#付款地址
            if ($set["payment_instructions"]) {#付款说明
                $xml .= "<PaymentInstructions>{$set['payment_instructions']}</PaymentInstructions>\n";
            }

            if (!$list['variation']) {#非多属性产品
                $xml .= "<Quantity>{$list['quantity']}</Quantity>\n";#数量
            }

            $xml .= "<StartPrice>{$list['start_price']}</StartPrice>\n";#起始价
            $xml .= "<SKU>{$list['listing_sku']}</SKU>\n";
            $siteInfo = (new EbaySite())->where(['siteid' => $list['site']])->find();
            $xml .= "<Site>{$siteInfo['country']}</Site>\n";#站点
            $xml .= "<Currency>{$list['currency']}</Currency>\n";#币种
            $xml .= "<Title><![CDATA[{$list['title']}]]></Title>\n";#标题
            if ($list['sub_title']) {#副标题
                $xml .= "<SubTitle>{$list['sub_title']}</SubTitle>\n";
            }
            $xml .= "<PrimaryCategory><CategoryID>{$list['primary_categoryid']}</CategoryID></PrimaryCategory>\n";#类目一
            if ($list['second_categoryid']) {
                $xml .= "<SecondaryCategory>{$list['second_categoryid']}</SecondaryCategory>\n";#类目二
            }

            #买家还价
            if ($list['best_offer']) {
                $xml .= "<ListingDetails>\n";
                $xml .= "<BestOfferAutoAcceptPrice>{$set['auto_accept_price']}</BestOfferAutoAcceptPrice>\n";
                $xml .= "<MinimumBestOfferPrice>{$set['minimum_accept_price']}</MinimumBestOfferPrice>\n";
                #<LocalListingDistance> string </LocalListingDistance>
                $xml .= "</ListingDetails>\n";
            }

            #增值税
            if (intval($list['vat_percent'])) {
                $xml .= "<VATDetails>\n";
                #$xml.="<BusinessSeller></BusinessSeller>";
                #$xml.="<RestrictedToBusiness></RestrictedToBusiness>";
                $varPercent = $list['vat_percent'] / 100;
                $xml .= "<VATPercent>{$varPercent}</VATPercent>\n";#百分比
                $xml .= "</VATDetails>\n";
            }

            #标题加粗
            if (intval($list['listing_enhancement'])) {
                $xml .= "<ListingEnhancement>BoldTitle</ListingEnhancement>";
            }

            #店铺
            if ($list['store_category_id']) {
                $xml .= "<Storefront>\n";
                $xml .= "<StoreCategoryID>{$list['store_category_id']}</StoreCategoryID>\n";#自定义类目一
                if ($list['store_category2_id']) {
                    $xml .= "<StoreCategory2ID>{$list['store_category2_id']}</StoreCategory2ID>\n";#自定义类目二
                }
                $xml .= "</Storefront>\n";
            }

            #物品描述
            if ($set['condition_id']) {
                $xml .= "<ConditionID>{$set['condition_id']}</ConditionID>\n";#物品条件
            }
            if (trim($set['condition_description'])) {
                $xml .= "<ConditionDescription>{$set['condition_description']}</ConditionDescription>\n";#物品描述
            }

            #图片
            $images = $data['images'];
            if (!empty($images)) {
                $xml .= "<PictureDetails>\n";
                foreach ($images as $kimg => $vimg) {
                    if (intval($vimg['main']) == 1) {
                        $xml .= "<PictureURL>{$vimg['eps_path']}</PictureURL>";
                    }
                }
                #橱窗展示None不使用,Gallery,Featured
                if ($list['picture_gallery']) {
                    $xml .= "<GalleryType>{$Variables['pictureGallery'][$list['picture_gallery']]}</GalleryType>\n";
                }
                $xml .= "</PictureDetails>\n";
            }

            #买家限制
            $buyerRequirmentDetails = json_decode($set['buyer_requirment_details'], true);
            $buyerRequirmentDetails = $buyerRequirmentDetails[0]??$buyerRequirmentDetails;
            if ($list['disable_buyer']) {
                $xml .= "<BuyerRequirementDetails>\n";
                if ($buyerRequirmentDetails['link_paypal']) {#paypal限制 0否 1是
                    $xml .= "<LinkedPayPalAccount>true</LinkedPayPalAccount>\n";
                }

                if ($buyerRequirmentDetails['violations']) {#违反政策相关
                    $xml .= "<MaximumBuyerPolicyViolations>\n";
                    $xml .= "<Count>{$buyerRequirmentDetails['violations_count']}</Count>\n";
                    $xml .= "<Period>{$buyerRequirmentDetails['violations_period']}</Period>\n";
                    $xml .= "</MaximumBuyerPolicyViolations>\n";
                }

                if ($buyerRequirmentDetails['requirements']) {#次数限制
                    $xml .= "<MaximumItemRequirements>\n";
                    $xml .= "<MaximumItemCount>{$buyerRequirmentDetails['requirements_max_count']}</MaximumItemCount>\n";
                    if ($buyerRequirmentDetails['minimum_feedback']) {#评分限制
                        $xml .= "<MinimumFeedbackScore>{$buyerRequirmentDetails['minimum_feedback_score']}</MinimumFeedbackScore>\n";
                    }
                    $xml .= "</MaximumItemRequirements>\n";
                }

                if ($buyerRequirmentDetails['strikes']) {#未付款限制
                    $xml .= "<MaximumUnpaidItemStrikesInfo>\n";
                    $xml .= "<Count>{$buyerRequirmentDetails['strikes_count']}</Count>\n";
                    $xml .= "<Period>{$buyerRequirmentDetails['strikes_period']}</Period>\n";
                    $xml .= "</MaximumUnpaidItemStrikesInfo>\n";
                }

                if ($buyerRequirmentDetails['credit']) {#信用限制
                    $xml .= "<MinimumFeedbackScore>{$buyerRequirmentDetails['requirements_feedback_score']}</MinimumFeedbackScore>\n";
                }

                if ($buyerRequirmentDetails['registration']) {#限制运送范围
                    $xml .= "<ShipToRegistrationCountry>true</ShipToRegistrationCountry>\n";
                }
                $xml .= "</BuyerRequirementDetails>\n";
            }

            #是否为私有物品
            if ($list['private_listing']) {
                $xml .= "<PrivateListing>true</PrivateListing>\n";
            }

            #是否支持退货:1:ReturnsAccepted,0:ReturnsNotAccepted
            if (trim($set['return_policy'])) {
                $xml .= "<ReturnPolicy>\n";
                #支持退货
                $xml .= "<ReturnsAcceptedOption>ReturnsAccepted</ReturnsAcceptedOption>\n";
                if ($set['return_description']) {#退货说明
                    $xml .= "<Description>{$set['return_description']}</Description>\n";
                }
                if ($set['extended_holiday']) {#节假日延期
                    $xml .= "<ExtendedHolidayReturns>true</ExtendedHolidayReturns>\n";
                }
                if ($set['return_type']) {#退货方式
                    $xml .= "<RefundOption>{$set['return_type']}</RefundOption>\n";
                }
                if ($list['return_time']) {#接受退货周期:(1 Days_14,2 Days_30,3 Days_60,4 Months_1)
                    $xml .= "<ReturnsWithinOption>{$Variables['returnTime'][$list['return_time']]}</ReturnsWithinOption>\n";
                }
                $returnOption = intval($set['return_shipping_option']) ? $set['return_shipping_option'] : 1;
                $xml .= "<ShippingCostPaidByOption>{$Variables['returnShippingOption'][$returnOption]}</ShippingCostPaidByOption>\n";
                if ($set['restocking_fee_code']) {#折旧费:NoRestockingFee,Percent_10,Percent_15,Percent_20
                    $xml .= "<RestockingFeeValueOption>{$set['restocking_fee_code']}</RestockingFeeValueOption>\n";
                }
                $xml .= "</ReturnPolicy>\n";
            } else {
                $xml .= "<ReturnPolicy>\n";
                $xml .= "<ReturnsAcceptedOption>ReturnsNotAccepted</ReturnsAcceptedOption>\n";
                $xml .= "</ReturnPolicy>\n";
            }

            #汽车兼容信息
            $compatibilityXML = "";
            if (intval($set['compatibility_count']) > 0) {
                $compatibility = json_decode($set['compatibility'], true);
                $compatibilityXML .= "<ItemCompatibilityList>\n";
                $compatibilityXML .= "<Type>NameValue</Type>\n";
                foreach ($compatibility as $comp) {
                    $compatibilityXML .= "<Compatibility>\n";
                    $compatibilityXML .= "<CompatibilityNotes>" . $comp['compatibility_notes '] . "</CompatibilityNotes>\n";
                    foreach ($comp['name_value_list'] as $NameValueList) {
                        $compatibilityXML .= "<NameValueList>\n";
                        $compatibilityXML .= "<Name>" . ucfirst($NameValueList['name']) . "</Name>\n";
                        if ($NameValueList['name'] == "trim" || $NameValueList['name'] == "engine") {
                            $compatibilityXML .= "<Value><![CDATA[" . $NameValueList['value'] . "]]></Value>\n";
                        } else {
                            $compatibilityXML .= "<Value>" . $NameValueList['value'] . "</Value>\n";
                        }
                        $compatibilityXML .= "</NameValueList>\n";
                    }
                    $compatibilityXML .= "</Compatibility>\n";
                }
                $compatibilityXML .= "</ItemCompatibilityList>\n";
            }
            $xml .= $compatibilityXML;
            #物流模块
            $xml .= "<ShippingDetails>\n";
            #不送达地区
            if ($set['exclude_location']) {
                $tempEx = json_decode($set['exclude_location'], true);
                $exShip = is_array($tempEx) ? $tempEx : json_decode($tempEx, true);
                foreach ($exShip as $kes => $ves) {
                    $xml .= "<ExcludeShipToLocation>{$ves}</ExcludeShipToLocation>\n";
                }
            }

            #消费税
            if ($list['sales_tax']) {
                $salePercent = intval($list['sales_tax']) / 100;
                $xml .= "<SalesTax>\n";
                $xml .= "<SalesTaxPercent>{$salePercent}</SalesTaxPercent>\n";#百分比
                $xml .= "<SalesTaxState>{$list['sales_tax_state']}</SalesTaxState>\n";
                $xml .= "<ShippingIncludedInTax>{$list['shipping_tax']}</ShippingIncludedInTax>\n";
                $xml .= "</SalesTax>\n";
            }

            #运输
            $tranin = json_decode($set['shipping'], true);#国内运输
            foreach ($tranin as $kai => $vai) {
                $xml .= "<ShippingServiceOptions>\n";
                $xml .= "<ShippingService>{$vai["shipping_service"]}</ShippingService>\n";
                if (is_numeric($vai['shipping_service_cost'])) {#不免运费
                    #续件运费
                    $xml .= "<ShippingServiceAdditionalCost>{$vai["shipping_service_additional_cost"]}</ShippingServiceAdditionalCost>\n";
                    #首件运费
                    $xml .= "<ShippingServiceCost>{$vai["shipping_service_cost"]}</ShippingServiceCost>\n";
                } else {#免运费
                    $xml .= "<FreeShipping>true</FreeShipping>\n";
                    #首件运费
                    $xml .= "<ShippingServiceCost>{$vai["shipping_service_cost"]}</ShippingServiceCost>\n";
                    #续件运费
                    $xml .= "<ShippingServiceAdditionalCost>{$vai["shipping_service_additional_cost"]}</ShippingServiceAdditionalCost>\n";
                }
                if (intval($vai['extra_cost'])) {
                    $xml .= "<ShippingSurcharge>{$vai['extra_cost']}</ShippingSurcharge>";
                }
                $xml .= "<ShippingServicePriority>{$kai}</ShippingServicePriority>\n";#排序方式
                $xml .= "</ShippingServiceOptions>\n";
            }

            $tran = json_decode($set['international_shipping'], true);#国际运输
            foreach ($tran as $ktr => $vtr) {
                $xml .= "<InternationalShippingServiceOption>\n";
                #运输方式
                $xml .= "<ShippingService>{$vtr['shipping_service']}</ShippingService>\n";
                #续件运费
                $xml .= "<ShippingServiceAdditionalCost>" . $vtr['shipping_service_additional_cost'] . "</ShippingServiceAdditionalCost>\n";
                #首件运费
                $xml .= "<ShippingServiceCost>" . $vtr['shipping_service_cost'] . "</ShippingServiceCost>\n";
                #运输方式排列顺序
                $xml .= "<ShippingServicePriority>{$ktr}</ShippingServicePriority>\n";
                #送达地区
                if ($vtr['shiptolocation']) {
                    $ships = is_array($vtr['shiptolocation']) ? $vtr['shiptolocation'] : [$vtr['shiptolocation']];
                    foreach ($ships as $vsh) {
                        $xml .= "<ShipToLocation>{$vsh}</ShipToLocation>\n";
                    }
                }
                $xml .= "</InternationalShippingServiceOption>\n";
            }
            $xml .= "</ShippingDetails>\n";

            #goods信息
            $xml .= "<ProductListingDetails>\n";
            $xml .= "<UPC>{$set["upc"]}</UPC>\n";
            $xml .= "<EAN>{$set["ean"]}</EAN>\n";
            $xml .= "<ISBN>{$set["isbn"]}</ISBN>\n";
            $xml .= "<BrandMPN>\n";
            $xml .= "<Brand>{$set["brand"]}</Brand>\n";
            $xml .= "<MPN>{$set["mpn"]}</MPN>\n";
            $xml .= "</BrandMPN>\n";
            $xml .= "</ProductListingDetails>\n";

            #类目属性
            $specifics = json_decode($set['specifics'], true);
            $vars = $data['vars'];
            if ($list['variation']) {
                $varkey = array_keys(json_decode($vars[0]["variation"], true));
                foreach ($specifics as $ktm1 => $vtm1) {
                    foreach ($varkey as $ktm2 => $vtm2) {
                        if ($vtm1['attr_name'] == $vtm2) {
                            unset($specifics[$ktm1]);
                        }
                    }
                }
            }
            if (count($specifics)) {
                $ItemSpecifics = null;
                foreach ($specifics as $sv) {
                    $ItemSpecifics .= "<NameValueList>\n";
                    $ItemSpecifics .= "<Name><![CDATA[{$sv['attr_name']}]]></Name>\n";
                    #$ItemSpecifics.="<Name>{$sv['attr_name']}</Name>\n";
                    $attrValue = $sv['attr_value'];
                    if (is_array($attrValue)) {
                        foreach ($attrValue as $atrVal) {
                            $ItemSpecifics .= "<Value><![CDATA[{$atrVal}]]></Value>\n";
                            #$ItemSpecifics.="<Value>{$atrVal}</Value>\n";
                        }
                    } else {
                        $ItemSpecifics .= "<Value><![CDATA[{$sv['attr_value']}]]></Value>\n";
                        #$ItemSpecifics.="<Value>{$sv['attr_value']}</Value>\n";
                    }
                    $ItemSpecifics .= "</NameValueList>\n";
                }
                if ($ItemSpecifics) {
                    $xml .= "<ItemSpecifics>\n";
                    $xml .= $ItemSpecifics;
                    $xml .= "</ItemSpecifics>\n";
                }
            }

            #多属性子产品
            $images = $data['images'];
            if ($list['variation']) {
                $xml .= "<Variations>\n";
                $xml .= "<Pictures>\n";
                $keys = array_keys(json_decode($vars[0]["variation"], true));
                $varImage = empty(trim($set['variation_image'])) ? $keys[0] : $set['variation_image'];
                $atrVals = $data['atr_values'];
                $xml .= "<VariationSpecificName><![CDATA[{$varImage}]]></VariationSpecificName>\n";
                foreach ($atrVals as $atrVal) {
                    $xml .= "<VariationSpecificPictureSet>\n";
                    $xml .= "<VariationSpecificValue><![CDATA[{$atrVal}]]></VariationSpecificValue>\n";
                    foreach ($images as $img) {
                        if ($img['main_de'] == 1 && $img['value'] == $atrVal) {
                            $xml .= "<PictureURL>{$img['eps_path']}</PictureURL>\n";
                        }
                    }
                    $xml .= "</VariationSpecificPictureSet>\n";
                }
                $xml .= "</Pictures>\n";

                $vArray = array();
                foreach ($vars as $sku) {
                    $xml .= "<Variation>\n";
                    $xml .= "<Quantity>{$sku['v_qty']}</Quantity>\n";
                    $xml .= "<SKU>{$sku['channel_map_code']}</SKU>\n";
                    $xml .= "<StartPrice>{$sku['v_price']}</StartPrice>\n";

                    $xml .= "<VariationSpecifics>\n";
                    foreach (json_decode($sku["variation"], true) as $nk => $nv) {
                        $xml .= "<NameValueList>\n";
                        $xml .= "<Name><![CDATA[{$nk}]]></Name>\n";
                        $xml .= "<Value><![CDATA[{$nv}]]></Value>\n";
                        $xml .= "</NameValueList>\n";
                        $vArray[$nk][$nv] = $nv;
                    }
                    $xml .= "</VariationSpecifics>\n";

                    $xml .= "<VariationProductListingDetails>\n";
                    $xml .= "<UPC>Does not apply</UPC>\n";
                    $xml .= "<EAN>Does not apply</EAN>\n";
                    $xml .= "<ISBN>Does not apply</ISBN>\n";
                    $xml .= "</VariationProductListingDetails>\n";
                    $xml .= "</Variation>\n";
                }

                $xml .= "<VariationSpecificsSet>\n";
                foreach ($vArray as $sk => $sv) {
                    $xml .= "<NameValueList>\n";
                    $xml .= "<Name><![CDATA[{$sk}]]></Name>\n";
                    foreach ($sv as $v) {
                        $xml .= "<Value><![CDATA[{$v}]]></Value>\n";
                    }
                    $xml .= "</NameValueList>\n";
                }
                $xml .= "</VariationSpecificsSet>\n";
                $xml .= "</Variations>\n";
            }
            $xml .= "</Item>\n";
            #根据销售方式创建表头
            #if($list['listing_type']=="FixedPriceItem"){#固定价格
            if ($list['listing_type'] == 1) {#固定价格
                $requestBody = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
                $requestBody .= '<AddFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">' . "\n";
                $requestBody .= '<RequesterCredentials><eBayAuthToken>' . $token . '</eBayAuthToken></RequesterCredentials>' . "\n";#token
                $requestBody .= '<ErrorLanguage>zh_CN</ErrorLanguage>' . "\n";
                $requestBody .= '<WarningLevel>High</WarningLevel>' . "\n";
                $requestBody .= $xml;
                $requestBody .= "</AddFixedPriceItemRequest>" . "\n";
                #}else if($list['listing_type']=="Chinese"){#拍卖
            } else if ($list['listing_type'] == 2) {#拍卖
                $requestBody = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
                $requestBody .= '<AddItemRequest  xmlns="urn:ebay:apis:eBLBaseComponents">' . "\n";
                $requestBody .= '<RequesterCredentials><eBayAuthToken>' . $token . '</eBayAuthToken></RequesterCredentials>' . "\n";#token
                $requestBody .= '<ErrorLanguage>zh_CN</ErrorLanguage>' . "\n";
                $requestBody .= '<WarningLevel>High</WarningLevel>' . "\n";
                $requestBody .= $xml;
                $requestBody .= "</AddItemRequest>" . "\n";
            }
            return $requestBody;
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    #获取刊登数据
    public function getPublishRows($listingId=0)
    {
        try {
            $list = [];
            if ($listingId) {
                $list = (new EbayListing())->where(['id' => $listingId])->find();
            }
            if ($list) {
                $listId = $list['id'];
                $rows['set'] = (new EbayListingSetting())->where(['id' => $listId])->find();#基础设置
                (new EbayUploadImgs())->uploadImg($listingId);
                $rows['images'] = (new EbayListingImage())->where(['listing_id' => $listId])->select();#图片
                $rows['vars'] = (new EbayListingVariation())->where(['listing_id' => $listId])->select();#多属性子产品
                $atrValuesTemp = (new EbayListingImage())->distinct(true)->field('value')->where(['listing_id' => $listId])->select();#图片
                $atrValues = [];
                foreach ($atrValuesTemp as $atrVal) {
                    $atrValues[] = $atrVal['value'];
                }
                $rows['atr_values'] = array_filter($atrValues);
                $rows['list'] = $list;
                return $rows;
            } else {
                return array();
            }
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    #记录刊登费用
    public function getFees($fees){
        foreach($fees['Fee'] as $fee){
            if($fee['Name'] == "ListingFee"){#上市费用
                $listingFee = 0;
                $listingFee = $fee['Fee'];
                if(isset($fee['PromotionalDiscount']) && !$listingFee){
                    $listingFee += $fee['PromotionalDiscount'];
                }
                $pubFee['listing_fee'] = $listingFee;
            }
            if($fee['Name'] == "InsertionFee"){#刊登费用
                $insertFee = 0;
                $insertFee = $fee['Fee'];
                if(isset($fee['PromotionalDiscount']) && !$insertFee){
                    $insertFee += $fee['PromotionalDiscount'];
                }
                $pubFee['insertion_fee'] = $insertFee;
            }
        }
        return $pubFee;
    }

    public function createApi($accountId,$verb,$site)
    {
        try {
            $acInfo = Cache::store('EbayAccount')->getTableRecord($accountId);
            $tokenArr = json_decode($acInfo['token'], true);
            $token = trim($tokenArr[0]) ? $tokenArr[0] : $acInfo['token'];
            $config['devID'] = $acInfo['dev_id'];
            $config['appID'] = $acInfo['app_id'];
            $config['certID'] = $acInfo['cert_id'];
            $config['userToken'] = $token;
            $config['compatLevel'] = 957;
            $config['siteID'] = $site;
            $config['verb'] = $verb;
            $config['appMode'] = 0;
            $config['account_id'] = $acInfo['id'];
            $this->token = $token;
            return new EbayApi($config);
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    #处理返回结果
    public function processingReponse(&$reponse,&$rows,&$xml)
    {
        if(!empty($reponse)){
            $upSet['send_content'] = $xml;
            if($reponse['Ack'] == "Failure"){#刊登失败
                $errStr = $reponse['Errors'];
                if(isset($errStr[0])){
                    $error=$errStr;
                }else{
                    $error=array($errStr);
                } 
                $msg=array();
                foreach($error as $val){
                    if($val["SeverityCode"]=="Error"){
                        $msg[]=$val["ErrorCode"]."：".$val["LongMessage"];
                    }
                }
                if(empty($msg)){
                    $upSet["message"]=json_encode($errStr);
                }else{
                    $upSet["message"]=implode("\n",$msg);
                }
                $up["listing_status"] = 4;
            }else if($reponse['Ack'] == "Success"){#刊登成功
                $up['listing_status'] = 3;
                $up['item_id'] = $reponse['ItemID'];
                $up['start_date'] = time();
                $fee = $this->getFees($reponse['Fees']);
                $up['insertion_fee'] = $fee['insertion_fee'];
                $up['listing_fee'] = $fee['listing_fee'];

                #关联本地产品
                #$this->relateCard($rows);
            }else if($reponse['Ack'] == "Warning"){#刊登成功,有告警
                $errStr = $reponse['Errors'];
                if(isset($errStr[0])){
                    $error=$errStr;
                }else{
                    $error=array($errStr);
                }
                $wMsg=array();
                foreach($error as $val){
                    if($val["SeverityCode"]=="Warning"){
                        $wMsg[]=$val["ErrorCode"]."：".$val["LongMessage"];
                    }
                }
                if(empty($wMsg)){
                    $upSet["message"]=json_encode($errStr);
                }else{
                    $upSet["message"]=implode("\n",$wMsg);
                }

                $up['listing_status'] = 3;
                $up['item_id'] = $reponse['ItemID'];
                $up['start_date'] = time();
                $fee = $this->getFees($reponse['Fees']);
                #var_dump($fee);
                $up['insertion_fee'] = $fee['insertion_fee'];
                $up['listing_fee'] = $fee['listing_fee'];

                #关联本地产品
                #$this->relateCard($rows);
            }else{#未知错误
                $up['listing_status']=4;
                $upSet["message"]="未知错误";
            }
            return ['list'=>$up,'set'=>$upSet];
        }
    }

    #关联本地产品
    public function relateCard($rows)
    {
        if($rows['list']['local_sku']){
            $sku = (new GoodsSku())
            ->where(['sku'=>$rows['list']['local_sku']])
            ->find();
        }else{
            $vars = isset($rows['vars'][0])?$rows['vars']:[];
            if($vars){
                $sku = (new GoodsSku())->where(['id'=>$vars[0]['sku_id']])->find();
            }
        }

        if($sku){
            $skuCode[$sku['id']]['sku_id']=$sku['id'];
            $skuCode[$sku['id']]['quantity']=1;
            $skuCode[$sku['id']]['sku_code']=$sku['sku'];

            $wh['sku_id']=$sku['id'];
            $wh['sku_code']=$sku['sku'];
            $wh['channel_id'] = 1;
            $wh['account_id'] = $rows['list']['account_id'];
            $wh['channel_sku'] = $rows['list']['listing_sku'];

            $res = (new GoodsSkuMap())->where($wh)->find();
            if(!$res){
                $map=$wh;
                $map['quantity']=1;
                $map['sku_code_quantity'] = json_encode($skuCode);
                $map['creator_id'] = $rows['list']['user_id'];
                $map['create_time'] = time();
                (new GoodsSkuMap())->insertGetId($map);
            }
        }
    }
    
    #替换模板
    public function replaceDescription($desc,$imgs,$title,$sid,$said)
    {   
        $des="";
        if($sid!=0){
            $style = (new EbayModelStyle())->where(['id'=>$sid])->find()->toArray();
        }else{
            $style = [];
        }

        if($said!=0){
            $sale = (new EbayModelSale())->where(['id'=>$said])->find()->toArray();
        }else{
            $sale = [];
        }
        $imgPath = [];
        foreach ($imgs as $k => $img) {
            $baseUrl = empty($img['base_url']) ? 'https://img.rondaful.com/' : $img['base_url'];
            $file = empty($img['path']) ? $img['thumb'] : $img['path'];
            $imgPath[$k] = $baseUrl.$file;
        }
        $desc = preg_replace_callback('/http(s)?:\/\/[\w\.\/:_]*\.((jpg)|(gif)|(bmp))/', function($matches) use ($imgs, $imgPath){
            foreach ($imgPath as $k => $v) {
                if ($v == $matches[0]) {
                    return $imgs[$k]['id'];
                }
            }
        }, $desc);
        if($style){
            #替换描述内容
            $des = str_replace('[DESCRIBE]',$desc, $style['style_detail']);
            #替换标题
            $des = str_replace('[TITLE]',$title, $des);
        }

        if($sale){
            $payment = trim($sale['payment'])?$sale['payment']:"";
            $Shipping = trim($sale['delivery_detail'])?$sale['delivery_detail']:"";
            $Terms = trim($sale['terms_of_sales'])?$sale['terms_of_sales']:"";
            $About = trim($sale['about_us'])?$sale['about_us']:"";
            $Contace = trim($sale['contact_us'])?$sale['contact_us']:"";
            $des = str_replace('[Payment]',$payment, $des);#付款
            $des = str_replace('[Shipping]',$Shipping, $des);#提货
            $des = str_replace('[Terms of Sale]',$Terms, $des);#销售条款
            $des = str_replace('[About Me]',$About, $des);#关于我们
            $des = str_replace('[Contace Us]',$Contace, $des);#联系我们
        }

        $imgsStr = "";
        $i=1;
        $k=1;
        foreach($imgs as $img){
            if($img['detail']==1){
                $imgsStr.="<img src={$img['eps_path']}><br>";
                $imgDetail = "<img src={$img['eps_path']}>";
                $des = str_replace("[TIMG".$k."]",$imgDetail,$des);
                $k++;
            }
            if($img['main']==1){
                $imgsStrNum ="<img src={$img['eps_path']}>";
                $des = str_replace("[IMG".$i."]",$imgsStrNum,$des);
                $i++;
            }
        }
        while($i<=20 || $k<=20){
            if($i<=20){
                $des = str_replace("[IMG".$i++."]","",$des);
            }
            if($k<=20){
                $des = str_replace("[TIMG".$k++."]","",$des);
            }
        }
        $des = str_replace("[PICTURE]",$imgsStr,$des);
        return trim($des)?$des:$desc;
    }

}
