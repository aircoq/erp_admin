<?php
/**
 * TOP API: aliexpress.postproduct.redefining.createproductgroup request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressPostproductRedefiningCreateproductgroupRequest
{
	/** 
	 * 分组的名称, 请控制在1～50个英文字符之内。
	 **/
	private $name;
	
	/** 
	 * 父分组的ID。如果为0则表示创建根分组，否则创建指定分组下的二级分组。
	 **/
	private $parentId;
	
	private $apiParas = array();
	
	public function setName($name)
	{
		$this->name = $name;
		$this->apiParas["name"] = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setParentId($parentId)
	{
		$this->parentId = $parentId;
		$this->apiParas["parent_id"] = $parentId;
	}

	public function getParentId()
	{
		return $this->parentId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.createproductgroup";
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
