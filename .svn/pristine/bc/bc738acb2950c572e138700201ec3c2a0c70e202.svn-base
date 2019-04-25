<?php
namespace app\api\controller;

use app\goods\service\GoodsHelp;
use think\Controller;
use think\Exception;
use think\Request;
use app\common\controller\Base;
use app\common\cache\Cache;
use think\Db;
use app\common\model\ebay\EbayNotification;


class  Index 
{

    //接收ebay 的通知消息
    public function index()
    {           
        $request = Request::instance();
        $params  = $request->param();
        $input   = $request->getInput();       
        //$xml = simplexml_load_file($input);
        //$data = json_decode(json_encode($input),TRUE);               
        
        $clean_xml = str_ireplace(['soapenv:', 'SOAP:'], '', $input);
        $data      = simplexml_load_string($clean_xml);
        $body      = $data->Body;
        $getItem   = $body->GetItemResponse;
        
        $ebayNotification = new EbayNotification();
        if ($getItem) {
            if ($getItem->Ack == "Success") { 
                $item                          = $getItem->Item;
                
                $currency                      = $item->Currency;
                $item_id                       = $item->ItemID;
                $start_time                    = $item->ListingDetails->StartTime;
                $end_time                      = $item->ListingDetails->EndTime;
               //$start_price                  = $item->ListingDetails->ConvertedStartPrice;
                $paypal_email_address          = $item->PayPalEmailAddress;
                $quantity                      = $item->Quantity;
                $seller_email                  = $item->Seller->Email;
                $feedback_score                = $item->Seller->FeedbackScore;
                $positive_feedback_percent     = $item->Seller->PositiveFeedbackPercent;
                $feedback_rating_star          = $item->Seller->FeedbackRatingStar;
                $user_id                       = $item->Seller->UserID;
                $site                          = $item->Site;
                $start_price                   = $item->StartPrice;
                $title                         = $item->Title;
                $sku                           = $item->SKU;
                
                $data = array (
                            'currency'                    => $currency,
                            'item_id'                     => $item_id,
                            'start_time'                  => strtotime($start_time),
                            'end_time'                    => strtotime($end_time),
                            'start_price'                 => $start_price,
                            'paypal_email_address'        => $paypal_email_address,
                            'quantity'                    => $quantity,
                            'seller_email'                => $seller_email,
                            'feedback_score'              => $feedback_score,
                            'positive_feedback_percent'   => $positive_feedback_percent,
                            'feedback_rating_star'        => $feedback_rating_star,
                            'user_id'                     => $user_id,
                            'site'                        => $site,
                            'title'                       => $title,
                            'sku'                         => $sku
                            
                        );                
                $ebayNotification->save($data);
                file_put_contents("/tmp/ebay.log",print_r($item,true),FILE_APPEND);
                
                
            }
        }
        echo '200';exit;
    }
    private function returnThumb(string $thumb): string
    {
        if ($thumb) {
            $result = parse_url($thumb);
            $path = $result['path'];
            $arr = explode('.',$path);
            $ext = $arr[1];
            $p = $arr[0];
            $arr1 = explode('_',$p);

            return $arr1[0].".".$ext;
        }
        return '';
    }
    public function test(){
//        $app = new \app\carrier\service\Shipping();
//       $html = $app->getSelfControlLabel('1060775919020545504',944);
//       if($html['success']==true){
//           echo $html['file'];exit;
//       }else{
//
//           halt($html['msg']);
//       }

       try{
           $keys= Cache::store('DeliveryCheck')->getAllSkuKeys(999998);
           halt($keys);
         //  $shipping_id = 944;
         // echo $app->getLabelHtml(1060775919020545504,$shipping_id);exit;


//            $app = new \app\carrier\service\Shipping();
          // $result = $app->getLabel('1055426902954743264');//151390857101
          // return  json($result,200);
         // $result = $app->uploadShipping('1055426902954743264',false);


//           $ModelPackageCollection = new \app\common\model\PackageCollection();
//           $aCollection = $ModelPackageCollection->where('id',258)->order('id desc')->limit(1)->select(false);
//           halt($aCollection);

//           $goods = new \app\goods\service\GoodsImport();
//           $goods->getExportSkuData([]);

          //  $result =  $app->getTrackingNumber('1055426854871242208');
           // halt($result);app\order\service\PackageService
//            $PickingPackageService = new \app\warehouse\service\PickingPackageService(1000167);
//            $pickingPackageDetail = $PickingPackageService->packageDetail();
//            halt($pickingPackageDetail);
//            $o = new \app\goods\task\GoodsPushListing();
//            $o->execute();
//
//            $PickingPackageService = new \app\warehouse\service\PickingPackageService(1000108);
//           $detail = $PickingPackageService->packageDetail();
//           halt($detail);
//             $tmp = $PickingPackageService->packageDetailByLabel('MXAHN6032194168YQ',false);
//             return json($tmp,200);
//            $PickingPackageService = new \app\warehouse\service\PickingPackageService(1000108);
//            $pickingPackageDetail = $PickingPackageService->packageDetail();
//
//            $PackageService = new  \app\order\service\PackageService();
//            $aPackageInfo = $PackageService->getPackageInfoByNumber('P151396493701');
            //halt($tmp);

//            $app = new \app\warehouse\service\PackageCollection();
//           halt($app->is_open_weight());
//            $barcode = new \barcode\BarcodeNew('UG076848128CN');
//            $barcode->createBarCode();
            //$a = Cache::store('shipping')->getShipping(1052);
            //halt($a);
        }catch (Exception $ex){
            halt($ex->getMessage().$ex->getFile().$ex->getLine());
        }




    }


    private function html(){
        $html = '';


    }




}

?>