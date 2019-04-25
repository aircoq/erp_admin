<?php

namespace app\carrier\queue;

use app\common\model\OrderPackage;
use app\common\service\SwooleQueueJob;
use think\Exception;
use app\warehouse\service\DeliveryCheck;
use app\order\service\PackageService;
use app\carrier\service\Shipping;
use app\carrier\service\PackageLabelFileService;

class UploadHtmlLabelQueue extends SwooleQueueJob
{
    public function getName(): string
    {
        return '生成html面单文件';
    }

    public function getDesc(): string
    {
        return '生成html面单文件';
    }

    public function getAuthor(): string
    {
        return "詹老师";
    }

    public function execute()
    {
        $params = (string)$this->params;
        try{
            $Shipping = new Shipping();
            $PackageService = new PackageService();
            $aPackageInfo  = $PackageService->getPackageInfoById($params);
            if(!$aPackageInfo){
                throw new Exception('该包裹不存在！');
            }
            $aPackageInfo = reset($aPackageInfo);
            $result  = $Shipping->getSelfControlLabel($aPackageInfo['id'],$aPackageInfo['shipping_id']);
            if($result['success'] == true){
                $service = new PackageLabelFileService();
                $aPackageInfo['number'] = $PackageService->analysisNumber($aPackageInfo['number']);
                $label_url = $service->uploadHtmlFile($aPackageInfo['number'], base64_encode($result['file']), 'html');
                $orderPackage = new OrderPackage();
                $orderPackage->where('id',$params)->update(['providers_label'=>$label_url]);
            }else{
                throw new Exception($result['msg']);
            }
        }catch (\Exception $ex){
            throw $ex;
        }

    }
}