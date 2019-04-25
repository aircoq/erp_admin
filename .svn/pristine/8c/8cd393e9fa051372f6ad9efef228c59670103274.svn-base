<?php
/**
 * TOP API: aliexpress.postproduct.redefining.setgroups request
 * 
 * @author auto create
 * @since 1.0, 2018.03.14
 */
class AliexpressPostproductRedefiningSetgroupsRequest
{
	/** 
	 * 商品分组ID。如果需要将一个商品设置成多个分组，需要将分组id用逗号分隔，如：'123,456,789' 至多30个。
	 **/
	private $groupIds;
	
	/** 
	 * 产品ID
	 **/
	private $productId;
	
	private $apiParas = array();
	
	public function setGroupIds($groupIds)
	{
		$this->groupIds = $groupIds;
		$this->apiParas["group_ids"] = $groupIds;
	}

	public function getGroupIds()
	{
		return $this->groupIds;
	}

	public function setProductId($productId)
	{
		$this->productId = $productId;
		$this->apiParas["product_id"] = $productId;
	}

	public function getProductId()
	{
		return $this->productId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.setgroups";
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
