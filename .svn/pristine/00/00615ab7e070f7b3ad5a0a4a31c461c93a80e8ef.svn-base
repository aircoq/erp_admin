<?php
/**
 * TOP API: aliexpress.offer.redefining.getcanusedproductbysizetemplateid request
 * 
 * @author auto create
 * @since 1.0, 2017.12.29
 */
class AliexpressOfferRedefiningGetcanusedproductbysizetemplateidRequest
{
	/** 
	 * 当前页码，从1开始
	 **/
	private $currentPage;
	
	/** 
	 * 尺码模版ID
	 **/
	private $sizeTemplateId;
	
	private $apiParas = array();
	
	public function setCurrentPage($currentPage)
	{
		$this->currentPage = $currentPage;
		$this->apiParas["current_page"] = $currentPage;
	}

	public function getCurrentPage()
	{
		return $this->currentPage;
	}

	public function setSizeTemplateId($sizeTemplateId)
	{
		$this->sizeTemplateId = $sizeTemplateId;
		$this->apiParas["size_template_id"] = $sizeTemplateId;
	}

	public function getSizeTemplateId()
	{
		return $this->sizeTemplateId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.offer.redefining.getcanusedproductbysizetemplateid";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->currentPage,"currentPage");
		RequestCheckUtil::checkNotNull($this->sizeTemplateId,"sizeTemplateId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
