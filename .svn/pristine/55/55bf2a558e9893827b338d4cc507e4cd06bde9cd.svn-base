<?php
/**
 * TOP API: aliexpress.merchant.redefining.queryservicescoreinfo request
 * 
 * @author auto create
 * @since 1.0, 2017.11.17
 */
class AliexpressMerchantRedefiningQueryservicescoreinfoRequest
{
	/** 
	 * 卖家loginId，需要与授权用户相同
	 **/
	private $param1;
	
	/** 
	 * 语言环境
	 **/
	private $param2;
	
	private $apiParas = array();
	
	public function setParam1($param1)
	{
		$this->param1 = $param1;
		$this->apiParas["param1"] = $param1;
	}

	public function getParam1()
	{
		return $this->param1;
	}

	public function setParam2($param2)
	{
		$this->param2 = $param2;
		$this->apiParas["param2"] = $param2;
	}

	public function getParam2()
	{
		return $this->param2;
	}

	public function getApiMethodName()
	{
		return "aliexpress.merchant.redefining.queryservicescoreinfo";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->param1,"param1");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
