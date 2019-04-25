<?php
/**
 * TOP API: aliexpress.offer.redefining.copysizetemplate request
 * 
 * @author auto create
 * @since 1.0, 2017.12.29
 */
class AliexpressOfferRedefiningCopysizetemplateRequest
{
	/** 
	 * 被复制的尺码模版ID
	 **/
	private $sizeTemplateId;
	
	/** 
	 * 要复制到的目标叶子类目ID
	 **/
	private $targetLeafId;
	
	private $apiParas = array();
	
	public function setSizeTemplateId($sizeTemplateId)
	{
		$this->sizeTemplateId = $sizeTemplateId;
		$this->apiParas["size_template_id"] = $sizeTemplateId;
	}

	public function getSizeTemplateId()
	{
		return $this->sizeTemplateId;
	}

	public function setTargetLeafId($targetLeafId)
	{
		$this->targetLeafId = $targetLeafId;
		$this->apiParas["target_leaf_id"] = $targetLeafId;
	}

	public function getTargetLeafId()
	{
		return $this->targetLeafId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.offer.redefining.copysizetemplate";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->sizeTemplateId,"sizeTemplateId");
		RequestCheckUtil::checkNotNull($this->targetLeafId,"targetLeafId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
