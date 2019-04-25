<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/29
 * Time: 10:39
 */

namespace app\common\model\wish;

use app\common\model\Account;
use think\Model;
class WishDraft extends Model{
	/**
	 * 初始化数据
	 */
	protected function initialize()
	{
		//需要调用 mdoel 的 initialize 方法
		parent::initialize();
	}
	//=====================================关联开始=============================
	//关联商品
	public function goods()
	{
		return $this->hasOne(\app\common\model\Goods::class,'id','goods_id')->field('id,thumb');
	}
	//关联账户
	public function account()
	{
		return $this->hasOne(WishAccount::class,'id','account_id')->field('id,code');
	}
	//关联用户
	public function user()
	{
		return $this->hasOne(\app\common\model\User::class,'id','uid')->field('id,realname');
	}
	//=====================================关联结束=============================

}