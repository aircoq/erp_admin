<?php
namespace app\publish\task;

/**
 * 曾绍辉
 * 18-1-24
 * 同步线上修改的listing促销设置
*/
use app\index\service\AbsTasker;
use app\common\model\Channel;
use app\common\model\GoodsSku;
use app\common\cache\Cache;
use app\listing\service\EbayListingHelper;
use service\ebay\EbayApi;
use think\Db;
use think\cache\driver;
use think\Exception;
use app\publish\queue\EbayRelistQueuer;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbayModelPromotion;

class EbaySetPromotionalSaleListings extends AbsTasker
{
    private $token;
    private $saleId;
    private $acInfo;
    private $ebayApi;
    public function getName()
    {
        return "同步线上修改的listing促销设置";
    }
    
    public function getDesc()
    {
        return "同步线上修改的listing促销设置";
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
        #self::SetPromotionalSaleListings();
    }

    public function SetPromotionalSaleListings($sId,$accountId,$site,$itemIds)
    {
        try {
            $proMod = new EbayModelPromotion();
            $this->acInfo = Cache::store('EbayAccount')->getTableRecord($accountId);#获取账号信息
            $config = $this->createConfig($this->acInfo, $site);
            $this->ebayApi = new EbayApi($config);
            $saleId = $proMod->where(['id' => $sId])->find();
            if ($saleId['end_date'] < time()) {
                throw new Exception('该listing使用的促销模板已过期');
            }
            if (empty($saleId['promotional_sale_id'])) {
                $accountInfo = Cache::store('EbayAccount')->getTableRecord($accountId);
                $res = (new EbayListingHelper())->promotionalSale($accountInfo['token'], $saleId);
                if ($res['result'] === false) {
                    throw new Exception('同步促销模板：'.$saleId['model_name'].' 出错');
                }
            }
            $xml = $this->createXml($saleId['promotional_sale_id'], $itemIds);
            $resText = $this->ebayApi->createHeaders()->__set("requesBody", $this->xml)->sendHttpRequest2();
            return $this->processingReponse($resText);
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    public function processingReponse($resText)
    {
        $reponse = isset($resText['SetPromotionalSaleListingsResponse'])?$resText['SetPromotionalSaleListingsResponse']:[];
        if($reponse){
            $res['message'] = $this->upMessage($reponse);
            if($reponse['Ack']=="Success"){
                $res['result'] = true;
            }else if($reponse['Ack']=="Warning"){
                $res['result'] = true;
            }else if($reponse['Ack']=="Failure"){
                $res['result'] = false;
            }else{
                $res['result'] = false;
            }
            return $res;
        }else{
            throw new Exception("未获取到返回信息节点，未知错误！");
        }
    }

    public function createConfig(&$acInfo,&$site)
    {
        $tokenArr = json_decode($acInfo['token'],true);
        $this->token = trim($tokenArr[0])?$tokenArr[0]:$acInfo['token'];
        $config['devID']=$acInfo['dev_id'];
        $config['appID']=$acInfo['app_id'];
        $config['certID']=$acInfo['cert_id'];
        $config['userToken']=$this->token;
        $config['compatLevel']=957;
        $config['siteID']=$site;
        $config['appMode']=0;
        $config['account_id']=$acInfo['id'];
        $config['verb'] = "SetPromotionalSaleListings";
        return $config;
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

    public function createXml($saleId,$itemIds=[],$categoryId=0,$storeId=0)
    {
        $xml ='<?xml version="1.0" encoding="utf-8"?>'."\n";
        $xml.='<SetPromotionalSaleListingsRequest xmlns="urn:ebay:apis:eBLBaseComponents">'."\n";
        $xml.="<RequesterCredentials><eBayAuthToken>{$this->token}</eBayAuthToken></RequesterCredentials>\n";
        $xml.="<Action>Add</Action>\n";
        $xml.="<AllAuctionItems>true</AllAuctionItems>\n";
        $xml.="<AllFixedPriceItems>true</AllFixedPriceItems>\n";
        $xml.="<PromotionalSaleID>{$saleId}</PromotionalSaleID>\n";
        if($itemIds){
            $xml.="<PromotionalSaleItemIDArray>\n";
            foreach($itemIds as $item){
                $xml.="<ItemID>{$item}</ItemID>\n";
            }
            $xml.="</PromotionalSaleItemIDArray>\n";
        }
        if($categoryId){
            $xml.="<CategoryID>{$categoryId}</CategoryID>\n";
        }
        if($storeId){
            $xml.="<StoreCategoryID>{$storeId}</StoreCategoryID>\n";
        }
        $xml.="</SetPromotionalSaleListingsRequest>\n";
        return $xml;
    }

}