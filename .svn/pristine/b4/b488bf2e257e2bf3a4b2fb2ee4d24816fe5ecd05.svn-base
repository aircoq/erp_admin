<?php

/**
 * none
 * @author auto create
 */
class AeopAeProductListQuery
{
	
	/** 
	 * 需要商品的当前页数。默认第一页。
	 **/
	public $current_page;
	
	/** 
	 * none
	 **/
	public $excepted_product_ids;
	
	/** 
	 * 商品分组搜索字段。输入商品分组id(groupId).
	 **/
	public $group_id;
	
	/** 
	 * 是否有差异化报价: y有 n无
	 **/
	public $have_national_quote;
	
	/** 
	 * 商品的剩余有效期。如果想查3天之内即将下架的商品，则offLineTime值为3。
	 **/
	public $off_line_time;
	
	/** 
	 * none
	 **/
	public $owner_member_id;
	
	/** 
	 * 每页查询商品数量。输入值小于100，默认20。
	 **/
	public $page_size;
	
	/** 
	 * 商品id搜索字段。
	 **/
	public $product_id;
	
	/** 
	 * 商品业务状态，目前提供4种，输入参数分别是：上架:onSelling ；下架:offline ；审核中:auditing ；审核不通过:editingRequired。
	 **/
	public $product_status_type;
	
	/** 
	 * 商品标题模糊搜索字段。只支持半角英数字，长度不超过128。
	 **/
	public $subject;
	
	/** 
	 * 商品下架原因：expire_offline(过期下架)，user_offline(用户手工下架)、violate_offline(违规下架)、punish_offline(处罚下架)、degrade_offline(降级下架)、industry_offline(行业准入未续约下架)
	 **/
	public $ws_display;	
}
?>