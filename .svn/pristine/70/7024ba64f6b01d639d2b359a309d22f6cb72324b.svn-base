<?php
/**
 * TOP API: aliexpress.freight.redefining.calculatefreight request
 * 
 * @author auto create
 * @since 1.0, 2017.08.14
 */
class AliexpressFreightRedefiningCalculatefreightRequest
{
	/** 
	 * count
	 **/
	private $count;
	
	/** 
	 * country
	 **/
	private $country;
	
	/** 
	 * 运费模板ID
	 **/
	private $freightTemplateId;
	
	/** 
	 * package height
	 **/
	private $height;
	
	/** 
	 * 是否为自定义打包计重,Y/N
	 **/
	private $isCustomPackWeight;
	
	/** 
	 * package length
	 **/
	private $length;
	
	/** 
	 * 打包计重超过部分每增加件数,当isCustomPackWeight=Y时必选
	 **/
	private $packAddUnit;
	
	/** 
	 * 打包计重超过部分续重,当isCustomPackWeight=Y时必选
	 **/
	private $packAddWeight;
	
	/** 
	 * 打包计重几件以内按单个产品计重,当isCustomPackWeight=Y时必选
	 **/
	private $packBaseUnit;
	
	/** 
	 * 产品价格
	 **/
	private $productPrice;
	
	/** 
	 * package weight
	 **/
	private $weight;
	
	/** 
	 * package width
	 **/
	private $width;
	
	private $apiParas = array();
	
	public function setCount($count)
	{
		$this->count = $count;
		$this->apiParas["count"] = $count;
	}

	public function getCount()
	{
		return $this->count;
	}

	public function setCountry($country)
	{
		$this->country = $country;
		$this->apiParas["country"] = $country;
	}

	public function getCountry()
	{
		return $this->country;
	}

	public function setFreightTemplateId($freightTemplateId)
	{
		$this->freightTemplateId = $freightTemplateId;
		$this->apiParas["freight_template_id"] = $freightTemplateId;
	}

	public function getFreightTemplateId()
	{
		return $this->freightTemplateId;
	}

	public function setHeight($height)
	{
		$this->height = $height;
		$this->apiParas["height"] = $height;
	}

	public function getHeight()
	{
		return $this->height;
	}

	public function setIsCustomPackWeight($isCustomPackWeight)
	{
		$this->isCustomPackWeight = $isCustomPackWeight;
		$this->apiParas["is_custom_pack_weight"] = $isCustomPackWeight;
	}

	public function getIsCustomPackWeight()
	{
		return $this->isCustomPackWeight;
	}

	public function setLength($length)
	{
		$this->length = $length;
		$this->apiParas["length"] = $length;
	}

	public function getLength()
	{
		return $this->length;
	}

	public function setPackAddUnit($packAddUnit)
	{
		$this->packAddUnit = $packAddUnit;
		$this->apiParas["pack_add_unit"] = $packAddUnit;
	}

	public function getPackAddUnit()
	{
		return $this->packAddUnit;
	}

	public function setPackAddWeight($packAddWeight)
	{
		$this->packAddWeight = $packAddWeight;
		$this->apiParas["pack_add_weight"] = $packAddWeight;
	}

	public function getPackAddWeight()
	{
		return $this->packAddWeight;
	}

	public function setPackBaseUnit($packBaseUnit)
	{
		$this->packBaseUnit = $packBaseUnit;
		$this->apiParas["pack_base_unit"] = $packBaseUnit;
	}

	public function getPackBaseUnit()
	{
		return $this->packBaseUnit;
	}

	public function setProductPrice($productPrice)
	{
		$this->productPrice = $productPrice;
		$this->apiParas["product_price"] = $productPrice;
	}

	public function getProductPrice()
	{
		return $this->productPrice;
	}

	public function setWeight($weight)
	{
		$this->weight = $weight;
		$this->apiParas["weight"] = $weight;
	}

	public function getWeight()
	{
		return $this->weight;
	}

	public function setWidth($width)
	{
		$this->width = $width;
		$this->apiParas["width"] = $width;
	}

	public function getWidth()
	{
		return $this->width;
	}

	public function getApiMethodName()
	{
		return "aliexpress.freight.redefining.calculatefreight";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->count,"count");
		RequestCheckUtil::checkNotNull($this->country,"country");
		RequestCheckUtil::checkNotNull($this->freightTemplateId,"freightTemplateId");
		RequestCheckUtil::checkNotNull($this->height,"height");
		RequestCheckUtil::checkNotNull($this->isCustomPackWeight,"isCustomPackWeight");
		RequestCheckUtil::checkNotNull($this->length,"length");
		RequestCheckUtil::checkNotNull($this->weight,"weight");
		RequestCheckUtil::checkNotNull($this->width,"width");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
