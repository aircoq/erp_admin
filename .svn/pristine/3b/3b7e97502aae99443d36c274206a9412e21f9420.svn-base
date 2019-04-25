<?php
/**
 * TOP API: aliexpress.postproduct.redefining.setshopwindowproduct request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressPostproductRedefiningSetshopwindowproductRequest
{
	/** 
	 * 待设置橱窗的商品id，可输入多个，之前用半角分号分割。
	 **/
	private $productIdList;
	
	private $apiParas = array();
	
	public function setProductIdList($productIdList)
	{
		$this->productIdList = $productIdList;
		$this->apiParas["product_id_list"] = $productIdList;
	}

	public function getProductIdList()
	{
		return $this->productIdList;
	}

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.setshopwindowproduct";
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
