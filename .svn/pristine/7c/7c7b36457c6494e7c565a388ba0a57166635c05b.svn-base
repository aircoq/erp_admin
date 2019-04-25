<?php

/**
 * Description of AliexpressRsyncProduct
 * @datetime 2017-5-26  16:47:33
 * @author joy
 */

namespace app\listing\task;
use app\index\service\AbsTasker;
use app\listing\service\RedisListing;
use app\listing\service\AliexpressListingHelper;

class AliexpressRsyncProduct extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "实时同步速卖通listing";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "实时同步速卖通listing";
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
    public  function execute()
    {
        set_time_limit(0);
        $redis = new RedisListing;
        $total = $redis->myZRangeByScore('findAeProductById',strtotime('-1 day'),time());      
        $page = 1;
        $pageSize =10;
        $helper = new AliexpressListingHelper;
        do{
            $queues = $redis->page($total,$page,$pageSize);
            if(empty($queues))
            {
                break;
            }else{
                $page=$page+1;  
                $this->findAeProductById($queues,$helper,$redis);
            }
        }while($pageSize== count($queues));         
    }
    /**
     * 根据商品id查询单个商品的详细信息
     * @param int $queues
     * @param type $helper
     * @param type $redis
     */
    public function findAeProductById($queues,$helper,$redis)
    {
        foreach($queues as $queue)
        {
            $response = $helper->findAeProductById($queue);
            if($response)
            {
                $redis->myZRem('findAeProductById',$queue);
            }
        }
    } 
}
