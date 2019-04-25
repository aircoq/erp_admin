<?php
/**
 * Created by PhpStorm.
 * User: joy
 * Date: 17-12-15
 * Time: 下午2:30
 */

namespace app\publish\task;

use app\common\model\amazon\AmazonHeelSaleLog;
use app\common\model\amazon\AmazonListing;
use app\common\model\amazon\AmazonUpLowerFrameRule;
use app\common\model\amazon\AmazonUpLowerFrameRuleDetail;
use app\common\service\UniqueQueuer;
use app\common\exception\TaskException;
use app\index\service\AbsTasker;
use app\listing\queue\AmazonActionLogQueue;
use app\listing\service\AmazonActionLogsHelper;
use app\publish\service\AmazonHeelSaleLogService;
use think\Exception;


class AmazonUpLowerPush extends AbsTasker
{
    public function getName()
    {
        return "Amazon定时上下架推送";
    }

    public function getDesc()
    {
        return "Amazon定时上下架推送";
    }

    public function getCreator()
    {
        return "hao";
    }

    public function getParamRule()
    {
        return [];
    }

    public function __construct()
    {
        $this->ruleModel = new AmazonUpLowerFrameRule();
        $this->ruleDetailModel = new AmazonUpLowerFrameRuleDetail();
    }

    private $ruleModel = null;
    private $ruleDetailModel = null;
    private $queuePrams = [];

    public function execute()
    {
        set_time_limit(0);
        try {
            $time = (time() + 8 * 3600) % 86400 + date('w') * 86400;

            /*
             * 注释内为测试代码；
             * $week = 3;  //星期日为0，星期一为1，星期二为2---星期六为6；
             * $hour = 23; //测试用的现在几点；
             * $min = 55;  //测试用的现在几分；
             * $time = $week * 86400 + $hour * 3600 + $min * 60;
             */

            $where=[
                'status' => 1,
                'time' => ['BETWEEN', [$time, $time + 600]],
                'action' => ['>', 0],
            ];

            $page = 1;
            $pageSize = 1000;
            while(true) {
                $rds = $this->ruleDetailModel->where($where)->field('rid,action,time')->page($page++, $pageSize)->select();
                //没有规则匹配到，直接跳过
                if (empty($rds)) {
                    break;
                }
                //过滤掉规则
                $nrds = $this->filterRule($rds);
                //处理跟卖纪录；
                $this->handHeelFromRules($nrds);

                //规则查出来比页码要少，说明没有后续的规则了；
                if (count($rds) < $pageSize) {
                    break;
                }
            }

            $time = time();
            $queue = new UniqueQueuer(AmazonActionLogQueue::class);
            foreach ($this->queuePrams as $type=>$params) {
                foreach ($params as $account_id=>$timer) {
                    $param = ['type' => $type, 'account_id' => $account_id];
                    //算出定时时间；
                    $crontime = strtotime(date('Y-m-d')) + $timer % 86400;
                    if ($crontime <= $time) {
                        $queue->push($param, 0);
                    } else {
                        $queue->push($param, ($crontime - $time));
                    }
                }
            }
        }catch (Exception $exp){
            throw new Exception($exp->getMessage(). '|'. $exp->getLine(). '|'. $exp->getFile());
        }
    }


    /**
     * 过滤开启的规则；
     * @param $rds
     * @return array
     */
    public function filterRule($rds) : array
    {
        $ids = [];
        $uniqueRules = [];
        foreach ($rds as $val) {
            if (!in_array($val['rid'], $ids)) {
                $ids[] = $val['rid'];
            }
            if (isset($uniqueRules[$val['rid']][$val['time']])) {
                $uniqueRules[$val['rid']][$val['time']] = max($uniqueRules[$val['rid']][$val['time']], $val['action']);
            } else {
                $uniqueRules[$val['rid']][$val['time']] = $val['action'];
            }
            //清除前面的操作时间；
            //while (count($uniqueRules[$val['rid']]) >= 2) {
            //    $minKey = min(array_keys($uniqueRules[$val['rid']]));
            //    unset($uniqueRules[$val['rid']][$minKey]);
            //}
        }

        if (empty($ids)) {
            return [];
        }
        $where = [
            'status' => 0,  //0正常，1关闭
            'is_delete' => 0,   //0未删除，1删除
            'start_time' => ['<=', time()],
            'end_time' => ['>=', time()],
        ];
        $ids = $this->ruleModel->where(['id' => ['in', $ids]])->where($where)->column('id');
        //rid,action,time
        $new = [];
        foreach ($uniqueRules as $rid=>$val) {
            if (!in_array($rid, $ids)) {
                continue;
            }
            foreach ($val as $time=>$action) {
                $new[] = [
                    'rid' => $rid,
                    'time' => $time,
                    'action' => $action,
                ];
            }
        }

        return $new;
    }


