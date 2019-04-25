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
use app\publish\service\AmazonHeelSaleLogService;
use think\Exception;

class AmazonAddUpOpenLogQueuer extends SwooleQueueJob {
    protected static $priority=self::PRIORITY_HEIGHT;

    protected $failExpire = 600;

    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 30;
    }

	public function getName():string
	{
		return '亚马逊定时上下架日志添加队列';
	}
	public function getDesc():string
	{
		return '亚马逊定时上下架日志添加队列';
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
			if($params)
			{
			    $service = new AmazonHeelSaleLogService;
                $service->addUpOpenLog($params);
			}
		}catch (Exception $exp){
			throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
		}catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
	}
}