<?php
/**
 * User: rocky
 * Date: 2017/10/14
 */

namespace app\publish\queue;

use service\ebay\EbayApi;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Db;
use think\cache\driver;
use app\common\cache\Cache;
use think\Exception;
use app\publish\service\EbayService;
use app\common\model\ebay\EbayActionLog;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\ebay\EbayListingSetting;
use app\common\service\UniqueQueuer;
use app\publish\queue\EbayGetItemQueue;
use app\publish\task\EbayPublishItem;
use app\common\service\EbayCommonVariables;


class EbayUpdateItemJob extends SwooleQueueJob 
{

    private $acInfo;
    private $ebayApi;
    private $xml="";
    private $ebayListMod;
    private $ebaySetMod;
    private $ebayVarMod;
    private $ebayLogMod;
    private $verb;
    private $token;
    private $publish;
    private $returnTime;
    private $returnShippingOption;


    public function getName():string
    {
        return 'ebay更新线上listing信息';
    }
    public function getDesc():string
    {
        return 'ebay更新线上listing信息';
    }
    public function getAuthor():string
    {
        return 'zengsh';
    }
    
    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function init(){
        $this->ebayListMod = new EbayListing;
        $this->ebaySetMod = new EbayListingSetting;
        $this->ebayVarMod = new EbayListingVariation;
        $this->ebayLogMod = new EbayActionLog;
        $this->publish = new EbayPublishItem;
        #接受退货周期:(1 Days_14,2 Days_30,3 Days_60,4 Months_1)
        $this->returnTime = [1=>'Days_14',2=>'Days_30',3=>'Days_60',4=>'Months_1'];
        #ShippingCostPaidByOption 运费承担方:(1: Buyer, 2: Seller)
        $this->returnShippingOption = [1=>'Buyer',2=>'Seller'];
    }

    public  function execute()
    {
        set_time_limit(0);
        try{
            $id = $this->params;
            #$id = 528;
            #var_dump($id);die;
            if($id)
            {
                throw new Exception('获取日志id失败');
            }
            $rows = $this->ebayLogMod->where(['id'=>$id])->find();
            #获取账号信息
            $this->acInfo = Cache::store('EbayAccount')->getTableRecord($rows['account_id']);
            if ($rows['api_type'] == 3) {

            } else {
                $list = $this->ebayListMod->field("id,item_id,listing_type")->where(['item_id'=>$rows['item_id'],'draft'=>0])->find();
                if($rows && $this->acInfo){
                    $config = $this->createConFig($this->acInfo,$rows);
                    $config['verb'] = $this->createVerb($rows,$list);
                    $this->ebayApi = new EbayApi($config);
                    $this->xml = $this->createXml($this->verb,$rows);
                    $resText = $this->ebayApi->createHeaders()->__set("requesBody",$this->xml)->sendHttpRequest2();
                    #echo "<pre>";print_r($resText);#die;
                    $this->processingReponse($resText,$rows,$list);
                }
            }
        }catch (Exception $e) {
            throw new Exception($e->getFile()."|".$e->getLine()."|".$e->getMessage());
        }
    }

    public function upMessage($reponse)
    {
        $errorsStr = isset($reponse['Errors'])?$reponse['Errors']:[];
        $msg = [];
        $message = "";
        if(!empty($errorsStr)){
            $errors = isset($errorsStr[0])?$errorsStr:[$errorsStr];
            foreach($errors as $err){
                if($err["SeverityCode"]=="Error" || $err["SeverityCode"]=="Warning"){#致命错误
                    $msg[] = $err['SeverityCode'].":".$err['ErrorCode'].": ".$err["LongMessage"];
                }
            }
        }
        if($msg){
            $message .=implode("\n",$msg); 
        }else{
            $message .="success";
        }
        return $message;
    }

    public function createConFig(&$acInfo,&$rows)
    {
        $tokenArr = json_decode($acInfo['token'],true);
        $this->token = trim($tokenArr[0])?$tokenArr[0]:$acInfo['token'];
        $config['devID']=$acInfo['dev_id'];
        $config['appID']=$acInfo['app_id'];
        $config['certID']=$acInfo['cert_id'];
        $config['userToken']=$this->token;
        $config['compatLevel']=957;
        $config['siteID']=$rows['site'];
        $config['appMode']=0;
        $config['account_id']=$acInfo['id'];
        return $config;
    }

