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
use app\publish\queue\AliexpressDelayWsValidNumQueue;
use app\index\service\AbsTasker;
use think\Exception;

class AliexpressDelayWsValidNum extends AbsTasker{
	/**
	* 定义任务名称
	* @return string
	*/
	public function getName()
	{
		return "速卖通延长商品有效天数";
	}

	/**
	* 定义任务描述
	* @return string
	*/
	public function getDesc()
	{
		return "速卖通延长商品有效天数";
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
            $pageSize=500;

            $model = new AliexpressProductModel();

            $star_time = strtotime(date('Y-m-d'));
            //每天23点+2天
            $end_time = strtotime(date('Y-m-d 23:59:59'))+172800;
            
            $where = ['ws_offline_date' => ['between time', [$star_time, $end_time]]];

            while (true) {
                $list = $model->where(['status' => 2])->where($where)
                    ->page($page,$pageSize)
                    ->field('id')
                    ->select();


                if(empty($list))
                {
                    break;
                }

                foreach ($list as $val) {
                    (new UniqueQueuer(AliexpressDelayWsValidNumQueue::class))->push($val['id']);
                }

                $page  = $page + 1;
            }

		}catch(Exception $exp){
			throw new TaskException("File:{$exp->getFile()}Line:{$exp->getLine()}Message:{$exp->getMessage()}");
		}catch (\Throwable $exp){
            throw new TaskException("File:{$exp->getFile()}Line:{$exp->getLine()}Message:{$exp->getMessage()}");
        }
	}
}