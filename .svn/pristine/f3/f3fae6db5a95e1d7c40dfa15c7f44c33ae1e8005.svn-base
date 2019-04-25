<?php
/**
 * Created by PhpStorm.
 * User: TOM
 * Date: 2017/9/1
 * Time: 16:41
 */

namespace app\api\service;


use app\api\help\SupplierHelp;
use app\common\cache\Cache;

class Supplier extends Base
{
    public function receiveSupplier()
    {
        $postData = $this->requestData;
        Cache::store('LogisticsLog')->setLogisticsLog('receiveSupplier',$postData);
        if(!isset($postData['json_content'])){
            $this->retData = [
                'postData'=>$postData,
                'status'=>0,
                'message'=>'参数错误'
            ];
        }else{
            $productData = json_decode($postData['json_content'],true);
            if(!is_array($productData)){
                $this->retData = [
                    'postData'=>$postData,
                    'status'=>0,
                    'message'=>'数据格式错误'
                ];
            }else{
                $help = new SupplierHelp();
                $result = $help->addSupplier($productData);
                $this->retData['postData'] = $postData;
                $this->retData['response'] = $result;
            }
        }
        return $this->retData;
    }
    public function updateSupplier(){
        $postData = $this->requestData;
        if(!isset($postData['json_content'])){
            $this->retData = [
                'postData'=>$postData,
                'status'=>0,
                'message'=>'参数错误'
            ];
        }else{
            $productData = json_decode($postData['json_content'],true);
            if(!is_array($productData)){
                $this->retData = [
                    'postData'=>$postData,
                    'status'=>0,
                    'message'=>'数据格式错误'
                ];
            }else{
                $help = new SupplierHelp();
                $result = $help->updateSupplier($productData);
                $this->retData['postData'] = $postData;
                $this->retData['response'] = $result;
                Cache::store('LogisticsLog')->setLogisticsLog('updateSupplier',$this->retData);
            }
        }
        return $this->retData;
    }

}