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

class AmazonUpLowerFrame extends AbsTasker{
	/**
	* 定义任务名称
	* @return string
	*/
	public function getName()
	{
		return "亚马逊定时上下架-停止使用";
	}

	/**
	* 定义任务描述
	* @return string
	*/
	public function getDesc()
	{
		return "亚马逊定时上下架-停止使用";
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


            //1.检测没有加入到队列的
            //2.每小时轮询一次,则将目前的时间段加入到队列中,并将加入队列的设置为加入队列中.
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