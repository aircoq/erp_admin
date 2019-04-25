<?php
/**
 * Created by PhpStorm.
 * User: panguofu
 * Date: 2018/11/8
 * Time: 下午4:37
 */

namespace app\report\queue;

use app\common\exception\QueueException;
use app\common\service\CommonQueuer;
use app\common\service\SwooleQueueJob;
use app\common\service\UniqueQueuer;
use app\listing\queue\WishExpressQueue;
use app\report\service\StatisticShelf;


class StatisticByPublishSpuQueue extends SwooleQueueJob
{
    protected static $priority = self::PRIORITY_HEIGHT;

    public static function swooleTaskMaxNumber(): int
    {
        return 10;
    }

    public function getName(): string
    {
        return '统计刊登SPU队列';
    }

    public function getDesc(): string
    {
        return '实时统计刊登SPU数量队列';
    }

    public function getAuthor(): string
    {
        return '潘多拉';
    }

    public function execute()
    {
        set_time_limit(0);

        try {
            $params = $this->params;
            if ($params) {
               $rlt= StatisticShelf::addReportShelfNow(
                    $params['channel_id'],
                    $params['account_id'],
                    $params['shelf_id'],
                    $params['goods_id'],
                    $params['times'],
                    $params['quantity'],
                    $params['dateline']);
               return $rlt;
            } else {
                throw new QueueException("数据不合法");
            }

        } catch (Exception $exp) {
            throw new QueueException($exp->getMessage());
        }


    }

}