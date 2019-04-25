<?php
namespace app\publish\task;
/**
 * 曾绍辉
 * 17-4-18
 * ebay刊登产品(固定价格)
*/

use app\index\service\AbsTasker;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayCategory;
use app\common\model\ebay\EbaySite;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingImage;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbayListingSpecifics;
use app\common\model\ebay\EbayListingTransport;
use app\common\model\ebay\EbayListingTransportIn;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\Channel;
use app\common\model\Goods;
use app\common\model\GoodsSkuMap;
use app\common\model\GoodsSkuAlias;
use app\common\model\GoodsSku;
use app\common\model\Currency;
use app\common\cache\Cache;
use service\ebay\EbayApi;
use think\Db;
use think\cache\driver;

class EbayAddFixedPriceItem extends AbsTasker
{
	public function getName()
    {
        return "ebay刊登产品(固定价格)";
    }
    
    public function getDesc()
    {
        return "ebay刊登产品(固定价格)";
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
        self::ebayPublish();      
    }

    public function ebayPublish(){
    	set_time_limit(0); 
    	$verb = "AddFixedPriceItem";
    	$ebayListing = new EbayListing();
    	$account = new EbayAccount();
    	$list = $ebayListing->getPublishRows();
    	$acInfo = $account->get(array("id"=>$list->account_id));
    	$tokenArr = json_decode($acInfo->token);
	    $token = $tokenArr[0];
    	$ebayApi = new EbayApi($token,$list->site,$acInfo->dev_id,$acInfo->app_id,$acInfo->cert_id,$verb);
    	$rows = $this->publishRows($list);
    	$xml = $this->createXml($rows);
    	
    	// $resText = $ebayApi->createHeaders()->__set("requesBody",$xml)->sendHttpRequest();
    	// echo "<pre>";
    	// print_r($resText);
    }

    public function publishRows($list){

    	$ebaySet = new EbayListingSetting();	
    	$ebayImg = new EbayListingImage();
    	$ebaySpec = new EbayListingSpecifics();
    	$transSportIn = new EbayListingTransportIn();#国内运输
    	$transSport = new EbayListingTransport();#国际运输
    	$ebayVar = new EbayListingVariation();#多属性产品
    	$account = new EbayAccount();

    	if(!empty($list)){
    		$result['list'] = $list;
	    	$result['set'] = $ebaySet->get(array("listing_id"=>$list->id));
	    	$result['imgs'] = $ebayImg->order("sort")->get(array("listing_id"=>$list->id));
	    	$result['specs'] = $ebaySpec->get(array("listing_id"=>$list->id));
	    	$result['transIn'] = $transSportIn->get(array("listing_id"=>$list->id));
	    	$result['trans'] = $transSport->get(array("listing_id"=>$list->id));
	    	$result['vars'] = $ebayVar->get(array("listing_id"=>$list->id));
	    	$result['account'] = $account->get(array("id"=>$list->account_id));
	    	return $result;
    	}else{
    		return array();
    	}
    }

