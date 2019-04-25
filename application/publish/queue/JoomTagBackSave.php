<?php

namespace app\publish\queue;

use app\common\cache\Cache;
use app\common\service\SwooleQueueJob;
use app\common\model\joom\JoomTagSearch as JoomTagSearchModel;

/**
 * ebay分类关键词搜索记录后台保存
 */
class JoomTagBackSave extends SwooleQueueJob{
	public function getName():string
	{
		return 'Joom关键字标签搜索后台保存';
	}

	public function getDesc():string
	{
		return 'Joom关键字标签搜索后台保存';
	}

	public function getAuthor():string
	{
		return '张冬冬';
	}

    public function execute()
    {
        $data = $this->params;
        try {
            if(!empty($data['tags'])) {
                $JoomTagSearchModel = new JoomTagSearchModel();
                $JoomTagSearchModel->updateTags($data);
            }
            return true;
        }catch (\Exception $ex){
            Cache::handler()->hset('hash:ebay_category_search','error_'.time(),$ex->getMessage());
        }
    }

}