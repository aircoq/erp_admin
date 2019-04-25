<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-6-6
 * Time: 下午5:01
 */

namespace app\publish\queue;


use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\service\SwooleQueueJob;
use app\publish\service\AliexpressTaskHelper;
use think\Exception;

class AliexpressCategoryPidQueue extends SwooleQueueJob
{
    private $config;
    public function getName(): string
    {
        return '速卖通一级分类队列';
    }

    public function getDesc(): string
    {
        return '速卖通一级分类队列';
    }

    public function getAuthor(): string
    {
        return 'hao';
    }
    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }
    public function init()
    {

    }

    public function execute()
    {
        try{
            $params = $this->params;
            if($params){

                $account = (new AliexpressAccount)->where(['id' => $params])->find();
                if(empty($account)) {
                    return;
                }

                $account = $account->toArray();
                $service = new AliexpressTaskHelper;

                $service->getAeCategory($account,0);
            }
        }catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }

    }

}