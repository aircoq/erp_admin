<?php
/**
 * TOP API: aliexpress.offer.redefining.createbundle request
 * 
 * @author auto create
 * @since 1.0, 2017.11.28
 */
class AliexpressOfferRedefiningCreatebundleRequest
{
	/** 
	 * 商品搭配
	 **/
	private $aeopOfferBundle;
	
	private $apiParas = array();
	
	public function setAeopOfferBundle($aeopOfferBundle)
	{
		$this->aeopOfferBundle = $aeopOfferBundle;
		$this->apiParas["aeop_offer_bundle"] = $aeopOfferBundle;
	}

	public function getAeopOfferBundle()
	{
		return $this->aeopOfferBundle;
	}

	public function getApiMethodName()
	{
		return "aliexpress.offer.redefining.createbundle";
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
