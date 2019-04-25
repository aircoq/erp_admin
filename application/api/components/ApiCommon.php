<?php
namespace app\api\components;

use app\common\service\Encryption;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/6
 * Time: 10:38
 */
class ApiCommon
{
    /** 生产加密 TOKEN
     * @param $key
     * @return string
     */
    public static function createToken($key)
    {
        $encryption = new Encryption();
        return $encryption->encode($key).'@@'.uniqid();
    }

    /** 解开用户TOKEN
     * @param $token
     * @return int
     */
    public static function undoToken($token)
    {
        $encryption = new Encryption();
        $token_array = explode("@@",$token);
        if(count($token_array) != 2){
            return 0;
        }
        $id = $encryption->decode($token_array[0]);
        return intval($id);
    }
}