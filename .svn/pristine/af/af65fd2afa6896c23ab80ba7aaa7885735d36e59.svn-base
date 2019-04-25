<?php
/**
 * TOP API: aliexpress.postproduct.redefining.editsimpleproductfiled request
 * 
 * @author auto create
 * @since 1.0, 2018.01.12
 */
class AliexpressPostproductRedefiningEditsimpleproductfiledRequest
{
	/** 
	 * 编辑的字段名称，为以下字段内容里的其中一项, 可以编辑的字段包括: subject: 商品的标题; Detail: 商品的详细描述信息； deliveryTime: 备货期； groupId: 产品组； freightTemplateId: 运费模版； packageLength: 商品包装长度； packageWidth: 商品包装宽度； packageHeight：商品包装高度； grossWeight: 商品毛重； wsValidNum商品的有效天数；mobileDetail：无线详描（注意：该字段的提交修改，数据生效时间：商品（到期或手动）下架再上架生效。”）; reduceStrategy: 库存扣减策略(总共有2种：下单减库存(place_order_withhold)和支付减库存(payment_success_deduct)。);imageURLs:商品主图 多个图片时，用冒号分隔 ;promiseTemplateId:服务模板id
	 **/
	private $fiedName;
	
	/** 
	 * 根据fiedName变化：fiedName=detail时，本字段是一段html字符串；fiedName=mobileDetail时，本字段的值是一段json字符串；fiedName=groupId时，本字段是一个产品分组唯一标识（数字类型）。
	 **/
	private $fiedvalue;
	
	/** 
	 * 指定编辑产品的id
	 **/
	private $productId;
	
	private $apiParas = array();
	
	public function setFiedName($fiedName)
	{
		$this->fiedName = $fiedName;
		$this->apiParas["fied_name"] = $fiedName;
	}

	public function getFiedName()
	{
		return $this->fiedName;
	}

	public function setFiedvalue($fiedvalue)
	{
		$this->fiedvalue = $fiedvalue;
		$this->apiParas["fiedvalue"] = $fiedvalue;
	}

	public function getFiedvalue()
	{
		return $this->fiedvalue;
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
		return "aliexpress.postproduct.redefining.editsimpleproductfiled";
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
