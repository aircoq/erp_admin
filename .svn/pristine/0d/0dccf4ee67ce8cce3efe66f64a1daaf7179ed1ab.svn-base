<?php
/**
 * TOP API: aliexpress.photobank.redefining.listgroup request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressPhotobankRedefiningListgroupRequest
{
	/** 
	 * 图片组ID。不填groupId则返回所有图片组信息
	 **/
	private $groupId;
	
	private $apiParas = array();
	
	public function setGroupId($groupId)
	{
		$this->groupId = $groupId;
		$this->apiParas["group_id"] = $groupId;
	}

	public function getGroupId()
	{
		return $this->groupId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.photobank.redefining.listgroup";
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
