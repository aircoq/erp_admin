<?php

/**
 * 申报产品信息,列表类型，以json格式来表达。{productId为产品ID(必填,如为礼品,则设置为0);categoryCnDesc为申报中文名称(必填,长度1-20);categoryEnDesc为申报英文名称(必填,长度1-60);productNum产品件数(必填1-999);productDeclareAmount为产品申报金额(必填,0.01-10000.00);productWeight为产品申报重量(必填0.001-2.000);isContainsBattery为是否包含锂电池(必填0/1);scItemId为仓储发货属性代码（团购订单，仓储发货必填，物流服务为RUSTON 哈尔滨备货仓 HRB_WLB_RUSTONHEB，属性代码对应AE商品的sku属性一级，暂时没有提供接口查询属性代码，可以在仓储管理--库存管理页面查看，例如： 团购产品的sku属性White对应属性代码 40414943126）;skuValue为属性名称（团购订单，仓储发货必填，例如：White）;hsCode为产品海关编码，获取相关数据请至：http://www.customs.gov.cn/Tabid/67737/Default.aspx};isAneroidMarkup为是否含非液体化妆品（必填，填0代表不含非液体化妆品；填1代表含非液体化妆品；默认为0）;isOnlyBattery为是否含纯电池产品（必填，填0代表不含纯电池产品；填1代表含纯电池产品；默认为0）;
 * @author auto create
 */
class AeopWlDeclareProductForTopDto
{
	
	/** 
	 * 判断是否属于非液体化妆品
	 **/
	public $aneroid_markup;
	
	/** 
	 * 是否易碎
	 **/
	public $breakable;
	
	/** 
	 * 类目中文名称
	 **/
	public $category_cn_desc;
	
	/** 
	 * 类目英文名称
	 **/
	public $category_en_desc;
	
	/** 
	 * 是否包含电池
	 **/
	public $contains_battery;
	
	/** 
	 * 海关编码
	 **/
	public $hs_code;
	
	/** 
	 * 是否纯电池
	 **/
	public $only_battery;
	
	/** 
	 * 产品申报金额
	 **/
	public $product_declare_amount;
	
	/** 
	 * 商品ID
	 **/
	public $product_id;
	
	/** 
	 * 产品数量
	 **/
	public $product_num;
	
	/** 
	 * 产品重量
	 **/
	public $product_weight;
	
	/** 
	 * scItem code
	 **/
	public $sc_item_code;
	
	/** 
	 * scItem id
	 **/
	public $sc_item_id;
	
	/** 
	 * scItem name
	 **/
	public $sc_item_name;
	
	/** 
	 * sku code
	 **/
	public $sku_code;
	
	/** 
	 * sku value
	 **/
	public $sku_value;	
}
?>