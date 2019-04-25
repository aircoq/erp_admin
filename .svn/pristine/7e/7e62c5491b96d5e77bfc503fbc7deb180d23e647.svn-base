<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 2017/8/24
 * Time: 9:31
 */

namespace app\publish\queue;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\publish\service\AliexpressTaskHelper;
use app\common\model\shopee\ShopeeCategory;
use app\common\service\GoogleTranslate;
use think\Exception;

class shopeeCategoryTranslateQueue extends SwooleQueueJob {
    protected static $priority=self::PRIORITY_HEIGHT;

    protected $failExpire = 600;

    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

	public function getName():string
	{
		return 'shopee分类翻译';
	}
	public function getDesc():string
	{
		return 'shopee分类翻译';
	}
	public function getAuthor():string
	{
		return 'hao';
	}

	public  function execute()
	{
		set_time_limit(0);
		try{
			$id = $this->params;
			if($id)
			{

			    $model = new ShopeeCategory;

				$categoryInfo = $model->where(['id' => $id])->field('category_name')->find();

				if(empty($categoryInfo)) {
                    return;
                }

                $categoryInfo = $categoryInfo->toArray();
                $googleTranslate = new GoogleTranslate;

                //获取翻译接口
                $translates = $googleTranslate->translateBatch([$categoryInfo['category_name']], ['target' => 'en'], 5, 9);

                if (isset($translates[0]['text']) && $translates[0]['text']) {

                    $category_name_en = $translates[0]['text'];
                    $model->update(['category_name_en' => $category_name_en], ['id' => $id]);
                }
			}

			true;
		}catch (Exception $exp){
			throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
		}catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
	}
}