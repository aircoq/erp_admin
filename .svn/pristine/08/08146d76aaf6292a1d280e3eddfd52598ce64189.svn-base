<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/9/22
 * Time: 13:42
 */

namespace app\listing\validate;

use think\Validate;
class ItemValidate extends Validate{
	protected $rules=[
		['id','require|number','平台SKU ID必填'],
		['sku','require','平台sku必填'],
		['local','require|array','本地sku数据必填,为数组类型'],
       		 ['local_sku','require','本地sku编码必填'],
		['goods_id','require|number','商品id必填'],
		['sku_id','require|number','本地SKU ID必填'],
		['quantity','require|number','商品数量必填,只能是数字'],
	];
	protected $scene = [
		'public'=>['id','sku','local'],
       		 'local'=>['local_sku','goods_id','sku_id','quantity'],
	];
	public function checkData($data,$scene='public')
	{
		$this->check($data,$this->rules,$scene);
		if($error = $this->getError())
		{
			return $error;
		}
	}
	public function checkLocalSku($data,$scene='local')
    {
        foreach ($data as $d)
        {
            $this->check($d,$this->rules,$scene);
            if($error = $this->getError())
            {
                return $error;
            }
        }
    }
}