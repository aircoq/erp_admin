<?php
/**
 * TOP API: aliexpress.logistics.redefining.getlogisticsselleraddresses request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressLogisticsRedefiningGetlogisticsselleraddressesRequest
{
	/** 
	 * 地址类型
	 **/
	private $sellerAddressQuery;
	
	private $apiParas = array();
	
	public function setSellerAddressQuery($sellerAddressQuery)
	{
		$this->sellerAddressQuery = $sellerAddressQuery;
		$this->apiParas["seller_address_query"] = $sellerAddressQuery;
	}

	public function getSellerAddressQuery()
	{
		return $this->sellerAddressQuery;
	}

	public function getApiMethodName()
	{
		return "aliexpress.logistics.redefining.getlogisticsselleraddresses";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->sellerAddressQuery,"sellerAddressQuery");
		RequestCheckUtil::checkMaxListSize($this->sellerAddressQuery,20,"sellerAddressQuery");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
