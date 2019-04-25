<?php
/**
 * TOP API: aliexpress.postproduct.redefining.onlineaeproduct request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressPostproductRedefiningOnlineaeproductRequest
{
	/** 
	 * 需要上架的产品id列表。可输入多个，之前用半角分号分割。
	 **/
	private $productIds;
	
	private $apiParas = array();
	
	public function setProductIds($productIds)
	{
		$this->productIds = $productIds;
		$this->apiParas["product_ids"] = $productIds;
	}

	public function getProductIds()
	{
		return $this->productIds;
	}

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.onlineaeproduct";
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
