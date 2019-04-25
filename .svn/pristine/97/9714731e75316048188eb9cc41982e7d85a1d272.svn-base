<?php
/**
 * TOP API: aliexpress.logistics.redefining.sellermodifiedshipmentsupportsubtradeorder request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressLogisticsRedefiningSellermodifiedshipmentsupportsubtradeorderRequest
{
	/** 
	 * Old logistics id
	 **/
	private $oldLogisticsNo;
	
	/** 
	 * Old logistics Service Name
	 **/
	private $oldServiceName;
	
	/** 
	 * subtrade order list
	 **/
	private $subTradeOrderList;
	
	/** 
	 * Trade order id
	 **/
	private $tradeOrderId;
	
	private $apiParas = array();
	
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

	public function setSubTradeOrderList($subTradeOrderList)
	{
		$this->subTradeOrderList = $subTradeOrderList;
		$this->apiParas["sub_trade_order_list"] = $subTradeOrderList;
	}

	public function getSubTradeOrderList()
	{
		return $this->subTradeOrderList;
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
		return "aliexpress.logistics.redefining.sellermodifiedshipmentsupportsubtradeorder";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->oldLogisticsNo,"oldLogisticsNo");
		RequestCheckUtil::checkNotNull($this->oldServiceName,"oldServiceName");
		RequestCheckUtil::checkNotNull($this->tradeOrderId,"tradeOrderId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
