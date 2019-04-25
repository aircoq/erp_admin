<?php
namespace app\listing\queue;

use app\common\cache\Cache;
use app\common\exception\QueueException;
use app\common\service\UniqueQueuer;
use app\common\service\SwooleQueueJob;
use app\listing\service\AmazonActionLogsHelper;
use app\publish\queue\AmazonPublishResultQueuer;

class AmazonActionLogQueue extends SwooleQueueJob
{

    public function getName(): string
    {
        return 'Amazon同步修改了的Listing (队列)';
    }

    public function getDesc(): string
    {
        return 'Amazon同步修改了的Listing(队列)';
    }

    public function getAuthor(): string
    {
        return '翟彬';
    }

    public static function swooleTaskMaxNumber(): int
    {
        return 30;
    }

    public function execute()
    {
        $params = $this->params;
        if (empty($params['account_id']) || empty($params['type'])) {
            return false;
        }

        /** @var  $lock \app\common\cache\driver\Lock */
        $lock = Cache::store('Lock');
        //1.加锁，失败则证明重了，需要下次处理；
        $lockParams = ['action' => 'amazon_action_log', 'type' => $params['type'], 'account_id' => $params['account_id']];
        //$lock->unlock($lockParams);
        //此处使用唯一锁,锁住120秒，足够完成所有查询了；
        if (!$lock->uniqueLock($lockParams, 120)) {
            return false;
        }

        try {
            $serv = new AmazonActionLogsHelper();
            $submissionId = $serv->getSubmissionIdByAccount($params['account_id'], $params['type']);

            if ($submissionId > 0) {
                $queue = [
                    'account_id' => $params['account_id'],
                    'submission_id' => $submissionId,
                ];
                (new UniqueQueuer(AmazonPublishResultQueuer::class))->push($queue, 60 * 10);
            }
            $lock->unlock($lockParams);
        } catch (QueueException $exp) {
            $lock->unlock($lockParams);
            throw new QueueException("File:{$exp->getFile()};Line:{$exp->getLine()};Message:{$exp->getMessage()}");
        }
    }
}