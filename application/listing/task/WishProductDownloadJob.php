<?php

namespace app\listing\task;

use app\common\exception\TaskException;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\listing\queue\WishListingJobStatus;
use app\listing\queue\WishProductDownloadJobQueue;
use think\Db;
use app\common\cache\Cache;
use service\wish\WishApi;
use think\Exception;
use app\publish\queue\WishQueue;

/**
 * wish平台批量下载产品任务job
 *
 * @author joy
 */
class WishProductDownloadJob extends AbsTasker
{
    protected $defaultTime = 3600;

    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "wish产品批量下载job";
    }

    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "wish产品批量下载job";
    }

    /**
     * 定义任务作者
     * @return string
     */

    public function getCreator()
    {
        return "joy";
    }

    /**
     * 定义任务参数规则
     * @return array
     */
    public function getParamRule()
    {
        return [];
    }

    /**
     * 任务执行内容
     * @return void
     */

    public function execute()
    {
        try {
            set_time_limit(0);

            $accounts = Cache::store('WishAccount')->getAccount();

            $jobs = [];
            if ($accounts) {
                foreach ($accounts as $account) {

                    if (!empty($account['access_token']) && $account['is_invalid'] == 1 && $account['download_listing'] > 0) {

                        $last_execute_time = Cache::store('WishAccount')->getWishLastRsynListingTime($account['id']);

                        if (empty($last_execute_time)) {
                            $can = true;
                        } else {
                            $now = time();

                            $leftTime = ($now - $last_execute_time) / 60; //间隔多少分钟

                            if ($leftTime >= $account['download_listing']) {
                                $can = true;
                            } else {
                                $can = false;
                            }
                        }
                        if ($can) {
                            (new UniqueQueuer(WishProductDownloadJobQueue::class))->push($account['id']);
                            //Cache::handler()->hSet('hash:wish:job' . date('Ymd') . ':' . date('H'), date('Y-m-d H:i:s'), json_encode(['account' => $account, 'job' => $job_id, 'config' => $config]));
                        }
                    }
                }
            }
        } catch (Exception $exp) {
            throw  new TaskException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
}
