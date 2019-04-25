<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-6
 * Time: 下午5:55
 */
namespace app\listing\task;

use app\common\exception\TaskException;
use app\common\service\CommonQueuer;
use app\index\service\AbsTasker;
use app\common\cache\Cache;
use app\listing\queue\JoomSyncListingQueue;
use app\listing\service\JoomSyncListingHelper;


class JoomSyncListing extends AbsTasker
{
    public function getName()
    {
        return "Joom抓取listing列表";
    }

    public function getDesc()
    {
        return "Joom抓取listing列表";
    }

    public function getCreator()
    {
        return "zhangdongdong";
    }

    public function getParamRule()
    {
        return [];
    }


    public function execute()
    {
        set_time_limit(0);
        try {
            $shops = Cache::store('JoomShop')->getTableRecord();

            if ($shops) {
                foreach ($shops as $shop) {
                    if ($shop['is_invalid'] && $shop['is_authorization'] && isset($shop['download_listing']) && $shop['download_listing']) {

                        //查看当前帐号缓存；
                        //var_dump(Cache::store('JoomListing')->getListinglist($shop['id']));
                        $can = $this->getGrapListingStatus($shop);
                        if ($can) {
                            $config = [
                                'id' => $shop['id'],
                                'account_id' => $shop['joom_account_id'],
                                'client_id' => $shop['client_id'],
                                'client_secret' => $shop['client_secret'],
                                'access_token' => $shop['access_token'],
                                'refresh_token' => $shop['refresh_token'],
                            ];

                            //$help = new JoomSyncListingHelper();//放开队列直接测试
                            //var_dump($help->downListing($config));
                            (new CommonQueuer(JoomSyncListingQueue::class))->push($config);
                        }
                    }
                }
            }
        } catch (TaskException $exp) {
            throw new TaskException($exp->getMessage());
        }
    }

    /**
     * 比较上次同步时间，间隔大于设定时间返回true;
     * @param $shop
     * @return bool
     */
    private function getGrapListingStatus($shop)
    {
        $last_execute_time = Cache::store('JoomListing')->getListingSyncTime($shop['id']);
        if (!isset($last_execute_time)) {
            return true;
        }
        $leftTime = time() - $last_execute_time;
        return $leftTime >= $shop['download_listing'] * 60 ? true : false;
    }
}
