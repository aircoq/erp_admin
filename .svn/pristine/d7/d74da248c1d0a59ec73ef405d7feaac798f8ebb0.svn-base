<?php
/**
 * TOP API: aliexpress.logistics.sellershipmentfortop request
 * 
 * @author auto create
 * @since 1.0, 2018.01.05
 */
class AliexpressLogisticsSellershipmentfortopRequest
{
	/** 
	 * memo
	 **/
	private $description;
	
	/** 
	 * 国际运单号
	 **/
	private $logisticsNo;
	
	/** 
	 * 交易订单号
	 **/
	private $outRef;
	
	/** 
	 * 声明发货类型，all表示全部发货，part表示部分声明发货。
	 **/
	private $sendType;
	
	/** 
	 * 物流服务名称
	 **/
	private $serviceName;
	
	/** 
	 * 追踪网址
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

	public function setLogisticsNo($logisticsNo)
	{
		$this->logisticsNo = $logisticsNo;
		$this->apiParas["logistics_no"] = $logisticsNo;
	}

	public function getLogisticsNo()
	{
		return $this->logisticsNo;
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

	public function setServiceName($serviceName)
	{
		$this->serviceName = $serviceName;
		$this->apiParas["service_name"] = $serviceName;
	}

	public function getServiceName()
	{
		return $this->serviceName;
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
		return "aliexpress.logistics.sellershipmentfortop";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->logisticsNo,"logisticsNo");
		RequestCheckUtil::checkNotNull($this->outRef,"outRef");
		RequestCheckUtil::checkNotNull($this->sendType,"sendType");
		RequestCheckUtil::checkNotNull($this->serviceName,"serviceName");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
