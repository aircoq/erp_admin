<?php
/**
 * TOP API: aliexpress.logistics.querysellershipmentinfo request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressLogisticsQuerysellershipmentinfoRequest
{
	/** 
	 * Logistics id
	 **/
	private $logisticsNo;
	
	/** 
	 * Logistics service name
	 **/
	private $serviceName;
	
	/** 
	 * Number of suborder in that order
	 **/
	private $subTradeOrderIndex;
	
	/** 
	 * Trade order id
	 **/
	private $tradeOrderId;
	
	private $apiParas = array();
	
	public function setLogisticsNo($logisticsNo)
	{
		$this->logisticsNo = $logisticsNo;
		$this->apiParas["logistics_no"] = $logisticsNo;
	}

	public function getLogisticsNo()
	{
		return $this->logisticsNo;
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

	public function setSubTradeOrderIndex($subTradeOrderIndex)
	{
		$this->subTradeOrderIndex = $subTradeOrderIndex;
		$this->apiParas["sub_trade_order_index"] = $subTradeOrderIndex;
	}

	public function getSubTradeOrderIndex()
	{
		return $this->subTradeOrderIndex;
	}

	public function setTradeOrderId($tradeOrderId)
	{
		$this->tradeOrderId = $tradeOrderId;
		$this->apiParas["trade_order_id"] = $tradeOrderId;
	}

	public function getTradeOrderId()
	{
		return $this->tradeOrderId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.logistics.querysellershipmentinfo";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->tradeOrderId,"tradeOrderId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
