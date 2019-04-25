<?php
namespace app\customerservice\task;

use app\common\service\UniqueQueuer;
use app\customerservice\queue\EbayCancelByIdQueue;
use app\customerservice\queue\EbayCaseByIdQueue;
use app\customerservice\queue\EbayQuiriesByIdQueue;
use app\customerservice\queue\EbayReturnByIdQueue;
use think\Exception;
use app\common\exception\TaskException;
use app\index\service\AbsTasker;
use app\customerservice\service\EbayDisputeHelp;
use app\common\model\ebay\EbayRequest as EbayRequestModel;
use app\common\model\ebay\EbayCase as EbayCaseModel;


class EbayDisputeAutoUpdate extends AbsTasker
{
    public function getName()
    {
        return "Ebay纠纷自动更新";
    }

    public function getDesc()
    {
        return "Ebay纠纷自动更新";
    }

    public function getCreator()
    {
        return "冬";
    }

    public function getParamRule()
    {
        return [
            'updateTime|更新时间' => 'require|select:默认10天:0,1天前到现在:1,3天前到现在:3,5天前到现在:5,10天前到现在:10,15天前到现在:15,20天前到现在:20,25天前到现在:25,30天前到现在:30,50天前到现在:50,90天前到现在:90,所有的纠纷:10000'
        ];
    }

