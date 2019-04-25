<?php
namespace app\publish\queue;

use app\common\exception\TaskException;
use service\ebay\EbayApi;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use think\Db;
use think\cache\driver;
use app\common\cache\Cache;
use think\Exception;
use app\common\model\ebay\EbayModelPromotion;

/**
 * rocky
 * 17-12-25
 * ebay获取促销方式账号队列
*/

class EbayGetPromotionListQueuer extends SwooleQueueJob
{
    private $acInfoCache = null;

    public function getName():string
    {
        return 'ebay获取促销方式队列';
    }

    public function getDesc():string
    {
        return 'ebay获取促销方式队列';
    }

    public function getAuthor():string
    {
        return 'zengsh';
    }

    public function init()
    {
        $this->acInfoCache = Cache::store('EbayAccount');
    }

    public function execute()
    {
        set_time_limit(0);
        $aId = $this->params;
        #$aId = 19;
        if($aId){
            $acInfo = $this->acInfoCache->getAccountById($aId);
            $tokenArr = json_decode($acInfo['token'], true);
            $token = trim($tokenArr[0]) ? $tokenArr[0] : $acInfo['token'];
            $ebayApi = $this->createApi($acInfo,$token);
            $xml = $this->createXml($token);
            $resText = $ebayApi->createHeaders()->__set("requesBody", $xml)->sendHttpRequest2();
            // echo "<pre>";
            // print_r($resText);
            if(isset($resText['GetPromotionalSaleDetailsResponse'])){
                $response=$resText['GetPromotionalSaleDetailsResponse'];
                if($response['Ack']=='Success' && !empty($response['PromotionalSaleDetails'])){
                    $PromotionalSaleDetails = isset($response['PromotionalSaleDetails']['PromotionalSale'][0])
                    ?$response['PromotionalSaleDetails']['PromotionalSale']:[$response['PromotionalSaleDetails']['PromotionalSale']];
                    foreach($PromotionalSaleDetails as $promo){
                        $this->syncModelPromotion($promo,$acInfo);
                    }
                }
            }

        }
    }

    public function syncModelPromotion($promo,$acInfo)
    {
        $data['ebay_account'] = $acInfo['id'];
        $data['model_name'] = $promo['PromotionalSaleName'];
        $data['start_date'] = strtotime($promo['PromotionalSaleStartTime']);
        $data['end_date'] = strtotime($promo['PromotionalSaleEndTime']);
        $data['promotion'] = 1;
        if($promo['DiscountType']=='Price'){#价格优惠
            $data['promotion_type']=1;
            $data['promotion_cash'] =$promo['DiscountValue'];
        }else if($promo['DiscountType']=='Percentage'){#折扣优惠
            $data['promotion_type'] = 2;
            $data['promotion_discount'] = $promo['DiscountValue'];
        }
        if($promo['PromotionalSaleType']=='FreeShippingOnly'){#只免运费
            $data['promotion_trans'] = 1;
        }else if($promo['PromotionalSaleType']=='PriceDiscountAndFreeShipping'){#免运费并且价格折扣
            $data['promotion_trans'] = 2;
        }else if($promo['PromotionalSaleType']=='PriceDiscountOnly'){#只提供价格折扣
            $data['promotion_trans'] = 3;
        }
        $data['status'] = $data['end_date']<time()?2:1;
        $data['res_status'] = isset($promo['Status'])?$promo['Status']:"";
        $data['promotional_sale_id'] = $promo['PromotionalSaleID'];#促销活动标识
        $rows = (new EbayModelPromotion())->where(['promotional_sale_id'=>$data['promotional_sale_id']])->find();
        if($rows){
            (new EbayModelPromotion())->where(['id'=>$rows['id']])->update($data);
        }else{
            (new EbayModelPromotion())->insertGetId($data);
        }
    }

    public function createApi(&$acInfo,$token)
    {
        $config['devID'] = $acInfo['dev_id'];
        $config['appID'] = $acInfo['app_id'];
        $config['certID'] = $acInfo['cert_id'];
        $config['userToken'] = $token;
        $config['compatLevel'] = 967;
        $config['siteID'] = 0;
        $config['verb'] = 'GetPromotionalSaleDetails';
        $config['appMode'] = 0;
        $config['account_id'] = $acInfo['id'];
        return new EbayApi($config);
    }

    public function createXml($token,$PromotionalSaleID=0)
    {
        $xml ='<?xml version="1.0" encoding="utf-8"?>';
        $xml.='<GetPromotionalSaleDetailsRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $xml.='<RequesterCredentials>';
        $xml.='<eBayAuthToken>'.$token.'</eBayAuthToken>';
        $xml.='</RequesterCredentials>';
        $xml.='<ErrorLanguage>en_US</ErrorLanguage>';
        $xml.='<Version>967</Version>';
        $xml.='<WarningLevel>High</WarningLevel>';
        if(intval($PromotionalSaleID)){
            $xml.='<PromotionalSaleID>'.$PromotionalSaleID.'</PromotionalSaleID>';
        }
        $xml.='<PromotionalSaleStatus>Active</PromotionalSaleStatus>';
        $xml.='<PromotionalSaleStatus>Scheduled</PromotionalSaleStatus>';
        $xml.='<PromotionalSaleStatus>Processing</PromotionalSaleStatus>';
        $xml.='</GetPromotionalSaleDetailsRequest>';
        return $xml;
    }
}