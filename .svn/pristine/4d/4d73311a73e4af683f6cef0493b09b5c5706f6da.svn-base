<?php
/**
 * TOP API: aliexpress.postproduct.redefining.editsingleskustock request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressPostproductRedefiningEditsingleskustockRequest
{
	/** 
	 * SKU的库存值
	 **/
	private $ipmSkuStock;
	
	/** 
	 * 需修改编辑的商品ID
	 **/
	private $productId;
	
	/** 
	 * 需修改编辑的商品单个SKUID。SKU ID可以通过api.findAeProductById接口中的aeopAeproductSKUs获取单个产品信息中"id"进行获取。
	 **/
	private $skuId;
	
	private $apiParas = array();
	
	public function setIpmSkuStock($ipmSkuStock)
	{
		$this->ipmSkuStock = $ipmSkuStock;
		$this->apiParas["ipm_sku_stock"] = $ipmSkuStock;
	}

	public function getIpmSkuStock()
	{
		return $this->ipmSkuStock;
	}

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

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.editsingleskustock";
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
