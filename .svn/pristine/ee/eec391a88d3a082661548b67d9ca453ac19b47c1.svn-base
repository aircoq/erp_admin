<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/24
 * Time: 9:31
 */

namespace app\publish\queue;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\publish\service\AliexpressTaskHelper;
use app\common\model\aliexpress\AliexpressPublishPlan;
use think\Exception;

class AliexpressPublishFailQueue extends SwooleQueueJob {
    protected static $priority=self::PRIORITY_HEIGHT;

    protected $failExpire = 600;

    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

	public function getName():string
	{
		return '速卖通刊登失败队列';
	}
	public function getDesc():string
	{
		return '速卖通刊登失败队列';
	}
	public function getAuthor():string
	{
		return 'hao';
	}

	public  function execute()
	{
		set_time_limit(0);
		try{
			$id = $this->params;
			if($id)
			{
				$where['ap_id']=['=',$id];
				$where['status']=['=',-1];

				$plan = AliexpressPublishPlan::where($where)->with(['product'=>function($query){$query->field('id,account_id,salesperson_id,goods_id');}])->find();
				if($plan)
				{
                    $plan = is_object($plan)?$plan->toArray():$plan;
					if(empty($plan['product'])) //如果没有对应的商品数据信息，则删除该条刊登计划任务,xxxx
                    {
                        AliexpressPublishPlan::where('ap_id','=',$id)->delete();
                    }else{
                        (new AliexpressTaskHelper)->publishOneProduct($plan,$plan['plan_time']);
                    }
				}
			}
		}catch (Exception $exp){
			throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
		}catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
	}
}