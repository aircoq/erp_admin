<?php
/**
 * TOP API: aliexpress.merchant.redefining.querylevelinfo request
 * 
 * @author auto create
 * @since 1.0, 2017.11.17
 */
class AliexpressMerchantRedefiningQuerylevelinfoRequest
{
	/** 
	 * 卖家loginId，需要与授权用户相同
	 **/
	private $param1;
	
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

	public function getApiMethodName()
	{
		return "aliexpress.merchant.redefining.querylevelinfo";
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
