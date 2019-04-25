<?php
/**
 * Created by PhpStorm.
 * User: rondaful_user
 * Date: 2019/1/3
 * Time: 11:33
 */

namespace app\index\queue;


use app\common\service\SwooleQueueJob;
use app\index\service\EbayAccountService;
use think\Exception;

class EbayRefreshToken extends SwooleQueueJob
{
    protected static $maxRunnerCount = 1000;
    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    public function getName(): string
    {
        return "eBay刷新token";
    }

    public function getDesc(): string
    {
        return "eBay刷新token";
    }

    public function getAuthor(): string
    {
        return "wlw2533";
    }

    public function execute()
    {
        try {
            $id = $this->params;
            if (!is_numeric($id)) {
                throw new Exception('未知参数' . $id);
            }
            $res = (new EbayAccountService())->refreshToken($id);
            if ($res['result'] === false) {
                throw new Exception($res['message']);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

}