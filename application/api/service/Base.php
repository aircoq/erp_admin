<?php
namespace app\api\service;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/8
 * Time: 15:22
 */
class Base
{
    public $retData = ['status' => 1];
    public $requestData = [];    //接收的参数
    public $object;

    /** 返回信息
     * @param $returnData
     * @return mixed
     */
    public function returnMessage($returnData)
    {
       if(isset($returnData['message']))
       {
           return $returnData['message'];
       }
        return $returnData['status'];
    }

    /** 获取返回数据后数据合成
     * @param $returnData
     * @return mixed
     */
    public function requestEnd($returnData)
    {
        $returnData = $this->retData;
        $returnData['message'] = $this->returnMessage($returnData);
        return $returnData;
    }
}