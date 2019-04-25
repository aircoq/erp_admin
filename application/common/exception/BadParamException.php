<?php
namespace app\common\exception;

use think\Exception;

/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-20
 * Time: 上午10:13
 */
class BadParamException extends Exception
{
    private $json = '';
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->json = $message;
    }

    public function getJson()
    {
        return $this->json;
    }
}