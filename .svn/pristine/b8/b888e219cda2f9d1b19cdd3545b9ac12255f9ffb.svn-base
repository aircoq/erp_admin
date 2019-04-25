<?php
/**
 * TOP API: aliexpress.warranty.order.query request
 * 
 * @author auto create
 * @since 1.0, 2018.01.29
 */
class AliexpressWarrantyOrderQueryRequest
{
	/** 
	 * warranty_id
	 **/
	private $warrantyId;
	
	private $apiParas = array();
	
	public function setWarrantyId($warrantyId)
	{
		$this->warrantyId = $warrantyId;
		$this->apiParas["warranty_id"] = $warrantyId;
	}

	public function getWarrantyId()
	{
		return $this->warrantyId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.warranty.order.query";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
