<?php

/**
 * 刊登速卖通刊登商品(非定时)
 * Description of AliexpressPostProduct
 * @datetime 2017-5-22  9:50:22
 * @author joy
 */

namespace app\publish\task;
use app\index\service\AbsTasker;
use app\common\exception\TaskException;
use think\Db;
use app\publish\service\AliexpressTaskHelper;
use app\publish\queue\WishQueue;
use app\common\model\aliexpress\AliexpressPublishPlan;
class AliexpressPostProduct extends AbsTasker{
    /**
     * 定义任务名称
     * @return string
     */
    public function getName()
    {
        return "速卖通刊登商品";
    }
    
    /**
     * 定义任务描述
     * @return string
     */
    public function getDesc()
    {
        return "速卖通刊登商品";
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
        set_time_limit(0);
        try{
	        $id = WishQueue::single('app\publish\queue\AliexpressQueue')->consumption();
	        if($id)
	        {
	        	$where['ap_id']=['=',$id];
		        $where['status']=['=',0];

		        $product = AliexpressPublishPlan::where($where)->find();

		        if($product)
		        {
			        //如果定时刊登时间为0，或者定时刊登时间小于等于当前时间，则满足刊登条件，执行刊登
			        if($product['plan_time']==0 || $product['plan_time']<=time())
			        {
				        (new AliexpressTaskHelper)->publishOneProduct($product);
			        }else{
				        WishQueue::single('app\publish\queue\AliexpressQueue')->production($id);
			        }
		        }
	        }

		}catch (TaskException $exp){
			throw new TaskException($exp->getFile().'<->'.$exp->getLine().'<->'.$exp->getMessage());
		}
    }
    
}
