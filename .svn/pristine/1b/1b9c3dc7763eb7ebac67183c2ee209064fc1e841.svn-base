<?php
/**
 * TOP API: aliexpress.marketing.redefining.adjustlimiteddiscountpromendtimebeta request
 * 
 * @author auto create
 * @since 1.0, 2017.12.27
 */
class AliexpressMarketingRedefiningAdjustlimiteddiscountpromendtimebetaRequest
{
	/** 
	 * 活动ID
	 **/
	private $promId;
	
	/** 
	 * 活动预计结束时间。格式：yyyy-MM-dd HH:mm:ss
	 **/
	private $promotionEndTime;
	
	private $apiParas = array();
	
	public function setPromId($promId)
	{
		$this->promId = $promId;
		$this->apiParas["prom_id"] = $promId;
	}

	public function getPromId()
	{
		return $this->promId;
	}

	public function setPromotionEndTime($promotionEndTime)
	{
		$this->promotionEndTime = $promotionEndTime;
		$this->apiParas["promotion_end_time"] = $promotionEndTime;
	}

	public function getPromotionEndTime()
	{
		return $this->promotionEndTime;
	}

	public function getApiMethodName()
	{
		return "aliexpress.marketing.redefining.adjustlimiteddiscountpromendtimebeta";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->promId,"promId");
		RequestCheckUtil::checkNotNull($this->promotionEndTime,"promotionEndTime");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
