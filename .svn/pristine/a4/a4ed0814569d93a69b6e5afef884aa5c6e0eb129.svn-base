<?php
/**
 * TOP API: aliexpress.postproduct.redefining.offlineaeproduct request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressPostproductRedefiningOfflineaeproductRequest
{
	/** 
	 * 需要下架的产品id。多个产品ID用英文分号隔开。
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
		return "aliexpress.postproduct.redefining.offlineaeproduct";
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
