<?php
/**
 * TOP API: aliexpress.marketing.redefining.queryproductlistresultforpromotion request
 * 
 * @author auto create
 * @since 1.0, 2018.01.02
 */
class AliexpressMarketingRedefiningQueryproductlistresultforpromotionRequest
{
	/** 
	 * 活动ID
	 **/
	private $promotionId;
	
	private $apiParas = array();
	
	public function setPromotionId($promotionId)
	{
		$this->promotionId = $promotionId;
		$this->apiParas["promotion_id"] = $promotionId;
	}

	public function getPromotionId()
	{
		return $this->promotionId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.marketing.redefining.queryproductlistresultforpromotion";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->promotionId,"promotionId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
