<?php
/**
 * TOP API: aliexpress.offer.redefining.initialnewbundle request
 * 
 * @author auto create
 * @since 1.0, 2018.03.15
 */
class AliexpressOfferRedefiningInitialnewbundleRequest
{
	/** 
	 * 主商品ID
	 **/
	private $mainItemId;
	
	/** 
	 * 搭配商品ID列表
	 **/
	private $subItemList;
	
	private $apiParas = array();
	
	public function setMainItemId($mainItemId)
	{
		$this->mainItemId = $mainItemId;
		$this->apiParas["main_item_id"] = $mainItemId;
	}

	public function getMainItemId()
	{
		return $this->mainItemId;
	}

	public function setSubItemList($subItemList)
	{
		$this->subItemList = $subItemList;
		$this->apiParas["sub_item_list"] = $subItemList;
	}

	public function getSubItemList()
	{
		return $this->subItemList;
	}

	public function getApiMethodName()
	{
		return "aliexpress.offer.redefining.initialnewbundle";
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
