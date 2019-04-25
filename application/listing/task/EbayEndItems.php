<?php

/**
 * Description of EbayEndItems
 * @datetime 2017-6-21  16:17:50
 * @author joy
 */

namespace app\listing\task;
use app\index\service\AbsTasker;
use app\listing\service\RedisListing;
class EbayEndItems extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "ebay下架商品";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "ebay下架商品";
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
        $total = $redis->myZRangeByScore('offlineEbayProduct',strtotime('-3 day'),time());      
        $page = 1;
        $pageSize =30;
        $helper = new \app\listing\service\EbayListingHelper;
        do{
            $queues = $redis->page($total,$page,$pageSize);
            
            if(empty($queues))
            {
                break;
            }else{
                $page=$page+1;  
                $helper->endItems($queues,$redis);
            }
        }while($pageSize== count($queues)); 
    }
    
}
