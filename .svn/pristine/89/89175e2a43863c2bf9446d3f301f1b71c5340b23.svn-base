<?php
/**
 * TOP API: aliexpress.marketing.redefining.findsellercouponactivity request
 * 
 * @author auto create
 * @since 1.0, 2017.12.29
 */
class AliexpressMarketingRedefiningFindsellercouponactivityRequest
{
	/** 
	 * Coupon活动ID
	 **/
	private $activityId;
	
	private $apiParas = array();
	
	public function setActivityId($activityId)
	{
		$this->activityId = $activityId;
		$this->apiParas["activity_id"] = $activityId;
	}

	public function getActivityId()
	{
		return $this->activityId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.marketing.redefining.findsellercouponactivity";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->activityId,"activityId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
