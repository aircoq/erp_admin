<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-3-20
 * Time: 下午3:38
 */

namespace app\common\exception;


use Exception;
use think\exception\HttpResponseException;

class JsonErrorException extends HttpResponseException
{
    public function __construct($message = "", $code = 400, Exception $previous = null)
    {
        $this->message = $message;
        parent::__construct(json_error($message,$code));
    }
}