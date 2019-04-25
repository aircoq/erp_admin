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
use app\common\model\aliexpress\AliexpressPublishPlan;
use think\Exception;
use app\common\model\aliexpress\AliexpressProductImage;

class AliexpressPublishImageQueue extends SwooleQueueJob {
    protected static $priority=self::PRIORITY_HEIGHT;

    protected $failExpire = 600;

    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 10;
    }

	public function getName():string
	{
		return '速卖通刊登图片记录队列';
	}
	public function getDesc():string
	{
		return '速卖通刊登图片记录队列';
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

			if(empty($params)){
			    return;
            }

            $data = [
                'ali_product_id' =>$params['ap_id'],
                'type' => $params['type'],
                'thumb' => $params['thumb']
            ];

			$productImageModel = new AliexpressProductImage();

			$productImageInfo = $productImageModel->field('id')->where($data)->find();
			if(empty($productImageInfo)) {
			    $data['base_url'] = $params['url'];
                $data['create_time'] = time();

                $productImageModel->insertGetId($data);
            }

            return true;
		}catch (Exception $exp){
			throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
		}catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
	}
}