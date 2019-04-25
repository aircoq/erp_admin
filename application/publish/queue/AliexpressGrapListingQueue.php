<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-23
 * Time: 上午10:30
 */

namespace app\publish\queue;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\service\SwooleQueueJob;
use app\publish\service\AliexpressTaskHelper;

class AliexpressGrapListingQueue extends SwooleQueueJob
{
    private $cache = null;
    private $helper = null;

    public function getName():string
    {
        return '速卖通抓取listing队列';
    }
    public function getDesc():string
    {
        return '速卖通抓取listing队列';
    }
    public function getAuthor():string
    {
        return 'joy';
    }
    
    public static function swooleTaskMaxNumber():int
    {
        return 20;
    }
    
    public function init()
    {
        $this->cache = Cache::store('AliexpressAccount');
        //$this->helper = new AliexpressTaskHelper();
    }
    
    public function execute()
    {
        $params = $this->params;
        try {
            list($accountId,$productId)=explode("|",$params);
            $account= AliexpressAccount::where('id',$accountId)->find();
            // $account= $this->cache->getAccountById($accountId);
            if ($account) {
                $account = $account->toArray();
                $account['token'] = $account['access_token'];
                $account['refreshtoken'] = $account['refresh_token'];
                (new AliexpressTaskHelper())->findAeProductById($account, $productId);
            } else {
                throw new Exception($accountId . '账号信息为空');
            }
        } catch(Exception $e) {
            throw $e;
        }
    }
}
