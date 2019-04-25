<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-3-26
 * Time: 上午10:09
 */

namespace app\publish\service;


use org\Curl;

class AliexpressService
{
    private static $apiServiceUrl='http://120.27.143.32/api';
    private static $ali_express_token = 'ALIEXPRESSQWERTYUIOPASDFGHJKLZXCVBNM';

    //private static $apiServiceUrl='http://fuck2018.com/api';
    public static function execute($params)
    {
        if(isset($params['access_token']))
        {
            $params['token']=$params['access_token'];
        }
        if(isset($params['class']))
        {
            $params['className']=$params['class'];
        }

        $time = time();
        $ali_express_token = md5(md5(self::$ali_express_token).'|'.$time);
        $params['time'] = $time;
        $params['ali_express_token'] = $ali_express_token;
        $params['requestParams']=json_encode($params);

        $json = Curl::curlPost(self::$apiServiceUrl,$params);
        $response = json_decode($json,true);
        if(is_array($response))
        {
            $respObject =unsnakeArray(self::managerReturn($response));

            if(isset($respObject['result']) && $respObject['result'])
            {
                $result = json_decode($respObject['result'],true);

                if(isset($result['result']) && $result['result'])
                {
                    $returnObject =unsnakeArray(self::managerReturn($result['result']));
                }else{
                    $returnObject =unsnakeArray(self::managerReturn($result));
                }
                return $returnObject;
            }else{
                return $respObject;
            }
        }else{
            return ['error_message'=>'系统错误！','result'=>false];
        }

    }
    public static function execute1($params)
    {
        if(isset($params['access_token']))
        {
            $params['token']=$params['access_token'];
        }
        if(isset($params['class']))
        {
            $params['className']=$params['class'];
        }
        $params['requestParams']=json_encode($params);

        $json = Curl::curlPost(self::$apiServiceUrl,$params);
        $response = json_decode($json,true);

        if(is_array($response))
        {
            $respObject =unsnakeArray(self::managerReturn($response));
            if(isset($respObject['result']) && $respObject['result'])
            {
                $result = json_decode($respObject['result'],true);
                if(isset($result['result']))
                {
                    $returnObject =unsnakeArray(self::managerReturn($result['result']));
                }else{
                    $returnObject =unsnakeArray(self::managerReturn($result));
                }
                return $returnObject;
            }else{
                return $respObject;
            }
        }else{
            return ['error_message'=>'系统错误！','result'=>false];
        }

    }
    public static function managerReturn($return)
    {
        $newReturn=[];
        foreach ($return as $name=>$value)
        {
            if($name=='code')
            {
                $newReturn['error_code']=$value;
            }elseif($name=='msg'){
                $newReturn['error_message']=$value;
            }elseif($name=='error_msg'){
                $newReturn['error_message']=$value;
            }else{
                $newReturn[$name]=$value;
            }
        }
        return $newReturn;
    }
}