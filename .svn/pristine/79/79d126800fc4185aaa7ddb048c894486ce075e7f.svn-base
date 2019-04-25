<?php
/**
 * TOP API: aliexpress.logistics.querylogisticsorderdetail request
 * 
 * @author auto create
 * @since 1.0, 2018.01.11
 */
class AliexpressLogisticsQuerylogisticsorderdetailRequest
{
	/** 
	 * 当前页
	 **/
	private $currentPage;
	
	/** 
	 * 国内运单号
	 **/
	private $domesticLogisticsNum;
	
	/** 
	 * 起始创建时间
	 **/
	private $gmtCreateEndStr;
	
	/** 
	 * 截止创建时间
	 **/
	private $gmtCreateStartStr;
	
	/** 
	 * 国际运单号
	 **/
	private $internationalLogisticsNum;
	
	/** 
	 * 订单状态
	 **/
	private $logisticsStatus;
	
	/** 
	 * 页面大小
	 **/
	private $pageSize;
	
	/** 
	 * 交易订单号
	 **/
	private $tradeOrderId;
	
	/** 
	 * 物流服务编码
	 **/
	private $warehouseCarrierService;
	
	private $apiParas = array();
	
	public function setCurrentPage($currentPage)
	{
		$this->currentPage = $currentPage;
		$this->apiParas["current_page"] = $currentPage;
	}

	public function getCurrentPage()
	{
		return $this->currentPage;
	}

	public function setDomesticLogisticsNum($domesticLogisticsNum)
	{
		$this->domesticLogisticsNum = $domesticLogisticsNum;
		$this->apiParas["domestic_logistics_num"] = $domesticLogisticsNum;
	}

	public function getDomesticLogisticsNum()
	{
		return $this->domesticLogisticsNum;
	}

	public function setGmtCreateEndStr($gmtCreateEndStr)
	{
		$this->gmtCreateEndStr = $gmtCreateEndStr;
		$this->apiParas["gmt_create_end_str"] = $gmtCreateEndStr;
	}

	public function getGmtCreateEndStr()
	{
		return $this->gmtCreateEndStr;
	}

	public function setGmtCreateStartStr($gmtCreateStartStr)
	{
		$this->gmtCreateStartStr = $gmtCreateStartStr;
		$this->apiParas["gmt_create_start_str"] = $gmtCreateStartStr;
	}

	public function getGmtCreateStartStr()
	{
		return $this->gmtCreateStartStr;
	}

	public function setInternationalLogisticsNum($internationalLogisticsNum)
	{
		$this->internationalLogisticsNum = $internationalLogisticsNum;
		$this->apiParas["international_logistics_num"] = $internationalLogisticsNum;
	}

	public function getInternationalLogisticsNum()
	{
		return $this->internationalLogisticsNum;
	}

	public function setLogisticsStatus($logisticsStatus)
	{
		$this->logisticsStatus = $logisticsStatus;
		$this->apiParas["logistics_status"] = $logisticsStatus;
	}

	public function getLogisticsStatus()
	{
		return $this->logisticsStatus;
	}

	public function setPageSize($pageSize)
	{
		$this->pageSize = $pageSize;
		$this->apiParas["page_size"] = $pageSize;
	}

	public function getPageSize()
	{
		return $this->pageSize;
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

	public function setWarehouseCarrierService($warehouseCarrierService)
	{
		$this->warehouseCarrierService = $warehouseCarrierService;
		$this->apiParas["warehouse_carrier_service"] = $warehouseCarrierService;
	}

	public function getWarehouseCarrierService()
	{
		return $this->warehouseCarrierService;
	}

	public function getApiMethodName()
	{
		return "aliexpress.logistics.querylogisticsorderdetail";
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
