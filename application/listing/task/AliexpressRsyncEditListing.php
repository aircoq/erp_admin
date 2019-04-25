<?php

/**
 * 同步速卖通编辑了的listing
 * @datetime 2017-5-31  15:47:33
 * @author joy
 */

namespace app\listing\task;
use app\index\service\AbsTasker;
use  app\common\exception\TaskException;
use app\listing\service\RedisListing;
use app\listing\service\AliexpressListingHelper;
use think\Db;
class AliexpressRsyncEditListing extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "速卖通修改编辑商品信息";
    }
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "速卖通修改编辑商品信息";
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
        $total = $redis->myZRangeByScore('editAeProduct',strtotime('-1 day'),time());      
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
                $this->editAeProduct($queues,$helper,$redis);
            }
        }while($pageSize== count($queues));         
    }
    /**
     * 编辑商品信息
     * @param int $queues
     * @param type $helper
     * @param type $redis
     */
    public function editAeProduct($queues,$helper,$redis)
    {
        foreach($queues as $queue)
        {
            $response = $helper->editAeProduct($queue);
           
            if(isset($response['success']) && $response['success'])
            {
                $redis->myZRem('editAeProduct',$queue);
                $data['lock_update']=0; //更新成功
                $data['update_message']= json_encode($response);
            }else{
                $data['lock_update']=2; //更新失败
                $data['update_message']= json_encode($response);
            }
            $where['product_id']=['=',$queue];
            Db::startTrans();
            try{
                $model = new \app\common\model\aliexpress\AliexpressProduct; 
                $model->save($data, $where);
                //$model->productSku->save();
                Db::commit();
            }catch(\Exception $exp)
            {
                Db::rollback();
                var_dump($exp->getMessage());
            }
        }
    } 
}
