<?php
namespace app\api\service;

use app\common\service\Barcode as BarcodeService;
use app\order\service\PackageService;
use think\Exception;

class Barcode extends Base
{

    const HTML = '';
    public function codeTag()
    {
        $code = $this->requestData['code'] ?? 123456;
        $width = $this->requestData['width'] ?? 1.6;
        $height =  $this->requestData['height'] ?? 50;
        $barcode = new BarcodeService('png');
        header("Content-Type: image/png");
        echo $barcode->create($code, $width, $height);
    }

    public function testShipping()
    {
        try{
            $app = new \app\carrier\service\Shipping();
            $package_id = $this->requestData['package_id'] ?? '1060775919020545504';
            $PackageService = new PackageService();
            $aPackage = $PackageService->deliveryCheckGetPackageInfoById($package_id);
            if (!$aPackage) {
                throw new Exception('该包裹不存在');
            }
            $aPackage = reset($aPackage);
            $ret = $app->getSelfControlLabel($package_id, $aPackage['shipping_id']);
            if($ret['success']==true){
                echo $ret['file'];exit;
            }
            throw new Exception($ret['msg']);
        }catch (Exception $ex){
            echo $ex->getMessage().$ex->getFile().$ex->getLine();exit;
        }



    }

}