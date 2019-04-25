<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 17:07
 */

namespace app\index\validate;

use think\Validate;

class Area extends Validate
{
	//验证规则
	protected $rule = [
		['english_name', 'require|alpha', '英文城市名必填|英文城市名只能输入英文'],
		['name', 'chsAlpha', '中文城市名只能为汉字或英文'],
		['country_code', 'require', '国家不能为空'],
		['id', 'require', '地区ID不能为空']
	];
	//验证场景
	protected $scene = [
		'save' => ['english_name', 'name', 'country_code'],
		'update' => ['english_name', 'name', 'country_code'],
		'cityList' => ['country_code'],
	];

}