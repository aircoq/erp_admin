<?php
/**
 * TOP API: aliexpress.logistics.createwarehouseorder request
 * 
 * @author auto create
 * @since 1.0, 2018.03.15
 */
class AliexpressLogisticsCreatewarehouseorderRequest
{
	/** 
	 * addresses
	 **/
	private $addressDTOs;
	
	/** 
	 * 申报产品信息,列表类型，以json格式来表达。{productId为产品ID(必填,如为礼品,则设置为0);categoryCnDesc为申报中文名称(必填,长度1-20);categoryEnDesc为申报英文名称(必填,长度1-60);productNum产品件数(必填1-999);productDeclareAmount为产品申报金额(必填,0.01-10000.00);productWeight为产品申报重量(必填0.001-2.000);isContainsBattery为是否包含锂电池(必填0/1);scItemId为仓储发货属性代码（团购订单，仓储发货必填，物流服务为RUSTON 哈尔滨备货仓 HRB_WLB_RUSTONHEB，属性代码对应AE商品的sku属性一级，暂时没有提供接口查询属性代码，可以在仓储管理--库存管理页面查看，例如： 团购产品的sku属性White对应属性代码 40414943126）;skuValue为属性名称（团购订单，仓储发货必填，例如：White）;hsCode为产品海关编码，获取相关数据请至：http://www.customs.gov.cn/Tabid/67737/Default.aspx};isAneroidMarkup为是否含非液体化妆品（必填，填0代表不含非液体化妆品；填1代表含非液体化妆品；默认为0）;isOnlyBattery为是否含纯电池产品（必填，填0代表不含纯电池产品；填1代表含纯电池产品；默认为0）;
	 **/
	private $declareProductDTOs;
	
	/** 
	 * 国内快递公司名称,物流公司Id为-1时,必填
	 **/
	private $domesticLogisticsCompany;
	
	/** 
	 * 国内快递ID(物流公司是other时,ID为-1)
	 **/
	private $domesticLogisticsCompanyId;
	
	/** 
	 * 国内快递运单号,长度1-32
	 **/
	private $domesticTrackingNo;
	
	/** 
	 * 包裹数量： 创建国家小包订单时非必填，创建国家快递订单时必填
	 **/
	private $packageNum;
	
	/** 
	 * 订单来源
	 **/
	private $tradeOrderFrom;
	
	/** 
	 * 交易订单号
	 **/
	private $tradeOrderId;
	
	/** 
	 * 不可达处理(退回:0/销毁:1) 。详情请参考：http://bbs.seller.aliexpress.com/bbs/read.php?tid=514111
	 **/
	private $undeliverableDecision;
	
	/** 
	 * ”根据订单号获取线上发货物流方案“API获取用户选择的实际发货物流服务（物流服务key,即仓库服务名称)例如：HRB_WLB_ZTOGZ是 中俄航空 Ruston广州仓库； HRB_WLB_RUSTONHEB为哈尔滨备货仓暂不支持，该渠道请做忽略。
	 **/
	private $warehouseCarrierService;
	
	private $apiParas = array();
	
	public function setAddressDTOs($addressDTOs)
	{
		$this->addressDTOs = $addressDTOs;
		$this->apiParas["address_d_t_os"] = $addressDTOs;
	}

	public function getAddressDTOs()
	{
		return $this->addressDTOs;
	}

	public function setDeclareProductDTOs($declareProductDTOs)
	{
		$this->declareProductDTOs = $declareProductDTOs;
		$this->apiParas["declare_product_d_t_os"] = $declareProductDTOs;
	}

	public function getDeclareProductDTOs()
	{
		return $this->declareProductDTOs;
	}

	public function setDomesticLogisticsCompany($domesticLogisticsCompany)
	{
		$this->domesticLogisticsCompany = $domesticLogisticsCompany;
		$this->apiParas["domestic_logistics_company"] = $domesticLogisticsCompany;
	}

	public function getDomesticLogisticsCompany()
	{
		return $this->domesticLogisticsCompany;
	}

	public function setDomesticLogisticsCompanyId($domesticLogisticsCompanyId)
	{
		$this->domesticLogisticsCompanyId = $domesticLogisticsCompanyId;
		$this->apiParas["domestic_logistics_company_id"] = $domesticLogisticsCompanyId;
	}

	public function getDomesticLogisticsCompanyId()
	{
		return $this->domesticLogisticsCompanyId;
	}

	public function setDomesticTrackingNo($domesticTrackingNo)
	{
		$this->domesticTrackingNo = $domesticTrackingNo;
		$this->apiParas["domestic_tracking_no"] = $domesticTrackingNo;
	}

	public function getDomesticTrackingNo()
	{
		return $this->domesticTrackingNo;
	}

	public function setPackageNum($packageNum)
	{
		$this->packageNum = $packageNum;
		$this->apiParas["package_num"] = $packageNum;
	}

	public function getPackageNum()
	{
		return $this->packageNum;
	}

	public function setTradeOrderFrom($tradeOrderFrom)
	{
		$this->tradeOrderFrom = $tradeOrderFrom;
		$this->apiParas["trade_order_from"] = $tradeOrderFrom;
	}

	public function getTradeOrderFrom()
	{
		return $this->tradeOrderFrom;
	}

	public function setTradeOrderId($tradeOrderId)
	{
		$this->tradeOrderId = $tradeOrderId;
		$this->apiParas["trade_order_id"] = $tradeOrderId;
	}

	public function getTradeOrderId()
	{
		return $this->tradeOrderId;
	}

	public function setUndeliverableDecision($undeliverableDecision)
	{
		$this->undeliverableDecision = $undeliverableDecision;
		$this->apiParas["undeliverable_decision"] = $undeliverableDecision;
	}

	public function getUndeliverableDecision()
	{
		return $this->undeliverableDecision;
	}

	public function setWarehouseCarrierService($warehouseCarrierService)
	{
		$this->warehouseCarrierService = $warehouseCarrierService;
		$this->apiParas["warehouse_carrier_service"] = $warehouseCarrierService;
	}

	public function getWarehouseCarrierService()
	{
		return $this->warehouseCarrierService;
	}

	public function getApiMethodName()
	{
		return "aliexpress.logistics.createwarehouseorder";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->domesticLogisticsCompanyId,"domesticLogisticsCompanyId");
		RequestCheckUtil::checkNotNull($this->domesticTrackingNo,"domesticTrackingNo");
		RequestCheckUtil::checkNotNull($this->tradeOrderFrom,"tradeOrderFrom");
		RequestCheckUtil::checkNotNull($this->tradeOrderId,"tradeOrderId");
		RequestCheckUtil::checkNotNull($this->warehouseCarrierService,"warehouseCarrierService");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
