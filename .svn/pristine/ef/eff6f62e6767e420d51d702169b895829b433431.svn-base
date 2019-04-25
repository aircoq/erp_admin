<?php

/**
 * 订单放款列表
 * @author auto create
 */
class OrderLoanItemVo
{
	
	/** 
	 * 总金额
	 **/
	public $amount_total;
	
	/** 
	 * 订单ID
	 **/
	public $order_id;
	
	/** 
	 * 订单状态： PLACE_ORDER_SUCCESS:等待买家付款; IN_CANCEL:买家申请取消; WAIT_SELLER_SEND_GOODS:等待您发货; SELLER_PART_SEND_GOODS:部分发货; WAIT_BUYER_ACCEPT_GOODS:等待买家收货; FUND_PROCESSING:买卖家达成一致，资金处理中； IN_ISSUE:含纠纷中的订单; IN_FROZEN:冻结中的订单; WAIT_SELLER_EXAMINE_MONEY:等待您确认金额; RISK_CONTROL:订单处于风控24小时中，从买家在线支付完成后开始，持续24小时。 以上状态查询可分别做单独查询，不传订单状态查询订单信息不包含（FINISH，已结束订单状态） FINISH:已结束的订单，需单独查询。
	 **/
	public $order_status;
	
	/** 
	 * 子订单元素列表
	 **/
	public $son_order_list;	
}
?>