    public function createVerb(&$rows,&$list)
    {
        if($list['listing_type']==1){#固定价格
            $action = json_decode($rows['new_val'],true);
            if(isset($action['quantity']) && isset($action['price']) && $rows['api_type']==1){
                $this->verb = "ReviseInventoryStatus";
            }else{
                $this->verb = "ReviseFixedPriceItem";
            }
        }else{#拍卖
            $this->verb = "ReviseItem";
        }
        return $this->verb;
    }

    public function processingReponse($resText,$rows,$list)
    {
        if($this->verb == "ReviseInventoryStatus"){
            $reponse = isset($resText['ReviseInventoryStatusResponse'])?$resText['ReviseInventoryStatusResponse']:[];
        }else if($this->verb == "ReviseFixedPriceItem"){
            $reponse = isset($resText['ReviseFixedPriceItemResponse'])?$resText['ReviseFixedPriceItemResponse']:[];
        }else if($this->verb == "ReviseItem"){
            $reponse = isset($resText['ReviseItemResponse'])?$resText['ReviseItemResponse']:[];
        }
        $message = $this->upMessage($reponse);
        $up['message'] = $message;
        if($reponse['Ack']=="Failure"){#修改失败
            $up['status'] = 3;
            $this->ebayListMod->where(['id'=>$list['id']])->update(['listing_status'=>6]);
        }else if($reponse['Ack']=="Warning"){#有告警
            $up['status'] = 2;
            (new UniqueQueuer(EbayGetItemQueue::class))->push($rows['account_id'].','.$list['item_id'],time()+60);
        }else if($reponse['Ack']=="Success"){#修改成功
            $up['status'] = 2;
            (new UniqueQueuer(EbayGetItemQueue::class))->push($rows['account_id'].','.$list['item_id'],time()+60);
        }
        $this->ebayLogMod->where(['id'=>$rows['id']])->update($up);
    }

    public function createXml(&$verb,&$rows)
    {   
        if($verb=="ReviseInventoryStatus"){#修改一口价价格与库存
            return $this->createReviseInventoryStatusXml($rows);
        }else if($verb == "ReviseFixedPriceItem"){#修改一口价其他信息
            return $this->createReviseFixedPriceItemXml($rows);
        }else if($verb == "ReviseItem"){#修改拍卖信息
            return $this->createReviseItemXml($rows);
        }
    }

    public function createReviseInventoryStatusXml(&$params)
    {
        $xml ='<?xml version="1.0" encoding="utf-8"?>'."\n";
        $xml.='<ReviseInventoryStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents">'."\n";
        $xml.='<RequesterCredentials>'."\n";
        $xml.='<eBayAuthToken>'.$this->token.'</eBayAuthToken>'."\n";
        $xml.='</RequesterCredentials>'."\n";
        $action = json_decode($params['new_val'],true);
        $xml.='<InventoryStatus>'."\n";
        $xml.='<ItemID>'.$params['item_id'].'</ItemID>'."\n";
        $xml.='<SKU>'.$params['listing_sku'].'</SKU>'."\n";
        if(isset($action['quantity'])){
            $xml.='<Quantity>'.$action['quantity'].'</Quantity>'."\n";
        }
        if(isset($action['price'])){
            $xml.='<StartPrice>'.$action['price'].'</StartPrice>'."\n";
        }else if(isset($action['price']['start_price'])){
            $xml.='<StartPrice>'.$action['price']['start_price'].'</StartPrice>'."\n";
        }
        $xml.='</InventoryStatus>'."\n";
        $xml.='</ReviseInventoryStatusRequest>'."\n";
        return $xml;
    }



