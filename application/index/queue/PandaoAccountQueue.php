<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-11
 * Time: 上午9:32
 */

namespace app\index\queue;


use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\index\service\PandaoAccountService;
use think\Exception;

class PandaoAccountQueue extends SwooleQueueJob
{
    const PRIORITY_HEIGHT = 10;
    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }
    public function getName(): string
    {
        return 'pandao账号token刷新队列';
    }

    public function getDesc(): string
    {
        return 'pandao账号token刷新队列';
    }

    public function getAuthor(): string
    {
        return 'joy';
    }

    public function execute()
    {
        set_time_limit(0);
        try{
            $params = $this->params;
            if($params){
                $account = Cache::store('PandaoAccountCache')->getAccountById($params);
                PandaoAccountService::refressAccessToken($account);
            }
        }catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }
}