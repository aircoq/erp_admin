<?php
namespace app\listing\task;
use app\index\service\AbsTasker;
use think\Db;
use app\common\exception\TaskException;
use app\listing\service\RedisListing;
use app\listing\service\AliexpressListingHelper;
class AliexpressOnlineProduct extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "速卖通上架商品";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "速卖通上架商品";
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
        $total = $redis->myZRangeByScore('onlineAeProduct',strtotime('-3 day'),time());      
        $page = 1;
        $pageSize =30;
        $helper = new AliexpressListingHelper;
        do{
            $queues = $redis->page($total,$page,$pageSize);
            
            if(empty($queues))
            {
                break;
            }else{
                $page=$page+1;  
                $this->onlineAeProduct ($queues,$helper,$redis);
            }
        }while($pageSize== count($queues)); 
    }
    /**
     * 上架商品
     * @param array $queues
     * @param type $helper
     * @param type $redis
     */
    private function onlineAeProduct($queues,$helper,$redis)
    {
        foreach($queues as $queue)
        {
            $response = $helper->onlineAeProduct($queue);
            if($response)
            {
                $redis->myZRem('onlineAeProduct',$queue);
            }
        }
    }
}

