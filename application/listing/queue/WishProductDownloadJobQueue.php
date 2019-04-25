<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-3-27
 * Time: 上午10:42
 */

namespace app\listing\queue;


use app\common\service\SwooleQueueJob;
use app\common\cache\Cache;
use app\common\service\UniqueQueuer;
use service\wish\WishApi;

class WishProductDownloadJobQueue extends SwooleQueueJob
{
    public function getName():string
    {
        return 'wish产品批量下载job队列';
    }
    public function getDesc():string
    {
        return 'wish产品批量下载job队列';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }
    protected $defaultTime = 3600;
    public  function execute()
    {
        $params = $this->params;
        if($params){
            $account_id = $params;
            $since = Cache::store('WishAccount')->getWishLastRsyncListingSinceTime($account_id);
            $config = Cache::store('WishAccount')->getAccount($account_id);
            //如果设置了last update time
            if ($since) {
                $time = strtotime($since) - $this->defaultTime;
                $config['since'] = gmdate("Y-m-d\TH:i:s", $time);
            } else {
                //如果没有设置，则拉去所有的listing
                //$config['since']=date('Y-m-d',strtotime('-15 day'));//上次更新时间
            }

            $type = 'Product';

            $service = WishApi::instance($config)->loader($type);

            $job_id = $service->batchProduct($config);

            //如果生成job_id
            if ($job_id) {
                $data = [
                    'job_id' => $job_id,
                    'id' => $account_id,
                    'time' => date('Y-m-d H:i:s', time())
                ];
                (new UniqueQueuer(WishListingJobStatus::class))->push($data);
                //$last_execute_time['last_rsyn_listing_time']=time();
                Cache::store('WishAccount')->setWishLastRsynListingTime($account_id, time());
            }
        }
    }
}