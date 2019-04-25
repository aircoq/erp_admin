<?php
/**
 * TOP API: aliexpress.postproduct.redefining.editmutilpleskustocks request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressPostproductRedefiningEditmutilpleskustocksRequest
{
	/** 
	 * 产品ID
	 **/
	private $productId;
	
	/** 
	 * SKU的库存信息(一个或着多个)
	 **/
	private $skuStocks;
	
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

	public function setSkuStocks($skuStocks)
	{
		$this->skuStocks = $skuStocks;
		$this->apiParas["sku_stocks"] = $skuStocks;
	}

	public function getSkuStocks()
	{
		return $this->skuStocks;
	}

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.editmutilpleskustocks";
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
