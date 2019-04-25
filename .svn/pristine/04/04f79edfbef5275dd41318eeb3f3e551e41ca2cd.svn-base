<?php
/**
 * TOP API: aliexpress.postproduct.redefining.editproductcategoryattributes request
 * 
 * @author auto create
 * @since 1.0, 2018.03.14
 */
class AliexpressPostproductRedefiningEditproductcategoryattributesRequest
{
	/** 
	 * none
	 **/
	private $productCategoryAttributes;
	
	/** 
	 * 产品的ID
	 **/
	private $productId;
	
	private $apiParas = array();
	
	public function setProductCategoryAttributes($productCategoryAttributes)
	{
		$this->productCategoryAttributes = $productCategoryAttributes;
		$this->apiParas["product_category_attributes"] = $productCategoryAttributes;
	}

	public function getProductCategoryAttributes()
	{
		return $this->productCategoryAttributes;
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

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.editproductcategoryattributes";
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
