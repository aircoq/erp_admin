<?php
namespace app\common\exception;

use Exception;
use think\exception\HttpResponseException;

/**
 * Created by PhpStorm.
 * User: phill
 * Date: 2017/7/26
 * Time: 10:16
 */
class JsonConfirmException extends HttpResponseException
{
    public function __construct($message = '', $code = 400,Exception $previous = null)
    {
        parent::__construct(json_confirm($message,$code));
    }
}