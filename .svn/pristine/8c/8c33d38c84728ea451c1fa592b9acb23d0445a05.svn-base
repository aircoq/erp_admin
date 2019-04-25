<?php
/**
 * TOP API: aliexpress.member.redefining.queryaccountlevel request
 * 
 * @author auto create
 * @since 1.0, 2018.01.02
 */
class AliexpressMemberRedefiningQueryaccountlevelRequest
{
	/** 
	 * 买家账号ID
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
		return "aliexpress.member.redefining.queryaccountlevel";
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
