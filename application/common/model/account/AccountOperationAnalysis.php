<?php
namespace app\common\model\account;


use app\common\traits\BaseModel;
use app\common\traits\ModelFilter;
use erp\ErpModel;
use think\db\Query;


/**
 * Created by PhpStorm.
 * User: ZhouFurong
 * Date: 2019/4/20
 * Time: 15:25
 */

class AccountOperationAnalysis extends ErpModel
{
	use BaseModel;
	use ModelFilter;

	public function scopeAccountChannel(Query $query,$params)
	{
		$query->where('__TABLE__.account_id','in',$params['account_id']);
		$query->where('__TABLE__.channel_id','in',$params['channel_id']);
	}

	/**
	 * 初始化
	 */
	protected function initialize()
	{
		parent::initialize();
	}



}