<?php

namespace app\listing\task;

use app\index\service\AbsTasker;
use app\common\cache\Cache;
use service\wish\WishApi;
use app\common\exception\TaskException;
use app\publish\queue\WishQueue;

/**
 * 根据wish产品批量下载job状态将商品数据csv文件保存到本地
 * 
 * @author joy
 */
class WishProductDownloadJobStatus extends AbsTasker{
   public function getName()
    {
        return "wish产品批量下载job状态";
    }

    public function getDesc()
    {
        return "wish产品批量下载job状态";
    }

    public function getCreator()
    {
        return "joy";
    }
    
    public function getParamRule()
    {
        return [];
    }

    public  function execute()
    {
    	try{
		    set_time_limit(0);
		    $jobs = (new WishQueue('Cache:wishGoodsDownload'))->lists();
		    if(!empty($jobs))
		    {
			    foreach ($jobs as $job)
			    {
				    $style = 0;
				    $job_id = $job['job_id'];
				    $response = WishApi::instance(['access_token'=>$job['access_token']])->loader('Product')->getProductJobStatus(['job_id'=>$job_id,'id'=>$job['id']],$style);
				    if($response['state'] && !empty($response['data'])) //如果下载成功，则从计划任务中删除该账户信息
				    {
					    //如果文件已经写入本地，则加入缓存序列进行写入数据库
					    //$res = $redis->SADD('Cache:wishGoodsInsert',$job['id'].'_'.$job_id);
					    (new WishQueue('Cache:wishGoodsDownload'))->remove($job);
					    (new WishQueue('Cache:wishGoodsInsert'))->push($job['id'].'_'.$job_id);
				    }else{
					    //$redis->lPush('Cache:wishGoodsDownload', json_encode($job));
					    (new WishQueue('Cache:wishGoodsDownload'))->push($job);
				    }
			    }
		    }
	    }catch (TaskException $exp){
		    throw  new TaskException($exp->getMessage().$exp->getFile().$exp->getLine());
	    }

    }
}
