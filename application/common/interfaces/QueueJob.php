<?php
/**
 * Created by PhpStorm.
 * User: wuchuguang
 * Date: 17-8-4
 * Time: 上午10:02
 */

namespace app\common\interfaces;


interface QueueJob
{
    const LOG_TYPE_OK = 0;
    const LOG_TYPE_FAIL = 1;
    const LOG_TYPE_EXCEPTION = 2;
    const LOG_TYPE_ERROR = 3;
    public function getName():string ;
    public function getDesc():string ;
    public function getAuthor():string ;

    public static function jobInfo():array ;

}