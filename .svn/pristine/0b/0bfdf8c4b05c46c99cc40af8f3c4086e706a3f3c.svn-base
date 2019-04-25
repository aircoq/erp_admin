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
use app\common\model\aliexpress\AliexpressPublishTemplate;
use app\common\model\GoodsCategoryMap;
use think\Exception;
use app\common\cache\Cache;

class AliexpressPublishTemplateQueue extends SwooleQueueJob {
    protected static $priority=self::PRIORITY_HEIGHT;

    protected $failExpire = 600;

    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

	public function getName():string
	{
		return '速卖通刊登模板队列';
	}
	public function getDesc():string
	{
		return '速卖通刊登模板队列';
	}
	public function getAuthor():string
	{
		return 'hao';
	}

	public  function execute()
	{
		set_time_limit(0);
		try{
			$params = $this->params;

			if(!$params) {
                return;
            }

            $goods_id = $params['goods_id'];
			$category_id = $params['channel_category_id'];

            $key = 'aliexpress_publish_template';
            $hash_key = $goods_id.$category_id;

            if(!Cache::handler()->hExists($key, $hash_key)) {
                return;
            }

            $result = Cache::handler()->hGet($key, $hash_key);

            if(!$result) {
                return;
            }

            $result = \GuzzleHttp\json_decode($result, true);

            //删除缓存
            Cache::handler()->hDel($key, $hash_key);

            $uid = $result['create_id'];

            $map = [
                'goods_id'=> $goods_id,
                'channel_category_id'=> $category_id,
            ];

            $publishTemplateData=[
                'goods_id'=>$goods_id,
                'channel_category_id'=>$category_id,
                'data'=>$result['data'],
            ];

            $publishTemplateModel = new AliexpressPublishTemplate();
            $categoryMapModel = new GoodsCategoryMap();

            if(!$publishTemplateModel->field('id')->where($map)->find()){
                $publishTemplateData['create_id'] = $uid;
                $publishTemplateData['create_time'] = time();

                if($publishTemplateModel->insertGetId($publishTemplateData)) {
                    $categoryMapWhere = [
                        'goods_id' => ['=', $goods_id],
                        'channel_id' => ['=', 4],
                        'channel_category_id' => ['=', $category_id],
                    ];

                    $goodsCategoryMapData = [
                        'goods_id' => $goods_id,
                        'channel_id' => 4,
                        'operator_id' => $uid,
                        'channel_category_id' => $category_id,
                    ];

                    if ($categoryMapModel->find($categoryMapWhere)) {
                        $goodsCategoryMapData['update_time'] = time();
                        $categoryMapModel->insertGetId($goodsCategoryMapData);
                    } else {
                        $goodsCategoryMapData['create_time'] = time();
                        $categoryMapModel->insertGetId($goodsCategoryMapData);
                    }
                }
            }

            return true;
		}catch (Exception $exp){
			throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
		}catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
	}
}