    public function createReviseFixedPriceItemXml(&$params)
    {
        $params = $params->toArray();
        $Variables = (new EbayCommonVariables())->__get('variables');
        $xml = "";
        $item ="<ItemID>".$params['item_id']."</ItemID>\n";
        $item.="<SKU>".$params['listing_sku']."</SKU>\n";
        $action = json_decode($params['new_val'],true);#echo "<pre>";print_r($action);die;
        #更新标题
        if(isset($action['title'])) $item.="<Title><![CDATA[".$action['title']['title']."]]></Title>\n";
        
        #更新描述
        if(isset($action['description'])){
            $item.="<Description><![CDATA[".$action['description']['description']."]]></Description>\n";
            $item.="<DescriptionReviseMode>Replace</DescriptionReviseMode>\n";
        }

        #价格
        if(isset($action['price'])){
            $item.="<StartPrice>".$action['price']."</StartPrice>\n";
        }
        #库存
        if(isset($action['quantity'])){
            $item.="<Quantity>".$action['quantity']['quantity']."</Quantity>\n";
        }else if(isset($action['mod_quantity'])){
            $item.="<Quantity>".$action['mod_quantity']."</Quantity>\n";
        }

        #备货
        if(isset($action['dispatch_max_time'])){
            $item.="<DispatchTimeMax>".$action['dispatch_max_time']."</DispatchTimeMax>\n";#发货处理时间
        }
        #橱窗展示
        if(isset($action['picture_gallery'])){
            $item.="<GalleryType>".$Variables['pictureGallery'][$action['picture_gallery']]."</GalleryType>\n";
        }

        #私人物品
        if(isset($action['private_listing'])){
            $item.="<PrivateListing>true</PrivateListing>\n";
        }

        #买家还价
        if(isset($action['best_offer'])){
            $bestOffer = $action['best_offer'];
            if($bestOffer['best_offer']){
                $item.="<ListingDetails>\n";
                $item.="<BestOfferAutoAcceptPrice>{$bestOffer['auto_accept_price']}</BestOfferAutoAcceptPrice>\n";
                $item.="<MinimumBestOfferPrice>{$bestOffer['minimum_accept_price']}</MinimumBestOfferPrice>\n";
                $item.="</ListingDetails>\n";
            }
        }

        #类目属性
        if(isset($action['specifics'])){
            $specifics = $action['specifics'];
            $item.="<ItemSpecifics>\n";
            foreach($specifics as $spec){
                $item.="<NameValueList>\n";
                $item.="<Name>".htmlspecialchars($spec['attr_name'])."</Name>\n";
                $attrValue = $spec['attr_value'];
                if(is_array($attrValue)){
                    foreach($attrValue as $atrV){
                        $item.="<Value>".htmlspecialchars($atrV)."</Value>\n";
                    }
                }else{
                    $item.="<Value>".htmlspecialchars($attrValue)."</Value>\n";
                }
                $item.="</NameValueList>\n";
            }
            $item.="</ItemSpecifics>\n";

        }
        #修改店铺分类
        if(isset($action['store'])){
            $item.="<Storefront>\n";
            $item.="<StoreCategoryID>".$action['store']['store']."</StoreCategoryID>\n";
            // $item.='<StoreCategoryName></StoreCategoryName>';
            $item.="<StoreCategory2ID>".$action['store']['second_store']."</StoreCategory2ID>\n";
            // $item.='<StoreCategory2Name></StoreCategory2Name>';
            $item.="</Storefront>\n";
        }

        #风格模板
        if(isset($action['style'])){
            $desc = $this->publish->replaceDescription($action['style']['desc'],$action['style']['images'],
                $action['style']['title'],$action['style']['style_id'],$action['style']['sale_id']);
            $item.="<Description><![CDATA[".$desc."]]></Description>\n";
            $item.="<DescriptionReviseMode>Replace</DescriptionReviseMode>\n";
        }

        #国内物流
        if(isset($action['transin'])){
            $item.="<ShippingDetails>\n";
            #不送达地区
            if(isset($action["exclude_location"])){
                $tempEx = json_decode($action['exclude_location'],true);
                $exShip = is_array($tempEx)?$tempEx:json_decode($tempEx,true);
                foreach($exShip as $kes => $ves){
                    $item.="<ExcludeShipToLocation>{$ves}</ExcludeShipToLocation>\n";
                }
            }
            #国内物流
            foreach($action['transin'] as $k2 => $tranin){
                $item.="<ShippingServiceOptions>\n";
                $item.="<ShippingService>{$tranin["shipping_service"]}</ShippingService>\n";
                if(intval($tranin['shipping_service_cost'])){#不免运费
                    $item.="<ShippingServiceAdditionalCost>".$tranin["shipping_service_additional_cost"]
                    ."</ShippingServiceAdditionalCost>\n";
                    $item.="<ShippingServiceCost>{$tranin["shipping_service_cost"]}</ShippingServiceCost>\n";
                }else{#免运费
                    $item.="<FreeShipping>true</FreeShipping>\n";
                }
                if(intval($tranin['extra_cost'])){#额外收费
                    $item.="<ShippingSurcharge>".$tranin['extra_cost']."</ShippingSurcharge>\n";
                }
                $item.="<ShippingServicePriority>".$k2."</ShippingServicePriority>\n";#排序方式
                $item.="</ShippingServiceOptions>\n";
            }
            #国际物流
            if(isset($action['trans'])){
                foreach($action['trans'] as $k1 => $tran){
                    $item.="<InternationalShippingServiceOption>\n";
                    $item.="<ShippingService>".$tran['shipping_service']."</ShippingService>\n";
                    $item.="<ShippingServiceAdditionalCost>".$tran['shipping_service_additional_cost']."</ShippingServiceAdditionalCost>\n";
                    $item.="<ShippingServiceCost>".$tran['shipping_service_cost']."</ShippingServiceCost>\n";
                    $item.="<ShippingServicePriority>".$k1."</ShippingServicePriority>\n";
                    if($tran['shiptolocation']){
                        $ships = explode(",",$tran['shiptolocation']);
                        foreach($ships as $ship){
                            $item.="<ShipToLocation>{$ship}</ShipToLocation>\n";
                        }
                    }
                    $item.="</InternationalShippingServiceOption>\n";
                }
            }
            $item.="</ShippingDetails>\n"; 
        }else if(!isset($action['transin']) && isset($action['trans'])){
            $item.="<ShippingDetails>\n";
            #不送达地区
            if(isset($action["exclude_location"])){
                $tempEx = json_decode($action['exclude_location'],true);
                $exShip = is_array($tempEx)?$tempEx:json_decode($tempEx,true);
                foreach($exShip as $kes => $ves){
                    $item.="<ExcludeShipToLocation>{$ves}</ExcludeShipToLocation>\n";
                }
            }
            foreach($action['trans'] as $k1 => $tran){
                $item.="<InternationalShippingServiceOption>\n";
                $item.="<ShippingService>".$tran['shipping_service']."</ShippingService>\n";
                $item.="<ShippingServiceAdditionalCost>".$tran['shipping_service_additional_cost']."</ShippingServiceAdditionalCost>\n";
                $item.="<ShippingServiceCost>".$tran['shipping_service_cost']."</ShippingServiceCost>\n";
                $item.="<ShippingServicePriority>".$k1."</ShippingServicePriority>\n";
                if($tran['shiptolocation']){
                    $ships = explode(",",$tran['shiptolocation']);
                    foreach($ships as $ship){
                        $item.="<ShipToLocation>{$ship}</ShipToLocation>\n";
                    }
                }
                $item.="</InternationalShippingServiceOption>\n";
            }
            $item.="</ShippingDetails>\n";
        }

        if(!isset($action['transin']) && !isset($action['trans']) && isset($action['exclude_location'])){
            $item.="<ShippingDetails>\n";
            if(isset($action["exclude_location"])){
                $tempEx = json_decode($action['exclude_location'],true);
                $exShip = is_array($tempEx)?$tempEx:json_decode($tempEx,true);
                foreach($exShip as $kes => $ves){
                    $item.="<ExcludeShipToLocation>{$ves}</ExcludeShipToLocation>\n";
                }
            }
            $item.="</ShippingDetails>\n";
        }

        #商品所在地
        if(isset($action['location'])){
            $item.="<Location>".$action['location']['location']."</Location>\n";#发货地
            $item.="<PostalCode>".$action['location']['postal_code']."</PostalCode>\n";#邮编
            $item.="<Country>".$action['location']['country']."</Country>\n";#国家
        }

        #退货
        if(isset($action['return'])){
            $return = $action['return'];
            if(trim($return['return_policy'])){
                $item.="<ReturnPolicy>\n";
                $item.="<ReturnsAcceptedOption>ReturnsAccepted</ReturnsAcceptedOption>\n";
                if($return['return_description']){#退货说明
                    $item.="<Description>{$return['return_description']}</Description>\n";
                }
                if($return['extended_holiday']){#节假日延期
                    $item.="<ExtendedHolidayReturns>true</ExtendedHolidayReturns>\n";
                }
                if($return['return_type']){#退货方式
                    $item.="<RefundOption>{$return['return_type']}</RefundOption>\n";
                }
                if($return['return_time']){#接受退货周期
                    $item.="<ReturnsWithinOption>".$this->returnTime[$return['return_time']]."</ReturnsWithinOption>\n";
                }
                if($return['return_shipping_option']){#运费承担方:Buyer,Seller
                    $item.="<ShippingCostPaidByOption>".$this->returnShippingOption[$return['return_shipping_option']]."</ShippingCostPaidByOption>\n";
                }
                if($return['restocking_fee_code']){#折旧费:NoRestockingFee,Percent_10,Percent_15,Percent_20
                    $item.="<RestockingFeeValueOption>{$return['restocking_fee_code']}</RestockingFeeValueOption>\n";
                }
                $item.="</ReturnPolicy>\n";
            }
        }

        #图片
        if(isset($action['images'])){
            $images = explode(';',$action['images']);
            $item.="<PictureDetails>\n";
            foreach($images as $img){
                $item.="<PictureURL>{$img}</PictureURL>\n";
            }
            $item.="</PictureDetails>\n";
        }

        #买家限制
        if(isset($action['buyer_requirment'])){
            $buRequirment = $action['buyer_requirment'];
            $item.="<BuyerRequirementDetails>\n";
            if(isset($buRequirment['link_paypal']) && $buRequirment['link_paypal']){#paypal限制 0否 1是
                $item.="<LinkedPayPalAccount>true</LinkedPayPalAccount>\n";
            }

            if(isset($buRequirment['violations']) && $buRequirment['violations']){#违反政策相关
                $item.="<MaximumBuyerPolicyViolations>\n";
                if(isset($buRequirment['violations_count']))$item.="<Count>{$buRequirment['violations_count']}</Count>\n";
                if(isset($buRequirment['violations_period']))$item.="<Period>{$buRequirment['violations_period']}</Period>\n";
                $item.="</MaximumBuyerPolicyViolations>\n";
            }

            if(isset($buRequirment['requirements']) && $buRequirment['requirements']){#次数限制
                $item.="<MaximumItemRequirements>\n";
                if(isset($buRequirment['requirements_max_count'])){
                    $item.="<MaximumItemCount>{$buRequirment['requirements_max_count']}</MaximumItemCount>\n";
                }
                if(isset($buRequirment['minimum_feedback']) && $buRequirment['minimum_feedback']){#评分限制
                    if(isset($buRequirment['minimum_feedback_score'])){
                        $item.="<MinimumFeedbackScore>{$buRequirment['minimum_feedback_score']}</MinimumFeedbackScore>\n";
                    }
                }
                $item.="</MaximumItemRequirements>\n";
            }

            if(isset($buRequirment['strikes']) && $buRequirment['strikes']){#未付款限制
                $item.="<MaximumUnpaidItemStrikesInfo>\n";
                if(isset($buRequirment['strikes_count'])) $item.="<Count>{$buRequirment['strikes_count']}</Count>\n";
                if(isset($buRequirment['strikes_period'])) $item.="<Period>{$buRequirment['strikes_period']}</Period>\n";
                $item.="</MaximumUnpaidItemStrikesInfo>\n";
            }

            if(isset($buRequirment['credit']) && $buRequirment['credit']){#信用限制
                if(isset($buRequirment['requirements_feedback_score'])){
                    $item.="<MinimumFeedbackScore>{$buRequirment['requirements_feedback_score']}</MinimumFeedbackScore>\n";
                }
            }

            if(isset($buRequirment['registration']) && $buRequirment['registration']){#限制运送范围
                $item.="<ShipToRegistrationCountry>true</ShipToRegistrationCountry>\n";
            }
            $item.="</BuyerRequirementDetails>\n";
        }

        #收款
        if(isset($action['pay'])){
            $pay = $action['pay'];
            $pMthods = json_decode($pay['payment_method'],true);
            if(is_array($pMthods)){
                foreach($pMthods as $pm){
                    $item.="<PaymentMethods>{$pm}</PaymentMethods>\n";#付款方式
                }
            }else{
                $item.="<PaymentMethods>{$pMthods}</PaymentMethods>\n";#付款方式
            }
            $item.="<PaymentInstructions>{$pay['payment_instructions']}</PaymentInstructions>\n";
            $item.="<AutoPay>True</AutoPay>\n";
        }

        #多属性
        if(isset($action['varians'])){
            $varians = $action['varians']?$action['varians']:[];
            $item.="<Variations>\n";
            foreach($varians as $ans){
                $item.="<Variation>\n";
                $item.="<SKU>".$ans['v_sku']."</SKU>\n";
                $item.="<StartPrice>".$ans['v_price']."</StartPrice>\n";
                $item.="<Quantity>".$ans['v_qty']."</Quantity>\n";
                $item.="<VariationSpecifics>\n";
                $varSpec=json_decode($ans['variation'],true);
                foreach($varSpec as $kSpec => $vSpec){
                    $item.="<NameValueList>";
                    $item.="<Name>" . htmlspecialchars($kSpec) . "</Name><Value>" . htmlspecialchars($vSpec) . "</Value>";
                    $item.="</NameValueList>";
                }
                $item.="</VariationSpecifics>\n";
                $item.="<VariationProductListingDetails>\n";
                $item.="<UPC>Does not apply</UPC>\n";
                $item.="<EAN>Does not apply</EAN>\n";
                $item.="<ISBN>Does not apply</ISBN>\n";
                $item.="</VariationProductListingDetails>\n";
                $item.="</Variation>\n";
            }
            #多属性信息
            if(isset($action['pictures'])){
                $pictures = $action['pictures'];
                $variationImage = $action['variation_image'];
                $specValue = [];
                foreach($pictures as $kpic => $vpic){
                    if(!in_array($vpic['value'],$specValue))$specValue[] = $vpic['value'];
                }

                $item.="<Pictures>\n";
                $item.="<VariationSpecificName>".htmlspecialchars($variationImage)."</VariationSpecificName>\n";
                
                foreach($specValue as $picVal){
                    $item.="<VariationSpecificPictureSet>\n";
                    $item.="<VariationSpecificValue>". htmlspecialchars($picVal) . "</VariationSpecificValue>\n";
                    foreach($pictures as $pic){
                        if($pic['value']==$picVal) $item.="<PictureURL>".$pic['eps_path']."</PictureURL>\n";
                    }
                    $item.="</VariationSpecificPictureSet>\n";
                }
                $item.="</Pictures>\n";
            }
            $item.="</Variations>\n";
        }

        $xml.='<?xml version="1.0" encoding="utf-8"?>'."\n";
        $xml.='<ReviseFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">'."\n";
        $xml.="<RequesterCredentials>\n";
        $xml.="<eBayAuthToken>".$this->token."</eBayAuthToken>\n";
        $xml.="</RequesterCredentials>\n";
        $xml.="<Item>\n".$item."</Item>\n";
        $xml.="</ReviseFixedPriceItemRequest>\n";
        return $xml;
    }

