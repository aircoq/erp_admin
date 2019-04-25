<?php
/**
 * TOP API: aliexpress.postproduct.redefining.categoryforecast request
 * 
 * @author auto create
 * @since 1.0, 2018.02.09
 */
class AliexpressPostproductRedefiningCategoryforecastRequest
{
	/** 
	 * 预测模式=1，精准预测； 模式=2，模糊预测；不填写时，默认为1
	 **/
	private $forecastMode;
	
	/** 
	 * 是否过滤类目准入权限N=不过滤|Y=过滤，不填写时，默认为N
	 **/
	private $isFilterByPermission;
	
	/** 
	 * 商品标题语言:en，ru，pt，id，es，fr，it，de，nl，tr，he，ja，ar，th，vi，ko，pl  默认为es
	 **/
	private $locale;
	
	/** 
	 * 商品标题
	 **/
	private $subject;
	
	private $apiParas = array();
	
	public function setForecastMode($forecastMode)
	{
		$this->forecastMode = $forecastMode;
		$this->apiParas["forecast_mode"] = $forecastMode;
	}

	public function getForecastMode()
	{
		return $this->forecastMode;
	}

	public function setIsFilterByPermission($isFilterByPermission)
	{
		$this->isFilterByPermission = $isFilterByPermission;
		$this->apiParas["is_filter_by_permission"] = $isFilterByPermission;
	}

	public function getIsFilterByPermission()
	{
		return $this->isFilterByPermission;
	}

	public function setLocale($locale)
	{
		$this->locale = $locale;
		$this->apiParas["locale"] = $locale;
	}

	public function getLocale()
	{
		return $this->locale;
	}

	public function setSubject($subject)
	{
		$this->subject = $subject;
		$this->apiParas["subject"] = $subject;
	}

	public function getSubject()
	{
		return $this->subject;
	}

	public function getApiMethodName()
	{
		return "aliexpress.postproduct.redefining.categoryforecast";
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
