<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/23
 * Time: 11:22
 */

namespace app\listing\task;
use app\index\service\AbsTasker;
use app\publish\queue\WishQueue;
use app\common\model\ebay\EbayModelPromotion;
use app\listing\service\EbayListingHelper;
class EbayPromotion extends AbsTasker{
	/**
	 * 定义任务名称
	 * @return string
	 */
	public function getName()
	{
		return "同步ebay促销规则";
	}
	/**
	 * 定义任务描述
	 * @return string
	 */
	public function getDesc()
	{
		return "同步ebay促销规则";
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
		$id = WishQueue::single('ebay:promotion')->consumption();

		if($id)
		{
			$promation =EbayModelPromotion::where('id','=',$id)->with(['account'=>function($query){$query->field('id,token');}])->find();
			$token = $promation['account']['token'];
			$response = (new EbayListingHelper)->promotionalSale($token,$promation);
		}
	}

}