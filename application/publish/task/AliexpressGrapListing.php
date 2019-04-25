<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-6
 * Time: 下午5:55
 */

namespace app\publish\task;

use app\common\exception\TaskException;
use app\common\service\CommonQueuer;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\common\cache\Cache;
use app\publish\queue\AliexpressGrapListingListQueue;


class AliexpressGrapListing extends AbsTasker
{
    public function getName()
    {
        return "Aliexpress抓取listing列表";
    }

    public function getDesc()
    {
        return "Aliexpress抓取listing列表";
    }

    public function getCreator()
    {
        return "joy";
    }

    public function getParamRule()
    {
        return [];
    }


    public function execute()
    {
        set_time_limit(0);
        try {
            $accounts = Cache::store('AliexpressAccount')->getAccounts();
            if ($accounts) {
                foreach ($accounts as $account) {
                    if ($account['is_invalid'] && $account['is_authorization'] && isset($account['download_listing']) && $account['download_listing']) {
                        $can = $this->getGrapListingStatus($account);
                        if ($can) {
                            $id = $account['id'];
                            (new UniqueQueuer(AliexpressGrapListingListQueue::class))->push($id);
                        }
                    }
                }
            }
        } catch (TaskException $exp) {
            throw new TaskException($exp->getMessage());
        }
    }

    private function getGrapListingStatus($account)
    {
        $last_execute_time = Cache::store('AliexpressAccount')->getListingSyncTime($account['id']);
        if (!isset($last_execute_time)) {
            return true;
        }
        $leftTime = time() - $last_execute_time;
        return $leftTime >= $account['download_listing'] * 60 ? true : false;
    }
}
