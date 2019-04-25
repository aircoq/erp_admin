<?php
/**
 * TOP API: aliexpress.merchant.redefining.queryremark request
 * 
 * @author auto create
 * @since 1.0, 2018.03.07
 */
class AliexpressMerchantRedefiningQueryremarkRequest
{
	/** 
	 * 业务类型，默认为订单备注
	 **/
	private $bizType;
	
	/** 
	 * 备注Id，业务类型为订单备注，则为订单Id
	 **/
	private $remarkId;
	
	private $apiParas = array();
	
	public function setBizType($bizType)
	{
		$this->bizType = $bizType;
		$this->apiParas["biz_type"] = $bizType;
	}

	public function getBizType()
	{
		return $this->bizType;
	}

	public function setRemarkId($remarkId)
	{
		$this->remarkId = $remarkId;
		$this->apiParas["remark_id"] = $remarkId;
	}

	public function getRemarkId()
	{
		return $this->remarkId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.merchant.redefining.queryremark";
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
