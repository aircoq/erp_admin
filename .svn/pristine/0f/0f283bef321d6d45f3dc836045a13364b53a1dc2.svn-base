<?php
/**
 * TOP API: aliexpress.offer.redefining.getsizetemplatesbycategoryid request
 * 
 * @author auto create
 * @since 1.0, 2017.12.29
 */
class AliexpressOfferRedefiningGetsizetemplatesbycategoryidRequest
{
	/** 
	 * 当前页码,从1开始
	 **/
	private $currentPage;
	
	/** 
	 * 叶子类目ID
	 **/
	private $leafCategoryId;
	
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

	public function setLeafCategoryId($leafCategoryId)
	{
		$this->leafCategoryId = $leafCategoryId;
		$this->apiParas["leaf_category_id"] = $leafCategoryId;
	}

	public function getLeafCategoryId()
	{
		return $this->leafCategoryId;
	}

	public function getApiMethodName()
	{
		return "aliexpress.offer.redefining.getsizetemplatesbycategoryid";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->currentPage,"currentPage");
		RequestCheckUtil::checkNotNull($this->leafCategoryId,"leafCategoryId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
