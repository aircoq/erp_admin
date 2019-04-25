<?php
namespace app\customerservice\task;

use app\common\model\paypal\PaypalDispute;
use app\common\model\paypal\PaypalDisputeRecord;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\PaypalDisputeByIdQueue;
use app\customerservice\queue\PaypalDisputeOperateQueue;
use think\Exception;
use app\index\service\AbsTasker;


class PaypalDisputeAutoUpdate extends AbsTasker
{
    public function getName()
    {
        return "Paypal纠纷自动更新";
    }

    public function getDesc()
    {
        return "Paypal纠纷自动更新";
    }

    public function getCreator()
    {
        return "冬";
    }

    public function getParamRule()
    {
        $updateTime = 'require|select:默认30天前零点并下载未完成数据:0';
        $endTime = 'require|select:默认当前时间:0';
        for ($i = 1; $i <= 180; $i++) {
            $updateTime .= ','. $i. '天前零点:'. $i;
            $endTime .= ','. $i. '天:'. $i;
        }
        return [
            'updateTime|更新时间' => $updateTime,
            'dayTotal|更新天数' => $endTime
        ];
    }

    public function execute()
    {
        try {
            @$down_time = (int)$this->getParam('updateTime');
            @$dayTotal = (int)$this->getParam('dayTotal');

            $limit = 100;

            $queue = new UniqueQueuer(PaypalDisputeByIdQueue::class);
            $disputeModel = new PaypalDispute();
            $params = [];

            //1.先把详情未下载完成的，下载详情；
            $where = [];
            $where['update_time'] = 0;
            $where['create_time'] = ['<', time() - 60 * 30];
            $start = 1;
            if (empty($down_time)) {
                do {
                    $pdata = $disputeModel->where($where)->page($start++, $limit)->order('id', 'asc')->field('account_id account,dispute_id')->select();
                    if (empty($pdata)) {
                        break;
                    }
                    foreach ($pdata as $data) {
                        $params[] = $data->toArray();
                    }
                } while ($limit == count($pdata));
            }


            //2.更新；
            //算出下载开始时间；
            if (empty($down_time)) {
                $down_time = 30;
            }
            $start_time = strtotime(date('Y-m-d', time() - 86400 * $down_time));

            //算出下载结束时间；
            if (empty($dayTotal)) {
                $endTime = time() - 60 * 30;
            } else {
                $endTime = $start_time + $dayTotal * 86400;
                $endTime = min($endTime, time() - 60 * 30);
            }
            $where = [];
            $where['update_time'] = ['>', 0];
            $where['dispute_create_time'] = ['BETWEEN', [$start_time, $endTime]];
            $where['status'] = ['<>', 'RESOLVED'];
            $start = 1;
            do {
                $pdata = $disputeModel->where($where)->page($start++, $limit)->order('id', 'asc')->field('account_id account,dispute_id')->select();
                if (empty($pdata)) {
                    break;
                }
                foreach ($pdata as $data) {
                    $params[] = $data->toArray();
                }
            } while ($limit == count($pdata));

            //放进列新队列；
            foreach ($params as $val) {
                $queue->push($val);
                //(new PaypalDisputeByIdQueue($val))->execute();
            }

            //3.处理队列推送；
            $params = [];
            $queue = new UniqueQueuer(PaypalDisputeOperateQueue::class);
            $where = [];
            $where['update_time'] = ['BETWEEN', [time() - 86400, time() - 60 * 10]];
            $where['status'] = ['IN', [0, 2]];
            $record = new PaypalDisputeRecord();
            $start = 1;
            do {
                $ids = $record->where($where)->page($start++, $limit)->order('id', 'asc')->column('id');
                if (empty($ids)) {
                    break;
                }
                $params = array_merge($params, $ids);
            } while ($limit == count($ids));

            //放进列新队列；
            foreach ($params as $val) {
                $queue->push($val);
                //(new PaypalDisputeOperateQueue($val))->execute();
            }

        } catch (Exception $e) {
            throw new Exception($e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
        }
    }

}