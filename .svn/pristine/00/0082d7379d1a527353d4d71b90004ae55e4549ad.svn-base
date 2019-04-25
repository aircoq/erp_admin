<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/17
 * Time: 15:53
 */

namespace app\publish\task;
use app\common\exception\TaskException;
use app\common\model\aliexpress\AliexpressProduct as AliexpressProductModel;
use app\common\service\UniqueQueuer;
use app\publish\queue\AliexpressQueueJob;
use app\index\service\AbsTasker;
use think\Exception;

class AliexpressPublish extends AbsTasker{
	/**
	* 定义任务名称
	* @return string
	*/
	public function getName()
	{
		return "速卖通刊登队列重新自动加入";
	}

	/**
	* 定义任务描述
	* @return string
	*/
	public function getDesc()
	{
		return "速卖通刊登队列重新自动加入";
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
            $model = new AliexpressProductModel();

            while (true) {
                $queues = $model->where('status','=',3)
                    ->page($page,$pageSize)
                    ->field('id, plan_publish_time')
                    ->select();

                if(empty($queues))
                {
                    break;
                }

                foreach ($queues as $val) {

                    $val['plan_publish_time'] = isset($val['plan_publish_time']) && $val['plan_publish_time'] ? $val['plan_publish_time'] : 0;
                    $this->pushQueue($val);
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
	private function pushQueue($queue)
    {
        $service = new UniqueQueuer(AliexpressQueueJob::class);
        $service->push($queue['id'],$queue['plan_publish_time']);
    }
}