    /**
     * 根据规则处理跟卖
     * @param $rules
     */
    public function handHeelFromRules($rules)
    {
        if (empty($rules)) {
            return;
        }

        $uniqueRules = [];
        foreach ($rules as $val) {
            if (isset($uniqueRules[$val['rid']][$val['time']])) {
                $uniqueRules[$val['rid']][$val['time']] = max($uniqueRules[$val['rid']][$val['time']], $val['action']);
            } else {
                $uniqueRules[$val['rid']][$val['time']] = $val['action'];
            }
        }

        foreach ($uniqueRules as $rid=>$val) {
            $lists = $this->getHeelList($rid);
            foreach ($val as $t=>$a) {
                $this->pushActionLog($lists, $t, $a);
            }
        }
    }


    /**
     * 获取跟卖列表；
     * @param $rid
     * @return array
     */
    public function getHeelList($rid) : array
    {
        if (empty($rid)) {
            return [];
        }
        $model = new AmazonHeelSaleLog();
        $where = [
            'type' => 1,
            'status' => ['in', [1, 4]],
            'product_status' => 1,
            'quantity_status' => 1,
            'price_status' => 1,
            'rule_id' => $rid,
        ];

        $lists = [];
        $page = 1;
        $pageSize = 1000;
        while (true) {
            $tmps = $model->field('id,account_id,sku,asin,price,sales_price,quantity,listing_id,is_sync,rule_id')->where($where)->page($page++, $pageSize)->select();
            if (empty($tmps)) {
                break;
            }
            $lists = array_merge($lists, $tmps);
            if (count($tmps) < $pageSize) {
                break;
            }
        }
        return $lists;
    }


    /**
     * 跟卖放进队列；
     * @param $lists
     * @param $t
     * @param $a
     */
    public function pushActionLog($lists, $timer, $action)
    {
        //加日志；
        $actionService = new AmazonActionLogsHelper();
        $saleLogServ = new AmazonHeelSaleLogService();
        $saleLogModel = new AmazonHeelSaleLog();
        $listingModel = new AmazonListing();

        $callback_type = 5;
        foreach ($lists as $saleLog) {
            //查看listing存不存在，不存在则新建；
            if ($saleLog['listing_id'] > 0) {
                if (!$listingModel->where(['id' => $saleLog['listing_id']])->count('id')) {
                    $saleLog['listing_id'] = 0;
                }
            }
            if ($saleLog['listing_id'] == 0) {
                $saleLog['listing_id'] = $saleLogServ->addListingFromSaleLog($saleLog);
                $saleLogModel->update(['listing_id' => $saleLog['listing_id']], ['id' => $saleLog['id']]);
            }
            //如果价格是待同步，且此时动作是上架时，把价格重新上传一下；
            if ($saleLog['is_sync'] == 0 && $action == 1) {
                $actionData = [];
                $actionData[] = [
                    'amazon_listing_id' => $saleLog['listing_id'],
                    'account_id' => $saleLog['account_id'],
                    'new_value' => $saleLog['price'],
                    'old_value' => $saleLog['sales_price'],
                ];
                $cronTime = date('Y-m-d H:i:s', strtotime(date('Y-m-d')) + $timer % 86400);
                $callback_param = json_encode(['heel_id' => $saleLog['id']]);
                $actionService->editListingData(json_encode($actionData), 'price', 0, '', $cronTime, $callback_type, $callback_param);
                if (empty($this->queuePrams[2][$saleLog['account_id']])) {
                    $this->queuePrams[2][$saleLog['account_id']] = $timer;
                } else {
                    $this->queuePrams[2][$saleLog['account_id']] = min($this->queuePrams[2][$saleLog['account_id']], $timer);
                }
            }

            $actionData = [];
            $actionData[] = [
                'amazon_listing_id' => $saleLog['listing_id'],
                'account_id' => $saleLog['account_id'],
                'new_value' => ($action % 2) ? $saleLog['quantity'] : 0,
                'old_value' => $saleLog['quantity'],
            ];
            $cronTime = date('Y-m-d H:i:s', strtotime(date('Y-m-d')) + $timer % 86400);
            $callback_param = json_encode(['heel_id' => $saleLog['id']]);
            $actionService->editListingData(json_encode($actionData), 'quantity', 0, '', $cronTime, $callback_type, $callback_param);
            if (empty($this->queuePrams[3][$saleLog['account_id']])) {
                $this->queuePrams[3][$saleLog['account_id']] = $timer;
            } else {
                $this->queuePrams[3][$saleLog['account_id']] = min($this->queuePrams[3][$saleLog['account_id']], $timer);
            }
        }
    }
}