    public function execute()
    {
        try {
            $down_time = (int)$this->getParam('updateTime');
            if (empty($down_time)) {
                $down_time = 10;
            }
            $start_time = time() - 86400 * $down_time;

            $limit = 100;
            $requestModel = new EbayRequestModel();
            $caseModel = new EbayCaseModel();

            //失败重下-下载cancel;
            $queue = new UniqueQueuer(EbayCancelByIdQueue::class);
            $where = [];
            $where['request_type'] = EbayRequestModel::EBAY_REQUEST_CANCEL;
            $where['update_time'] = 0;
            $where['created_time'] = ['<', time() - 60 * 30];
            $start = 0;
            while (true) {
                $pdata = $requestModel->where($where)->limit($start * $limit, $limit)->order('id', 'asc')->field('account_id,request_id cancel_id')->select();
                if (empty($pdata)) {
                    break;
                }
                foreach ($pdata as $data) {
                    $params = $data->toArray();
                    $queue->push($params);
                }
                if (count($pdata) < $limit) {
                    break;
                }
                $start++;
            }

            //更新cancel;
            $queue = new UniqueQueuer(EbayCancelByIdQueue::class);
            $where = [];
            $where['request_type'] = EbayRequestModel::EBAY_REQUEST_CANCEL;
            $where['initiates_time'] = ['>', $start_time];
            //20分钟内更新的不再加入队列
            $where['update_time'] = ['<', time() - 60 * 10];
            $where['state'] = ['NEQ', 'CLOSED'];
            $start = 0;
            while (true) {
                $pdata = $requestModel->where($where)->limit($start * $limit, $limit)->order('id', 'asc')->field('account_id,request_id cancel_id')->select();
                if (empty($pdata)) {
                    break;
                }
                foreach ($pdata as $data) {
                    $params = $data->toArray();
                    $queue->push($params);
                }
                if (count($pdata) < $limit) {
                    break;
                }
                $start++;
            }

            //下载return;
            $queue = new UniqueQueuer(EbayReturnByIdQueue::class);
            $where = [];
            $where['request_type'] = EbayRequestModel::EBAY_REQUEST_RETURN;
            $where['update_time'] = 0;
            $where['created_time'] = ['<', time() - 60 * 30];
            $start = 0;
            while (true) {
                $pdata = $requestModel->where($where)->limit($start * $limit, $limit)->order('id', 'asc')->field('account_id,request_id return_id')->select();
                if (empty($pdata)) {
                    break;
                }
                foreach ($pdata as $data) {
                    $params = $data->toArray();
                    $queue->push($params);
                }
                if (count($pdata) < $limit) {
                    break;
                }
                $start++;
            }

            //更新return;
            $queue = new UniqueQueuer(EbayReturnByIdQueue::class);
            $where = [];
            $where['request_type'] = EbayRequestModel::EBAY_REQUEST_RETURN;
            $where['initiates_time'] = ['>', $start_time];
            //20分钟内更新的不再加入队列
            $where['update_time'] = ['<', time() - 60 * 10];
            $where['state'] = ['NEQ', 'CLOSED'];
            $start = 0;
            while (true) {
                $pdata = $requestModel->where($where)->limit($start * $limit, $limit)->order('id', 'asc')->field('account_id,request_id return_id')->select();
                if (empty($pdata)) {
                    break;
                }
                foreach ($pdata as $data) {
                    $params = $data->toArray();
                    $queue->push($params);
                }
                if (count($pdata) < $limit) {
                    break;
                }
                $start++;
            }

            //下载case;
            $queue = new UniqueQueuer(EbayCaseByIdQueue::class);
            $where = [];
            $where['case_type'] = ['in', ['01', '11']];
            $where['update_time'] = 0;
            $where['created_time'] = ['<', time() - 60 * 30];
            $start = 0;
            while (true) {
                $pdata = $caseModel->where($where)->limit($start * $limit, $limit)->order('id', 'asc')->field('account_id,case_id')->select();
                if (empty($pdata)) {
                    break;
                }
                foreach ($pdata as $data) {
                    $params = $data->toArray();
                    $queue->push($params);
                }
                if (count($pdata) < $limit) {
                    break;
                }
                $start++;
            }

            //更新case;
            $queue = new UniqueQueuer(EbayCaseByIdQueue::class);
            $where = [];
            $where['case_type'] = ['in', ['01', '11']];
            $where['initiates_time'] = ['>', $start_time];
            //20分钟内更新的不再加入队列
            $where['update_time'] = ['<', time() - 60 * 10];
            $where['state'] = ['NEQ', 'CLOSED'];
            $start = 0;
            while (true) {
                $pdata = $caseModel->where($where)->limit($start * $limit, $limit)->order('id', 'asc')->field('account_id,case_id')->select();
                if (empty($pdata)) {
                    break;
                }
                foreach ($pdata as $data) {
                    $params = $data->toArray();
                    $queue->push($params);
                }
                if (count($pdata) < $limit) {
                    break;
                }
                $start++;
            }

            //更新inquiry;
            $queue = new UniqueQueuer(EbayQuiriesByIdQueue::class);
            $where = [];
            $where['case_type'] = ['in', ['02', '12']];
            $where['update_time'] = 0;
            $where['created_time'] = ['<', time() - 60 * 30];
            $start = 0;
            while (true) {
                $pdata = $caseModel->where($where)->limit($start * $limit, $limit)->order('id', 'asc')->field('account_id,case_id inquiry_id')->select();
                if (empty($pdata)) {
                    break;
                }
                foreach ($pdata as $data) {
                    $params = $data->toArray();
                    $queue->push($params);
                }
                if (count($pdata) < $limit) {
                    break;
                }
                $start++;
            }

            //更新inquiry;
            $queue = new UniqueQueuer(EbayQuiriesByIdQueue::class);
            $where = [];
            $where['case_type'] = ['in', ['02', '12']];
            $where['initiates_time'] = ['>', $start_time];
            //20分钟内更新的不再加入队列
            $where['update_time'] = ['<', time() - 60 * 10];
            $where['state'] = ['NEQ', 'CLOSED'];
            $start = 0;
            while (true) {
                $pdata = $caseModel->where($where)->limit($start * $limit, $limit)->order('id', 'asc')->field('account_id,case_id inquiry_id')->select();
                if (empty($pdata)) {
                    break;
                }
                foreach ($pdata as $data) {
                    $params = $data->toArray();
                    $queue->push($params);
                }
                if (count($pdata) < $limit) {
                    break;
                }
                $start++;
            }
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
        } catch (TaskException $e) {
            throw new Exception($e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
        } catch (\Exception $e) {
            throw new Exception($e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
        }
    }

}