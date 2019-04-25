<?php
/**
 * TOP API: aliexpress.postproduct.redefining.editsingleskuprice request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressPostproductRedefiningEditsingleskupriceRequest
{
	/** 
	 * 需修改编辑的商品ID
	 **/
	private $productId;
	
	/** 
	 * 需修改编辑的商品单个SKUID。SKU ID可以通过api.findAeProductById接口中的aeopAeproductSKUs获取单个产品信息中"id"进行获取。 没有SKU属性的商品SKUID回传“<none>”
	 **/
	private $skuId;
	
	/** 
	 * 修改编辑后的商品价格
	 **/
	private $skuPrice;
	
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

	public function setSkuId($skuId)
	{
		$this->skuId = $skuId;
		$this->apiParas["sku_id"] = $skuId;
	}

	public function getSkuId()
	{
		return $this->skuId;
	}

	public function setSkuPrice($skuPrice)
	{
		$this->skuPrice = $skuPrice;
		$this->apiParas["sku_price"] = $skuPrice;
	}

	public function getSkuPrice()
	{
		return $this->skuPrice;
	}

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.editsingleskuprice";
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
