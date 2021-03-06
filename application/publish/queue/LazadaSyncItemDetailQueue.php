<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-25
 * Time: 下午6:00
 */

namespace app\publish\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\publish\helper\lazada\LazadaHelper;
use think\Exception;

class LazadaSyncItemDetailQueue //extends SwooleQueueJob
{
    public function getName(): string
    {
        return 'lazada获取商品详情队列';
    }

    public function getDesc(): string
    {
        return 'lazada获取商品详情队列';
    }

    public function getAuthor(): string
    {
        return 'thomas';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }

    public function execute()
    {
        try{
//            $params = $this->params;
//            $params = '2|my|484204023';
//            $params = '31|308210489';
            $params = '31|325360543';
            list($accountId, $itmeId) = explode("|", $params);
            $res = (new LazadaHelper())->syncItemInformation($accountId, $itmeId);
            if ($res !== true) {
                echo $res."\n";
            } else {
                echo "sync category completely\n";
            }
        } catch (Exception $exp) {
            echo $exp->getMessage();
        }
    }

}