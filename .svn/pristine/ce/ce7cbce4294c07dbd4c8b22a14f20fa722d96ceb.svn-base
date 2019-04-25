<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-4-7
 * Time: 上午10:02
 */

namespace app\common\exception;


use app\common\cache\CacheModel;

class CacheModelException extends \Exception
{
    public function __construct(CacheModel $cacheModel, $message)
    {
        $this->message = "CacheModel:".get_class($cacheModel)." exception:".$message;
    }
}