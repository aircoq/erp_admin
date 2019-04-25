<?php
/**
 * TOP API: aliexpress.logistics.redefining.getprintinfos request
 * 
 * @author auto create
 * @since 1.0, 2018.03.14
 */
class AliexpressLogisticsRedefiningGetprintinfosRequest
{
	/** 
	 * print detail
	 **/
	private $printDetail;
	
	/** 
	 * 12345
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
		return "aliexpress.logistics.redefining.getprintinfos";
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
