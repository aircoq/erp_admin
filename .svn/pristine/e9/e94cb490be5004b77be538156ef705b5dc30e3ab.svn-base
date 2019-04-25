<?php
/**
 * TOP API: aliexpress.logistics.sellermodifiedshipmentfortop request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressLogisticsSellermodifiedshipmentfortopRequest
{
	/** 
	 * memo
	 **/
	private $description;
	
	/** 
	 * 新的运单号
	 **/
	private $newLogisticsNo;
	
	/** 
	 * 新的发货物流服务
	 **/
	private $newServiceName;
	
	/** 
	 * 旧的运单号
	 **/
	private $oldLogisticsNo;
	
	/** 
	 * 用户需要修改的的老的发货物流服务
	 **/
	private $oldServiceName;
	
	/** 
	 * 交易订单号
	 **/
	private $outRef;
	
	/** 
	 * 状态包括：全部发货(all)、部分发货(part)
	 **/
	private $sendType;
	
	/** 
	 * 跟踪网址
	 **/
	private $trackingWebsite;
	
	private $apiParas = array();
	
	public function setDescription($description)
	{
		$this->description = $description;
		$this->apiParas["description"] = $description;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setNewLogisticsNo($newLogisticsNo)
	{
		$this->newLogisticsNo = $newLogisticsNo;
		$this->apiParas["new_logistics_no"] = $newLogisticsNo;
	}

	public function getNewLogisticsNo()
	{
		return $this->newLogisticsNo;
	}

	public function setNewServiceName($newServiceName)
	{
		$this->newServiceName = $newServiceName;
		$this->apiParas["new_service_name"] = $newServiceName;
	}

	public function getNewServiceName()
	{
		return $this->newServiceName;
	}

	public function setOldLogisticsNo($oldLogisticsNo)
	{
		$this->oldLogisticsNo = $oldLogisticsNo;
		$this->apiParas["old_logistics_no"] = $oldLogisticsNo;
	}

	public function getOldLogisticsNo()
	{
		return $this->oldLogisticsNo;
	}

	public function setOldServiceName($oldServiceName)
	{
		$this->oldServiceName = $oldServiceName;
		$this->apiParas["old_service_name"] = $oldServiceName;
	}

	public function getOldServiceName()
	{
		return $this->oldServiceName;
	}

	public function setOutRef($outRef)
	{
		$this->outRef = $outRef;
		$this->apiParas["out_ref"] = $outRef;
	}

	public function getOutRef()
	{
		return $this->outRef;
	}

	public function setSendType($sendType)
	{
		$this->sendType = $sendType;
		$this->apiParas["send_type"] = $sendType;
	}

	public function getSendType()
	{
		return $this->sendType;
	}

	public function setTrackingWebsite($trackingWebsite)
	{
		$this->trackingWebsite = $trackingWebsite;
		$this->apiParas["tracking_website"] = $trackingWebsite;
	}

	public function getTrackingWebsite()
	{
		return $this->trackingWebsite;
	}

	public function getApiMethodName()
	{
		return "aliexpress.logistics.sellermodifiedshipmentfortop";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->newLogisticsNo,"newLogisticsNo");
		RequestCheckUtil::checkNotNull($this->newServiceName,"newServiceName");
		RequestCheckUtil::checkNotNull($this->oldLogisticsNo,"oldLogisticsNo");
		RequestCheckUtil::checkNotNull($this->oldServiceName,"oldServiceName");
		RequestCheckUtil::checkNotNull($this->outRef,"outRef");
		RequestCheckUtil::checkNotNull($this->sendType,"sendType");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
