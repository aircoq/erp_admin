<?php
/**
 * TOP API: aliexpress.freight.redefining.getfreightsettingbytemplatequery request
 * 
 * @author auto create
 * @since 1.0, 2018.03.07
 */
class AliexpressFreightRedefiningGetfreightsettingbytemplatequeryRequest
{
	/** 
	 * Template id
	 **/
	private $templateId;
	
	private $apiParas = array();
	
	public function setTemplateId($templateId)
	{
		$this->templateId = $templateId;
		$this->apiParas["template_id"] = $templateId;
	}

	public function getTemplateId()
	{
		return $this->templateId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.freight.redefining.getfreightsettingbytemplatequery";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->templateId,"templateId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
