<?php

namespace app\publish\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\common\model\ebay\EbayCategoryKeyword;

/**
 * ebay分类关键词搜索记录后台保存
 */
class EbayCategoryBackSave extends SwooleQueueJob{
	public function getName():string
	{
		return 'eBay分类关键词搜索记录后台保存';
	}

	public function getDesc():string
	{
		return 'eBay分类关键词搜索记录后台保存';
	}

	public function getAuthor():string
	{
		return '张冬冬';
	}

    public function execute()
    {
        try {
            $eckModel = new EbayCategoryKeyword();
            $eckModel->saveData($this->params);
            return true;
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:ebay_category_search','error_'.time(),$ex->getMessage());
        }
    }

}