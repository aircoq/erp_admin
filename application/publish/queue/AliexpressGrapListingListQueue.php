<?php
namespace app\publish\queue;

use app\common\exception\QueueException;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\aliexpress\AliexpressProduct;
use app\common\service\SwooleQueueJob;
use app\common\service\CommonQueuer;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\publish\service\AliexpressTaskHelper;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-23
 * Time: 上午11:28
 */
class AliexpressGrapListingListQueue extends SwooleQueueJob
{
    private $helpServer = null;
    private $statusType = [];
    private $queue = null;
    private $cache = null;
    const PRIORITY_HEIGHT = 10;
    protected $maxFailPushCount = 1;
    private $gmtModifiedTime='';
    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function getName():string
    {
        return '速卖通抓取listing列表队列';
    }
    public function getDesc():string
    {
        return '速卖通抓取listing列表队列';
    }
    
    public function getAuthor():string
    {
        return 'joy';
    }
    
    protected function init()
    {
        $this->helpServer = new AliexpressTaskHelper();
        $this->statusType = AliexpressProduct::PRODUCT_STATUS;
        $this->queue = new UniqueQueuer(AliexpressGrapListingQueue::class);
        $this->productCache = Cache::store('AliexpressRsyncListing');
        $this->cache = Cache::store('AliexpressAccount');
    }
    
    public function execute()
    {
        set_time_limit(0);
        $accountId = $this->params;
        try {
            if($accountId){
                $this->accountId = $accountId;
                $account= $this->cache->getAccountById($this->accountId);
                //$account  = AliexpressAccount::where('id',$this->accountId)->find();
                if ($account) {
                    $account = is_object($account)?$account->toArray():$account;
                    $account['token'] = $account['access_token'];
                    $account['refreshtoken'] = $account['refresh_token'];
                } else {
                    throw new Exception($this->accountId . '账号信息为空');
                }
                $this->getProductByModifiedTime($account);
            }
        } catch (\Exception $ex) {
           throw $ex;
        }
    }

    /**
     * 根据修改时间获取商品数据
     * @param $account
     */
    private function getProductByModifiedTime($account){
        try{
            foreach ($this->statusType as $status) {
                $gmt_modified_start = $this->getGmtModifiedStartTime($status);//开始时间是上次获取的第一个商品的修改时间
                $gmt_modified_end = $this->getGmtModifiedEndTime($status);//结束时间永远是当前时间
                $query=[
                    'product_status_type'=>$status,
                    'gmt_modified_start'=>$gmt_modified_start,
                    'gmt_modified_end'=>$gmt_modified_end
                ];
                $this->getProductListNow($account,$query,$status);
            }
            //所有状态都获取成功，则更新账号的同步时间
            $this->cache->setListingSyncTime($this->accountId, time());
        }catch (Exception $exp){
            throw $exp;
        }
    }

    private function getProductListNow($account,$query,$status){
        $message = [];
        $page = 1;
        do {
            //获取产品列表
            $query['current_page']=$page;
            $response = $this->helpServer->findproductinfolistquery($account,$query);

            if (isset($response['error_message'])) {
                $message = $response['error_message'];
                break;
            }
            if (!isset($response['totalPage']) || !isset($response['data']) || empty($response['data'])) {
                break;
            }
            $totalPage = $response['totalPage'];

            foreach ($response['data'] as $k=>$product) {
                if($k==0 && $page==1){ //第一页的第一个商品的修改时间
                    $this->gmtModifiedTime = $product['gmtModified'];
                }
                if (false === $this->checkModifiedTime($product['productId'], strtotime($product['gmtModified']))) {
                    $queue = $this->accountId . "|" . $product['productId'];
                    $this->queue->push($queue);
                    Cache::handler(true)->hSet('aliexpress:grap:listing','queue',$queue);
                }else{
                    continue;
                }
            }
            //保证当前状态下的所有商品都正常获取到
            if($page==$totalPage && $this->gmtModifiedTime){
                $this->setGmtModifiedStartTime($status,$this->gmtModifiedTime);
            }
            $page++;
        } while ($page <= $totalPage);
        if ($message) {
            throw new QueueException($message);
        }
    }
    /**
     * 根据商品审核状态获取商品数据
     * @param $account
     * @throws Exception
     */
    private function getProductByStatus($account){
        foreach ($this->statusType as $status) {
            $this->process($account,$status);
        }
    }
    /**
     * 获取账号gmt_modified_start的时间
     * @param $account
     */
    private function getGmtModifiedStartTime($status){
        $gmtModifiedStartTime = $this->productCache->getGmtModifiedStartTime($this->accountId,$status)?:'';
        return $gmtModifiedStartTime;
    }
    /**
     * 获取账号gmt_modified_end的时间
     * @param $account
     */
    private function getGmtModifiedEndTime($status){
        $gmtModifiedEndTime = $this->productCache->getGmtModifiedEndTime($this->accountId,$status)?:now();
        return $gmtModifiedEndTime;
    }
    /**
     * 设置账号gmt_modified_start的时间
     * @param $account
     */
    private function setGmtModifiedStartTime($status,$time){
        $this->productCache->setGmtModifiedStartTime($this->accountId,$status,$time);
    }
    /**
     * 设置账号gmt_modified_end的时间
     * @param $account
     */
    private function setGmtModifiedEndtTime($status,$time){
        $this->productCache->setGmtModifiedEndTime($this->accountId,$status,$time);
    }
    /**
     * 处理程序
     * @param string 状态
     */
    private function process($account,$status)
    {
        $message = [];
        $page = 1;
        $totalPage = 0;
        do {
            //获取产品列表

            $response = $this->helpServer->getAliProductList($account,$status, $page);

            if (isset($response['error_message'])) {
                $message = $response['error_message'];
                break;
            }
            if (!isset($response['totalPage']) || !isset($response['data']) || empty($response['data'])) {
                break;
            }
            $totalPage = $response['totalPage'];

            foreach ($response['data'] as $product) {
                if (false === $this->checkModifiedTime($product['productId'], strtotime($product['gmtModified']))) {
                    $queue = $this->accountId . "|" . $product['productId'];
                    $this->queue->push($queue);
                    Cache::handler(true)->hSet('aliexpress:grap:listing','queue',$queue);
                    //(new CommonQueuer(AliexpressGrapListingQueue::class))->push($queue);
                }else{
                    continue;
                }
            }
            $page++;
        } while ($page <= $totalPage);
        if ($message) {
            throw new Exception($message);
        }
    }
    
    private function checkModifiedTime($productId, $time)
    {
        $cacheInfo = $this->productCache->getProductCache($this->accountId, $productId);

        if ($cacheInfo && $cacheInfo['gmt_modified'] == $time) {
            return true;
        }
        
        return false;
    }
}
