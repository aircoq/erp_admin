<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-25
 * Time: 下午5:23
 */

namespace app\publish\task;

use app\common\cache\Cache;
use app\common\cache\driver\LazadaAccount;
use app\common\model\lazada\LazadaSite;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\helper\lazada\LazadaHelper;
use app\publish\queue\LazadaSyncItemDetailQueue;
use think\Exception;

class LazadaSyncListing extends AbsTasker
{
    public function getName()
    {
        return 'lazada获取商品列表';
    }

    public function getDesc()
    {
        return 'lazada获取商品列表';
    }

    public function getCreator()
    {
        return 'thomas';
    }

    public function getParamRule()
    {
       return [];
    }

    public function execute()
    {
        set_time_limit(0);
        try{
//            $accounts = Cache::store('LazadaAccount')->getAllAccounts();
            $wh = [
                'platform_status' => 1,
                'app_key' => ['neq', ''],
                'status' => 1,
            ];
            $accounts = (new \app\common\model\lazada\LazadaAccount())->where($wh)->select();
            foreach ($accounts as $k => $v) {
                $accountId = $v['id'];
                $page = $offset = 0;
                $pageSize = 100;
                //获取上次拉取的时间
                $updateTime = Cache::store('LazadaItem')->getLazadaLastRsyncListingSinceTime($accountId);
                $updateTime = $updateTime ?? '';
                $updateTime = '';
                do {
                    try {
                        $response  = (new LazadaHelper())->syncListing($accountId, $offset, $pageSize, $updateTime);
                    } catch (Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                    if (is_string($response)) {
                        continue;
                    }
                    if (empty($response)) {
                        break;
                    }
                    $totalPage = ceil($response['total_products'] / $pageSize);
                    foreach ($response['products'] as $k=>$v) {
//                        //将商品的item_id 放入队列
                        $queue = $accountId. '|'. $v['item_id'];
                        (new LazadaSyncItemDetailQueue)->execute($queue);
////                        (new UniqueQueuer(LazadaSyncItemDetailQueue::class))->push($queue);
                    }
                    $offset += 100;
                    $page++;
                } while ($page <= $totalPage);
                //更新拉取时间
//                Cache::store('LazadaItem')->setLazadaLastRsyncListingSinceTime($accountId, time());
            }
        }catch (Exception $exp){
            throw new Exception($exp->getMessage());
        }
    }

}