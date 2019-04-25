<?php
/**
 * TOP API: aliexpress.logistics.getpdfsbycloudprint request
 * 
 * @author auto create
 * @since 1.0, 2018.03.15
 */
class AliexpressLogisticsGetpdfsbycloudprintRequest
{
	/** 
	 * 是否打印详情
	 **/
	private $printDetail;
	
	/** 
	 * 批量查询线上发货信息进去打印,列表类型，以json格式来表达
	 **/
	private $warehouseOrderQueryDTOs;
	
	private $apiParas = array();
	
	public function setPrintDetail($printDetail)
	{
		$this->printDetail = $printDetail;
		$this->apiParas["print_detail"] = $printDetail;
	}

	public function getPrintDetail()
	{
		return $this->printDetail;
	}

	public function setWarehouseOrderQueryDTOs($warehouseOrderQueryDTOs)
	{
		$this->warehouseOrderQueryDTOs = $warehouseOrderQueryDTOs;
		$this->apiParas["warehouse_order_query_d_t_os"] = $warehouseOrderQueryDTOs;
	}

	public function getWarehouseOrderQueryDTOs()
	{
		return $this->warehouseOrderQueryDTOs;
	}

	public function getApiMethodName()
	{
		return "aliexpress.logistics.getpdfsbycloudprint";
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
