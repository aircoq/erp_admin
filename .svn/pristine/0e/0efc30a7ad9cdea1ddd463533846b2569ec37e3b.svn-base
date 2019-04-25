<?php
/**
 * TOP API: aliexpress.postproduct.redefining.setsizechart request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressPostproductRedefiningSetsizechartRequest
{
	/** 
	 * 商品Id
	 **/
	private $productId;
	
	/** 
	 * 尺码表模版Id, 必须与当前商品所在类目想对应。
	 **/
	private $sizechartId;
	
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

	public function setSizechartId($sizechartId)
	{
		$this->sizechartId = $sizechartId;
		$this->apiParas["sizechart_id"] = $sizechartId;
	}

	public function getSizechartId()
	{
		return $this->sizechartId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.setsizechart";
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
