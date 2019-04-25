<?php
/**
 * TOP API: aliexpress.postproduct.redefining.findaeproductprohibitedwords request
 * 
 * @author auto create
 * @since 1.0, 2017.11.30
 */
class AliexpressPostproductRedefiningFindaeproductprohibitedwordsRequest
{
	/** 
	 * 商品类目ID
	 **/
	private $categoryId;
	
	/** 
	 * 商品的详细描述
	 **/
	private $detail;
	
	/** 
	 * none
	 **/
	private $keywords;
	
	/** 
	 * none
	 **/
	private $productProperties;
	
	/** 
	 * 商品的标题
	 **/
	private $title;
	
	private $apiParas = array();
	
	public function setCategoryId($categoryId)
	{
		$this->categoryId = $categoryId;
		$this->apiParas["category_id"] = $categoryId;
	}

	public function getCategoryId()
	{
		return $this->categoryId;
	}

	public function setDetail($detail)
	{
		$this->detail = $detail;
		$this->apiParas["detail"] = $detail;
	}

	public function getDetail()
	{
		return $this->detail;
	}

	public function setKeywords($keywords)
	{
		$this->keywords = $keywords;
		$this->apiParas["keywords"] = $keywords;
	}

	public function getKeywords()
	{
		return $this->keywords;
	}

	public function setProductProperties($productProperties)
	{
		$this->productProperties = $productProperties;
		$this->apiParas["product_properties"] = $productProperties;
	}

	public function getProductProperties()
	{
		return $this->productProperties;
	}

	public function setTitle($title)
	{
		$this->title = $title;
		$this->apiParas["title"] = $title;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.findaeproductprohibitedwords";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkMaxListSize($this->keywords,100,"keywords");
		RequestCheckUtil::checkMaxListSize($this->productProperties,100,"productProperties");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
