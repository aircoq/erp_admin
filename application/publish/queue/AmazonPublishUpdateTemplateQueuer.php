<?php

namespace app\publish\queue;

use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\service\SwooleQueueJob;
use app\publish\service\AmazonPublishResultService;
use think\Exception;

class AmazonPublishUpdateTemplateQueuer extends  SwooleQueueJob {

    public function getName():string
    {
        return 'amazon刊登-自动更新模板';
    }

    public function getDesc():string
    {
        return 'amazon刊登-自动更新模板';
    }

    public function getAuthor():string
    {
        return '冬';
    }

    public function init()
    {
    }

    public static function swooleTaskMaxNumber():int
    {
        return 2;
    }


    public function execute()
    {
        set_time_limit(0);
        $params = $this->params;
        if (empty($params)) {
            return true;
        }
        try {
            $type = $this->params['type'] ?? 0;
            $name = $this->params['name'] ?? '';
            $pid = $this->params['pid'] ?? 0;
            $cid = $this->params['cid'] ?? 0;
            if (
                empty($type) ||
                empty($name) ||
                empty($pid) ||
                empty($cid)
            ) {
                return false;
            }


            /** @var  $lock \app\common\cache\driver\Lock */
            $lock = Cache::store('Lock');
            //1.加锁，失败则证明重了，需要下次处理；
            $lockParams = $this->params;
            $lockParams['action'] = 'AmazonPublishUpdateTemplateQueuer';
            //$lock->unlock($lockParams);
            //此处使用唯一锁,锁住120秒，足够完成所有查询了；
            if (!$lock->uniqueLock($lockParams, 120)) {
                return false;
            }

            $serv = new AmazonPublishResultService();
            $serv->autoUpdateTemplate($name, $pid, $cid, $type);

            return true;
        } catch (Exception $exp){
            throw new QueueException($exp->getMessage());
        }
    }
}