<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 18-5-2
 * Time: 下午2:13
 */

namespace app\publish\queue;


use app\common\exception\QueueException;
use app\common\model\pandao\PandaoActionLog;
use app\common\service\SwooleQueueJob;
use app\publish\service\PandaoApiService;
use think\Exception;

class PandaoListingUpdateQueue extends SwooleQueueJob
{
    protected static $priority=self::PRIORITY_HEIGHT;
    public static function swooleTaskMaxNumber():int
    {
        return 4;
    }
    public function getName():string
    {
        return 'pandao在线listing更新队列';
    }
    public function getDesc():string
    {
        return 'pandao在线listing更新队列';
    }
    public function getAuthor():string
    {
        return 'joy';
    }

    public  function execute()
    {
        try {
            $id = $this->params;
            if ($id)
            {
                $log = (new PandaoActionLog())
                    ->with(['product'=>function($query){$query->field('id,account_id,product_id')->with(['account'=>function($query){$query->field('id,access_token,code');}]);}])
                    ->where('id','=',$id)->find();

                if($log)
                {
                    $status = $log->getData('status');
                    $log = $log->toArray();
                    if($status!=1)
                    {
                        $api = $log['type'];
                        switch ($api)
                        {
                            case 1:
                                PandaoApiService::updateProduct($log);
                                break;
                            case 2:
                                PandaoApiService::updateVariant($log);
                                break;
                            case 3:
                                PandaoApiService::enableProduct($log);
                                break;
                            case 4:
                                PandaoApiService::disableProduct($log);
                                break;
                            case 5:
                                PandaoApiService::enableVariant($log);
                                break;
                            case 6:
                                PandaoApiService::disableVariant($log);
                                break;
                            case 11:
                                PandaoApiService::rsyncProduct($log);
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }

}