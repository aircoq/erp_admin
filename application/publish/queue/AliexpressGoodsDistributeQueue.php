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
use think\Exception;
use app\common\model\ChannelUserAccountMap;
use app\common\model\DepartmentUserMap;
use app\common\model\aliexpress\AliexpressPublishTask;

class AliexpressGoodsDistributeQueue extends SwooleQueueJob {
    protected static $priority=self::PRIORITY_HEIGHT;

    protected $failExpire = 600;

    protected $maxFailPushCount = 3;

    public static function swooleTaskMaxNumber():int
    {
        return 5;
    }

    public function getName():string
    {
        return '速卖通产品自动分配队列';
    }
    public function getDesc():string
    {
        return '速卖通产品自动分配队列';
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


            //账号管理的销售人员
            $accountMapModel = new ChannelUserAccountMap();
            $accountMaps = $accountMapModel->field('seller_id')->where('account_id', $params['account_id'])->where('channel_id','=',4)->where('seller_id','>',0)->find();

            if($accountMaps) {
                //写入每日刊登记录表
                $accountMaps = $accountMaps->toArray();
                $userMapModel = new DepartmentUserMap();
                $depId = $userMapModel->field('department_id')->where('user_id','=', $accountMaps['seller_id'])->find();

                $time = time();
                $data = [
                    'sales_id' => $accountMaps['seller_id'],
                    'spu' => $params['goods_spu'],
                    'goods_id' => $params['goods_id'],
                    'account_id' => $params['account_id'],
                    'pre_publish_time' => $time,
                    'create_time' => $time,
                    'tag_id' => $depId ? $depId['department_id'] : 0
                ];


                $taskModel = new AliexpressPublishTask();
                $taskModel->insertGetId($data);

            }
            return true;

        }catch (Exception $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }catch (\Throwable $exp){
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
}