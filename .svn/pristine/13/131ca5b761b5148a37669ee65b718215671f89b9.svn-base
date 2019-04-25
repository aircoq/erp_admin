<?php
namespace app\publish\task;
/**
 * rocky
 * 17-4-11
 * ebay在线Listing抓取任务
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
use app\publish\queue\EbayRelistQueuer;
use app\publish\queue\EbayGetListQueuer;
use app\publish\queue\EbayTimingQueuer;

class EbayGetListings extends AbsTasker
{
	 public function getName()
    {
        return "获取ebay账号listing";
    }
    
    public function getDesc()
    {
        return "获取ebay账号listing";
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
        self::GetSellerList();           
    }

    public function GetSellerList(){#获取ebay在线listing信息
    	// set_time_limit(0);
     //    #从队列中获取待更新的ebay账号

     //    $gListQueuer = new EbayGetListQueuer();
     //    $aid = $gListQueuer->consumption();
     //    if(!$aid)die;
    	// $verb = "GetSellerList";
     //    $step=86400*12;
    	// $ebayAccount = new EbayAccount();#获取token   
     //    $acInfo = Cache::store('account')->ebayAccount($aid);

	    // $tokenArr = json_decode($acInfo['token'],true);
	    // $token = trim($tokenArr[0])?$tokenArr[0]:$acInfo['token'];
     //    $config['userToken']=$token;
     //    $config['compatLevel']=957;
     //    $config['siteID']=0;
     //    $config['verb']=$verb;
     //    $config['appMode']=0;
     //    $config['account_id']=$acInfo['id'];
     //    $ebayApi = new EbayApi($config);

     //    // if(date("Y-m-d",$acInfo->start)>date('Y-m-d',time())){#判断是否大于当前时间
     //    //     $start=date("Y-m-d H:i:s",time()-$step);
     //    //     $end=date("Y-m-d H:i:s",time());
     //    // }else{
     //    //     $start=date("Y-m-d H:i:s",$acInfo->start);
     //    //     $end=date("Y-m-d H:i:s",$acInfo->end);
     //    // }
     //    $tn = time();
     //    $start = date("Y-m-d H:i:s",$tn-$step);#开始时间
     //    $end = date("Y-m-d H:i:s",$tn);#结束时间
     //    #$xml = $this->createXml($token,$start,$end,$acInfo->page,$acInfo->page_size);
     //    $xml = $this->createXml($token,$start,$end,$acInfo['page'],20);
	    // $resText = $ebayApi->createHeaders()->__set("requesBody",$xml)->sendHttpRequest2();
     //    // echo "<pre>";
     //    // print_r($resText);

     //    if(isset($resText['GetSellerListResponse'])){
     //        $response=$resText['GetSellerListResponse'];
     //        if($response['Ack']=="Success"){
     //            $TotalNumberOfPages = intval($response['PaginationResult']['TotalNumberOfPages']);#总页数
     //            if($acInfo['page'] >= $TotalNumberOfPages || $TotalNumberOfPages==0){#最后一页,重置时间
     //                #$data['start']=$start+$step;
     //                #$data['end']=$end+$step;
     //                $data['page']=1;
     //                $data['end']=$end+$step;
     //                $ebayAccount->edit($data,['id'=>$acInfo['id']]);
     //            }else{#页码加一
     //                $data['page']=$acInfo['page']+1;
     //                $ebayAccount->edit($data,['id'=>$acInfo['id']]);
     //            }
     //            if(isset($response['ItemArray']['Item']) && count($response['ItemArray']['Item'])){
     //                $item = isset($response['ItemArray']['Item'][0])?$response['ItemArray']['Item']:[$response['ItemArray']['Item']];
     //                foreach($item as $k => $v){
     //                    $this->syncEbayListing($v,$acInfo['id']);
     //                }
     //            }
     //        }
     //    }
    }
    
    /*
    *title 同步listing信息
    *@param list listing详细信息
    *@param account_id 销售账号ID
    */
    public function syncEbayListing($list,$account_id){
        $site = new EbaySite();
        $siteInfo = $site->get(["country"=>$list['Site']]);
        $listingSku = $list['SKU'];
        $goods = $this->getGoodsInfoBysku($listingSku,$account_id);
        $listing['account_id']=$account_id;
        $listing['site_code']=$list['Site'];
        $listing['site']=intval($siteInfo['siteid']);
        $listing['item_id']=$list['ItemID'];

        #商品信息
        if(!empty($goods)){
            if(isset($goods['goodInfo'])){
                $goodInfo=$goods['goodInfo'];
                $listing['goods_id']=$goodsInfo->id;
            }
            if($goods['goodSku']){
                $skuInfo=$goods['goodSku'];
                $listing['spu']=$skuInfo->spu;
                $listing['sku']=$skuInfo->sku;
            }
        }
        $listing['listing_sku']=$listingSku;

        #币种
        $cur = new Currency();
        $currInfo = $cur->get(["code"=>$list['Currency']]);
        $listing['currency_id']=isset($currInfo->id)?$currInfo->id:0;
        $listing['currency']=$list['Currency'];

        if(isset($list['Variations'])){#判断是否为多属性产品
            $listing['varions']=1;
            $listing['goods_type']=2;
        }else{
            $listing['varions']=0;
            $listing['goods_type']=1;
        }

        #listing基本信息
        $listing['paypal_emailaddress']=$list['PayPalEmailAddress'];
        $listing['primary_categoryid']=$list['PrimaryCategory']['CategoryID'];
        $listing['primary_category_pahtname']=$list['PrimaryCategory']['CategoryName'];
        $listing['quantity']=$list['Quantity'];
        $listing['sold_quantity']=intval($list["SellingStatus"]["QuantitySold"]);
        $listing['buy_it_nowprice']=$list["BuyItNowPrice"];#一口价
        $listing['start_price']=$list["StartPrice"];#起始价
        $listing['reserve_price']=$list['ReservePrice'];#保留价
        $listing['img']=isset($list["PictureDetails"]["GalleryURL"])?$list["PictureDetails"]["GalleryURL"]:"";
        $listing['title']=$list['Title'];#标题
        $listing['hit_count']=isset($list['HitCount'])?$list['HitCount']:0;#点击量
        $listing['watch_count']=isset($list['WatchCount'])?$list['WatchCount']:0;#收藏量
        $listing['listing_type']=$list['ListingType'];#刊登类型
        $listing['description']=$list['Description'];#描述
        $listing['store']=isset($list['Storefront']['StoreCategoryID'])?$list['Storefront']['StoreCategoryID']:0;
        $listing['second_store']=isset($list['Storefront']['StoreCategory2ID'])?$list['Storefront']['StoreCategory2ID']:0;
        $listing['condition']=isset($list['ConditionID'])?$list['ConditionID']:'';
        $listing['condition_description']=isset($list['ConditionDescription'])?$list['ConditionDescription']:"";
        $listing['start_date']=strtotime($list['ListingDetails']['StartTime']);
        $listing['end_date']=strtotime($list['ListingDetails']['EndTime']);
        $listing['listing_code']=$list['SellingStatus']['ListingStatus'];
        if($listing['listing_code']=="Active"){#在线
            $listing['listing_status']=3;
        }else if($listing['listing_code']=="Completed"){#下架,已退回成交费
            $listing['listing_status']=11;
        }else if($listing['listing_code']=="Custom"){#待刊登
            $listing['listing_status']=12;
        }else if($listing['listing_code']=="CustomCode"){#定制编号
            $listing['listing_status']=12;
        }else if($listing['listing_code']=="Ended"){#下架,未退回成交费
            $listing['listing_status']=11;
        }

        #图片
        $images = array();
        if(isset($list["PictureDetails"]["PictureURL"])){
            $images=is_array($list["PictureDetails"]["PictureURL"])?$list["PictureDetails"]["PictureURL"]:array($list["PictureDetails"]["PictureURL"]);
        }

        #运输
        $International = array();
        $detail = array();
        $detail["location"]=$list["Location"];#商品所在地
        $detail['listing_duration']=isset($list['ListingDuration'])?$list['ListingDuration']:'';#刊登天数
        $detail["country"]=$list["Country"];#发货国家代码
        $detail['application_data'] = isset($list['ApplicationData'])?$list['ApplicationData']:"";#应用名称
        $detail["choice_date"]=isset($list["DispatchTimeMax"])?$list["DispatchTimeMax"]:0;#发货处理时间(dispatch_time_max)
        $detail["pay_method"]=json_encode($list['PaymentMethods']);#付款方式
        if(isset($list["ShippingDetails"])){
            $ShippingDetails=$list["ShippingDetails"];
            if(isset($ShippingDetails["PaymentInstructions"])){
                $detail["payment_instructions"]=$ShippingDetails["PaymentInstructions"];#付款说明
            }else{
                $detail["payment_instructions"]="";
            }

            if(isset($ShippingDetails["ExcludeShipToLocation"])){#不送达地区
                $detail["exclude"]=implode(",",$ShippingDetails["ExcludeShipToLocation"]);
            }

            $transIn = array();
            if(isset($ShippingDetails["ShippingServiceOptions"])){//国内运输
                $ship=isset($ShippingDetails["ShippingServiceOptions"][0])?$ShippingDetails["ShippingServiceOptions"]:array($ShippingDetails["ShippingServiceOptions"]);
                foreach($ship as $ksh => $vsh){
                    $transIn[$ksh]['shipping_service']=$vsh['ShippingService'];
                    $transIn[$ksh]['shipping_service_cost']=isset($vsh['ShippingServiceCost'])?$vsh['ShippingServiceCost']:0;
                    $transIn[$ksh]['shipping_service_additional_cost']=isset($vsh['ShippingServiceAdditionalCost'])?$vsh['ShippingServiceAdditionalCost']:0;
                    $transIn[$ksh]['shipping_service_priority']=isset($vsh['ShippingServicePriority'])?$vsh['ShippingServicePriority']:0;
                    $transIn[$ksh]['expedited_service']=isset($vsh['ExpeditedService'])?($vsh['ExpeditedService']=="true"?1:0):0;
                    $transIn[$ksh]['shipping_time_min']=isset($vsh['ShippingTimeMin'])?$vsh['ShippingTimeMin']:0;
                    $transIn[$ksh]['shipping_time_max']=isset($vsh['ShippingTimeMax'])?$vsh['ShippingTimeMax']:0;
                    $transIn[$ksh]['free_shipping']=isset($vsh['FreeShipping'])?($vsh['FreeShipping']=="true"?1:0):0;
                }
            }

            if(isset($ShippingDetails["InternationalShippingServiceOption"])){#国际运输
                $InternationalShippingServiceOption=isset($ShippingDetails["InternationalShippingServiceOption"][0])?$ShippingDetails["InternationalShippingServiceOption"]:array($ShippingDetails["InternationalShippingServiceOption"]);
                $i=0;
                foreach($InternationalShippingServiceOption as $in){
                    $International[$i]["shipping_service"]=$in["ShippingService"];
                    if(isset($in["ShippingServiceAdditionalCost"])){
                        $International[$i]["shipping_service_additional_cost"]=$in["ShippingServiceAdditionalCost"];
                    }
                    if(isset($in["ShippingServiceCost"])){
                        $International[$i]["shipping_service_cost"]=$in["ShippingServiceCost"];
                    }
                    $International[$i]["shipping_service_priority"]=$in["ShippingServicePriority"];
                    $ShipToLocation=is_array($in["ShipToLocation"])?implode(",",$in["ShipToLocation"]):$in["ShipToLocation"];
                    $International[$i]["shiptolocation"]=$ShipToLocation;
                    $i++;
                }
            }
            #$detail["internal"]=1;
        }else{
            #$detail["internal"]=0;
        }

        #退换货政策
        if(isset($list["ReturnPolicy"])){
            $ReturnPolicy=$list["ReturnPolicy"];
            #退款详情
            if(isset($ReturnPolicy["Description"])){
                $detail["return_description"]=$ReturnPolicy["Description"];
            }else{
                $detail["return_description"]="";
            }
            #退款方式
            if(isset($ReturnPolicy["RefundOption"]))$detail["return_type"]=$ReturnPolicy["RefundOption"];
            #退款天数
            $detail["return_time"]=isset($ReturnPolicy["ReturnsWithinOption"])?$ReturnPolicy["ReturnsWithinOption"]:"";
            #运费承担方
            $detail["return_shipping_option"]=$ReturnPolicy["ShippingCostPaidByOption"]=="Buyer"?0:1;
            #是否支持退换货
            $detail["return_policy"]=1;
        }else{
            $detail["return_policy"]=0;
        }

        #买家限制
        if(isset($list["BuyerRequirementDetails"])){
            #paypal限制  
            $BuyerRequirementDetails=$list["BuyerRequirementDetails"];
            if(isset($BuyerRequirementDetails["LinkedPayPalAccount"])){
                $detail["link_paypal"]=$BuyerRequirementDetails["LinkedPayPalAccount"]=="true"?1:0;
            }else{
                $detail["link_paypal"]=0;
            } 

            #未付款限制
            if(isset($BuyerRequirementDetails["MaximumUnpaidItemStrikesInfo"])){
                #次数
                $detail["strikes_count"]=isset($BuyerRequirementDetails["MaximumUnpaidItemStrikesInfo"]["Count"])?$BuyerRequirementDetails["MaximumUnpaidItemStrikesInfo"]["Count"]:0;
                #时限
                $detail["strikes_period"]=isset($BuyerRequirementDetails["MaximumUnpaidItemStrikesInfo"]["Period"])?$BuyerRequirementDetails["MaximumUnpaidItemStrikesInfo"]["Period"]:"";
                $detail["strikes"]=1;
            }else{
                $detail["strikes"]=0;
            }

            #违反政策相关
            if(isset($BuyerRequirementDetails["MaximumBuyerPolicyViolations"])){
                #次数
                $detail["violations_count"]=isset($BuyerRequirementDetails["MaximumBuyerPolicyViolations"]["Count"])?$BuyerRequirementDetails["MaximumBuyerPolicyViolations"]["Count"]:0;
                #时限
                $detail["violations_period"]=isset($BuyerRequirementDetails["MaximumBuyerPolicyViolations"]["Period"])?$BuyerRequirementDetails["MaximumBuyerPolicyViolations"]["Period"]:"";
                $detail["violations"]=1;
            }else{
                $detail["violations"]=0;
            }

            #限制条件
            if(isset($BuyerRequirementDetails["MaximumItemRequirements"])){
                $detail["requirements_max_count"]=isset($BuyerRequirementDetails["MaximumItemRequirements"]["MaximumItemCount"])?$BuyerRequirementDetails["MaximumItemRequirements"]["MaximumItemCount"]:0;

                if(isset($BuyerRequirementDetails["MaximumItemRequirements"]["MinimumFeedbackScore"])){
                    $detail["minimum_feedback"]=1;
                    $detail['minimum_feedback_score']=$BuyerRequirementDetails["MaximumItemRequirements"]["MinimumFeedbackScore"];
                }
                $detail["requirements"]=1;
            }else{
                $detail["requirements"]=0;
            }

            #信用限制
            if(isset($BuyerRequirementDetails["MinimumFeedbackScore"])){
                $detail["credit"]=1;
                if(isset($BuyerRequirementDetails["MinimumFeedbackScore"]))$detail["requirements_feedback_score"]=$BuyerRequirementDetails["MinimumFeedbackScore"];
            }else{
                $detail["credit"]=0;
            }

            #不在我的配送地
            if(isset($BuyerRequirementDetails["ShipToRegistrationCountry"])){
                $detail["registration"]=$BuyerRequirementDetails["ShipToRegistrationCountry"]=="true"?1:0;
            }else{
                $detail["registration"]=0;
            }
            $detail["refuse"]=1;
        }else{
            $detail["refuse"]=0;
        }

        $mSpecifics=array();#类目属性与多属性产品记录
        $vs=array();
        if(isset($list["Variations"])){
            $variations=isset($list["Variations"]["Variation"][0])?$list["Variations"]["Variation"]:array($list["Variations"]["Variation"]);
            $i=0;
            foreach($variations as $ia){
                $vs[$i]['sku_id']=0;
                $vs[$i]["v_sku"]=$ia["SKU"];
                $vs[$i]["v_price"]=$ia["StartPrice"];
                $vs[$i]["v_qty"]=$ia["Quantity"];
                $vs[$i]["v_sold"]=intval($ia["SellingStatus"]["QuantitySold"]);
                if(isset($ia["VariationProductListingDetails"]["UPC"]))$vs[$i]["upc"]=$ia["VariationProductListingDetails"]["UPC"];
                if(isset($ia["VariationProductListingDetails"]["ISBN"]))$vs[$i]["isbn"]=$ia["VariationProductListingDetails"]["ISBN"];
                if(isset($ia["VariationProductListingDetails"]["EAN"]))$vs[$i]["ean"]=$ia["VariationProductListingDetails"]["EAN"];
                $Specifics=isset($ia["VariationSpecifics"]["NameValueList"][0])?$ia["VariationSpecifics"]["NameValueList"]:array($ia["VariationSpecifics"]["NameValueList"]);
                $temp=array();
                foreach($Specifics as $val){
                    $temp[$val["Name"]]=$val["Value"];
                    $mSpecifics[$val["Name"]]=$val["Value"];
                }
                $vs[$i]["variation"]=json_encode($temp);
                $i++;
            }
        }

        if(isset($vs[0])){#多属性名称
            $vvk = json_decode($vs[0]['variation'],true);
            $listing['v_varkey'] = json_encode(array_keys($vvk));
        }

        $listingData = array("listing"=>$listing,"images"=>$images,"detail"=>$detail,"international"=>$International,"variation"=>$vs,"specifics"=>$mSpecifics,"transIn"=>$transIn);

        #同步数据库
        $this->syncListingData($listingData);

    }

    /*
    *title 同步listing数据
    *@param listingData listing信息
    */
    public function syncListingData($listingData){

        $mList = new EbayListing();
        $mImage = new EbayListingImage();
        $mSet = new EbayListingSetting();
        $mTrans = new EbayListingTransport();
        $mTransIn = new EbayListingTransportIn();
        $mSpec = new EbayListingSpecifics();
        $mVar = new EbayListingVariation();

        $listing=$listingData['listing'];
        $images=$listingData['images'];
        $detail=$listingData['detail'];
        $trans=$listingData['international'];#国际物流
        $transIn=$listingData['transIn'];#国内物流
        $variation=$listingData['variation'];
        $specifics=$listingData['specifics'];

        #设置信息
        $listing_id = $mList->syncListing($listing);
        $detail['listing_id'] = $listing_id;
        $mSet->syncSetting($detail);
        #listing属性
        $spcArr = array();
        $i=0;
        foreach($specifics as $ksp => $vsp){
            $spcArr[$i]['attr_name']=$ksp;
            $spcArr[$i]['attr_value']=$vsp;
            $spcArr[$i]['listing_id']=$listing_id;
            $i++;
        }
        $mSpec->syncListingSpecifics($spcArr,$listing_id);

        #图片
        $imgArr = array();
        foreach($images as $k => $v){
            $imgArr[$k]['listing_id']=$listing_id;
            $imgArr[$k]['spu']=isset($listing['spu'])?$listing['spu']:"";
            $imgArr[$k]['sku']=isset($listing['sku'])?$listing['sku']:"";
            $imgArr[$k]['thumb']=$v;
            $imgArr[$k]['eps_path']=$v;
            $imgArr[$k]['sort']=$k;
            $imgArr[$k]['status']=3;#已上传至eps
            if($k==0){
                $imgArr[$k]['main']=1;
            }
        }
        $mImage->syncListingImages($imgArr,$listing_id);

        #物流方式(国际)
        foreach($trans as $ktr => $vtr){
            $trans[$ktr]['listing_id']=$listing_id;
        }
        $mTrans->syncListingTrans($trans,$listing_id);

        #物流方式(国内)
        foreach($transIn as $ktrIn => $vtrIn){
            $transIn[$ktrIn]['listing_id']=$listing_id;
        }
        $mTransIn->syncListingTransIn($transIn,$listing_id);

        #多属性子产品
        foreach($variation as $kvar => $vvar){
            $mVar->syncListingVarions($vvar,$listing_id);
        }

        #检查重上规则
        if($listing['listing_status']==11){#已下架
            $this->listeningRelistItem($listing_id);
        }
        
    }
    /*
    *title 创建获取在线listing信息的xml
    *@param token 账号秘钥
    *@param start 开始时间
    *@param end 结束时间
    *@param page 页数
    *@param pageSize 每页尺码数
    */
    public function createXml($token,$start,$end,$page=1,$pageSize){#创建获取在线listing信息的xml方法
        $requesBody ='<?xml version="1.0" encoding="utf-8"?>';
        $requesBody.='<GetSellerListRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $requesBody.='<RequesterCredentials>';
        $requesBody.='<eBayAuthToken>'.$token.'</eBayAuthToken>';
        $requesBody.='</RequesterCredentials>';
        $requesBody.='<ErrorLanguage>en_US</ErrorLanguage>';
        $requesBody.='<WarningLevel>High</WarningLevel>';
        $requesBody.='<DetailLevel>ReturnAll</DetailLevel>';
        $requesBody.='<StartTimeFrom>'.gmdate("Y-m-d\T23:23:59.000\Z",strtotime($start)).'</StartTimeFrom>';
        $requesBody.='<StartTimeTo>'.gmdate("Y-m-d\T23:23:59.000\Z",strtotime($end)).'</StartTimeTo>';
        $requesBody.='<IncludeVariations>true</IncludeVariations>';
        $requesBody.='<Pagination>';
        $requesBody.='<PageNumber>'.$page.'</PageNumber>';
        $requesBody.='<EntriesPerPage>'.$pageSize.'</EntriesPerPage>';
        $requesBody.='</Pagination>';
        $requesBody.='</GetSellerListRequest>';
        return $requesBody;
    }

    /*
    *title 检查重上信息
    *@param listingId 
    */
    public function listeningRelistItem($listingId){
        $relistQueuer = new EbayRelistQueuer();
        $reTimQue = new EbayTimingQueuer('ebay_timing_relist');#ebay定时重上队列
        $wh['l.id']=$listingId;
        $wh['s.restart'] = 1;

        $data = Db::name("ebay_listing")->alias("l")->join("ebay_listing_setting s","l.id=s.listing_id","LEFT")
        ->field("s.restart,s.restart_rule,s.restart_count,s.restart_way,s.restart_time,s.restart_number,
            l.quantity,l.sold_quantity,l.id")
        ->where($wh)->order("l.update_date")
        ->find();

        if($data){
            $rule = intval($data['restart_rule']);#重上规则
            if($rule==1){#只要物品结束
                $rt = true;
            }else if($rule==2){#所有物品卖出
                if(intval($data['quantity'])==0 && intval($data['sold_quantity'])>0){
                    $rt = true;
                }else{
                    $rt = false;
                }
            }else if($rule==3){#没有物品卖出
                if(intval($data['sold_quantity']==0)){
                    $rt = true;
                }else{
                    $rt = false;
                }
            }else if($rule==4){#没有物品卖出后仅刊登一次
                if(intval($data['sold_quantity']==0) && $data['restart_number']<1){
                    $rt = true;
                }else{
                    $rt = false;
                }
            }else if($rule==5){#当物品卖出一定数量
                if(intval($data['sold_quantity'])>=$data['restart_count']){
                    $rt = true;
                }else{
                    $rt = false;
                }
            }

            if($rt){
                #重上方式
                if($data['restart_way']==1){#立即重上
                    Db::name("ebay_listing")->where(['id'=>$data['id']])->update(['listing_status'=>13]);
                    #加入立即重上集合
                    $relistQueuer->production([$listingId]);
                }else if($data['restart_way']==2){#定时重上
                    $th = date("H:i:s",intval($data['restart_time']));#获取时分秒
                    $tm = date('Y-m-d',time());
                    $t = strtotime($th." ".$tm);#获取重上时间
                    $tn = time();
                    if($t<$tn){
                        Db::name("ebay_listing")->where(['id'=>$data['id']])->update(['listing_status'=>13,
                            'update_date'=>time()]);
                    }
                    #加入定时重上集合
                    $reTimQue->production(['timing'=>$t,'listId'=>$listingId]);
                }
                Db::name("ebay_listing_setting")->where(['listing_id'=>$listing_id])->update(['restart'=>1]);
            }
        }
    }

    /*
    *title 获取本地映射产品信息
    *@param sku 线上SKU
    *@param account_id 账号ID
    */
    public function getGoodsInfoBysku($sku,$account_id){#以线上sku获取相对映射的产品信息
        $goods = new Goods;
        $goodsSku = new GoodsSku();
        $goodsSkuMap = new GoodsSkuMap();
        $goodsSkuAlias = new GoodsSkuAlias();
        $channel=Cache::store('channel')->getChannel();
        $cha=array();
        foreach($channel as $c){
            if($c['name']=="ebay"){
                $cha=$c;
            }
        }
        $whMap['channel_id']=$cha['id'];
        $whMap['account_id']=$account_id;
        $whMap['channel_sku']=$sku;

        $goodMap = $goodsSkuMap->get($whMap);
        if($goodMap){
            $skuId = $goodMap->sku_id;
        }else{
            $whAli['alias']=$sku;
            $goodAli = $goodsSkuAlias->get($whAli);
            if($goodAli){
                $skuId = $goodAli->sku_id;
            }else{
                $skuId = 0;
            }
        }

        if($skuId!=0){
            $goodSku = $goodsSku->get($skuId);
            if($goodSku){
                $goodInfo=$goods->get($goodSku->goods_id);
                if($goodInfo){
                    $result['goodInfo']=$goodInfo;
                    $result['goodSku']=$goodSku;
                    return $result;
                }else{
                    return [];
                }
            }else{
                return [];
            }
        }else{
            return [];
        }

    }

}