    public function createReviseItemXml(&$params)
    {

        $xml = "";
        $Variables = (new EbayCommonVariables())->__get('variables');
        $item ="<ItemID>".$params['item_id']."</ItemID>\n";
        $item.="<SKU>".$params['listing_sku']."</SKU>\n";
        $action = json_decode($params['new_val'],true);
        #更新标题
        if(isset($action['title'])) $item.="<Title><![CDATA[".$action['title']['title']."]]></Title>\n";

        #库存
        if(isset($action['quantity'])){
            $item.="<Quantity>".$action['quantity']['quantity']."</Quantity>\n";
        }else if(isset($action['mod_quantity'])){
            $item.="<Quantity>".$action['mod_quantity']."</Quantity>\n";
        }

        #备货
        if(isset($action['dispatch_max_time'])){
            $item.="<DispatchTimeMax>".$action['dispatch_max_time']."</DispatchTimeMax>\n";#发货处理时间
        }
        #橱窗展示
        if(isset($action['picture_gallery'])){
            $item.="<GalleryType>".$Variables['pictureGallery'][$action['picture_gallery']]."</GalleryType>\n";
        }

        #私人物品
        if(isset($action['private_listing'])){
            $item.="<PrivateListing>true</PrivateListing>\n";
        }

        #买家还价
        if(isset($action['best_offer'])){
            $bestOffer = $action['best_offer'];
            if($bestOffer['best_offer']){
                $item.="<ListingDetails>\n";
                $item.="<BestOfferAutoAcceptPrice>{$bestOffer['auto_accept_price']}</BestOfferAutoAcceptPrice>\n";
                $item.="<MinimumBestOfferPrice>{$bestOffer['minimum_accept_price']}</MinimumBestOfferPrice>\n";
                $item.="</ListingDetails>\n";
            }
        }

        #更新描述
        if(isset($action['description'])){
            $item.="<Description><![CDATA[".$action['description']."]]></Description>\n";
            $item.="<DescriptionReviseMode>Replace</DescriptionReviseMode>\n";
        }

        #修改店铺分类
        if(isset($action['store'])){
            $item.="<Storefront>\n";
            $item.="<StoreCategoryID>".$action['store']['store']."</StoreCategoryID>\n";
            // $item.='<StoreCategoryName></StoreCategoryName>';
            $item.="<StoreCategory2ID>".$action['store']['second_store']."</StoreCategory2ID>\n";
            // $item.='<StoreCategory2Name></StoreCategory2Name>';
            $item.="</Storefront>\n";
        }
        
        #国内物流
        if(isset($action['transin'])){
            $item.="<ShippingDetails>\n";
            #国内物流
            foreach($action['transin'] as $k2 => $tranin){
                $item.="<ShippingServiceOptions>\n";
                $item.="<ShippingService>{$tranin["shipping_service"]}</ShippingService>\n";
                if(intval($tranin['shipping_service_cost'])){#不免运费
                    $item.="<ShippingServiceAdditionalCost>".$tranin["shipping_service_additional_cost"]
                    ."</ShippingServiceAdditionalCost>\n";
                    $item.="<ShippingServiceCost>{$tranin["shipping_service_cost"]}</ShippingServiceCost>\n";
                }else{#免运费
                    $item.="<FreeShipping>true</FreeShipping>\n";
                }
                if(intval($tranin['extra_cost'])){#额外收费
                    $item.="<ShippingSurcharge>".$tranin['extra_cost']."</ShippingSurcharge>\n";
                }
                $item.="<ShippingServicePriority>".$k2."</ShippingServicePriority>\n";#排序方式
                $item.="</ShippingServiceOptions>\n";
            }
            #国际物流
            if(isset($action['trans'])){
                foreach($action['trans'] as $k1 => $tran){
                    $item.="<InternationalShippingServiceOption>\n";
                    $item.="<ShippingService>".$tran['shipping_service']."</ShippingService>\n";
                    $item.="<ShippingServiceAdditionalCost>".$tran['shipping_service_additional_cost']."</ShippingServiceAdditionalCost>\n";
                    $item.="<ShippingServiceCost>".$tran['shipping_service_cost']."</ShippingServiceCost>\n";
                    $item.="<ShippingServicePriority>".$k1."</ShippingServicePriority>\n";
                    if($tran['shiptolocation']){
                        $ships = explode(",",$tran['shiptolocation']);
                        foreach($ships as $ship){
                            $item.="<ShipToLocation>{$ship}</ShipToLocation>\n";
                        }
                    }
                    $item.="</InternationalShippingServiceOption>\n";
                }
            }
            $item.="</ShippingDetails>\n"; 
        }

        #商品所在地
        if(isset($action['location'])){
            $item.="<Location>".$action['location']['location']."</Location>\n";#发货地
            $item.="<PostalCode>".$action['location']['post_code']."</PostalCode>\n";#邮编
            $item.="<Country>".$action['location']['country']."</Country>\n";#国家
        }

        #退货
        if(isset($action['return'])){
            $return = $action['return'];
            if(trim($return['return_policy'])){
                $item.="<ReturnPolicy>\n";
                $item.="<ReturnsAcceptedOption>ReturnsAccepted</ReturnsAcceptedOption>\n";
                if($return['return_description']){#退货说明
                    $item.="<Description>{$return['return_description']}</Description>\n";
                }
                if($return['extended_holiday']){#节假日延期
                    $item.="<ExtendedHolidayReturns>true</ExtendedHolidayReturns>\n";
                }
                if($return['return_type']){#退货方式
                    $item.="<RefundOption>{$return['return_type']}</RefundOption>\n";
                }
                if($return['return_time']){#接受退货周期
                    $item.="<ReturnsWithinOption>".$this->returnTime[$return['return_time']]."</ReturnsWithinOption>\n";
                }
                if($return['return_shipping_option']){#运费承担方:Buyer,Seller
                    $item.="<ShippingCostPaidByOption>".$this->returnShippingOption[$return['return_shipping_option']]."</ShippingCostPaidByOption>\n";
                }
                if($return['restocking_fee_code']){#折旧费:NoRestockingFee,Percent_10,Percent_15,Percent_20
                    $item.="<RestockingFeeValueOption>{$return['restocking_fee_code']}</RestockingFeeValueOption>\n";
                }
                $item.="</ReturnPolicy>\n";
            }
        }

        #图片
        if(isset($action['images'])){
            $images = explode(';',$action['images']);
            foreach($images as $img){
                $item.="<PictureDetails>\n";
                $item.="<PictureURL>{$img}</PictureURL>\n";
                $item.="</PictureDetails>\n";
            }
        }

        #买家限制
        if(isset($action['buyer_requirment'])){
            $buRequirment = $action['buyer_requirment'];
            $item.="<BuyerRequirementDetails>\n";
            if(isset($buRequirment['link_paypal']) && $buRequirment['link_paypal']){#paypal限制 0否 1是
                $item.="<LinkedPayPalAccount>true</LinkedPayPalAccount>\n";
            }

            if(isset($buRequirment['violations']) && $buRequirment['violations']){#违反政策相关
                $item.="<MaximumBuyerPolicyViolations>\n";
                if(isset($buRequirment['violations_count']))$item.="<Count>{$buRequirment['violations_count']}</Count>\n";
                if(isset($buRequirment['violations_period']))$item.="<Period>{$buRequirment['violations_period']}</Period>\n";
                $item.="</MaximumBuyerPolicyViolations>\n";
            }

            if(isset($buRequirment['requirements']) && $buRequirment['requirements']){#次数限制
                $item.="<MaximumItemRequirements>\n";
                if(isset($buRequirment['requirements_max_count'])){
                    $item.="<MaximumItemCount>{$buRequirment['requirements_max_count']}</MaximumItemCount>\n";
                }
                if(isset($buRequirment['minimum_feedback']) && $buRequirment['minimum_feedback']){#评分限制
                    if(isset($buRequirment['minimum_feedback_score'])){
                        $item.="<MinimumFeedbackScore>{$buRequirment['minimum_feedback_score']}</MinimumFeedbackScore>\n";
                    }
                }
                $item.="</MaximumItemRequirements>\n";
            }

            if(isset($buRequirment['strikes']) && $buRequirment['strikes']){#未付款限制
                $item.="<MaximumUnpaidItemStrikesInfo>\n";
                if(isset($buRequirment['strikes_count'])) $item.="<Count>{$buRequirment['strikes_count']}</Count>\n";
                if(isset($buRequirment['strikes_period'])) $item.="<Period>{$buRequirment['strikes_period']}</Period>\n";
                $item.="</MaximumUnpaidItemStrikesInfo>\n";
            }

            if(isset($buRequirment['credit']) && $buRequirment['credit']){#信用限制
                if(isset($buRequirment['requirements_feedback_score'])){
                    $item.="<MinimumFeedbackScore>{$buRequirment['requirements_feedback_score']}</MinimumFeedbackScore>\n";
                }
            }

            if(isset($buRequirment['registration']) && $buRequirment['registration']){#限制运送范围
                $item.="<ShipToRegistrationCountry>true</ShipToRegistrationCountry>\n";
            }
            $item.="</BuyerRequirementDetails>\n";
        }

        #收款
        if(isset($action['pay'])){
            $pay = $action['pay'];
            $pMthods = json_decode($pay['payment_method'],true);
            if(is_array($pMthods)){
                foreach($pMthods as $pm){
                    $item.="<PaymentMethods>{$pm}</PaymentMethods>\n";#付款方式
                }
            }else{
                $item.="<PaymentMethods>{$pMthods}</PaymentMethods>\n";#付款方式
            }
            $item.="<PaymentInstructions>{$pay['payment_instructions']}</PaymentInstructions>\n";
            $item.="<AutoPay>True</AutoPay>\n";
        }

        $xml.="<?xml version='1.0' encoding='utf-8'?>\n";
        $xml.="<ReviseItemRequest xmlns='urn:ebay:apis:eBLBaseComponents'>\n";
        $xml.="<RequesterCredentials>\n";
        $xml.="<eBayAuthToken>".$token."</eBayAuthToken>\n";
        $xml.="</RequesterCredentials>\n";
        $xml.="<Item>\n".$item."</Item>\n";
        $xml.="</ReviseItemRequest>\n";
        return $xml;
    }
}