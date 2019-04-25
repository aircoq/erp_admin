<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 2018/9/17
 * Time: 17:35
 */

namespace app\customerservice\queue;

use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use app\common\service\SwooleQueueJob;
use app\customerservice\service\ShopeeDisputeService;
use think\Exception;

class ShopeeReturnQueue extends SwooleQueueJob
{

    public function getName(): string
    {
        return "抓取Shopee退货清单";
    }

    public function getDesc(): string
    {
        return "抓取Shopee退货清单";
    }

    public function getAuthor(): string
    {
        return "denghaibo";
    }

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

    public function execute()
    {
        set_time_limit(0);
        try {
            if (is_numeric($this->params)) {
                $account_id = $this->params;
            } else if (isset($this->params['account_id'])) {
                $account_id = $this->params['account_id'];
            } else {
                throw new Exception('队列参数丢失');
            }
//            var_dump($this->params);
            //默认下载数据时间段
            $overTime = (isset($this->params['overTime']) && $this->params['overTime'] != 0) ? $this->params['overTime']: 0;
            $lastTime = (isset($this->params['lastTime']) && $this->params['lastTime'] != 0) ? $this->params['lastTime']: 360;
            $paramRule = $overTime.'_'.$lastTime;

            $create_time_to = strtotime(sprintf('-%d days',$overTime));
            $create_time_from   = $create_time_to - 3600*$lastTime;

            $cache = Cache::store('ShopeeAccount');
            $syncTime = $cache->getReturnSyncTime($account_id, $paramRule);//获取账号退货最近同步时间

            $pageOffset = 0;
            $pageLength = 40;//@todo
            $returnService = (new ShopeeDisputeService())->getReturnService($account_id);
//            $uniqueQueue = new UniqueQueuer(ShopeeReturnSyncQueue::class);
            $syncToMysql = new ShopeeDisputeService();
            while (true){

                $response = $returnService->getReturnList($pageOffset, $pageLength, $create_time_from, $create_time_to);//默认处理15天内的退货数据
//                var_dump('<pre/>', $pageOffset, $pageLength, $create_time_from, $create_time_to, $response);//die;

                $pageOffset += $pageLength;
                if(!isset($response['returns'])) {
                    break;
                }
                foreach ($response['returns'] as $v){
//                    if($v['update_time'] <= $syncTime){//??? @todo 如果更新时间小于最后同步时间，说明已被同步
//                        continue;
//                    }
                    $v['sync_time'] = $syncTime;
                    $v['returnsn'] = sprintf('%.0f',$v['returnsn']);//避免bigint转换成科学计数法
                    $v['account_id'] = $account_id;
                    $syncToMysql->syncReturn($v);
//                    $uniqueQueue->push($v);
//                    var_dump($v['returnsn']);//die;
                }
                if(!$response['more']){
                    break;
                }
            }
            $cache->setReturnSyncTime($account_id, $paramRule, $create_time_to);//更新账号退货同步时间

        }catch (Exception $e){
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }



}