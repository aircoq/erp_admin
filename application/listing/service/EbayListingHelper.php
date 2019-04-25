<?php

/**
 * Description of AliexpressListingHelper
 * @datetime 2017-5-25  17:32:10
 * @author joy
 */

namespace app\listing\service;
use app\common\service\UniqueQueuer;
use app\publish\helper\ebay\EbayPublish;
use app\publish\service\EbayDealApiInformation;
use app\publish\service\EbayListingCommonHelper;
use app\publish\service\EbayPackApi;
use think\Db;
use think\Exception;
use think\Cache;
use service\ebay\EbayItemApi;
use org\CustomEbayXml;
use app\common\exception\JsonErrorException;
use app\common\model\ebay\EbayCommonTemplate;
use app\common\model\ebay\EbayCommonTrans;
use app\common\model\ebay\EbayCommonTransDetail;
use app\common\model\ebay\EbayCommonExclude;
use app\common\model\ebay\EbayCommonLocation;
use app\common\model\ebay\EbayCommonReturn;
use app\common\model\ebay\EbayCommonReceivables;
use app\common\model\ebay\EbayCommonBargaining;
use app\common\model\ebay\EbayCommonChoice;
use app\common\model\ebay\EbayCommonPickup;
use app\common\model\ebay\EbayCommonGallery;
use app\common\model\ebay\EbayCommonIndividual;
use app\common\model\ebay\EbayCommonQuantity;
use app\common\model\ebay\EbayModelPromotion;
use app\common\model\ebay\EbayModelSale;
use app\common\model\ebay\EbayCommonRefuseBuyer;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbayListingTransport;
use app\common\model\ebay\EbayListingTransportIn;
use app\common\model\ebay\EbayListingImage;
use app\common\model\ebay\EbaySite;
use app\common\model\ebay\EbayListingSpecifics;
use app\common\model\ebay\EbayAccount;
use app\publish\helper\ebay\EbayPublish as EbayPublishHelper;
use app\common\service\Twitter;
use app\goods\service\GoodsImage;
use app\common\model\ebay\ebayListingPriceQuanlity;
class EbayListingHelper {
    protected $EbayProductModel;
    protected $EbayProductSettingModel;
    protected $validate;
    protected $xml;
    protected $EbayVariantModel;
    protected $EbayListingTransportModel;
    protected $EbayListingTransportInModel;
    protected $EbayPromotionModel;
    protected $EbayImageModel;
    protected $EbaySiteModel;
    protected $EbayListingSpecifics;
    protected $EbayAccountModel;
    /** 初始化模型
     * EbayListingHelper constructor.
     */
    public function __construct()
    {
        if(is_null($this->EbayProductModel))
        {
            $this->EbayProductModel = new EbayListing;
        }
        
        if(is_null($this->EbayProductSettingModel))
        {
            $this->EbayProductSettingModel = new EbayListingSetting;
        }
        
        if(is_null($this->validate))
        {
            $this->validate = new \app\listing\validate\EbayListingValidate();
        }
        
        if(is_null($this->xml))
        {
            $this->xml = new CustomEbayXml();
        }
        
        if(is_null($this->EbayVariantModel))
        {
            $this->EbayVariantModel = new \app\common\model\ebay\EbayListingVariation;
        }
        
        if(is_null($this->EbayListingTransportModel))
        {
            $this->EbayListingTransportModel = new EbayListingTransport;
        }
        if(is_null($this->EbayListingTransportInModel))
        {
            $this->EbayListingTransportInModel = new EbayListingTransportIn();
        }
        if(is_null($this->EbayPromotionModel))
        {
            $this->EbayPromotionModel = new EbayModelPromotion();
        }
        if(is_null($this->EbayImageModel))
        {
             $this->EbayImageModel = new EbayListingImage;
        }
        if(is_null($this->EbaySiteModel))
        {
             $this->EbaySiteModel = new EbaySite;
        }
        if(is_null($this->EbayListingSpecifics))
        {
            $this->EbayListingSpecifics = new EbayListingSpecifics;
        }
        if(is_null($this->EbayAccountModel))
        {
            $this->EbayAccountModel = new  EbayAccount;
        }
    }

