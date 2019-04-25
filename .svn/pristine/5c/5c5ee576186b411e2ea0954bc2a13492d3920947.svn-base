<?php
/**
 * TOP API: aliexpress.offer.redefining.findbundlebyid request
 * 
 * @author auto create
 * @since 1.0, 2017.11.28
 */
class AliexpressOfferRedefiningFindbundlebyidRequest
{
	/** 
	 * 套餐ID
	 **/
	private $bundleId;
	
	private $apiParas = array();
	
	public function setBundleId($bundleId)
	{
		$this->bundleId = $bundleId;
		$this->apiParas["bundle_id"] = $bundleId;
	}

	public function getBundleId()
	{
		return $this->bundleId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.offer.redefining.findbundlebyid";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->bundleId,"bundleId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
