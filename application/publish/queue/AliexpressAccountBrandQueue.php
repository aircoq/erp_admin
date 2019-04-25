<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-4
 * Time: 上午11:38
 */

namespace app\publish\queue;


use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\service\SwooleQueueJob;
use app\publish\service\AliexpressTaskHelper;
use think\Exception;

class AliexpressAccountBrandQueue extends SwooleQueueJob
{
    protected static $priority=self::PRIORITY_HEIGHT;
    /**
     * @doc 失败时下次处理秒数
     * @var int
     */
    protected $failExpire = 600;
    /**
     * @doc 失败最大重新处理次数
     * @var int
     */
    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }
    public function getName(): string
    {
        return '速卖通账号授权品牌队列';
    }

    public function getDesc(): string
    {
        return '速卖通账号授权品牌队列';
    }

    public function getAuthor(): string
    {
        return 'hao';
    }

    public function execute()
    {
         set_time_limit(0);

         try{
             $params = $this->params;
             if(!$params) {
                return;
             }

             $accountId = $params['account_id'];
             $categoryId = $params['category_id'];
             $accountModel = new AliexpressAccount();
             $account = $accountModel->where('id','=',$accountId)->find();
             if(!$account) {
                 return;
             }
             $account = $account->toArray();
             
             $service = new AliexpressTaskHelper();
             $service->getAeBrandAttribute($account,$categoryId);
			 
			 return true;
         }catch (Exception $exp){
             throw new QueueException($exp->getMessage());
         }
    }
}