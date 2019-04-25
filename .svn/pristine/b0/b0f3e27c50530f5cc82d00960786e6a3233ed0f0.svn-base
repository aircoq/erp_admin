<?php
/**
 * TOP API: aliexpress.logistics.redefining.getprintinfo request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressLogisticsRedefiningGetprintinfoRequest
{
	/** 
	 * internationalLogisticsId is international logistics waybill ID (Required)
	 **/
	private $internationalLogisticsId;
	
	private $apiParas = array();
	
	public function setInternationalLogisticsId($internationalLogisticsId)
	{
		$this->internationalLogisticsId = $internationalLogisticsId;
		$this->apiParas["international_logistics_id"] = $internationalLogisticsId;
	}

	public function getInternationalLogisticsId()
	{
		return $this->internationalLogisticsId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.logistics.redefining.getprintinfo";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->internationalLogisticsId,"internationalLogisticsId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