    public function createXml($rows){#固定价格
    	$acInfo = $rows['account'];
    	$tokenArr = json_decode($acInfo->token);
    	$token = $tokenArr[0];

    	$list = $rows['list'];
    	$set = $rows['set'];
    	$imgs = $rows['imgs'];
    	$specs = $rows['specs'];
    	$transIn = $rows['transIn'];
    	$trans = $rows['trans'];
    	$vars = $rows['vars'];

    	$xml ="<Item>\n";
    	$xml.="<OutOfStockControl>True</OutOfStockControl>\n";
    	$xml.="<ApplicationData>{$list['id']}</ApplicationData>\n";
   	 	$xml.="<CategoryMappingAllowed>true</CategoryMappingAllowed>\n";
    	$xml.="<Country>{$set['country']}</Country>\n";

    	#描述
    	$xml.="<Description><![CDATA[".$list['description']."]]></Description>\n";#描述
    	$xml.="<DispatchTimeMax>{$set['dispatch_time_max']}</DispatchTimeMax>\n";#发货处理时间
    	$xml.="<ListingDuration>{$set['listing_duration']}</ListingDuration>\n";#上架时间
    	$xml.="<ListingType>{$list['listing_type']}</ListingType>\n";#销售方式
    	if($set['autopay']){#是否要求立即付款
    		$xml.="<AutoPay>True</AutoPay>\n";
    	}

    	$xml.="<Location>{$set['location']}</Location>\n";#发货地址
    	$xml.="<PaymentMethods>{$set['payment_methods']}</PaymentMethods>\n";#付款方式
    	$xml.="<PayPalEmailAddress>{$set['paypal_emailaddress']}</PayPalEmailAddress>\n";#付款地址
    	if($set["payment_instructions"]){#付款说明
	        $xml.="<PaymentInstructions>{$set['payment_instructions']}</PaymentInstructions>\n";
	    }

	    if(!$list['varions']){#多属性产品
	    	$xml.="<Quantity>{$list['quantity']}</Quantity>\n";#数量
	    }
	    $xml.="<StartPrice>{$list['start_price']}</StartPrice>\n";#起始价
	    $xml.="<SKU>{$list['listing_sku']}</SKU>\n";
	    $xml.="<Site>{$list['site_code']}</Site>\n";#站点
	    $xml.="<Currency>{$list['currency']}</Currency>";#币种
	    $xml.="<Title><![CDATA[{$list['title']}]]></Title>\n";#标题
	    $xml.="<PrimaryCategory><CategoryID>{$list['Primary_categoryid']}</CategoryID></PrimaryCategory>\n";#类目

	    #产品图片
	    $xml.="<PictureDetails>\n";
	    foreach($imgs as $kimg => $vimg){
	    	if($vimg['main_de']==0){
	    		$xml.="<PictureURL>{$img['eps_path']}</PictureURL>\n";
	    	}
	    }
    	$xml.="</PictureDetails>\n";

    	#屏蔽买家
    	if($set["refuse_buyer"]){
	        $xml.="<BuyerRequirementDetails>\n";
	        $xml.="<LinkedPayPalAccount>".($set["r_linked_paypal_account"]?"True":"False")."</LinkedPayPalAccount>\n";
	        if($set["r_maximum_unpaid_item_strikes_info"]){
	            $xml.="<MaximumUnpaidItemStrikesInfo>\n";
	            $xml.="<Count>{$set['r_maximum_unpaid_item_strikes_info_count']}</Count>\n";
	            $xml.="<Period>{$set['r_maximum_unpaid_item_strikes_info_period']}</Period>\n";
	            $xml.="</MaximumUnpaidItemStrikesInfo>\n";
	        }

	        if($set["r_maximum_buyer_policy_violations"]){
	            $xml.="<MaximumBuyerPolicyViolations>\n";
	            $xml.="<Count>{$set['r_maximum_buyer_policy_violations_count']}</Count>\n";
	            $xml.="<Period>{$set['r_maximum_buyer_policy_violations_period']}</Period>\n";
	            $xml.="</MaximumBuyerPolicyViolations>\n";
	        }

	        if($set["r_maximum_item_requirements"]){
	            $xml.="<MaximumItemRequirements>\n";
	            $xml.="<MaximumItemCount>{$set['r_maximum_item_requirements_maximum_item_count']}</MaximumItemCount>\n";
	            $xml.="<MinimumFeedbackScore>{$set['r_maximum_item_requirements_minimum_feedback_score']}</MinimumFeedbackScore>\n";
	            $xml.="</MaximumItemRequirements>\n";
	        }

	        if($set["has_r_minimum_feedback_score"]){
	            $xml.="<MinimumFeedbackScore>{$set['r_minimum_feedback_score']}</MinimumFeedbackScore>\n";
	        }

	        if($set["r_ship_to_registration_country"]){
	            $xml.="<ShipToRegistrationCountry>".($set["r_ship_to_registration_country"]?"True":"False")."</ShipToRegistrationCountry>\n";
	        }
	        $xml.="</BuyerRequirementDetails>\n";
	    }

	    #账号自定义类目
	    if($list['store']){
	        $xml.="<Storefront>\n";
	        $xml.="<StoreCategoryID>{$list['store']}</StoreCategoryID>\n";
	        if($list['second_store']){
	        	$xml.="<StoreCategory2ID>{$list['second_store']}</StoreCategory2ID>";
	        }
	        $xml.="</Storefront>\n";
	    }

	    #成色
    	$xml.="<ConditionID>".($list['condition']?$list['condition']:"1000")."</ConditionID>\n";

    	#是否允许退货
	    if($set['returnpolicy']){
	        $xml.="<ReturnPolicy>\n";
	        $xml.="<ReturnsAcceptedOption>ReturnsAccepted</ReturnsAcceptedOption>\n";
	        #退款详情
	        if($set['refund_policy_description']){
	            $xml.="<Description><![CDATA[{$set['refund_policy_description']}]]></Description>\n";
	        }

	        #退款方式*
	        $xml.="<RefundOption>{$set['refund_policy_refund_option']}</RefundOption>\n";
	        #退货天数*
	        $xml.="<ReturnsWithinOption>{$set['returnpolicy_returns_within_option']}</ReturnsWithinOption>\n";

	        #退货运费承担方*
	        if($set['returnpolicy_shipping_cost_paid_by_option']==0){
	            $o="Buyer";
	        }elseif($set['returnpolicy_shipping_cost_paid_by_option']==1){
	            $o="Seller";
	        }else{
	            $o="CustomCode";
	        }
	        $xml.="<ShippingCostPaidByOption>{$o}</ShippingCostPaidByOption>\n";
	        $xml.="</ReturnPolicy>\n";
	    }

	    #运输方式
	    $xml.="<ShippingDetails>\n";
	    #不送达地区
	    if($set['shipping_details_exclude_ship_to_location']){
	        $exShip = explode(",",$set['shipping_details_exclude_ship_to_location']);
	        foreach($exShip as $kes => $ves){
	            $xml.="<ExcludeShipToLocation>{$ves}</ExcludeShipToLocation>\n";
	        }
	    }

	    #国内运输
	    if($set["internal"] || !empty($transIn)){
	    	foreach($transIn as $kin => $vin){
	    		$xml.="<ShippingServiceOptions>\n";
		        $xml.="<FreeShipping>".($vin["free_shipping"]?"True":"False")."</FreeShipping>\n";
		        $xml.="<ShippingService>{$vin["shipping_service"]}</ShippingService>\n";
		        if(!$vin["free_shipping"]){
		            $xml.="<ShippingServiceAdditionalCost>{$vin["shipping_service_additional_cost"]}</ShippingServiceAdditionalCost>\n";
		            $xml.="<ShippingServiceCost>{$vin["shipping_service_cost"]}</ShippingServiceCost>\n";
		        }
		        $xml.="<ShippingServicePriority>{$vin["shipping_service_priority"]}</ShippingServicePriority>\n";
		        $xml.="</ShippingServiceOptions>\n";
	    	}
	    }

	    #国际运输
	    if(count($trans)){
	    	foreach($trans as $ktr => $vtr){
	            $xml.="<InternationalShippingServiceOption>\n";
	            #运输方式
	            $xml.="<ShippingService>{$vtr['shipping_service']}</ShippingService>\n";
	            #每增加一件运费
	            $xml.="<ShippingServiceAdditionalCost>{$vtr['shipping_service_additional_cost']}</ShippingServiceAdditionalCost>\n";
	            #首件运费
	            $xml.="<ShippingServiceCost>{$vtr['shipping_service_cost']}</ShippingServiceCost>\n";
	            #运输方式排列顺序
	            $xml.="<ShippingServicePriority>{$vtr['shipping_service_priority']}</ShippingServicePriority>\n";

	            #送达地区
	            if($vtr['shiptolocation']){
	                $ships = explode(",",$vtr['shiptolocation']);
	                foreach($ships as $vsh){
	                    $xml.="<ShipToLocation>{$vsh}</ShipToLocation>\n";
	                }
	            }
	            $xml.="</InternationalShippingServiceOption>\n";
	        }
	    }

	    $xml.="</ShippingDetails>\n";

	    $xml.="<ProductListingDetails>\n";
	    $xml.="<UPC>{$list["upc"]}</UPC>\n";
	    $xml.="<EAN>{$list["ean"]}</EAN>\n";
	    $xml.="<ISBN>{$list["isbn"]}</ISBN>\n";
	    $xml.="<BrandMPN>\n";
	    $xml.="<Brand>{$list["brand"]}</Brand>\n";
	    $xml.="<MPN>{$list["mpn"]}</MPN>\n";
	    $xml.="</BrandMPN>\n";
	    $xml.="</ProductListingDetails>\n";

	    #商品类目属性
	    if(count($specs)){
	        $ItemSpecifics=null;
	        foreach($specs as  $sv){
	            if(intval($sv['d_load'])==0){
	                $ItemSpecifics.="<NameValueList>\n";
	                $ItemSpecifics.="<Name><![CDATA[{$sv['attr_name']}]]></Name>\n";
	                $ItemSpecifics.="<Value><![CDATA[{$sv['attr_value']}]]></Value>\n";
	                $ItemSpecifics.="</NameValueList>\n";
	            }
	        }
	        if($ItemSpecifics){
	            $xml.="<ItemSpecifics>\n";
	            $xml.=$ItemSpecifics;
	            $xml.="</ItemSpecifics>\n";
	        }
	    }

	    #多属性子产品
	    if($list['varions']){
	    	$xml.="<Variations>\n";
	        $xml.="<Pictures>\n";
	        $keys=array_keys(json_decode($vars[0]["variation"],true));
	        $xml.="<VariationSpecificName><![CDATA[{$keys[0]}]]></VariationSpecificName>\n";
	        foreach($vars as $vLsit){
	            $xml.="<VariationSpecificPictureSet>\n";
	                foreach($imgs as $img){
	                    if(intval($img["main_de"])==1){
	                        $xml.="<PictureURL><![CDATA[{$img['eps_path']}]]></PictureURL>\n";
	                    }
	                }
	            $vKey=json_decode($vLsit["variation"],true);
	            $xml.="<VariationSpecificValue><![CDATA[{$vKey[$keys[0]]}]]></VariationSpecificValue>\n";
	            $xml.="</VariationSpecificPictureSet>\n";
	        }
	        $xml.="</Pictures>\n";

	        $vArray=array();
	        foreach($vars as $sku){
	            $xml.="<Variation>\n";
	            $xml.="<Quantity>{$sku['v_qty']}</Quantity>\n";
	            $xml.="<SKU>{$sku['v_sku']}</SKU>\n";
	            $xml.="<StartPrice>{$sku['v_price']}</StartPrice>\n";

	            $xml.="<VariationSpecifics>\n";
	            foreach(json_decode($sku["variation"],true) as $nk=>$nv){
	                $xml.="<NameValueList>\n";
	                $xml.="<Name><![CDATA[{$nk}]]></Name>\n";
	                $xml.="<Value><![CDATA[{$nv}]]></Value>\n";
	                $xml.="</NameValueList>\n";
	                $vArray[$nk][$nv]=$nv;
	            }
	            $xml.="</VariationSpecifics>\n";

	            $xml.='<VariationProductListingDetails>';
	            $xml.='<UPC>Does not apply</UPC>';
	            $xml.='<EAN>Does not apply</EAN>';
	            $xml.='<ISBN>Does not apply</ISBN>';
	            $xml.='</VariationProductListingDetails>';

	            $xml.="</Variation>\n";
	        }

	        $xml.="<VariationSpecificsSet>\n";
	        foreach($vArray as $sk=>$sv){
	            $xml.="<NameValueList>\n";
	            $xml.="<Name><![CDATA[{$sk}]]></Name>\n";
	            foreach($sv as $v){
	                $xml.="<Value><![CDATA[{$v}]]></Value>\n";
	            }
	            $xml.="</NameValueList>\n";
	        }
	        $xml.="</VariationSpecificsSet>\n";
	        $xml.="</Variations>\n";
	    }

    	$requestBody ='<?xml version="1.0" encoding="utf-8"?>';
    	$requestBody.='<AddFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
    	$requestBody.='<RequesterCredentials><eBayAuthToken>'.$token.'</eBayAuthToken></RequesterCredentials>';
    	$requestBody.='<ErrorLanguage>zh_CN</ErrorLanguage>';
    	$requestBody.='<WarningLevel>High</WarningLevel>';
    	$requestBody.=$xml;
    	$requestBody.='</AddFixedPriceItemRequest>';

    	return $requestBody;
    }
}
