<?php
/**
 * TOP API: aliexpress.postproduct.redefining.editmultilanguageproduct request
 * 
 * @author auto create
 * @since 1.0, 2018.02.07
 */
class AliexpressPostproductRedefiningEditmultilanguageproductRequest
{
	/** 
	 * none
	 **/
	private $aeopAEProductMultilanguageInfo;
	
	/** 
	 * 待编辑的商品ID。
	 **/
	private $productId;
	
	private $apiParas = array();
	
	public function setAeopAEProductMultilanguageInfo($aeopAEProductMultilanguageInfo)
	{
		$this->aeopAEProductMultilanguageInfo = $aeopAEProductMultilanguageInfo;
		$this->apiParas["aeop_a_e_product_multilanguage_info"] = $aeopAEProductMultilanguageInfo;
	}

	public function getAeopAEProductMultilanguageInfo()
	{
		return $this->aeopAEProductMultilanguageInfo;
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
		return "aliexpress.postproduct.redefining.editmultilanguageproduct";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->productId,"productId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
