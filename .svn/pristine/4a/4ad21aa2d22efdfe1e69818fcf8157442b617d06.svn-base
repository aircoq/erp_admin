<?php
/**
 * TOP API: aliexpress.postproduct.redefining.renewexpire request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressPostproductRedefiningRenewexpireRequest
{
	/** 
	 * 需要延长有效期的商品id
	 **/
	private $productId;
	
	private $apiParas = array();
	
	public function setProductId($productId)
	{
		$this->productId = $productId;
		$this->apiParas["product_id"] = $productId;
	}

	public function getProductId()
	{
		return $this->productId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.renewexpire";
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
