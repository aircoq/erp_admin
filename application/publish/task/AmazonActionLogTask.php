<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2019/3/8
 * Time: 19:30
 */

namespace app\publish\task;


use app\common\model\amazon\AmazonActionLog;
use app\common\service\UniqueQueuer;
use app\index\service\AbsTasker;
use app\listing\queue\AmazonActionLogQueue;
use app\publish\queue\AmazonPublishResultQueuer;

class AmazonActionLogTask extends AbsTasker
{
    public function getName()
    {
        return "Amazon-listing修改商品参数任务";
    }

    public function getDesc()
    {
        return "Amazon-listing修改商品参数";
    }

    public function getCreator()
    {
        return "冬";
    }

    public function getParamRule()
    {
        return [];
    }


    public function execute()
    {
        $model = new AmazonActionLog();
        $pageSize = 1000;

        //1.查出需修改的数据，以帐号和类型为一个分组；
        $where1 = [
            'create_time' => ['>', strtotime('-1 days')],
            'status' => 0,
            'request_number' => ['<', 30],
        ];
        $page = 1;
        do {
            $lists = $model->where($where1)->group('account_id,type')->page($page++, $pageSize)->field('account_id,type')->select();
            $queue1 = new UniqueQueuer(AmazonActionLogQueue::class);
            foreach ($lists as $val) {
                $queue1->push(['account_id' => $val['account_id'], 'type' => $val['type']]);
            }
        } while(count($lists) == $pageSize);


        //2.查出需查询结果的数据，以帐号和submission为一个分组
        $where2 = [
            'create_time' => ['between', [strtotime('-1 days'), time() - 30 * 60]],
            'submission_id' => ['<>', ''],
            'status' => 3,
        ];
        $page = 1;
        do {
            $lists = $model->where($where2)->group('account_id,submission_id')->page($page++, $pageSize)->field('account_id,submission_id')->select();
            $queue2 = new UniqueQueuer(AmazonPublishResultQueuer::class);
            foreach ($lists as $val) {
                $queue2->push(['account_id' => $val['account_id'], 'submission_id' => $val['submission_id']]);
            }
        } while(count($lists) == $pageSize);
    }

}