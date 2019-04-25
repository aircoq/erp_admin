<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 17:07
 */

namespace app\index\validate;

use think\Validate;

class TransferShippingFee extends Validate
{
	//验证规则
	protected $rule = [
		['warehouse_id', 'require|number', '发货仓库ID不能为空|发货仓库ID为数字'],
		['carrier_id', 'require|number', '物流商ID不能为空|物流商ID为数字'],
		['currency_code', 'require', '币种不能为空'],
		['fee', 'require|number', '物流费用不能为空|请正确输入金额!'],
		['date', 'require', '年月不能为空！'],
		['status', 'require|in:0,1', '状态不能为空|状态在0和1之间！'],
		['id', 'require', 'id不能为空'],
	];
	//验证场景
	protected $scene = [
		'save_base' => ['warehouse_id', 'carrier_id', 'currency_code', 'fee', 'date'],
		'status' => ['status', 'id'],
		'history' => ['warehouse_id', 'carrier_id'],
		'transShippingFee' => ['warehouse_id', 'carrier_id', 'date']
	];

}