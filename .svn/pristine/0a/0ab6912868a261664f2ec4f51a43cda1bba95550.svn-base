<?php
/**
 * TOP API: aliexpress.member.redefining.uicquerytbnick request
 * 
 * @author auto create
 * @since 1.0, 2017.10.17
 */
class AliexpressMemberRedefiningUicquerytbnickRequest
{
	/** 
	 * AE用户的登录ID
	 **/
	private $loginId;
	
	private $apiParas = array();
	
	public function setLoginId($loginId)
	{
		$this->loginId = $loginId;
		$this->apiParas["login_id"] = $loginId;
	}

	public function getLoginId()
	{
		return $this->loginId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.member.redefining.uicquerytbnick";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->loginId,"loginId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
