<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/17
 * Time: 15:53
 */

namespace app\publish\task;
use app\common\exception\TaskException;
use app\common\model\aliexpress\AliexpressPublishPlan;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\publish\queue\AliexpressQueue;
use app\publish\queue\AliexpressPublishFailQueue;
use think\Exception;

class AliexpressPushQueue extends AbsTasker{
	/**
	* 定义任务名称
	* @return string
	*/
	public function getName()
	{
		return "速卖通刊登异常自动加入队列";
	}

	/**
	* 定义任务描述
	* @return string
	*/
	public function getDesc()
	{
		return "速卖通刊登异常自动加入队列";
	}
	/**
	* 定义任务作者
	* @return string
	*/
	public function getCreator()
	{
		return "hao";
	}
	/**
	 * 定义任务参数规则
	 * @return array
	 */
	public function getParamRule()
	{
		return [];
	}

	public function execute()
	{
		set_time_limit(0);
		try{
            $page = 1;
            $pageSize=100;
            $model = new AliexpressPublishPlan();

            $failQueueModel = new UniqueQueuer(AliexpressPublishFailQueue::class);

            while (true) {
                $queues = $model->where('status','=',-1)
                    ->page($page,$pageSize)
                    ->field('ap_id, plan_time')
                    ->select();

                if(empty($queues))
                {
                    break;
                }


                foreach ($queues as $val) {
                    $this->pushQueue($val, $failQueueModel);
                }

                $page  = $page + 1;
            }

		}catch(Exception $exp){
			throw new TaskException("File:{$exp->getFile()}Line:{$exp->getLine()}Message:{$exp->getMessage()}");
		}catch (\Throwable $exp){
            throw new TaskException("File:{$exp->getFile()}Line:{$exp->getLine()}Message:{$exp->getMessage()}");
        }
	}

    /**
     * 加入队列
     * @param $queues
     */
	private function pushQueue($queue, $failQueueModel)
    {
        $failQueueModel->push($queue['ap_id'],$queue['plan_time']);
    }
}