	/**
	 * 将指定元素入指定队列
	 * @param $param
	 * @param $class
	 */
    public function pushParamToQueue($param,$class)
    {
    	return (new UniqueQueuer($class))->push($param);
    }
    /**
     * 获取在线listing列表
     * @param time $EndTimeFrom 
     * @param time $EndTimeTo 
     * @param time $StartTimeFrom 
     * @param time $StartTimeTo 
     * @param string  $DetailLevel  ReturnAll/ItemReturnDescription
     * @param time $Sort  排序 0：No Sortting ;1:Sort in descending order; 2: Sort in ascending order
     * @param time $page 
     * @param time $pageSize default:25,max:200
     * @return 
     */
    public  function getSellerList($account_id,$token,$EndTimeFrom,$EndTimeTo,$StartTimeFrom,$StartTimeTo,$page=1,$pageSize=200,$DetailLevel='ItemReturnDescription',$Sort=1)
    {     
        if($EndTimeFrom)
        {
            $xmlData['EndTimeFrom']=$EndTimeFrom;
        }
        
        if($EndTimeTo)
        {
            $xmlData['EndTimeTo']=$EndTimeTo;
        }
        
        $xmlData['StartTimeFrom']=$StartTimeFrom;
        $xmlData['StartTimeTo']=$StartTimeTo;
        $xmlData['DetailLevel']='';
        $xmlData['Pagination']['EntriesPerPage']=$pageSize;
        $xmlData['Pagination']['PageNumber']=$page;
        $xmlData['DetailLevel']=$DetailLevel;
        $xmlData['Sort']=$Sort;
        $xmlData['OutputSelector']='ItemID';
        //$xmlData['OutputSelector']='PaginationResult';
        $xmlData['RequesterCredentials']['eBayAuthToken']=$token;
        $xmlData['ErrorLanguage']='zh_CN';
        $xmlData['Version']='1021';
        $xmlData['WarningLevel']='High';
        $xml = $this->xml->arrayToXml($xmlData, 'GetSellerListRequest',['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);

        $api = new EbayItemApi($token);
        
        $response = $api->sendRequest($xml, 'GetSellerList');
        
        //$TotalNumberOfPages = (int)$response['data']['PaginationResult']->TotalNumberOfPages; 
        
        if($response['result'] && $response['data']['Ack'] !='Failure')
        {
            $itemArray =(array)$response['data']['ItemArray'];
            if(!empty($itemArray))
            {
                $items = $itemArray['Item'];
                
                foreach($items as $item)
                {
                    if(is_object($item))
                    {
                        $item = (array)$item;         
                    }
                    $item_id= $item['ItemID'];
                    $this->getItem($token,$account_id, $item_id); 
                 }
            
                //$this->managerGetSellerListData($account_id,$items);
                if(isset($GLOBALS['totalPage']))
                {
                    $TotalNumberOfPages = $GLOBALS['totalPage'];
                }else{
                    $TotalNumberOfPages = $this->getTotalPages($account_id, $token, $EndTimeFrom, $EndTimeTo, $StartTimeFrom, $StartTimeTo, $page, $pageSize, $DetailLevel, $Sort);
                    $GLOBALS['totalPage'] = $TotalNumberOfPages;
                }
                //$TotalNumberOfPages = (int)$response['data']['PaginationResult']['TotalNumberOfPages'];
                if($page<=$TotalNumberOfPages)
                {
                    $this->EbayAccountModel->where(['id'=>$account_id])->inc('page',1);
                    $page = $page + 1;
                    $this->getSellerList($account_id,$token, $EndTimeFrom, $EndTimeTo, $StartTimeFrom, $StartTimeTo, $page);
                }
            }   
        } 
    }
    
    public  function getTotalPages($account_id,$token,$EndTimeFrom,$EndTimeTo,$StartTimeFrom,$StartTimeTo,$page=1,$pageSize=10,$DetailLevel='ItemReturnDescription',$Sort=1)
    {
        if($EndTimeFrom)
        {
            $xmlData['EndTimeFrom']=$EndTimeFrom;
        }
        
        if($EndTimeTo)
        {
            $xmlData['EndTimeTo']=$EndTimeTo;
        }
        
        //$xmlData['EndTimeFrom']=$EndTimeFrom;
        //$xmlData['EndTimeTo']=$EndTimeTo;
        $xmlData['StartTimeFrom']=$StartTimeFrom;
        $xmlData['StartTimeTo']=$StartTimeTo;
        $xmlData['DetailLevel']='';
        $xmlData['Pagination']['EntriesPerPage']=$pageSize;
        $xmlData['Pagination']['PageNumber']=$page;
        $xmlData['DetailLevel']=$DetailLevel;
        $xmlData['Sort']=$Sort;
        //$xmlData['OutputSelector']='Item.ItemID';
        $xmlData['OutputSelector']='PaginationResult';
        $xmlData['RequesterCredentials']['eBayAuthToken']=$token;
        $xmlData['ErrorLanguage']='zh_CN';
        $xmlData['Version']='1021';
        $xmlData['WarningLevel']='High';
        $xml = $this->xml->arrayToXml($xmlData, 'GetSellerListRequest',['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);

        $api = new EbayItemApi($token);
        $response = $api->sendRequest($xml, 'GetSellerList');
        //dump($response);die;
        if($response['result'] && $response['data']['Ack'] !='Failure')
        {
            $TotalNumberOfPages = (int)$response['data']['PaginationResult']->TotalNumberOfPages;
           
            //$GLOBALS['totalPage'] = $TotalNumberOfPages;
            return $TotalNumberOfPages;
        }
    }
    /**
     * 处理抓取到的listing数据
     * @param array $itemArray
     */
    public  function managerGetSellerListData($account_id,$items)
    {
        foreach($items as $item)
        {
            if(is_object($item))
            {
                $item = (array)$item;         
            }
            
            $listing['item_id']= $item['ItemID'];
            $listing['listing_status']=(string)$item['SellingStatus']->ListingStatus;
            $listing['listing_type']=$item['ListingType'];
            $listing['goods_type']='';      
            $listing['listing_sku']=$item['SKU'];
            $listing['account_id']= $account_id;
            $listing['site']=$item['Site'];
            $listing['currency']=$item['Currency'];    
            if(isset($item['Variations']))
            {
                $listing['varions']=1;
            }else{
                $listing['varions']=0;
            }
            
            $listing['paypal_emailaddress']= $item['PayPalEmailAddress'];
            
            $listing['primary_categoryid']=(int)$item['PrimaryCategory']->CategoryID;
            $listing['primary_category_pahtname']=(string)$item['PrimaryCategory']->CategoryName;
            
            $listing['second_categoryid'] = isset($item['SecondaryCategory'])?(int)$item['SecondaryCategory']['CategoryID']:0;
            $listing['second_category_name'] = isset($item['SecondaryCategory'])?(string)$item['SecondaryCategory']['CategoryName']:'';
            
            if(isset($item['Storefront']))
            {
                $listing['store']=(int)$item['Storefront']->StoreCategoryID;
                $listing['second_store']=(int)$item['Storefront']->StoreCategory2ID;
            }
            
            
            $listing['quantity']=$item['Quantity'];
            $listing['sold_quantity']= (int)$item['SellingStatus']->QuantitySold;
            $listing['hit_count'] = isset($item['HitCount'])?$item['HitCount']:0;
            
            $listing['buy_it_nowprice']=$item['BuyItNowPrice'];
            $listing['reserve_price']=$item['ReservePrice'];      
            $listing['start_price']=$item['StartPrice'];
            $listing['title']= $item['Title'];
            $listing['sub_title']=isset($item['SubTitle'])?$item['SubTitle']:'';
            $listing['description']=$item['Description'];      
            $listing['condition']=$item['ConditionID'];
            $listing['start_date']=$this->ebayTimeToIntTime((string)$item['ListingDetails']->StartTime);
            $listing['end_date']=$this->ebayTimeToIntTime((string)$item['ListingDetails']->EndTime);
            $listing['img'] = (string)$item['PictureDetails']->GalleryURL;
           
            //===============================ItemSpecifics=====================
            if(isset($item['ItemSpecifics']))
            {
                
            }
            //===============================ItemSpecifics end=====================

            //===============================ProductListingDetails=====================
            if(isset($item['ProductListingDetails']))
            {
                if(isset($item['ProductListingDetails']->UPC))
                {
                    $listing['upc']= (string)$item['ProductListingDetails']->UPC;
                }
                if(isset($item['ProductListingDetails']->ISBN))
                {
                    $listing['isbn']= (string)$item['ProductListingDetails']->ISBN;
                }
                
                if(isset($item['ProductListingDetails']->EAN))
                {
                    $listing['ean']= (string)$item['ProductListingDetails']->EAN;
                }
                
                if(isset($item['ProductListingDetails']->BrandMPN->Brand))
                {
                    $listing['brand']= (string)$item['ProductListingDetails']->EAN;
                }
                
                if(isset($item['ProductListingDetails']->BrandMPN->MPN))
                {
                    $listing['MPN']= (string)$item['ProductListingDetails']->EAN;
                }    
                
            }
            //================================ProductListingDetails end =========================

            
            $images=[];
            $imageUrls=(array)$item['PictureDetails']->PictureURL;
            dump($imageUrls);
//            foreach ($imageUrls as $key => $imageUrls) 
//            {
//                
//            }
            
            //=================================退货=========================================
            $setting['return_policy'] = (string)$item['ReturnPolicy']->ReturnsAcceptedOption;
            $setting['return_type'] = (string)$item['ReturnPolicy']->RefundOption;
            $setting['return_time'] = (string)$item['ReturnPolicy']->ReturnsWithinOption;
            $setting['extension'] = (string)$item['ReturnPolicy']->ReturnsAcceptedOption;
            $setting['return_shipping_option'] = (string)$item['ReturnPolicy']->ShippingCostPaidByOption;
            $setting['restocking_fee_code'] = (string)$item['ReturnPolicy']->RestockingFeeValueOption;
            //=================================退货结束=================================
            $setting['location']=$item['Location'];
            $setting['country'] = $item['Country'];
            $setting['application_data'] = isset($item['ApplicationData'])?$item['ApplicationData']:'';
            $setting['post_code']=isset($item['PostalCode'])?$item['PostalCode']:'';
            $setting['choice_date']=isset($item['DispatchTimeMax'])?$item['DispatchTimeMax']:0;
            $setting['exclude'] = json_encode($item['ShippingDetails']->ExcludeShipToLocation);
            $setting['picture_gallery']=(string)$item['PictureDetails']->GalleryType;
            $setting['individual_listing'] =$item['PrivateListing']=='true'?1:0;
            $setting['listing_duration']=$item['ListingDuration'];
            $setting['pay_method']=$item['PaymentMethods'];
            $setting['autopay'] =$item['AutoPay']=='true'?1:0;
            
            //========================买家限制============================
            if(isset($item['BuyerRequirementDetails']))
            {
                $setting['refuse']=1;
                
                if(isset($item['BuyerRequirementDetails']->LinkedPayPalAccount))
                {
                    $setting['link_paypal']= (string)$item['BuyerRequirementDetails']->LinkedPayPalAccount=='true'?1:0;
                }
                
                if(isset($item['BuyerRequirementDetails']->ShipToRegistrationCountry))
                {
                    $setting['registration']= (string)$item['BuyerRequirementDetails']->ShipToRegistrationCountry=='true'?1:0;
                }
                
                if(isset($item['BuyerRequirementDetails']->MaximumBuyerPolicyViolations))
                {
                    $setting['violations']= 1;
                    $setting['violations_count']= (int)$item['BuyerRequirementDetails']->MaximumBuyerPolicyViolations->Count;
                    $setting['violations_period']= (string)$item['BuyerRequirementDetails']->MaximumBuyerPolicyViolations->Period;
                }else{
                    $setting['violations']= 0;
                }
                
                if(isset($item['BuyerRequirementDetails']->MaximumUnpaidItemStrikesInfo))
                {
                    $setting['strikes']= 1;
                    $setting['strikes_count']= (int)$item['BuyerRequirementDetails']->MaximumUnpaidItemStrikesInfo->Count;
                    $setting['strikes_period']= (string)$item['BuyerRequirementDetails']->MaximumUnpaidItemStrikesInfo->Period;
                }else{
                    $setting['strikes']= 0;
                }
                
                if(isset($item['BuyerRequirementDetails']->ZeroFeedbackScore))
                {
                    $setting['credit']= (string)$item['BuyerRequirementDetails']->ZeroFeedbackScore=='true'?1:0;
                }
                
                if(isset($item['BuyerRequirementDetails']->MinimumFeedbackScore))
                {
                    $setting['minimum_feedback']= 1;
                    $setting['minimum_feedback_score']= (int)$item['BuyerRequirementDetails']->MinimumFeedbackScore;
                }else{
                    $setting['minimum_feedback']= 0;
                }
                
                
                if(isset($item['BuyerRequirementDetails']->MaximumItemRequirements))
                {
                    $setting['requirements']= 1;
                    $setting['requirements_feedback_score']= (int)$item['BuyerRequirementDetails']->MaximumItemRequirements->MinimumFeedbackScore;
                    $setting['requirements_max_count']= (int)$item['BuyerRequirementDetails']->MaximumItemRequirements->MaximumItemCount;
                }else{
                    $setting['requirements']= 0;
                }
                if(isset($item['BuyerRequirementDetails']->LinkedPayPalAccount))
                {
                    $setting['link_paypal']= (string)$item['BuyerRequirementDetails']->LinkedPayPalAccount=='true'?1:0;
                }         
            }else{
                $setting['refuse']=0;
            }
            //========================买家限制结束========================
        }
        
    }
    
    /**
     * 处理抓取到的listing数据
     * @param array $itemArray
     */
    public  function managerOneItemData($account_id,$item)
    {
        
        if(is_object($item))
        {
            $item = (array)$item;         
        }
       
        $listing['item_id']= $item['ItemID'];
        $listing_status=(string)$item['SellingStatus']->ListingStatus;
        
        if($listing_status=='Completed')
        {
            $listing['listing_status']=11;
        }elseif($listing_status=='Active'){
            $listing['listing_status']=3;
        }elseif($listing_status=='Ended'){
            $listing['listing_status']=11;
        }
        
        $listing['listing_code']=(string)$item['SellingStatus']->ListingStatus;
        
        $listing['listing_type']=$item['ListingType'];
        $listing['goods_type']='';      
        $listing['listing_sku']=$item['SKU'];
        $listing['account_id']= $account_id;
        $listing['site_code']=$item['Site'];
        $listing['currency']=$item['Currency'];    
        
        //===========================物流============================
        $shipping=[];
        $internationShipping=[];
        if(isset($item['ShippingDetails']))
        {
            $len=0;
            foreach($item['ShippingDetails']->ShippingServiceOptions as $k=>$ship)
            {
                $shipping[$len]['shipping_service'] =  (string)$ship->ShippingService;
                $shipping[$len]['shipping_service_cost'] =  (float)$ship->ShippingServiceCost;
                $shipping[$len]['shipping_service_additional_cost'] =  (float)$ship->ShippingServiceAdditionalCost;
                $shipping[$len]['shipping_service_priority'] =  (int)$ship->ShippingServicePriority;
                $shipping[$len]['extra_cost'] =  isset($ship->ShippingSurcharge)?(float)$ship->ShippingSurcharge:'0';
                $shipping[$len]['expedited_service'] = isset($ship->ExpeditedService)?(string)$ship->ExpeditedService:'';
                $shipping[$len]['shipping_time_min'] =  (int)$ship->ShippingTimeMin;
                $shipping[$len]['shipping_time_max'] =  (int)$ship->ShippingTimeMax;
                ++$len;
            }
            
            $len=0;
            foreach($item['ShippingDetails']->InternationalShippingServiceOption as $k=>$iship)
            {
                $internationShipping[$len]['shipping_service'] =  (string)$iship->ShippingService;
                $internationShipping[$len]['shipping_service_cost'] =  (float)$iship->ShippingServiceCost;
                $internationShipping[$len]['shipping_service_additional_cost'] =  (float)$iship->ShippingServiceAdditionalCost;
                $internationShipping[$len]['shipping_service_priority'] =  (int)$iship->ShippingServicePriority;
                $internationShipping[$len]['shiptolocation'] =  isset($iship->ShipToLocation)?(string)$iship->ShipToLocation:''; 
                ++$len;
            }
        }
        
        //===========================物流end===========================
        
        //============================多属性========================
        $ebayvariants=[];
        
        if(isset($item['Variations']))
        {
            $listing['varions']=1;
            $len=0;
            foreach($item['Variations']->Variation as $variant)
            {
                 
                $ebayvariants[$len]['v_sku'] = (string)$variant->SKU;
                $ebayvariants[$len]['v_price'] = (float)$variant->StartPrice;
                $ebayvariants[$len]['v_qty'] = (int)$variant->Quantity;
                $ebayvariants[$len]['v_sold'] = (int)$variant->SellingStatus->QuantitySold;
                $ebayvariants[$len]['quantity_sold_by_pickup_in_store'] = (int)$variant->SellingStatus->QuantitySoldByPickupInStore;
                $variationSpecifics=[];
                foreach ($variant->VariationSpecifics->NameValueList as $key =>$Spec) 
                {
                    $variationSpecifics[(string)$Spec->Name]=(string)$Spec->Value;
                }   
                $ebayvariants[$len]['variation'] = json_encode($variationSpecifics);
                        
                $ebayvariants[$len]['upc'] = isset($variant->VariationProductListingDetails->UPC)?(string)$variant->VariationProductListingDetails->UPC:'';
                $ebayvariants[$len]['ean'] = isset($variant->VariationProductListingDetails->EAN)?(string)$variant->VariationProductListingDetails->EAN:'';
                $ebayvariants[$len]['isbn'] = isset($variant->VariationProductListingDetails->ISBN)?(string)$variant->VariationProductListingDetails->ISBN:'';
                
                ++$len ;  
            }
        }else{
            $listing['varions']=0;
        }
         //============================多属性ending========================    
        $listing['paypal_emailaddress']= $item['PayPalEmailAddress'];

        $listing['primary_categoryid']=(int)$item['PrimaryCategory']->CategoryID;
        $listing['primary_category_pahtname']=(string)$item['PrimaryCategory']->CategoryName;

        $listing['second_categoryid'] = isset($item['SecondaryCategory'])?(int)$item['SecondaryCategory']['CategoryID']:0;
        $listing['second_category_name'] = isset($item['SecondaryCategory'])?(string)$item['SecondaryCategory']['CategoryName']:'';

        if(isset($item['Storefront']))
        {
            $listing['store']=(int)$item['Storefront']->StoreCategoryID;
            $listing['second_store']=(int)$item['Storefront']->StoreCategory2ID;
        }


        $listing['quantity']=$item['Quantity'];
        $listing['sold_quantity']= (int)$item['SellingStatus']->QuantitySold;
        $listing['hit_count'] = isset($item['HitCount'])?$item['HitCount']:0;
        $listing['watch_count'] = isset($item['WatchCount'])?$item['WatchCount']:0;
        $listing['buy_it_nowprice']=$item['BuyItNowPrice'];
        $listing['reserve_price']=$item['ReservePrice'];      
        $listing['start_price']=$item['StartPrice'];
        $listing['title']= $item['Title'];
        $listing['sub_title']=isset($item['SubTitle'])?$item['SubTitle']:'';
        $listing['description']=$item['Description'];      
        $listing['condition']=$item['ConditionID'];
        $listing['start_date']=$this->ebayTimeToIntTime((string)$item['ListingDetails']->StartTime);
        $listing['end_date']=$this->ebayTimeToIntTime((string)$item['ListingDetails']->EndTime);
        $listing['img'] = (string)$item['PictureDetails']->GalleryURL;
           
        //===============================ItemSpecifics=====================
        $specifics=[];
        if(isset($item['ItemSpecifics']))
        {
            $len=0;
            foreach ($item['ItemSpecifics']->NameValueList as $key => $Spec) 
            {
                $specifics[$len]['attr_name']=(string)$Spec->Name;
                $specifics[$len]['attr_value']=(string)$Spec->Value;
                ++$len;
            }          
        }
        //===============================ItemSpecifics end=====================

        //===============================ProductListingDetails=====================
        if(isset($item['ProductListingDetails']))
        {
            if(isset($item['ProductListingDetails']->UPC))
            {
                $listing['upc']= (string)$item['ProductListingDetails']->UPC;
            }
            if(isset($item['ProductListingDetails']->ISBN))
            {
                $listing['isbn']= (string)$item['ProductListingDetails']->ISBN;
            }

            if(isset($item['ProductListingDetails']->EAN))
            {
                $listing['ean']= (string)$item['ProductListingDetails']->EAN;
            }

            if(isset($item['ProductListingDetails']->BrandMPN->Brand))
            {
                $listing['brand']= (string)$item['ProductListingDetails']->BrandMPN->Brand;
            }
                
            if(isset($item['ProductListingDetails']->BrandMPN->MPN))
            {
                $listing['mpn']= (string)$item['ProductListingDetails']->BrandMPN->MPN;
            }    
                
        }
        //================================ProductListingDetails end =========================




        $images=[];
        $imageUrls=$item['PictureDetails']->PictureURL;
        $len=0;
        foreach ($item['PictureDetails']->PictureURL as $imageUrl) 
        {
            $images[$len]['eps_path']=(string)$imageUrl;
            $images[$len]['status']=1;
            ++$len;
        }
         
        //=================================退货=========================================
        $setting['return_policy'] = (string)$item['ReturnPolicy']->ReturnsAcceptedOption=='ReturnsAccepted'?1:0;
        
        $setting['return_type'] = (string)$item['ReturnPolicy']->RefundOption;
        $setting['return_time'] = (string)$item['ReturnPolicy']->ReturnsWithinOption;
        $setting['extension'] = (string)$item['ReturnPolicy']->ExtendedHolidayReturns;
        $setting['return_shipping_option'] = (string)$item['ReturnPolicy']->ShippingCostPaidByOption;
        $setting['restocking_fee_code'] = (string)$item['ReturnPolicy']->RestockingFeeValueOption;
        $setting['return_description'] = (string)$item['ReturnPolicy']->Description;
        //=================================退货结束=================================
        $setting['location']=$item['Location'];
        $setting['country'] = $item['Country'];
        $setting['application_data'] = isset($item['ApplicationData'])?$item['ApplicationData']:'';
        $setting['post_code']=isset($item['PostalCode'])?$item['PostalCode']:'';
        $setting['choice_date']=isset($item['DispatchTimeMax'])?$item['DispatchTimeMax']:0;
        $setting['exclude'] = json_encode($item['ShippingDetails']->ExcludeShipToLocation);
        $setting['picture_gallery']=(string)$item['PictureDetails']->GalleryType;
        $setting['individual_listing'] =$item['PrivateListing']=='true'?1:0;
        $setting['listing_duration']=$item['ListingDuration'];
        $setting['pay_method']=$item['PaymentMethods'];
        $setting['autopay'] =$item['AutoPay']=='true'?1:0;
        $setting['shipping_type']=(string)$item['ShippingDetails']->ShippingType;
        if(isset($item['ShippingDetails']->ExcludeShipToLocation))
        {
            $ExcludeShipToLocation = (array)$item['ShippingDetails']->ExcludeShipToLocation;
            
            if(count($ExcludeShipToLocation)>1)
            {
                $setting['exclude'] = json_encode($ExcludeShipToLocation);
            }else{
                $setting['exclude'] = (string)$item['ShippingDetails']->ExcludeShipToLocation;
            }
        }

        //========================买家限制============================
        if(isset($item['BuyerRequirementDetails']))
        {
            $setting['refuse']=1;

            if(isset($item['BuyerRequirementDetails']->LinkedPayPalAccount))
            {
                $setting['link_paypal']= (string)$item['BuyerRequirementDetails']->LinkedPayPalAccount=='true'?1:0;
            }

            if(isset($item['BuyerRequirementDetails']->ShipToRegistrationCountry))
            {
                $setting['registration']= (string)$item['BuyerRequirementDetails']->ShipToRegistrationCountry=='true'?1:0;
            }

            if(isset($item['BuyerRequirementDetails']->MaximumBuyerPolicyViolations))
            {
                $setting['violations']= 1;
                $setting['violations_count']= (int)$item['BuyerRequirementDetails']->MaximumBuyerPolicyViolations->Count;
                $setting['violations_period']= (string)$item['BuyerRequirementDetails']->MaximumBuyerPolicyViolations->Period;
            }else{
                $setting['violations']= 0;
            }
                
            if(isset($item['BuyerRequirementDetails']->MaximumUnpaidItemStrikesInfo))
            {
                $setting['strikes']= 1;
                $setting['strikes_count']= (int)$item['BuyerRequirementDetails']->MaximumUnpaidItemStrikesInfo->Count;
                $setting['strikes_period']= (string)$item['BuyerRequirementDetails']->MaximumUnpaidItemStrikesInfo->Period;
            }else{
                $setting['strikes']= 0;
            }

            if(isset($item['BuyerRequirementDetails']->ZeroFeedbackScore))
            {
                $setting['credit']= (string)$item['BuyerRequirementDetails']->ZeroFeedbackScore=='true'?1:0;
            }
                
            if(isset($item['BuyerRequirementDetails']->MinimumFeedbackScore))
            {
                $setting['minimum_feedback']= 1;
                $setting['minimum_feedback_score']= (int)$item['BuyerRequirementDetails']->MinimumFeedbackScore;
            }else{
                $setting['minimum_feedback']= 0;
            }
                
                
            if(isset($item['BuyerRequirementDetails']->MaximumItemRequirements))
            {
                $setting['requirements']= 1;
                $setting['requirements_feedback_score']= (int)$item['BuyerRequirementDetails']->MaximumItemRequirements->MinimumFeedbackScore;
                $setting['requirements_max_count']= (int)$item['BuyerRequirementDetails']->MaximumItemRequirements->MaximumItemCount;
            }else{
                $setting['requirements']= 0;
            }
            if(isset($item['BuyerRequirementDetails']->LinkedPayPalAccount))
            {
                $setting['link_paypal']= (string)$item['BuyerRequirementDetails']->LinkedPayPalAccount=='true'?1:0;
            }         
        }else{
            $setting['refuse']=0;
        }
        //========================买家限制结束========================
        
        return [
            'listing'=>$listing,
            'images'=>$images,
            'setting'=>$setting,
            'specifics'=>$specifics,
            'variant'=>$ebayvariants,
            'shipping'=>$shipping,
            'internationShipping'=>$internationShipping
        ];
        
    }
    /**
     * 获取产品信息
     * @param type $queues
     */
    public function getProductInfo(array $products,$redis)
    {
        if(is_array($products))
        {
            foreach($products as $product)
            {
                
                $info = $this->EbayProductModel->field('id,account_id,item_id')->with(['account'=>function($query){$query->field('id,token');}])->where(['id'=>$product])->find();
                if($info && is_object($info))
                {
                    $info = $info->toArray();
                }
                if(!empty($info))
                {
                   $token = $info['account']['token'];
                   $account_id = $info['account_id'];
                   $ItemID = $info['item_id'];
                   $res = $this->getItem($token, $account_id, $ItemID);
                   if($res['result']===true)
                   {
                        $redis->myZRem('findEbayProductById',$product); //删除缓存
                   }
                }   
            }
        }
    }
    
    /**
     * 
     * @param string $ItemID
     * @param string $DetailLevel
     * @param bool $forceUpdate 是否强制更新
     * @param bool $IncludeItemSpecifics 
     * @param bool $IncludeWatchCount
     * @param bool $IncludeItemCompatibilityList
     * @param bool $IncludeTaxTable
     */
    public function getItem($token,$account_id,$ItemID,$forceUpdate=0,$DetailLevel='ReturnAll',$IncludeItemSpecifics=true,$IncludeWatchCount=true,$IncludeItemCompatibilityList=true,$IncludeTaxTable=false)
    {
        set_time_limit(0);
        $xmlData['ItemID']=$ItemID;
        $xmlData['DetailLevel']=$DetailLevel;
        $xmlData['IncludeItemSpecifics']=$IncludeItemSpecifics;
        $xmlData['IncludeWatchCount']=$IncludeWatchCount;
        $xmlData['IncludeItemCompatibilityList']=$IncludeItemCompatibilityList;
        $xmlData['IncludeTaxTable']=$IncludeTaxTable;        
        $xmlData['RequesterCredentials']['eBayAuthToken']=$token;
        $xmlData['ErrorLanguage']='zh_CN';
        $xmlData['Version']='1021';
        $xmlData['WarningLevel']='High';
        
        $xml = $this->xml->arrayToXml($xmlData, 'GetItemRequest',['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);

        $api = new EbayItemApi($token);

        $response = $api->sendRequest($xml, 'GetItem');
        
        if($response['result'] && $response['data']['Ack'] !='Failure')
        {
            try{
                if(isset($response['data']['Item']))
                {
                    $item  = $response['data']['Item'];

                    $fixedData = $this->managerOneItemData($account_id,$item);

                    $item_id = $fixedData['listing']['item_id'];

                    if($res = $this->EbayProductModel->where(['item_id'=>$item_id])->find())
                    {
                        if(is_object($res))
                        {
                            $res = $res->toArray();
                        }

                        $listing_id = $res['id'];

                        $listing_status = $res['listing_status'];

                        $fixedData['listing']['site'] = $res['site'];

                        if($listing_status!=5)
                        {
                            $this->EbayProductModel->allowField(true)->save( $fixedData['listing'],['id'=>$listing_id]);
                        }

                    }else{

                        $siteInfo = $this->EbaySiteModel->get(['country'=>$fixedData['listing']['site_code']]);

                        if($siteInfo)
                        {
                            $siteInfo = is_object($siteInfo)?$siteInfo->toArray():$siteInfo;

                            $fixedData['listing']['site']=$siteInfo['siteid'];

                            $timestamp = time();

                            $fixedData['listing']['create_date'] = $timestamp;

                            $listing_id = Twitter::instance()->nextId(1, $account_id);

                            $fixedData['listing']['id']  = $listing_id;

                            $this->EbayProductModel->insert($fixedData['listing']);  

                            time_partition(\app\common\model\ebay\EbayListing::class, $timestamp, 'create_date');
                        }
                        $listing_status = $fixedData['listing']['listing_status'];
                    }
                    //是否更新
                    if($forceUpdate || $listing_status !=5)
                    {
                      $update=1;  
                    }else{
                       $update=0;  
                    }
                
   
                    if($listing_id>0 && $update==1)
                    {

                         //'listing','images','setting','specifics','variant','shipping','internationShipping'

                        //图片
                        if($fixedData['images'])
                        {
                            $images = $this->addOneValueToArray('listing_id', $listing_id, $fixedData['images']);

                            $this->EbayImageModel->destroy(['listing_id'=>$listing_id]);
                            $this->EbayImageModel->allowField(true)->saveAll($images);
                        }
                        //设置
                        if($fixedData['setting'])
                        {
                            $setting = $this->addOneValueToArray('listing_id', $listing_id, $fixedData['setting']);
                            $this->EbayProductSettingModel->destroy(['listing_id'=>$listing_id]); 
                            $this->EbayProductSettingModel->insert($setting);
                        }
                        //商品属性
                        if($fixedData['specifics'])
                        {
                            $specifics = $this->addOneValueToArray('listing_id', $listing_id, $fixedData['specifics']);
                            $this->EbayListingSpecifics->destroy(['listing_id'=>$listing_id]);
                            $this->EbayListingSpecifics->allowField(true)->saveAll($specifics);
                        }
                        //多属性sku
                        if($fixedData['variant'])
                        {
                            $variants = $this->addOneValueToArray('listing_id', $listing_id, $fixedData['variant']);
                            $this->EbayVariantModel->destroy(['listing_id'=>$listing_id]);
                            $this->EbayVariantModel->allowField(true)->saveAll($variants);
                        }
                        //国内物流

                        if($fixedData['shipping'])
                        {
                            $shipping = $this->addOneValueToArray('listing_id', $listing_id, $fixedData['shipping']);
                            $this->EbayListingTransportInModel->destroy(['listing_id'=>$listing_id]);
                            $this->EbayListingTransportInModel->allowField(true)->saveAll($shipping);
                        }
                        //国际物流
                        if($fixedData['internationShipping'])
                        {
                            $internationShipping = $this->addOneValueToArray('listing_id', $listing_id, $fixedData['internationShipping']);

                            $this->EbayListingTransportModel->destroy(['listing_id'=>$listing_id]);
                            $this->EbayListingTransportModel->allowField(true)->saveAll($internationShipping);
                        }  
                    }
            }
                return ['result'=>true,'message'=>''];
            }catch(Exception $exp){
                return ['result'=>false,'message'=>$exp->getMessage()];
            }
            
        }else{
            return ['result'=>false,'message'=>$response['data']['Ack']];
        }
        
    }
    /**
     * 向一个数组的每个元素追加一个值
     * @param string $key
     * @param string $val
     * @param array $array
     * @return array
     */
    public  function addOneValueToArray($key,$val,&$array)
    {
        if(count($array,1) == count($array))
        {
            $array[$key]  = $val;
        }else{
            foreach($array as &$arr)
            {
                $arr[$key]  = $val;
            }
        }
        
        return $array;
    }
    /**
     * 自动补货
     * @param string $item_id
     * @param int $quantity
     * @param string $sku  
     */
    public  function autoReplenishment($item_id,$quantity,$sku)
    {
        $product = $this->EbayProductModel->field('id,account_id,start_price')
                ->with(['account'=>function($query){$query->field('id,token');},'setting'=>function($query){$query->field('id,listing_id,replen');},'variant'=>function($query){$query->field('id,listing_id,v_price,v_sku,v_qty');}])
                //->hasWhere('variant',['v_sku'=>$sku])
                ->where(['item_id'=>$item_id])->find();
        
        if(is_object($product))
        {
            $product = $product->toArray();
        }
        
        if(!empty($product['setting']) && $product['setting']['replen']==1)
        {
            if(empty($product['variant']))
            {
                $start_price = $product['start_price'];
            }else{
                 $variants = $product['variant'];
                 foreach ($variants as $key => $variant) 
                 {
                     if($variant['v_sku'] == $sku)
                     {
                         $start_price = $variant['v_price'];
                         $variant_id = $variant['id'];
                         break;
                     }
                 }
            }
            
            $token = $product['account']['token'];
        
            $xmlData['InventoryStatus']['ItemID']=$item_id;
            $xmlData['InventoryStatus']['Quantity']=$quantity;
            $xmlData['InventoryStatus']['SKU']=$sku;
            $xmlData['InventoryStatus']['StartPrice']=$start_price;
            $xmlData['RequesterCredentials']['eBayAuthToken']=$token;
            $xmlData['ErrorLanguage']='zh_CN';
            $xmlData['Version']='1021';
            $xmlData['WarningLevel']='High';
            $xml = $this->xml->arrayToXml($xmlData, 'ReviseInventoryStatusRequest',['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);
       
            $api = new EbayItemApi($token);

            $response = $api->sendRequest($xml, 'ReviseInventoryStatus');

            if($response['result'] && $response['data']['Ack'] !='Failure')
            {
                //单属性
                if(empty($product['variant']))
                {
                    $return=[
                        'result'=>$response['data']['Ack'],
                        'message'=>'Success',
                        'time'=>date('Y-m-d H:i:s',time())
                    ];
                    $update=[
                        'quantity'=>$product['quantity'] + $quantity,
                        'buhuo_return'=> json_encode($return)
                    ];
                }else{
                    //多属性
                    if($variant_id)
                    {
                        $this->EbayVariantModel->where(['id'=>$variant_id])->inc('v_qty',$quantity);
                    }else{
                        $this->EbayVariantModel->where(['v_sku'=>$sku,'listing_id'=>$product['id']])->inc('v_qty',$quantity);
                    }
                }
               
               
            }else{
                $return=[
                    'result'=>$response['data']['Ack'],
                    'message'=>(string)$response['data']['Errors']->LongMessage,
                    'time'=>date('Y-m-d H:i:s',time())
                ];
                $update=[
                    'buhuo_return'=> json_encode($return)
                ];
            }
            $this->EbayPromotionModel->save($update,['item_id'=>$item_id]);
        }   
    }
    /**
     * 
     * @param url $image
     * @param string $token
     * @return array|null
     */
    public function uploadSiteHostedPictures($image,$token)
    {
        //$xmlData['ExtensionInDays']=30;
        $xmlData['ExternalPictureURL']=$image;
         
        //$xmlData['PictureName']=$start_price;
        //$xmlData['PictureSet']=$start_price;
        $xmlData['PictureSystemVersion']=2;
        $xmlData['PictureUploadPolicy']='Add';
        
        $xmlData['RequesterCredentials']['eBayAuthToken']=$token;
        $xmlData['ErrorLanguage']='zh_CN';
        $xmlData['Version']='1021';
        $xmlData['WarningLevel']='High';
        $xml = $this->xml->arrayToXml($xmlData, 'UploadSiteHostedPicturesRequest',['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);

        $api = new EbayItemApi($token);

        $response = $api->sendRequest($xml, 'UploadSiteHostedPictures');
        if($response['result'] && $response['data']['Ack'] !='Failure')
        {
            $return = [
                'fullUrl'=>(string)$response['data']['SiteHostedPictureDetails']->FullURL,
                'baseUrl'=>(string)$response['data']['SiteHostedPictureDetails']->BaseURL,
            ];
            return ['result'=>true,'data'=>$return];
        }else{
            return ['result'=>false,'data'=>''];
        }
    }

    public function reviseItem($product)
    {
	    $item_id=$product['item_id'];
	    $item =$this->setItemData($product);
	    $xmlData['Item']=$item;
	    $xmlData['RequesterCredentials']['eBayAuthToken']=$product['account']['token'];
	    $xmlData['ErrorLanguage']='zh_CN';
	    $xmlData['Version']='1021';
	    $xmlData['WarningLevel']='High';

	    if($product['listing_type']=='FixedPriceItem')
	    {
		    $request = 'ReviseFixedPriceItemRequest';
	    }else{
		    $request = 'ReviseItemRequest';
	    }

	    if($product['listing_type']=='FixedPriceItem')
	    {
		    $callName = 'ReviseFixedPriceItem';
	    }else{
		    $callName = 'ReviseItem';
	    }

	    $xml = $this->xml->arrayToXml($xmlData, $request,['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);

	    $api = new EbayItemApi($product['account']['token']);

	    $response = $api->sendRequest($xml,$callName);

	    if($response['result'] && $response['data']['Ack'] !='Failure')
	    {
		    $update=[
			    'listing_status'=>3,
			    'message'=>$response['data']['Ack'],
		    ];
	    }else{
		    if(is_array($response['data']['Errors']))
		    {
			    $update=[
				    'listing_status'=>4,
				    'message'=> json_encode($response['data']['Errors']),
			    ];
		    }else{
			    $update=[
				    'listing_status'=>4,
				    'message'=>(string)$response['data']['Errors']->LongMessage,
			    ];
		    }

	    }
        $this->EbayProductModel->save($update,['item_id'=>$item_id]);
    }
    /**
     * 修改一口价商品信息
     * @param array $products
     */
    public  function reviseFixedPriceItem($products)
    {   
        set_time_limit(0);
        if(is_array($products) && !empty($products))
        {
            foreach($products as $product)
            {
	            $item_id=$product['item_id'];
                $item =$this->setItemData($product);
                $xmlData['Item']=$item;
                $xmlData['RequesterCredentials']['eBayAuthToken']=$product['account']['token'];
                $xmlData['ErrorLanguage']='zh_CN';
                $xmlData['Version']='1021';
                $xmlData['WarningLevel']='High';
                
                if($product['listing_type']=='FixedPriceItem')
                {
                    $request = 'ReviseFixedPriceItemRequest';
                }else{
                    $request = 'ReviseItemRequest';
                }

	            if($product['listing_type']=='FixedPriceItem')
	            {
		            $callName = 'ReviseFixedPriceItem';
	            }else{
		            $callName = 'ReviseItem';
	            }
                
                $xml = $this->xml->arrayToXml($xmlData, $request,['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);
                 
                $api = new EbayItemApi($product['account']['token']);
                
                $response = $api->sendRequest($xml,$callName);
               
                if($response['result'] && $response['data']['Ack'] !='Failure')
                {
                    $update=[
                        'listing_status'=>3,
                        'message'=>$response['data']['Ack'],
                    ];
                }else{
                    if(is_array($response['data']['Errors']))
                    {
                        $update=[
                            'listing_status'=>4,
                            'message'=> json_encode($response['data']['Errors']),
                        ];
                    }else{
                        $update=[
                            'listing_status'=>4,
                            'message'=>(string)$response['data']['Errors']->LongMessage,
                        ];
                    }
                    
                }
                $this->EbayProductModel->save($update,['item_id'=>$item_id]);
            }
        }
    }

    public function setItemData($product)
    {
	    if(is_object($product))
	    {
		    $product = $product->toArray();
	    }

	    $item_id = $product['item_id'];
	    $token = $product['account']['token'];
	    $code =  $product['account']['code'];

	    if(!empty($product['images']))
	    {
		    foreach ($product['images'] as $key => &$img)
		    {
			    $img['image_path'] = GoodsImage::getImagePath($img['image_path'], $code);

			    $ebayImageResponse = $this->uploadSiteHostedPictures($img['image_path'], $token);

			    if($ebayImageResponse['result'])
			    {
				    $img['eps_path'] = $ebayImageResponse['data']['fullUrl'];
				    $img['status']=1;
				    $this->EbayImageModel->allowField(true)->save($img,['id'=>$img['id']]);
			    }
		    }
	    }


	    if(!empty($product['images']))
	    {
		    $item['PictureDetails']['GalleryDuration']='Lifetime';

		    $item['PictureDetails']['GalleryType']=$product['setting']['picture_gallery']?$product['setting']['picture_gallery']:'None';
		    //$item['PictureDetails']['PhotoDisplay']='Lifetime';
		    $item['PictureDetails']['PictureSource']='EPS'; //Vendor第三方
		    foreach($product['images'] as $key => $img)
		    {
			    if(!empty($img['eps_path']))
			    {
				    $item['PictureDetails']['PictureURL'][$key]=$img['eps_path'];
			    }
		    }
	    }

	    //开启促销
	    if(!empty($product['promotion']) && $product['promotion']['promotional_sale_id']==0)
	    {

		    $promotionResponse = $this->promotionalSale($token,$product['promotion']);
		    if($promotionResponse['result'] && $promotionResponse['promotionalSaleID'])
		    {
			    $product['promotion']['promotional_sale_id']=$promotionResponse['promotionalSaleID'];
		    }
	    }

	    //如果listing设置了促销活动，且促销活动已经存在
	    if(!empty($product['promotion']) && $product['promotion']['promotional_sale_id'])
	    {
		    $this->SetPromotionalSaleListings($product['promotion']['promotional_sale_id'], $item_id, $token);
	    }

	    $item['ApplicationData']=$product['setting']['application_data']?$product['setting']['application_data']:'Rondaful.com';
	    $item['AutoPay']=$product['setting']['autopay']?'true':'false';
	    $item['CategoryMappingAllowed']='true';
	    $item['ConditionID']=$product['condition'];

	    if($product['condition_description'])
	    {
		    $item['ConditionDescription']=$product['condition_description']?$product['condition_description']:'';
	    }


	    $item['Country'] = $product['setting']['country'];

	    $item['Description'] = $product['description'];

	    $item['DescriptionReviseMode']='Replace'; //描述编辑模式：Append，Prepend，Replace

	    $item['DispatchTimeMax']=$product['setting']['choice_date']; //备货期

	    //$item['HitCounter']='GreenLED';// listing点击次数显示样式

	    $item['ItemID'] = $product['item_id'];

	    $specifics = $product['specifics'];

	    if(is_array($specifics) && !empty($specifics))
	    {
		    foreach($specifics as $k=>$spec){
			    $item['ItemSpecifics'][$k]['NameValueList']['Name']=$spec['attr_name'];
			    $item['ItemSpecifics'][$k]['NameValueList']['Value']=$spec['attr_value'];
		    }
	    }

	    $item['ListingDuration'] = $product['setting']['listing_duration'];
	    $item['Location'] = $product['setting']['location'];

	    $payments = json_decode($product['setting']['pay_method'],true);
	    if($payments && is_array($payments))
	    {
		    foreach($payments as $payment)
		    {
			    $item['PaymentMethods'][] = $payment;
		    }
	    }else{
		    $item['PaymentMethods'] = $product['setting']['pay_method'];
	    }

	    $item['PayPalEmailAddress'] = $product['paypal_emailaddress'];

	    //只有美国站点能应用pickup，the 'In-Store Pickup' feature is only available on the eBay US site
	    if($product['site_code']=='US' && !empty($product['setting']))
	    {
		    $item['PickupInStoreDetails']['EligibleForPickupInStore']=$product['setting']['local_pickup']?'true':'false';
	    }

	    $item['PostalCode'] = $product['setting']['post_code'];

	    $item['PrimaryCategory']['CategoryID'] = $product['primary_categoryid'];
	    if($product['second_categoryid'])$item['SecondaryCategory']['CategoryID']= $product['second_categoryid'];

	    $item['PrivateListing'] = $product['setting']['individual_listing'];

	    if(empty($product['variant']))
	    {
		    $item['ProductListingDetails']['BrandMPN']['Brand']=$product['brand'];
		    $item['ProductListingDetails']['BrandMPN']['MPN']=$product['mpn'];
		    $item['ProductListingDetails']['EAN']=$product['ean'];
		    $item['ProductListingDetails']['IncludeeBayProductDetails']='true';
		    $item['ProductListingDetails']['ISBN']=$product['isbn'];
	    }

	    if($product['setting']['return_policy'])
	    {
		    $item['ReturnPolicy']['Description'] = $product['setting']['return_description']; //退货描述
		    $item['ReturnPolicy']['ReturnsAcceptedOption'] = $product['setting']['return_policy']?'ReturnsAccepted':'ReturnsNotAccepted'; //退货描述
		    $item['ReturnPolicy']['ExtendedHolidayReturns']=$product['setting']['extension'];//节假日延迟退货
		    $item['ReturnPolicy']['RefundOption'] = $product['setting']['return_type']; //退货方式
		    $item['ReturnPolicy']['RestockingFeeValueOption'] = $product['setting']['restocking_fee_code']; //折旧费
		    $item['ReturnPolicy']['ReturnsAcceptedOption'] = $product['setting']['return_time'];//退货时间
		    $item['ReturnPolicy']['ShippingCostPaidByOption'] = $product['setting']['return_shipping_option'];//运费承受方
	    }

	    //==================================================================
	    //  运费设置
	    //==================================================================

	    //不送达地区
	    $exclude = json_decode($product['setting']['exclude'],true);
	    if(empty($exclude))$exclude = explode (',', $product['setting']['exclude']);
	    if($exclude && is_array($exclude))
	    {
		    foreach ($exclude as $key => $ex)
		    {
			    $item['ShippingDetails']['ExcludeShipToLocation'][]=$ex;
		    }
	    }else{
		    $item['ShippingDetails']['ExcludeShipToLocation'] = $product['setting']['exclude'];
	    }
	    //$item['GlobalShipping']='false';

	    if($product['international_shipping'])
	    {
		    $shipping = $product['international_shipping'];
		    if(is_array($shipping))
		    {
			    foreach ($shipping as $key => $ship)
			    {

				    $item['ShippingDetails'][$key]['InternationalShippingServiceOption']['ShippingService']=$ship['shipping_service'];
				    $item['ShippingDetails'][$key]['InternationalShippingServiceOption']['ShippingServiceAdditionalCost']=$ship['shipping_service_additional_cost'];
				    $item['ShippingDetails'][$key]['InternationalShippingServiceOption']['ShippingServiceCost']=$ship['shipping_service_cost'];
				    $item['ShippingDetails'][$key]['InternationalShippingServiceOption']['ShippingServicePriority']=$key+1;

				    $include = json_decode($ship['shiptolocation'],true);

				    if(empty($include))$include = explode (',', $ship['shiptolocation']);

				    if($include && is_array($include) && count($include)>1)
				    {
					    foreach ($include as $ex)
					    {
						    $item['ShippingDetails'][$key]['InternationalShippingServiceOption']['ShipToLocation'][]=$ex;
					    }
				    }else{
					    $item['ShippingDetails'][$key]['InternationalShippingServiceOption']['ShipToLocation']=$ship['shiptolocation'];
				    }
			    }
		    }
	    }

	    if($product['shipping'])
	    {
		    $shipping = $product['shipping'];
		    if(is_array($shipping))
		    {
			    foreach ($shipping as $key => $ship)
			    {
				    if($product['promotion']['promotion_trans'] ==1 && $product['promotion']['promotional_sale_id']!=0)
				    {
					    $item['ShippingDetails'][$key]['ShippingServiceOptions']['FreeShipping']='true';
				    }else{
					    $item['ShippingDetails'][$key]['ShippingServiceOptions']['FreeShipping']=$ship['free_shipping']?'true':'false';
				    }
				    $item['ShippingDetails'][$key]['ShippingServiceOptions']['ShippingServicePriority']=$key+1;
				    $item['ShippingDetails'][$key]['ShippingServiceOptions']['ShippingService']=$ship['shipping_service'];
				    $item['ShippingDetails'][$key]['ShippingServiceOptions']['ShippingServiceCost']=$ship['shipping_service_cost'];

				    $item['ShippingDetails'][$key]['ShippingServiceOptions']['ShippingServiceAdditionalCost']=$ship['shipping_service_additional_cost'];
				    if($ship['extra_cost']>0)$item['ShippingDetails'][$key]['ShippingServiceOptions']['ShippingSurcharge']=$ship['extra_cost'];

			    }
		    }
	    }
	    if($product['setting']['shipping_type'])
	    {
		    $item['ShippingDetails']['ShippingType']=$product['setting']['shipping_type'];
	    }else{
		    $item['ShippingDetails']['ShippingType']='NotSpecified';
	    }


	    //==================================================================
	    //  运费设置结束
	    //==================================================================

	    $item['SKU'] = $product['listing_sku'];

	    if($product['ListingType']!='FixedPriceItem')
	    {
		    $item['BuyItNowPrice'] = $product['buy_it_nowprice'];
		    $item['LiveAuction']='true';
	    }

	    $item['StartPrice'] = $product['start_price'];


	    if(empty($product['variant']))
	    {
		    $item['Quantity']=$product['quantity'];
	    }


	    $item['Title'] = $product['title'];

	    if($product['sub_title'])
	    {
		    $item['SubTitle'] = $product['sub_title'];
	    }


	    //店铺分类设置
	    if($product['store'])$item['Storefront']['StoreCategoryID']=$product['store'];
	    if($product['second_store'])$item['Storefront']['StoreCategory2ID']=$product['second_store'];


	    //==================================================================
	    //  sku变体设置
	    //==================================================================

	    if($product['variant'])
	    {
		    $SpecificsSet = array();
		    foreach ($product['variant'] as $kk => $vv)
		    {
			    $properties = json_decode($vv['variation'],true);
			    $pkey = array_keys($properties);
			    $pro_keys = $pkey;
			    foreach ($pro_keys as $i => $key)
			    {
				    if(isset($SpecificsSet[$key]) && @!in_array($properties[$key],$SpecificsSet[$key]))
				    {
					    $SpecificsSet[$key][] = $properties[$key];
				    } else if (!isset($SpecificsSet[$key])) {
                        $SpecificsSet[$key] = [];
                        $SpecificsSet[$key][] = $properties[$key];
                    }
			    }
		    }

		    $count=0;
		    foreach($SpecificsSet as $ssk => $spec)
		    {

			    if($spec)
			    {
				    $item['Variations']['VariationSpecificsSet'][ $count]['NameValueList']['Name']=$ssk;
				    foreach($spec as $ks => $speval)
				    {
					    $item['Variations']['VariationSpecificsSet'][ $count]['NameValueList']['Value'][$ks]=$speval;
				    }
			    }
			    $count = $count + 1;
		    }

		    foreach($product['variant'] as $vk=>$vart)
		    {
			    $item['Variations'][$vk]['Variation']['Quantity']=$vart['v_qty'];
			    $item['Variations'][$vk]['Variation']['SKU']=$vart['v_sku'];
			    $item['Variations'][$vk]['Variation']['StartPrice']=$vart['v_price'];
			    $item['Variations'][$vk]['Variation']['VariationProductListingDetails']['EAN']=$vart['ean'];
			    $item['Variations'][$vk]['Variation']['VariationProductListingDetails']['ISBN']=$vart['isbn'];
			    $item['Variations'][$vk]['Variation']['VariationProductListingDetails']['UPC']=$vart['upc'];
			    $variation = json_decode($vart['variation'], true);

			    if(is_array($variation) && !is_null($variation))
			    {
				    $kk=0;
				    foreach($variation as $name => $value)
				    {
					    $item['Variations'][$vk]['Variation']['VariationSpecifics'][$kk]['NameValueList']['Name']=$name;
					    $item['Variations'][$vk]['Variation']['VariationSpecifics'][$kk]['NameValueList']['Value']=$value;
					    $kk+1;
				    }
			    }
		    }
	    }
	    return $item;
    }
    /**
     * 设置促销活动
     */
    public function  promotionalSale($token,$promotion)
    {
        try{
            if($promotion['promotional_sale_id']==0)
            {
                $xmlData['Action']='Add';//Add,Delete,Update
            }else{
                $xmlData['Action']='Update';//Add,Delete,Update
            }

            //优惠多少钱
            if($promotion['promotion_type']==1)
            {
                $xmlData['PromotionalSaleDetails']['DiscountType']='Price';
                $xmlData['PromotionalSaleDetails']['DiscountValue']=$promotion['promotion_cash'];
            }elseif($promotion['promotion_type']==2){ //折扣
                $xmlData['PromotionalSaleDetails']['DiscountType']='Percentage';
                $xmlData['PromotionalSaleDetails']['DiscountValue']=$promotion['promotion_discount'];
            }

            //免运费
            if($promotion['promotion_trans']==1) //FreeShippingOnly
            {
                if($promotion['promotion_cash'] || $promotion['promotion_discount'])
                {
                    $xmlData['PromotionalSaleDetails']['PromotionalSaleType']='PriceDiscountAndFreeShipping';
                }else{
                    $xmlData['PromotionalSaleDetails']['PromotionalSaleType']='FreeShippingOnly';
                }
            }else{
                $xmlData['PromotionalSaleDetails']['PromotionalSaleType']='PriceDiscountOnly';
            }

            $xmlData['PromotionalSaleDetails']['PromotionalSaleStartTime']=$this->setEbayTimeToTime($promotion['start_date']);
            $xmlData['PromotionalSaleDetails']['PromotionalSaleEndTime']=$this->setEbayTimeToTime($promotion['end_date']);

            $xmlData['PromotionalSaleDetails']['PromotionalSaleID']=date('YmdHis',$promotion['start_date']);

            $xmlData['PromotionalSaleDetails']['PromotionalSaleName']=$promotion['model_name'];

            $xmlData['RequesterCredentials']['eBayAuthToken']=$token;
            $xmlData['ErrorLanguage']='zh_CN';
            $xmlData['Version']='1021';
            $xmlData['WarningLevel']='High';
            $xml = $this->xml->arrayToXml($xmlData, 'SetPromotionalSaleRequest',['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);

            $api = new EbayItemApi($token);

            $response = $api->sendRequest($xml, 'SetPromotionalSale');

            if($response['result'] && $response['data']['Ack'] !='Failure')
            {
                $PromotionalSaleID = $response['data']['PromotionalSaleID'];
                $this->EbayPromotionModel->save(['promotional_sale_id'=>$PromotionalSaleID],['id'=>$promotion['id']]);
                return ['result'=>true,'promotionalSaleID'=>$PromotionalSaleID];
            }else{
                return ['result'=>false,'promotionalSaleID'=>''];
            }
        }catch(Exception $exp){
            throw new Exception($exp->getMessage());
        }


        
        
    }
    
    /**
     * 设置item参与促销
     * @param $PromotionalSaleID bigint  促销活动id
     * @param $item_id string item_id 
     * @param $token varchar 授权token
     * @param $AllAuctionItems bool true|false
     * @param $AllFixedPriceItems bool true|false
     * @param $CategoryID 分类id
     * @param $StoreCategoryID 店铺分类id
     */
    public function  SetPromotionalSaleListings($PromotionalSaleID,$item_id,$token,$AllAuctionItems='false',$AllFixedPriceItems='false',$CategoryID=0,$StoreCategoryID=0)
    {

        try{
            $xmlData['Action']='Delete';//Add,Delete,Update
            $xmlData['AllAuctionItems']=$AllAuctionItems; //所有促销活动items
            $xmlData['AllFixedPriceItems']=$AllFixedPriceItems; //所有一口价items
            if($CategoryID>0)
            {
                $xmlData['CategoryID']=$CategoryID;
            }
            if($StoreCategoryID>0)
            {
                $xmlData['StoreCategoryID']=$StoreCategoryID;
            }

            $xmlData['PromotionalSaleID']=$PromotionalSaleID;
            $xmlData['PromotionalSaleItemIDArray']['ItemID']=$item_id;
            $xmlData['RequesterCredentials']['eBayAuthToken']=$token;
            $xmlData['ErrorLanguage']='zh_CN';
            $xmlData['Version']='1021';
            $xmlData['WarningLevel']='High';
            $xml = $this->xml->arrayToXml($xmlData, 'SetPromotionalSaleListingsRequest',['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);

            $api = new EbayItemApi($token);
            $response = $api->sendRequest($xml, 'SetPromotionalSaleListings');

            if($response['result'] && $response['data']['Ack'] !='Failure')
            {
                $update=[
                    'result'=>$response['data']['Ack'],
                    'message'=>'',
                ];
            }else{
                $update=[
                    'result'=>$response['data']['Ack'],
                    'message'=>(string)$response['data']['Errors']->LongMessage,
                ];
            }
            $this->EbayProductModel->save(['promotion_set_return'=> json_encode($update)],['item_id'=>$item_id]);

        }catch(Exception $exp){
            throw new Exception($exp->getMessage());
        } 
    }


	/**
     * 整型时间转换为2017-06-29T09:37:34.000Z
     * @param int $time
     * @return string 
     */
    public  function setEbayTimeToTime($time)
    {
        if(!is_integer($time))
        {
            $time = strtotime($time);
        }
        $time_str = $time ? date('Y-m-d',$time).'T'.date('H:i:s',$time).'.000Z' : 0;
        return $time_str;
    }
    /**
     * GM时间转换为整型时间
     * @param type $str 2017-03-02T09:13:05.000Z
     * @return int 
     */
    public  function ebayTimeToIntTime($str)
    {
        return strtotime(substr(str_replace('T', ' ', $str),0,19));
    }

	/**
	 * 上架商品
	 * @param $product
	 *
	 * @throws Exception
	 */
	public function RelistItem($product)
	{

        try{
            $item_id =$product['item_id'];
            $item =$this->setItemData($product);
            $xmlData['Item']=$item;
            $xmlData['RequesterCredentials']['eBayAuthToken']=$product['account']['token'];
            $xmlData['ErrorLanguage']='zh_CN';
            $xmlData['Version']='1021';
            $xmlData['WarningLevel']='High';

            if($product['listing_type']=='FixedPriceItem')
            {
                $request = 'RelistFixedPriceItemRequest';
                $callName = 'RelistFixedPriceItem';
            }else{
                $request = 'RelistItemRequest';
                $callName = 'RelistItem';
            }

            $xml = $this->xml->arrayToXml($xmlData, $request,['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);

            $api = new EbayItemApi($product['account']['token']);

            $response = $api->sendRequest($xml,$callName);

            if($response['result'] && $response['data']['Ack'] !='Failure')
            {
                $update=[
                    'listing_status'=>3,
                    'message'=>$response['data']['Ack'],
                ];
            }else{
                if(is_array($response['data']['Errors']))
                {
                    $update=[
                        'listing_status'=>4,
                        'message'=> json_encode($response['data']['Errors']),
                    ];
                }else{
                    $update=[
                        'listing_status'=>4,
                        'message'=>(string)$response['data']['Errors']->LongMessage,
                    ];
                }

            }
            $this->EbayProductModel->save($update,['item_id'=>$item_id]);

        }catch(Exception $exp){
 			throw new Exception($exp->getMessage());
		}
	}

    /**
     * 执行下架
     * @param $item
     * @param int $tortId
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public  function endItem($item, $tortId=0)
	{
	    $listing = EbayListing::field('goods_id,account_id,site')->where(['item_id'=>$item])->find();
	    if (empty($listing)) {
	        return false;
        }
	    $accountInfo = EbayAccount::get($listing['account_id']);
	    if (empty($accountInfo)) {
	        return false;
        }
        $accountInfo = $accountInfo->toArray();
	    $packApi = new EbayPackApi();
	    $api = $packApi->createApi($accountInfo, 'EndItem', $listing['site']);
	    $xml = $packApi->createXml(['item_id'=>$item]);
	    $response = $api->createHeaders()->__set('requesBody', $xml)->sendHttpRequest2();
	    if (!isset($response['EndItemResponse'])) {
	        return false;
        }
        $res = $response['EndItemResponse'];
	    if ($res['Ack'] == 'Failure') {
            $errMsg = (new EbayPublishHelper())->dealEbayApiError($response);
            if (isset($errMsg['1047'])) {
                EbayListing::update(['listing_status'=>11,'manual_end_time'=>time()], ['item_id'=>$item]);
            } else {
                EbayListing::where('item_id', '=', $item)->update(['listing_status' => 3]);//下架失败，仍然在线
            }
        } else if ($res['Ack'] == 'Success' || $res['Ack'] == 'Warning') {
	        EbayListing::update(['listing_status'=>11,'manual_end_time'=>time()], ['item_id'=>$item]);
        }
        if (isset($listing['end_type']) && $listing['end_type'] == 2) {//侵权下架失败回写
            $backWriteData = [
                'goods_id' => $listing['goods_id'],
                'goods_tort_id' => $tortId,
                'channel_id' => 1,
                'status' => ($response['Ack'] == 'Failure' ? 2 : 1),
            ];
            (new UniqueQueuer(\app\goods\queue\GoodsTortListingQueue::class))->push($backWriteData);//回写
        }
	}
    /**
     * ebay下架
     * @param array $items
     */
    public  function endItems($items,$redis)
    {
        if($redis instanceof RedisListing )
        {
            if(is_array($items))
            {
                foreach ($items as $key => $item) 
                {
                    $productInfo = $this->EbayProductModel->field('id,item_id,account_id')->with(['account'=>function($query){$query->field('id,token');}])->where(['item_id'=>$item])->find()->toArray();
                    
                    $xmlData['EndingReason']='OtherListingError';
                    $xmlData['ItemID']=$item;
                    $xmlData['RequesterCredentials']['eBayAuthToken']=$productInfo['account']['token'];
                    $xmlData['ErrorLanguage']='zh_CN';
                    $xmlData['Version']='1021';
                    $xmlData['WarningLevel']='High'; 
                    $xml = $this->xml->arrayToXml($xmlData, 'EndItemRequest',['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);
                    $api = new EbayItemApi($productInfo['account']['token']);
                    $return = $api->endItem($xml);
                    if($return['result']===true)
                    {
                        $redis->myZRem('findEbayProductById',$item); //删除缓存.
                        $this->EbayProductModel->update(['listing_status'=>9],['item_id'=>$item]);
                    }
                }
            }
        } 
    }
    /**
     * 修改listing的price和quanlity
     * @param array $products
     */
    public  function reviseInventoryStatus(array $products)
    {
        try{
            if(is_array($products))
            {
                foreach($products as $product)
                {
                    if(is_object($product))
                    {
                        $product= $product->toArray();
                    }

                    $token = json_decode($product['token'],true)[0];

                    $xmlData['InventoryStatus']['ItemID']=$product['item_id'];
                    $xmlData['InventoryStatus']['Quantity']=$product['quantity'];
                    $xmlData['InventoryStatus']['SKU']=$product['sku'];
                    $xmlData['InventoryStatus']['StartPrice']=$product['price'];
                    $xmlData['RequesterCredentials']['eBayAuthToken']=$token;
                    $xmlData['ErrorLanguage']='zh_CN';
                    $xmlData['Version']='1021';
                    $xmlData['WarningLevel']='High';
                    $xml = $this->xml->arrayToXml($xmlData, 'ReviseInventoryStatusRequest',['xmlns'=>'urn:ebay:apis:eBLBaseComponents']);

                    $api = new EbayItemApi($token);

                    $response = $api->sendRequest($xml, 'ReviseInventoryStatus');

                    if($response['result'] && $response['data']['Ack'] !='Failure')
                    {
                        $InventoryStatus = (array)$response['data']['InventoryStatus'];

                        if($this->inEbayListing(['item_id'=>$InventoryStatus['ItemID'],'listing_sku'=>$InventoryStatus['SKU']]))
                        {
                            $update=array();
                            $update['start_price']=$InventoryStatus['StartPrice'];
                            $update['quantity']=$InventoryStatus['Quantity'];
                            $this->EbayProductModel->where(['item_id'=>$InventoryStatus['ItemID'],'listing_sku'=>$InventoryStatus['SKU']])->update($update);
                        }else{
                            $update=array();
                            $update['v_price']=$InventoryStatus['StartPrice'];
                            $update['v_qty']=$InventoryStatus['Quantity'];
                            $productInfo = $this->EbayProductModel->field('id')->where(['item_id'=>$InventoryStatus['ItemID']])->find();
                            $this->EbayVariantModel->where(['listing_id'=>$productInfo['id'],'v_sku'=>$InventoryStatus['SKU']])->update($update);
                        }
                        $message = json_encode(['Ack'=>$response['data']['Ack']]);
                        $status=1;
                    }else{
                        $message = json_encode($response['data']['Errors']);
                        $status=2;
                    }
                    $log=[
                        'status'=>$status,
                        'message'=>$message,
                        'run_time'=> time(),
                    ];
                    ebayListingPriceQuanlity::where(['id'=>$product['id']])->update($log);
                }
            }
        }catch (Exception $exp)
        {
            throw new Exception($exp->getFile().$exp->getLine().$exp->getMessage());
        }

    }
    
    
    /**
     * 应用公共模块
     * @param type $data
     */
    public  function commonModule($data,$scene)
    {
        try{
            $products = json_decode($data,true);
    //        if($error = $this->validate->checkEdit($products, $scene))
    //        {
    //            return ['result'=>false,'message'=>$error];
    //        }
            $result = $this->sameAccountSite($products);
            if(!$result['result'])
            {
                return $result;
            }else{
                $products = $this->getCommonModuleData($products);

                if($products)
                {
                    foreach ($products as $product)
                    {

                        $product['listing_status']= 5 ; //更新了资料
                        $product['promotion_id'] = $product['mod_promotion'];

                        //更新ebay_listing_setting
                        $this->EbayProductSettingModel->allowField(true)->save($product, ['listing_id'=>$product['id']]);
                        //如果是多属性商品

                        if(isset($product['quantity']) && $product['quantity'])
                        {
                            if($this->EbayVariantModel->where(['listing_id'=>$product['id']])->find())
                            {
                                $this->EbayVariantModel->allowField(true)->save(['v_qty'=>$product['quantity']],['listing_id'=>$product['id']]);
                            }
                        }

                        //物流设置
                        if(isset($product['transport']) && $product['transport'])
                        {
                            $internationalShipping=[];
                            $shipping=[];
                            foreach ($product['transport'] as $k2 => $trans)
                            {
                                //国际物流
                                if($trans['inter'])
                                {
                                    $internationalShipping[$k2]['listing_id']=$product['id'];
                                    $internationalShipping[$k2]['shipping_service']=$trans['trans_code'];
                                    $internationalShipping[$k2]['shipping_service_cost']=$trans['cost'];
                                    $internationalShipping[$k2]['shipping_service_additional_cost']=$trans['add_cost'];
                                    $internationalShipping[$k2]['shiptolocation']=$trans['location'];
                                    if($this->EbayListingTransportModel->where(['listing_id'=>$product['id'],'shipping_service'=>$trans['trans_code']])->find())
                                    {
                                        $this->EbayListingTransportModel->allowField(true)->save($internationalShipping[$k2],['listing_id'=>$product['id'],'shipping_service'=>$trans['trans_code']]);
                                    }else{
                                        $this->EbayListingTransportModel->allowField(true)->save($internationalShipping[$k2]);
                                    }
                                }else{//国内物流
                                    $shipping[$k2]['listing_id']=$product['id'];
                                    $shipping[$k2]['shipping_service']=$trans['trans_code'];
                                    $shipping[$k2]['shipping_service_cost']=$trans['cost'];
                                    $shipping[$k2]['shipping_service_additional_cost']=$trans['add_cost'];
                                    $shipping[$k2]['extra_cost']=$trans['extra_cost'];
                                    if($this->EbayListingTransportInModel->where(['listing_id'=>$product['id'],'shipping_service'=>$trans['trans_code']])->find())
                                    {
                                        $this->EbayListingTransportInModel->allowField(true)->save($shipping[$k2],['listing_id'=>$product['id'],'shipping_service'=>$trans['trans_code']]);
                                    }else{
                                        $this->EbayListingTransportInModel->allowField(true)->save($shipping[$k2]);
                                    }
                                }
                            }
                        }
                        $this->EbayProductModel->allowField(true)->save($product,['id'=>$product['id']]);
                    }
                    return ['result'=>true,'message'=>'修改成功'];
                }else{
                    return ['result'=>false,'message'=>'数据格式'];
                }

            }

        }catch (JsonErrorException $exp){
            throw new JsonErrorException($exp->getFile().$exp->getLine().$exp->getMessage());
        }

    }
    /**
     * 获取公共模块对应的数据
     */
    public  function getCommonModuleData($products)
    {
       if(is_array($products))
       {
           foreach($products as &$product)
           {
               if($product['mod_style']) //刊登风格
               {
                   $template = EbayCommonTemplate::get(['id'=>$product['mod_style']]);
                   if(is_object($template)) $template = $template->toArray ();
                   if($template) $product['publish_style']=$template['content'];
               }
               //销售说明
               if($product['mod_sale']) 
               {
                   $sale_note = EbayModelSale::get(['id'=>$product['mod_sale']]);
                   if(is_object($sale_note)) $sale_note = $sale_note->toArray ();
                   if($sale_note)
                   {
                       $product['sale_note']=$sale_note['payment'].$sale_note['delivery_detail'].$sale_note['terms_of_sales'].$sale_note['about_us'].$sale_note['contact_us'];
                   }

               }
               
               //物流
               if($product['mod_trans']) 
               {
                   $transport = EbayCommonTrans::where(['id'=>$product['mod_trans']])->with(['detail'])->find();
                  
                   if(is_object($transport)) $transport = $transport->toArray ();
                   if($transport) $product['transport']=$transport['detail'];
               }
               //不送达地区
               if($product['mod_exclude']) 
               {
                   $exclude = EbayCommonExclude::where(['id'=>$product['mod_exclude']])->find();
                  
                   if(is_object($exclude)) $exclude = $exclude->toArray ();
                   if($exclude) $product['exclude']=$exclude['exclude'];
               }
               //位置所在
               if($product['mod_location']) 
               {
                   $location = EbayCommonLocation::where(['id'=>$product['mod_location']])->find();
                  
                   if(is_object($location)) $location = $location->toArray ();
                    if($location)
                    { 
                       $product['location']=$location['location'];
                       $product['country']=$location['country'];
                       $product['post_code']=$location['post_code'];
                    }
               }
               //销售说明
               if($product['mod_return']) 
               {
                   $return = EbayCommonReturn::where(['id'=>$product['mod_return']])->find();
                  
                   if(is_object($return)) $return = $return->toArray();
                   if($return)
                   {
                      $product['return_policy']=$return['return_policy'];
                      $product['return_type']=$return['return_type'];
                      $product['return_time']=$return['return_time'];
                      $product['extension']=$return['extension'];
                      $product['return_shipping_option']=$return['return_shipping_option'];
                      $product['restocking_fee_code']=$return['restocking_fee_code'];
                      $product['return_description']=$return['return_description'];
                       
                   }
               }
               
                //收款说明
               if($product['mod_receivables']) 
               {
                   $receive = EbayCommonReceivables::where(['id'=>$product['mod_receivables']])->find();
                  
                   if(is_object($receive)) $receive = $receive->toArray();
                   if($receive)
                   {
                      $product['pay_method']=$receive['pay_method'];
                      $product['autopay']=$receive['auto_pay'];
                      $product['payment_instructions']=$receive['payment_instructions'];
                   }
               }
               //备货时间
               if($product['mod_choice']) 
               {
                   $dispatch = EbayCommonChoice::where(['id'=>$product['mod_choice']])->find();
                  
                   if(is_object($dispatch)) $dispatch = $dispatch->toArray();
                   if($dispatch)
                   {
                      $product['choice_date']=$dispatch['choice_date'];
                   }
               }
               
               //取货
               if($product['mod_pickup']) 
               {
                   $pickup = EbayCommonPickup::where(['id'=>$product['mod_pickup']])->find();
                  
                   if(is_object($pickup)) $pickup = $pickup->toArray();
                   if($pickup)
                   {
                      $product['local_pickup']=$pickup['local_pickup'];
                   }
               }
               
               //是否使用图片橱窗
               if($product['mod_galley']) 
               {
                   $galley = EbayCommonGallery::where(['id'=>$product['mod_galley']])->find();
                  
                   if(is_object($galley)) $galley = $galley->toArray();
                   if($galley)
                   {
                      $product['picture_gallery']=$galley['picture_gallery'];
                   }
               }
               
               //不显示购买记录，private listing 
               if($product['mod_individual']) 
               {
                   $individual = EbayCommonIndividual::where(['id'=>$product['mod_individual']])->find();
                  
                   if(is_object($individual)) $individual = $individual->toArray();
                   if($individual)
                   {
                      $product['individual_listing']=$individual['individual_listing'];
                   }
               }
               
               //接收还价
               if($product['mod_bargaining']) 
               {
                   $bargain = EbayCommonIndividual::where(['id'=>$product['mod_bargaining']])->find();
                  
                   if(is_object($bargain)) $bargain = $bargain->toArray();
                   if($bargain)
                   {
                      //$product['individual_listing']=$bargain['individual_listing'];
                   }
               }
               //刊登数量
                if($product['mod_quantity']) 
               {
                   $quantity = EbayCommonQuantity::where(['id'=>$product['mod_quantity']])->find();
                  
                   if(is_object($quantity)) $quantity = $quantity->toArray();
                   if($quantity)
                   {
                      $product['quantity']=$quantity['quantity'];
                   }
               }              
           }
           return $products;
       }else{
           return null;
       }
    }
    /**
     * 判断商品是否属于同一个站点同一个账号
     * @param type $products
     */
    public  function sameAccountSite(array $products)
    {
        $list=[];
        foreach ($products as $key => $product) 
        {
            $info = $this->EbayProductModel->field('site,account_id,item_id')->where(['id'=>$product['id']])->find();
            if($info && is_object($info))
            {
                $info = $info->toArray();
            }
            $list[]=$info;
        }
        if($list && count($list) >1 )
        {
            $total = count($list);
            for($i=1;$i<$total ;++$i)
            {
                if($list[$i]['site'] !=  $list[$i-1]['site'] || $list[$i]['account_id'] !=  $list[$i-1]['account_id'])
                {
                    return ['result'=>false,'message'=>'item id['.$list[$i]['item_id'].']的账号或者站点与其他listing不一致'];
                }
            }
        }
        return ['result'=>true,'message'=>'没有异常'];
    }
    /**
     * 获取item相册
     */
    public  function ProductImages($ids)
    {
        $ids = explode(';', $ids);
        $return=[];
        try {
            $lists = EbayListing::whereIn('id', $ids)->column('id,spu,item_id', 'id');
            foreach ($ids as $key => $id) {
                $res = (new EbayPublish())->listingImgVersionO2N($id);
                if ($res['result'] === false) {
                    throw new Exception($res['message']);
                }
                $res = EbayPublish::seperateImgs($res['data']);
                if (isset($res['result'])) {
                    throw new Exception($res['message']);
                }
                foreach ($res['publishImgs'] as &$publishImg) {
                    $publishImg['base_url'] = 'https://img.rondaful.com/';
                }
                $return[] = [
                    'id' => $id,
                    'item_id' => $lists[$id]['item_id']??0,
                    'spu' => $lists[$id]['spu']??'',
                    'imgs' => $res['publishImgs']
                ];
            }
            return $return;
        } catch (\Exception $e) {
            return ['result'=>false,'message'=>$e->getMessage()];
        }
    }
    /**
     * 修改商品相册
     */
    public  function updateProductImages($data)
    {

        try{
            $products = json_decode($data,true);
            $EbayListingImage = new EbayListingImage;
            foreach($products as $product)
            {

                $images = explode(";", $product['images']);
                $listing_id = $product['id'];
                $dataSet = $this->dealImages($listing_id, $images);
                $EbayListingImage->destroy(['listing_id'=>$listing_id]);
                $EbayListingImage->saveAll($dataSet);
            }
            $message='更新成功';
            return $message;
        } catch (Exception $exp){
            throw  new Exception($exp->getMessage());
        }
       
    }
    /**
     * 处理商品相册
     * @param type $images
     */
    public  function dealImages($listing_id,array $images)
    {
        $image=[];
        foreach ($images as $key => $img) 
        {
            if($key==0)
            {
                $image[$key]['main']=1;
            }
            $image[$key]['listing_id']=$listing_id;
            $image[$key]['sort']=$key;
            $image[$key]['path']=$img;
        }
        return $image;
    }


    /**
     * 更新ebay_listing表或者ebay_variant表
     * @param array $data post数据
     * @param string $scene 验证场景
     */
    public function updateListingOrVariant($data,$scene)
    {
        try{
            $products = json_decode($data,true);

            if($error = $this->validate->checkEdit($products, $scene))
            {
                return ['result'=>false,'message'=>$error];
            }
            $user = \app\common\service\Common::getUserInfo();
            $userId = empty($user)?0:$user['user_id'];
            $update_date = time();

            foreach ($products as $product)
            {
                $product['listing_status']= 5 ; //更新了资料
                $product['user_id'] = $userId;
                $product['update_date'] = $update_date;
                if($variant = $this->EbayVariantModel->where(['listing_id'=>$product['id'],'channel_map_code'=>$product['listing_sku']])->find())//如果多属性
                {
                    if(is_object($variant))
                    {
                        $variant = $variant->toArray();

                    }
                    $update['v_price']=$product['start_price'];
                    $update['v_qty']=$product['quantity'];
                    EbayListingVariation::where('id','=',$variant['id'])->update($update);
                    //$this->EbayVariantModel->allowField(true)->save($update, ['id'=>$variant['id']]);
                    $quantity = $this->EbayVariantModel->where(['listing_id'=>$product['id']])->sum('v_qty');
                    EbayListing::where('id','=',$product['id'])->update(['listing_status'=>$product['listing_status'],'quantity'=>$quantity,'user_id'=>$userId,'update_date'=>$update_date]);
                    //$this->EbayProductModel->allowField(true)->save(['listing_status'=>$product['listing_status']],['id'=>$product['id']]);
                    //$totalQuantity = EbayListingVariation::where('')

                }else{
                    EbayListing::where('id','=',$product['id'])->update($product);
                    //$this->EbayProductModel->allowField(true)->save($product,['id'=>$product['id']]);
                }

            }
            return ['result'=>true,'message'=>'修改成功'];
        } catch (Exception $exp) {
            throw new exception($exp->getFile().$exp->getLine().$exp->getMessage());
        }
    }
    /**
     * 更新listing状态
     * @param int $status listing状态
     * @param array $where 更新条件
     */
    public  function updateListingStatus($status,$where)
    {
        $this->EbayProductModel->update(['listing_status'=>$status],$where);
    }
    
    
    /**
     * 查询是否存在ebay_listing中
     * @param type $data
     * @return boolean
     */
    public  function inEbayListing($where)
    {
        if($this->EbayProductModel->where($where)->find())
        {
            return true;
        }else{
            return false;
        }
    }


    /**
     * 下架商品
     * @param int $product_id
     * @return boolean
     */
    public function offlineAeProduct($product_id)
    {
        if(empty($product_id))
        {
            return false;
        }      
        $product = $this->EbayProductModel->where(['product_id'=>$product_id])->find();       
        if($product)
        {
            $account = $product->account->toArray(); 
            $product = $product->toArray();
            $api = AliexpressApi::instance($account)->loader('Product');
            $response = $api->offlineAeProduct(['productIds'=>$product_id]);
            if(isset($response['success']) && $response['success'])
            {
                return true;
            }else{
                return false;
            }  
        }else{
            return false;
        }  
    }
    
    /**
     * 上架商品
     * @param int $product_id
     * @return boolean
     */
    public function onlineAeProduct($product_id)
    {
        if(empty($product_id))
        {
            return false;
        }      
        $product = $this->EbayProductModel->where(['product_id'=>$product_id])->find();       
        if($product)
        {
            $account = $product->account->toArray(); 
            $product = $product->toArray();
            $api = AliexpressApi::instance($account)->loader('Product');
            $response = $api->onlineAeProduct(['productIds'=>$product_id]);
            if(isset($response['success']) && $response['success'])
            {
                return true;
            }else{
                return false;
            }  
        }else{
            return false;
        }  
    }
    
     /**
     * 修改产品数据
     */
    public  function editProductSettingData($data,$scene)
    {

        try{
            $products = json_decode($data,true);

            if($error =  $this->validate->checkSetting($products,$scene))
            {
                return ['result'=>false,'message'=>$error];
            }
            foreach ($products as $key => &$product)
            {
                if(isset($product['restart_time']))
                {
                    $product['restart_time'] = strtotime($product['restart_time']);
                }
                $product['listing_id']=$product['id'];
                unset($product['id']);
            }

            foreach ($products as $product)
            {
                $id = $product['listing_id'];
                unset($product['listing_id']);
                $this->EbayProductModel->update(['restart'=>$product['restart']],['id'=>$id]);
                unset($product['restart']);
                $this->EbayProductSettingModel->update($product,['id'=>$id]);
            }
            $message="修改成功";
            return ['result'=>true,'message'=>$message];
        } catch (Exception $exp){
            $message = $exp->getMessage();
            throw new Exception($message);
        }
    }

    /**
     * 自动补货设置
     */
    public  function ebayReplenishmentService($data,$scene)
    {

        try{
            $products = json_decode($data,true);

            if($error =  $this->validate->checkSetting($products,$scene))
            {
                return ['result'=>false,'message'=>$error];
            }
            foreach ($products as $key => &$product)
            {
                if(isset($product['restart_time']))
                {
                    $product['restart_time'] = strtotime($product['restart_time']);
                }
                $product['listing_id']=$product['id'];
                unset($product['id']);
            }

            foreach ($products as $product)
            {
                #$this->EbayProductSettingModel->update($product,['listing_id'=>$product['listing_id']]);
                $this->EbayProductModel->update(['replen'=>$product['replen']],['id'=>$product['listing_id']]);

            }
            $message="修改成功";
            return ['result'=>true,'message'=>$message];
        } catch (Exception $exp){
            $message = $exp->getMessage();
            throw new Exception($message);
        }
    }
    
    /**
     * 修改产品数据
     */
    public  function editVariantData($data,$scene)
    {
         

        try{
            $products = json_decode($data,true);

            if($error =  $this->validate->checkEdit($products,$scene))
            {
                return ['result'=>true,'message'=>$error];
            }

            foreach ($products as $key => &$product)
            {
                $product['listing_status']= 5 ; //更新了资料
            }
            $this->EbayProductModel->variant()->update($products);
            $message="修改成功";
        } catch (Exception $exp){
            $message = $exp->getMessage();
        }
        return $message;    
    }
    
    /**
     * 修改产品数据
     */
    public  function editProductData($data,$scene)
    {
         try{
            $products = json_decode($data,true);

            if($error =  $this->validate->checkEdit($products,$scene))
            {
                return ['result'=>false,'message'=>$error];
            }
             $user = \app\common\service\Common::getUserInfo();
             $userId = empty($user)?0:$user['user_id'];
             $update_date = time();
            foreach($products as $key=>$product) {
                if (!isset($product['id']) && !isset($product['item_id'])) {
                    return ['result' => false, 'message' => '必须有id或item_id'];
                }else if((isset($product['id'])&&!is_numeric($product['id'])) || (isset($product['item_id'])&&!is_numeric($product['item_id'])) ){
                    return ['result' => false, 'message' => 'id或item_id类型有误'];
                }
            }
            foreach ($products as $key => $product)
            {
                $listInfo = [];
                if (isset($product['id'])) {
                    $listInfo = EbayListing::get($product['id']);
                    if (empty($listInfo)) {
                        throw new Exception('获取信息失败');
                    }
                    $listInfo = $listInfo->toArray();
                }
                $product['user_id'] = $userId;
                $product['update_date'] = $update_date;
                !empty($listInfo['draft']) && $product['listing_status']= 5 ; //更新了资料
                #$rs = $this->EbayProductModel->allowField(true)->isUpdate(true)->save($product,['id'=>$product['id']]);
//                isset($product['store']) && $updateData['store_category_id'] = $product['store'];
//                isset($product['second_store']) && $updateData['store_category2_id'] = $product['second_store'];
                $product['user_id'] = $userId;
                $product['update_date'] = time();
                if(isset($product['item_id'])) {
                    $product['listing_status'] = 5;
                    $rs = $this->EbayProductModel->where(['item_id' => $product['item_id']])->update($product);
                }else if(isset($product['id'])){
                    $rs = $this->EbayProductModel->where(['id' => $product['id']])->update($product);
                }
            }
            $message="修改成功";
            return ['result'=>true,'message'=>$message];
        } catch (Exception $exp){
             $message = $exp->getMessage();
            return ['result'=>false,'message'=>$message];
        } 
    }
    /*
     * 获取ebay在线listing是否修改了
     */
    public function getEbayProductUpdateStatus($item_id,$exp,$status=5)
    {     
        $where=[
            'item_id'=>['eq',$item_id],
            'listing_status'=>[$exp,$status],
        ];
        return $this->EbayProductModel ->field('item_id')->where($where)->find();
    }

    /*
    *  应用公共模块数据
    */
    public function saveCommonModule($data){
        try{
            $params = json_decode($data,true);
            $list = [];
            $set = [];
            foreach ($params as $param) {
                $info = (new EbayPublishHelper())->applyCommonTemplate($param);
                $info['list']['id'] = $param['id'];
                $info['set']['id'] = $param['id'];
                $list[] = $info['list'];
                $set[] = $info['set'];
            }
            (new EbayListing())->saveAll($list);
            (new EbayListingSetting())->saveAll($set);
//            foreach($params as $k => $param){
//                $id = $param['id'];unset($param['id']);
//                $list = $this->EbayProductModel->where(['id'=>$id])->find();
//                $setInfo = [];
//                #物流
//                if($param['mod_trans'] ){
//                    $comTrans = (new EbayCommonTransDetail())->where(['trans_id'=>$param['mod_trans']])->select();
//                    $shipping = [];#国内物流
//                    $interShipping = [];#国际物流
//                    $i=0;$k=0;
//                    foreach($comTrans as $trans){
//                        if($trans['inter']==1){#国际物流
//                            $interShipping[$i]['shiptolocation'] = $trans['location']=="Worldwide"?"Worldwide":
//                            json_decode($trans['location'],true);
//                            $interShipping[$i]['shipping_service'] = $trans['trans_code'];
//                            $interShipping[$i]['shipping_service_cost'] = $trans['cost'];
//                            $interShipping[$i]['shipping_service_additional_cost'] = $trans['add_cost'];
//                            $i++;
//                        }else{#国内物流
//                            $shipping[$k]['extra_cost'] = $trans['extra_cost'];
//                            $shipping[$k]['shipping_service'] = $trans['trans_code'];
//                            $shipping[$k]['shipping_service_cost'] = $trans['cost'];
//                            $shipping[$k]['shipping_service_additional_cost'] = $trans['add_cost'];
//                            $k++;
//                        }
//                    }
//                    $setInfo['shipping'] = json_encode($shipping);
//                    $setInfo['international_shipping'] = json_encode($interShipping);
//                }
//                #不送达地区
//                if($param['mod_exclude']){
//                    $exclude = (new EbayCommonExclude())->where(['id'=>$param['mod_exclude']])->find();
//                    $setInfo['exclude_location'] = $exclude['exclude'];
//                }
//                #商品所在地
//                if($param['mod_location']){
//                    $location = (new EbayCommonLocation())->where(['id'=>$param['mod_location']])->find();
//                    $param['location'] = $location['location'];
//                    $param['country'] = $location['country'];
//                    $setInfo['postal_code'] = $location['post_code'];
//                }
//                #退货
//                if($param['mod_return']){
//                    $return = (new EbayCommonReturn())->where(['id'=>$param['mod_return']])->find();
//                    if($return){
//                        $param['return_time'] = $return['return_time'];
//                        $setInfo['return_policy'] = $return['return_policy'];
//                        $setInfo['return_type'] = $return['return_type'];
//                        $setInfo['extended_holiday'] = $return['extension'];
//                        $setInfo['return_shipping_option'] = $return['return_shipping_option'];
//                        $setInfo['restocking_fee_code'] = $return['restocking_fee_code'];
//                        $setInfo['return_description'] = $return['return_description'];
//                    }
//                }
//                #买家限制
//                if($param['mod_refuse']){
//                    $refuse = (new EbayCommonRefuseBuyer())->where(['id'=>$param['mod_refuse']])->find();
//                    if($refuse){
//                        $param['disable_buyer'] = $refuse['refuse'];#是否开启买家限制
//                        $buyerRequirmentDetails['credit'] = $refuse['credit'];#信用限制
//                        $buyerRequirmentDetails['strikes'] = $refuse['strikes'];#未付款限制
//                        $buyerRequirmentDetails['violations'] = $refuse['violations'];#违反政策相关
//                        $buyerRequirmentDetails['link_paypal'] = $refuse['link_paypal'];#paypal限制
//                        $buyerRequirmentDetails['registration'] = $refuse['registration'];#是否限制运送范围
//                        $buyerRequirmentDetails['requirements'] = $refuse['requirements'];
//                        $buyerRequirmentDetails['strikes_count'] = $refuse['strikes_count'];
//                        $buyerRequirmentDetails['strikes_period'] = $refuse['strikes_period'];
//                        $buyerRequirmentDetails['minimum_feedback'] = $refuse['minimum_feedback'];
//                        $buyerRequirmentDetails['violations_count'] = $refuse['violations_count'];
//                        $buyerRequirmentDetails['violations_period'] = $refuse['violations_period'];
//                        $buyerRequirmentDetails['minimum_feedback_score'] = $refuse['minimum_feedback'];
//                        $buyerRequirmentDetails['requirements_max_count'] = $refuse['requirements_max_count'];
//                        $buyerRequirmentDetails['requirements_feedback_score'] = $refuse['requirements_feedback_score'];
//                        $setInfo['buyer_requirment_details'] = json_encode([$buyerRequirmentDetails]);
//                    }
//                }
//                #收款选项
//                if($param['mod_receivables']){
//                    $receivables = (new EbayCommonReceivables())->where(['id'=>$param['mod_receivables']])->find();
//                    if($receivables){
//                        $setInfo['payment_method'] = $receivables['pay_method'];#付款方式
//                        $setInfo['payment_instructions'] = $receivables['payment_instructions'];#支付说明
//                        $param['autopay'] = $receivables['auto_pay'];#自动付款
//                    }
//                }
//                #备货期
//                if($param['mod_choice']){
//                    $choice = (new EbayCommonChoice())->where(['id'=>$param['mod_choice']])->find();
//                    if($choice){
//                        $param['dispatch_max_time'] = $choice['choice_date'];
//                    }
//                }
//                #上门提货
//                if($param['mod_pickup']){
//                    $pickup = (new EbayCommonPickup())->where(['id'=>$param['mod_pickup']])->find();
//                    if($pickup){
//                        $setInfo['local_pickup'] = $pickup['local_pickup'];
//                    }
//                }
//                #橱窗展示
//                if($param['mod_galley']){
//                    $galley = (new EbayCommonGallery())->where(['id'=>$param['mod_galley']])->find();
//                    if($galley){
//                        $param['picture_gallery'] = $galley['picture_gallery'];
//                    }
//                }
//                #私人物品
//                if($param['mod_individual'] ){
//                    $individual = (new EbayCommonIndividual())->where(['id'=>$param['mod_individual']])->find();
//                    if($individual){
//                        $param['private_listing'] = $individual['individual_listing'];
//                    }
//                }
//                #买家还价
//                if($param['mod_bargaining']){
//                    $bargaining = (new ebayCommonBargaining())->where(['id'=>$param['mod_bargaining']])->find();
//                    if($bargaining){
//                        $param['best_offer'] = $bargaining['best_offer'];
//                        $setInfo['auto_accept_price'] = $bargaining['accept_lowest_price'];
//                        $setInfo['minimum_accept_price'] = $bargaining['reject_lowest_price'];
//                    }
//                }
//                #库存
//                if($param['mod_quantity'] ){
//                    $quantity = (new EbayCommonQuantity())->where(['id'=>$param['mod_quantity']])->find();
//                    if($quantity){
//                        $param['quantity'] = $quantity['quantity'];
//                    }
//                }
//                $this->EbayProductModel->where(['id'=>$id])->update($param);
//                $this->EbayProductSettingModel->where(['id'=>$id])->update($setInfo);
//            }
            return ['result'=>true,'message'=>'修改成功'];
        }catch(Exception $e){
            return ['result'=>false,'message'=>$e->getFile().$e->getLine().$e->getMessage()];
        }

    }

    public function syncItem($itemId,$accountId)
    {
        try {
            $res = (new EbayPublishHelper())->getItem($itemId);
            if ($res['result'] === false) {
                return $res;
            }
            $res = $res['data'];
            if ($res['Ack'] == 'Failure') {
                $errMsg = (new EbayPublishHelper())->dealEbayApiError($res);
                if (isset($errMsg[17])) {
                    EbayPublishHelper::updateListingStatusWithErrMsg('ended',0,['item_id'=>$itemId]);
                    return ['result'=>true,'message'=>'同步成功'];
                }
                throw new Exception(json_encode($errMsg));
            }
            $listingHelper = new EbayListingCommonHelper($accountId);
            $listingData = $listingHelper->syncEbayListing($res['Item']);
            $listingHelper->syncListingData($listingData);
            return ['result'=>true,'message'=>'同步成功'];
        } catch (\Exception $e) {
            return ['result'=>false, 'message'=>$e->getFile().'|'.$e->getLine().'|'.$e->getMessage()];
        }
    }

}
