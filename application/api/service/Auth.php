<?php
namespace app\api\service;

/**
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2017/5/23
 * Time: 18:09
 */
class Auth extends Base
{
    public function index()
    {
        $fileName = date('Y-m-d',time());
        $logFile = LOG_PATH.$fileName."_auth.log";
        file_put_contents($logFile,json_encode($_GET)."\r\n".json_encode($_POST),FILE_APPEND);
    }
}