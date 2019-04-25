<?php
namespace app\publish\task;
/**
 * 曾绍辉
 * 17-7-7
 * ebay刊登产品
*/

use app\index\service\AbsTasker;
use app\common\model\Channel;
use app\common\model\GoodsSku;
use app\common\cache\Cache;
use service\ebay\EbayApi;
use think\Db;
use think\cache\driver;
use app\publish\queue\EbayRelistQueuer;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingSetting;
use think\Exception;

class EbayRelistItem extends AbsTasker
{
    private $listMod;
    private $acInfo;
    private $setMod;
    private $ebayApi;
    private $xml="";

    function __construct()
    {
        $this->listMod = new EbayListing;
        $this->setMod = new EbayListingSetting;
    }

    public function getName()
    {
        return "ebay下架产品重上";
    }
    
    public function getDesc()
    {
        return "ebay下架产品重上";
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
        self::ebayRelistItem();
    }

    public function ebayRelistItem($listingId){
        set_time_limit(0);
        #获取重新上架数据
        $rows = $this->getPublishRows($listingId);
        $verb = $this->createVerb($rows);
        $this->acInfo = Cache::store('EbayAccount')->getTableRecord($rows['account_id']);#获取账号信息
        if($rows && $this->acInfo){
            $config = $this->createConfig($this->acInfo,$rows,$verb);
            $this->ebayApi = new EbayApi($config);
            $this->xml = $this->createXml($verb,$rows);#echo $this->xml;die;
            $resText = $this->ebayApi->createHeaders()->__set("requesBody",$this->xml)->sendHttpRequest2();
            #echo "<pre>";print_r($resText);die;
            $resUp = $this->processingReponse($resText,$rows,$verb);
            if(isset($resUp['listing_status']) && $resUp['listing_status']==3){#重上成功
                return ['result'=>true,'listing_id'=>$listingId,"rows"=>$resUp];
            }else{#重上失败
                return ['result'=>false,'listing_id'=>$listingId,"rows"=>$resUp];
            }
        }else{
            return ['result'=>false,'listing_id'=>0];
        }
    }

    public function createConfig(&$acInfo,&$rows,&$verb)
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
        $config['verb'] = $verb;
        return $config;
    }

    public function createVerb(&$rows)
    {
        if($rows['listing_type']==1){#固定价格
            $verb = "RelistFixedPriceItem";
        }else{#拍卖
            $verb = "RelistItem";
            #$verb = "VerifyRelistItem";
        }
        return $verb;
    }

    #记录刊登费用
    public function getFees($fees){
        foreach($fees['Fee'] as $fee){
            if($fee['Name'] == "ListingFee"){#上市费用
                $listingFee = 0;
                $listingFee = $fee['Fee'];
                if(isset($fee['PromotionalDiscount'])){
                    $listingFee += $fee['PromotionalDiscount'];
                }
                $pubFee['listing_fee'] = $listingFee;
            }
            if($fee['Name'] == "InsertionFee"){#刊登费用
                $insertFee = 0;
                $insertFee = $fee['Fee'];
                if(isset($fee['PromotionalDiscount'])){
                    $insertFee += $fee['PromotionalDiscount'];
                }
                $pubFee['insertion_fee'] = $insertFee;
            }
        }
        return $pubFee;
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

    #处理返回结果
    public function processingReponse(&$resText,&$rows,$verb)
    {
        if($verb=="RelistItem" || $verb=="VerifyRelistItem"){
            $reponse = isset($resText['RelistItemResponse'])?$resText['RelistItemResponse']:[];
            #$reponse = isset($resText['VerifyRelistItemResponse'])?$resText['VerifyRelistItemResponse']:[];
        }else if($verb=="RelistFixedPriceItem"){
            $reponse = isset($resText['RelistFixedPriceItemResponse'])?$resText['RelistFixedPriceItemResponse']:[];
        }
        if(!$reponse){
            throw new Exception("未获取到返回信息节点，未知错误！");
        }
        $message = $this->upMessage($reponse);
        $up=[];
        $upSet=[];
        $upTemp=[];
        $upSet['message'] = $message;
        #echo "<pre>";print_r($resText);die;
        if($reponse['Ack']=="Success"){#重上成功
            $fees = $this->getFees($reponse['Fees']);
            $up['listing_fee'] = $fees['listing_fee'];
            $up['insertion_fee'] = $fees['insertion_fee'];
            #$up['item_id'] = isset($reponse['ItemID'])?intval($reponse['ItemID']):0;
            $up['item_id'] = $reponse['ItemID'];
            $up['listing_status'] = 3;
        }else if($reponse['Ack']=="Warning"){#重上成功,有告警
            $fees = $this->getFees($reponse['Fees']);
            $up['listing_fee'] = $fees['listing_fee'];
            $up['insertion_fee'] = $fees['insertion_fee'];
            #$up['item_id'] = isset($reponse['ItemID'])?intval($reponse['ItemID']):0;
            $up['item_id'] = $reponse['ItemID'];
            $up['listing_status'] = 3;
        }else if($reponse['Ack']=="Failure"){#重上失败
            $up['listing_status'] = 14;
            $up['listing_fee'] = 0;
            $up['insertion_fee'] = 0;
            $upTemp['item_id'] = 0;
        }else{#未知错误
            $upSet['message'] = "重上:未知错误！";
            $upTemp['listing_fee'] = 0;
            $upTemp['insertion_fee'] = 0;
            $upTemp['item_id'] = 0;
        }
        $this->listMod->where(['id'=>$rows['id']])->update($up);
        $this->setMod->where(['id'=>$rows['id']])->update($upSet);
        $up['message'] = $upSet['message'];
        return array_merge($up,$upTemp);
    }

    public function createXml($verb,$rows){
        if($verb=="RelistItem" || $verb=="VerifyRelistItem"){
            $requestBody ='<?xml version="1.0" encoding="utf-8"?>'."\n";
            $requestBody.='<RelistItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">'."\n";
            #$requestBody.='<VerifyRelistItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">'."\n";
            $requestBody.="<RequesterCredentials>\n";
            $requestBody.="<eBayAuthToken>".$this->token."</eBayAuthToken>\n";
            $requestBody.="</RequesterCredentials>";
            $requestBody.="<Item>\n";
            $requestBody.="<ItemID>".$rows['item_id']."</ItemID>";
            $requestBody.="</Item>\n";
            $requestBody.="</RelistItemRequest>\n";
            #$requestBody.="</VerifyRelistItemRequest>\n";
        }else if($verb=="RelistFixedPriceItem"){
            $requestBody ='<?xml version="1.0" encoding="utf-8"?>'."\n";
            $requestBody.='<RelistFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">'."\n";
            $requestBody.="<RequesterCredentials>\n";
            $requestBody.="<eBayAuthToken>".$this->token."</eBayAuthToken>\n";
            $requestBody.="</RequesterCredentials>";
            $requestBody.="<Item>\n";
            $requestBody.="<ItemID>".$rows['item_id']."</ItemID>";
            $requestBody.="</Item>\n";
            $requestBody.="</RelistFixedPriceItemRequest>\n";
        }
        return $requestBody;
    }

    #获取重上listing
    public function getPublishRows($listingId=0){
        if($listingId!=0){
            $rows = $this->listMod->where(['id'=>$listingId])->find();
            if($rows){
                return $rows->toArray();
            }else{
                throw new Exception("请输入有效ID！");
            }
        }else{
            throw new Exception("请输入有效ID！");
        }
    }

}
