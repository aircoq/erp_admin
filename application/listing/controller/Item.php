<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/9/22
 * Time: 12:01
 */

namespace app\listing\controller;

use app\common\controller\Base;
use app\common\exception\JsonErrorException;
use app\listing\service\ItemService;
/**
 * @module listing系统
 * @title 本地sku与平台sku关联
 * Class Wish
 * @package app\listing\controller
 */
class Item extends Base{

	/**
	 * @title 更新线上sku与本地sku关系
	 * @url /update-sku-relation
	 * @author joy
	 * @method post
	 * @param \think\Request $request
	 * @return type
	 */
	public function updateRelation()
	{
		try{
			$post = request()->param('data');
			$platform=request()->param('platform');
			$response = (new ItemService())->updateRelation($post,$platform);
			if($response['result'])
			{
				return json($response);
			}else{
				return json($response,500);
			}
		}catch (JsonErrorException $exp){
			throw new JsonErrorException("File:".$exp->getFile().";Line:".$exp->getLine().";message:".$exp->getMessage());
		}
	}
}