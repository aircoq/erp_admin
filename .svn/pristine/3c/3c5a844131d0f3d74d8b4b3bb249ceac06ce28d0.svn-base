<?php
namespace app\publish\task;
/**
 * 曾绍辉
 * 17-7-1
 * ebay上传图片至图床eps
*/
use service\ebay\EbayApi;
use app\common\cache\Cache;
use app\index\service\AbsTasker;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingImage;
use app\publish\service\EbayListingCommonHelper;
use think\Exception;

class EbayUploadImgs extends AbsTasker
{
    private $token;
    private $ebayApi;
    public function getName()
    {
        return "ebay上传图片至图床eps";
    }
    
    public function getDesc()
    {
        return "ebay上传图片至图床eps";
    }
    
    public function getCreator()
    {
        return "wlw2533";
    }
    
    public function getParamRule()
    {
        return [];
    }
    
    
    public function execute()
    {
        try {
            self::uploadImg();
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    public function uploadImg($listId=0){
        set_time_limit(0);
        try {
            $listInfo = EbayListing::get($listId);
            if (empty($listInfo)) {
                throw new Exception('获取listing信息失败');
            }
            $accountInfo = Cache::store('EbayAccount')->getTableRecord($listInfo->account_id);
            (new EbayListingCommonHelper())->uploadImgToEPS($listId, $accountInfo, $listInfo->site);

//            static $sendCnt = 0;
//            $verb = "UploadSiteHostedPictures";
//            $list = [];
//            if ($listId) {
//                $list = (new EbayListing())->field("id,account_id")->where(['id' => $listId])->find();
//            }
//            if ($list) {
//                #获取账号token
//                $acInfo = Cache::store('EbayAccount')->getTableRecord($list['account_id']);
//
//                $config = $this->createConfig($acInfo, $verb);
//                $ebayApi = new EbayApi($config);
//                $imgs = (new EbayListingImage())->where(['listing_id' => $list['id'], 'status' => 0])->select();
//                if (!$imgs) {#图片为空
//                    (new EbayListing())->where(['id' => $list['id']])->update(['create_date' => time()]);
//                } else {
//                    $xmls = [];
//                    foreach ($imgs as $img) {
//                        $xmls[] = $this->createXml($img, $this->token);
//                    }
//                    $response = $ebayApi->createHeaders()->sendHttpRequestMulti($xmls);
//                    $nImgsArr = $response['response'];
//                    $errorMsg = $response['error_msg'];
//                    $nImgs = [];
//                    foreach ($imgs as $k => $vimg) {
//                        $nImgs[$k] = $vimg->toArray();
//                        $nImgs[$k]['update_time'] = time();
//                        if (isset($nImgsArr[$k])) {
//                            $PictureSetMember = $nImgsArr[$k]['UploadSiteHostedPicturesResponse']['SiteHostedPictureDetails'];
//                            $nImgs[$k]['eps_path'] = isset($PictureSetMember['PictureSetMember'][3]['MemberURL']) ? $PictureSetMember['PictureSetMember'][3]['MemberURL'] : $PictureSetMember['FullURL'];
//                            $nImgs[$k]['status'] = 3;
//                            $nImgs[$k]['message'] = '';
//                        } else if (isset($errorMsg[$k])) {
//                            $nImgs[$k]['message'] = $errorMsg[$k];
//                        } else {
//                            $nImgs[$k]['message'] = 'Unknown error';
//                        }
//                    };
//                    (new EbayListingImage())->saveAll($nImgs);
//                }
//                $tolCount = (new EbayListingImage())->where(['listing_id' => $list['id']])->count();#所有图片数量
//                $count = (new EbayListingImage())->where(['listing_id' => $list['id'], 'status' => 3])->count();#上传成功的图片数量
//                Cache::handler()->set('ebay:publish:img:imgcount', $listId . ':' . $tolCount . '_' . $count);
//                if ($tolCount == $count) {#所有图片上传至eps
//                    $sendCnt = 0;
//                    return true;
//                } else {
//                    $this->uploadImg($listId);
//                    if (++$sendCnt == 5) {
//                        $sendCnt = 0;
//                        throw new Exception('图片连续5次上传失败');
//                    }
//                    $sendCnt = 0;
//                }
//            }
//            $sendCnt = 0;
        } catch (Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    public function uploadImgImmediately($imgs,$acInfo)
    {
        set_time_limit(0);
        $config = $this->createConfig($acInfo);
        $ebayApi = new EbayApi($config);
        $xmls = [];
        foreach($imgs as $k => $img){
            $xmls[$k] = $this->createXmlImmediately($img,$this->token);
        }
        $response = $ebayApi->createHeaders()->sendHttpRequestMulti($xmls);
        $responseArr = $response['response'];
        $errorMsg = $response['error_msg'];
        $upEpsImgArr = [];
        foreach($imgs as $k => $img){
            if(isset($responseArr[$k])){
                #$upEpsImgArr[$k]=$responseArr[$k]['UploadSiteHostedPicturesResponse']['SiteHostedPictureDetails']['FullURL'];
                $PictureSetMember = $responseArr[$k]['UploadSiteHostedPicturesResponse']['SiteHostedPictureDetails'];
                $upEpsImgArr[$k] = isset($PictureSetMember['PictureSetMember'][3]['MemberURL'])?$PictureSetMember['PictureSetMember'][3]['MemberURL']:$PictureSetMember['FullURL'];
            }
        }
        return $upEpsImgArr;
    }

//    public function createConfig($acInfo,$verb="UploadSiteHostedPictures")
//    {
//        $tokenArr = json_decode($acInfo['token'],true);
//        $token = trim($tokenArr[0])?$tokenArr[0]:$acInfo['token'];
//        $config['devID']=$acInfo['dev_id'];
//        $config['appID']=$acInfo['app_id'];
//        $config['certID']=$acInfo['cert_id'];
//        $config['userToken']=$token;
//        $config['compatLevel']=957;
//        $config['siteID']=0;
//        $config['verb']=$verb;
//        $config['appMode']=0;
//        $config['account_id']=$acInfo['id'];
//        $this->token = $token;
//        return $config;
//    }

    #创建xml
//    public function createXml($img,$token){
/*        $xml ="<?xml version='1.0' encoding='utf-8'?>\n";*/
//        $xml.="<UploadSiteHostedPicturesRequest xmlns='urn:ebay:apis:eBLBaseComponents'>\n";
//        $xml.="<RequesterCredentials>\n";
//        $xml.="<eBayAuthToken>".$token."</eBayAuthToken>\n";#账号token
//        $xml.="</RequesterCredentials>\n";
//        $xml.="<ExternalPictureURL>".$img['ser_path']."</ExternalPictureURL>\n";#图片地址
//        $xml.="<PictureSet>Supersize</PictureSet>";
//        $xml.="<WarningLevel>High</WarningLevel>\n";
//        $xml.="</UploadSiteHostedPicturesRequest>\n";
//        return $xml;
//    }

//    public function createXmlImmediately($img,$token){
/*        $xml ="<?xml version='1.0' encoding='utf-8'?>\n";*/
//        $xml.="<UploadSiteHostedPicturesRequest xmlns='urn:ebay:apis:eBLBaseComponents'>\n";
//        $xml.="<RequesterCredentials>\n";
//        $xml.="<eBayAuthToken>".$token."</eBayAuthToken>\n";#账号token
//        $xml.="</RequesterCredentials>\n";
//        $xml.="<ExternalPictureURL>".$img."</ExternalPictureURL>\n";#图片地址
//        $xml.="<PictureSet>Supersize</PictureSet>";
//        $xml.="<WarningLevel>High</WarningLevel>\n";
//        $xml.="</UploadSiteHostedPicturesRequest>\n";
//        return $xml;
//    }
}
