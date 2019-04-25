<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/17
 * Time: 15:06
 */

namespace app\publish\queue;
use app\common\model\wish\WishWaitUploadProduct;
use app\common\exception\TaskException;
use app\common\service\UniqueQueuer;
class WishQueue extends UniqueQueuer{

	/**
	 * @var static
	 */
	private static $__sigle = null;

	public static function single($key) {
		if(!static::$__sigle){
			static::$__sigle = new static($key);
		}
		return static::$__sigle;
	}
	public function production($params)
	{
		$this->push($params);
 	}
	public function consumption()
	{
		return $this->pop();
	}
	public function delete( $params )
	{
		if($this->exist($params))
		{
			return $this->remove($params);
		}else{
			return false;
		}
	}
}