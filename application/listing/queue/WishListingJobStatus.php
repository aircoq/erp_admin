<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/25
 * Time: 13:56
 */
namespace app\listing\queue;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\publish\queue\WishQueue;
use app\common\cache\Cache;
use service\wish\WishApi;
use app\common\exception\TaskException;
class WishListingJobStatus extends  SwooleQueueJob{
    public function getName():string
    {
        return 'wish在线listing获取job状态';
    }
    public function getDesc():string
    {
        return 'wish在线listing获取job状态';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }
    
    public  function execute()
    {
        try{
            set_time_limit(0);
            $job = $this->params;

            if($job)
            {
                $style = 0;
                $job_id = $job['job_id'];

                $account = Cache::store('WishAccount')->getAccount($job['id']);

                if($account)
                {

                    $response = WishApi::instance(['access_token'=>$account['access_token']])->loader('Product')->getProductJobStatus(['job_id'=>$job_id,'id'=>$job['id']],$style);

                    if($response['state']) //如果下载成功，则从计划任务中删除该账户信息
                    {
                        if(!empty($response['data'])){
                            //如果文件已经写入本地，则加入缓存序列进行写入数据库
                            //(new WishQueue('Cache:wishGoodsDownload'))->remove($job);
                            (new WishQueue(WishListingInsertDb::class))->push($job['id'].'_'.$job_id);
                        }
                    }else{
                        //如果数据为空则删除队列任务
                        if(isset($response['total_count']) && $response['total_count']==0)
                        {
                            (new WishQueue(WishListingJobStatus::class))->remove($job);
                        }else{
                            $data = [
                                'job_id'=>$job_id,
                                'id'=>$job['id'],
                                'time'=>date('Y-m-d H:i:s',time())
                            ];
                            (new WishQueue(WishListingJobStatus::class))->push($data,strtotime('+15 minute'));
                        }

                    }
                }
            }
        }catch (QueueException $exp){
            throw  new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
}