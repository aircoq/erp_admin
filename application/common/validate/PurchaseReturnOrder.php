<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21
 * Time: 18:52
 */

namespace app\common\validate;


use think\Validate;

class PurchaseReturnOrder extends Validate
{
    // 规则
    protected $rule = [
        'purchase_order_id' => 'require|number',
        'supplier_id' => 'require|number',
        'warehouse_id' => 'require|number',
        'remark' => 'require|max:200',
        'shipping_fee' => 'require|number',
        'attachment' => 'require',
        'currency_code' => 'require',
        'page' => 'require|number',
        'pageSize' => 'require|number|in:20,50,200,500',
        'id' => 'require|number',
        'audit' => 'require|number|in:0,1',
        'reason' => 'chsAlphaNum|max:200',
        'ids' => 'require',
        'export_type' => 'require|number|in:1,2',
        'fields' => 'require',
        'return_goods_id' => 'require|number',
        'logistics_company_id' => 'require|number',
        'logistics_company_name' => 'require|chsAlphaNum',
        'tracking_number' => 'require|chsAlphaNum',
        'sku_list' => 'require|array',
    ];
    // 信息
    protected $message = [
        'purchase_order_id.require' => '采购单ID必须',
        'purchase_order_id.number' => '采购单ID必须是数字',
        'supplier_id.require' => '采购单ID必须',
        'supplier_id.number' => '采购单ID必须是数字',
        'warehouse_id.require' => '采购单ID必须',
        'warehouse_id.number' => '采购单ID必须是数字',
        'remark.require' => '退款原因必须',
        'remark.max' => '退款原因不能超过200字符',
        'attachment.require' => '附件必须',
        'currency_code.require' => '币种必须',
        'currency_code.number' => '币种必须是数字',
        'page.require' => '页数必须',
        'page.number' => '页数必须是数字',
        'id.require' => '退款单ID必须',
        'audit.require' => '审核状态必须',
        'audit.number' => '审核状态必须是数字',
        'audit.in' => '审核状态必须0或1',
        'reason.chsAlphaNum' => '原因必须为汉字,英文',
        'reason.max' => '原因不能大于200字符',
        'ids.require' => 'ids必须',
        'export_type.require' => '导出类型必须',
        'export_type.number' => '导出类型必须是数字',
        'export_type.in' => '导出类型必须在1,2',
        'fields' => '导出字段必须',
        'return_goods_id.require' => '退货单ID必须',
        'return_goods_id.number' => '退货单ID必须是数字',
        'logistics_company_id.require' => '物流公司ID必须',
        'logistics_company_id.number' => '物流公司ID必须是数字',
        'logistics_company_name.require' => '物流公司名称必须',
        'logistics_company_name.chsAlphaNum' => '物流公司名称必须中英文',
        'tracking_number.require' => '运单号必须',
        'tracking_number.chsAlphaNum' => '运单号必须是中英文',
        'sku_list.require' => 'sku_list必须',
        'sku_list.array' => 'sku_list必须为数组'
    ];
    // 场景
    protected $scene = [
        //新增
        'create' => ['purchase_order_id', 'supplier_id', 'warehouse_id', 'remark', 'shipping_fee', 'attachment', 'currency_code'],
        // 列表
        'list' => ['page', 'pageSize'],
        // 审核
        'audit' => ['id', 'audit', 'reason'],
        // 批量审核
        'batch' => ['ids', 'audit', 'reason'],
        // 导出
        'export' => ['export_type', 'fields'],
        // 仓库退回单回写
        'write_back' => ['return_goods_id', 'logistics_company_id', 'logistics_company_name', 'tracking_number', 'sku_list'],
    ];
}