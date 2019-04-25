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

class ShopeeCancelQueue extends SwooleQueueJob
{

    public function getName(): string
    {
        return "抓取Shopee取消订单清单";
    }

    public function getDesc(): string
    {
        return "抓取Shopee取消订单清单";
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
            $cancelService = (new ShopeeDisputeService())->getOrderService($account_id);
            $syncToMysql = new ShopeeDisputeService();
            while (true){

                $response = $cancelService->getOrdersByStatus('IN_CANCEL', $create_time_from, $create_time_to, $pageOffset, $pageLength);//默认处理15天内的退货数据
//                var_dump('<pre/>', $pageOffset, $pageLength, $create_time_from, $create_time_to, $response);//die;

                $pageOffset += $pageLength;
                if(!isset($response['orders'])) {
                    break;
                }
                foreach ($response['orders'] as $v){
//                    if($v['update_time'] <= $syncTime){//??? @todo 如果更新时间小于最后同步时间，说明已被同步
//                        continue;
//                    }
                    $v['status'] = $v['order_status'];
                    unset($v['order_status']);
                    $v['account_id'] = $account_id;
                    $syncToMysql->syncCancel($v);
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