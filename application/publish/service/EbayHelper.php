<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\publish\service;
use app\common\model\Goods;
use app\common\model\ebay\EbayShipping;
use app\common\model\ebay\EbayRefundoption;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayCustomCategory;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingSetting;
use app\common\model\ebay\EbayCategorySpecific;
use service\ebay\EbayApi;
use think\Model;
 

/**
 * Ebay刊登商品助手类
 *
 * @author joy
 * @editor rocky
 */
class EbayHelper 
{
    private $cusCa;

    public function __construct(){
        $this->cusCa = new EbayCustomCategory();
    }

    public function goodsExist($goods_id=0)
    {
        $goodsModel = new Goods();
        return $goodsModel->isHas($goods_id);
    }
    
    public  function shipping()
    {
        $shipping = new EbayShipping();
        return $shipping->select();
    }
    
    public function refundoption()
    {
        $refund = new EbayRefundoption();
        
        return $refund->select();
    }

    #获取ebay在线listing列表
    public function getEbayListings($order,$sort,$field,$wh=array(),$whOr=array(),$page=1,$pageSize=20){
        $ebayListing = new EbayListing();
        $rows = $ebayListing->getListings($order,$sort,$field,$wh,$whOr,$page,$pageSize);
        return $rows;
    }

    #获取ebay类目属性
    public function getEbaySpecifics($cate){

        set_time_limit(0); 
        $verb = "GetCategorySpecifics";
        $ebayAccount = new EbayAccount();#获取token
        $ebaySpec = new EbayCategorySpecific();

        $acInfo = $ebayAccount->get(175);
        $tokenArr = json_decode($acInfo->token,true);
        $token = $tokenArr[0]?$tokenArr[0]:$acInfo->token;

        $config['devID']=$acInfo['dev_id'];
        $config['appID']=$acInfo['app_id'];
        $config['certID']=$acInfo['cert_id'];
        $config['userToken']=$token;
        $config['compatLevel']=957;
        $config['siteID']=$cate['site'];
        $config['verb']=$verb;
        $config['appMode']=0;
        $config['account_id']=$acInfo['id'];
        $ebayApi = new EbayApi($config);    

        $xml = $this->createXml($token,$cate);
        $resText = $ebayApi->createHeaders()->__set("requesBody",$xml)->sendHttpRequest2();
        // echo "<pre>";
        // print_r($resText);die;
        if(isset($resText['GetCategorySpecificsResponse'])){
            $response = $resText['GetCategorySpecificsResponse'];
            if($response['Ack']=="Success" && isset($response['Recommendations']['NameRecommendation'])){
                $specs = isset($response['Recommendations']['NameRecommendation'][0])?$response['Recommendations']['NameRecommendation']:array($response['Recommendations']['NameRecommendation']);
                foreach($specs as $k => $v){
                    if(!empty($v['Name']) || $v['Name']==0){
                        $rows['category_id'] = $cate['category_id'];
                        $rows['category_specific_name']=$v['Name'];
                        $rows['platform']="ebay";    
                        $rows['site']=$cate['site'];
                        $rows['selection_mode']=isset($v['ValidationRules']['SelectionMode'])?$v['ValidationRules']['SelectionMode']:"";
                        $rows['value_type']=isset($v['ValidationRules']['ValueType'])?$v['ValidationRules']['ValueType']:"";
                        $rows['max_values']=isset($v['ValidationRules']['MaxValues'])?$v['ValidationRules']['MaxValues']:0;
                        $rows['min_values']=isset($v['ValidationRules']['MinValues'])?$v['ValidationRules']['MinValues']:0;
                        $rows['variation_specifics']=isset($v['ValidationRules']['VariationSpecifics'])?$v['ValidationRules']['VariationSpecifics']:"";
                        $rows['variation_picture']=isset($v['ValidationRules']['VariationPicture'])?$v['ValidationRules']['VariationPicture']:"";
                        $rows['relationship']=isset($v['ValidationRules']['Relationship'])?1:0;

                        #同步数据库
                        $specId = $ebaySpec->syncSpecifics($rows);
                        if(isset($v['ValueRecommendation'])){
                            $specArr=isset($v['ValueRecommendation'][0])?$v['ValueRecommendation']:[$v['ValueRecommendation']];
                            if(count($specArr)){
                                foreach($specArr as $karr => $varr){
                                    if(!empty($varr['Value']) || $varr['Value']==0){
                                        $rowsDetail['ebay_specific_id'] = $specId;
                                        $rowsDetail['category_specific_name'] = $v['Name'];
                                        $rowsDetail['category_specific_value'] = $varr['Value']===0?0:empty($varr['Value'])?''
                                        :$varr['Value'];
                                        $rowsDetail['category_id'] = $cate['category_id'];
                                        $rowsDetail['site'] = $cate['site'];
                                        if(isset($varr['ValidationRules']['Relationship'])){
                                            $parInfo=isset($varr['ValidationRules']['Relationship'][0])?$varr['ValidationRules']['Relationship']:array($varr['ValidationRules']['Relationship']);
                                            foreach($parInfo as $pk => $pv){
                                                $rowsDetail['parent_name']=$pv['ParentName'];
                                                $rowsDetail['parent_value']=$pv['ParentValue'];
                                                #同步数据库
                                                $ebaySpec->syncSpecificsDetail($rowsDetail);
                                            }
                                        }else{
                                            #同步数据库
                                            $ebaySpec->syncSpecificsDetail($rowsDetail);
                                        }
                                        unset($rowsDetail);
                                    }
                                }
                            }
                        }   
                        unset($rows);
                    }
                }
            }
        }
    }

    public function createXml($token,$cate){
        $requestBody = '<?xml version="1.0" encoding="utf-8"?>';
        $requestBody.= '<GetCategorySpecificsRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $requestBody.= '<WarningLevel>High</WarningLevel>';
        $requestBody.= '<CategorySpecific>';
        $requestBody.= '<CategoryID>'.$cate['category_id'].'</CategoryID>';
        $requestBody.= '</CategorySpecific>';
        $requestBody.= '<RequesterCredentials>';
        $requestBody.= '<eBayAuthToken>'.$token.'</eBayAuthToken>';
        $requestBody.= '</RequesterCredentials>';
        $requestBody.= '</GetCategorySpecificsRequest>';
        return $requestBody;
    }

    #获取发货处理时间
    public function getEbaySetingByItemId($itemidArr){
        $itemidStr = implode(",",$itemidArr);
        $itemidStr = "(".$itemidStr.")";
        $ebayListing = new EbayListing();
        $set = new EbayListingSetting();

        $list = $ebayListing->query("select id from ebay_listing where item_id in {$itemidStr}");

        $listStr="(";
        foreach($list as $kl => $vl){
            $listStr.=$vl['id'].",";
        }
        $listStr = substr($listStr,0,-1);
        $listStr.=")";
        if(!empty($list)){
            $info=$set->query("select * from ebay_listing_setting where listing_id in {$listStr} order by dispatch_time_max limit 0,1");
            return array("result"=>true,"DispatchTimeMax"=>$info[0]['dispatch_time_max']);
        }else{
            return array("result"=>false,"DispatchTimeMax"=>0);
        }
    }

}
