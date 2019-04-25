<?php
/**
 * Created by PhpStorm.
 * User: zhangdongdong
 * Date: 2018/5/18
 * Time: 14:44
 */

namespace app\index\service;


use app\common\cache\Cache;
use app\common\model\Account;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\aliexpress\AliexpressAccountHealth;
use app\common\model\aliexpress\AliexpressAccountHealthList;
use app\common\model\aliexpress\AliexpressAccountHealthGoal;
use app\common\model\aliexpress\AliexpressAccountHealthPayment;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\service\Encryption;
use app\common\service\Filter;
use app\common\traits\User;
use app\order\filter\AliexpressOrderByAccountFilter;
use think\Exception;

class AliexpressAccountHealthService
{
    use User;

    /** @var int 模式，0测试，1正式 */
    private $pattern = 1;

    private $test_send_ip = '172.20.1.43';

    /** @var string 测试站Url */
    private $test_url = 'http://172.20.1.242';

    /** @var string 正试站接收url */
    private $callback_url = 'http://www.zrzsoft.com:8081';

    /** @var string 接收路由 */
    private $route_url = '/api/health-receive/aliexpress/';

    public function lists($params)
    {
        $where = $this->getCondition($params);
        $page = intval($params['page'] ?? 1);
        $pageSize = intval($params['pageSize'] ?? 20);

        $listModel = new AliexpressAccountHealthList();
        $count = $listModel->where($where)->count();

        //需返回数据；
        $returnData = [
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'unit' => $this->getUnit(),
            'data' => []
        ];

        $sort_field = !empty($params['sort_field']) ? $params['sort_field'] : 'update_time';
        $sort_type = !empty($params['sort_type']) ? $params['sort_type'] : 'desc';
        if ($sort_field == 'null' || is_null($sort_field)) {
            $sort_field = 'update_time';
        }

        $lists = $listModel->where($where)->page($page, $pageSize)
            ->order([$sort_field => $sort_type])
            ->select();

        $cache = Cache::store('AliexpressAccount');
        //帐号ID；
        $goal_ids = [];

        $newlist = [];
        foreach ($lists as $val) {
            $temp = $val->toArray();
            $account = $cache->getAccountById($temp['account_id']);
            $temp['account_name'] = $account['account_name'] ?? '-';
            $temp['code'] = $account['code'] ?? '-';

            $temp['download_health'] = '-';
            $temp['account_status'] = '停用';
            if (!empty($account)) {
                $temp['download_health'] = ($account['download_health'] == 0) ? '未启用' : ($account['download_health'] / 60). '小时';
                $temp['account_status'] = $account['is_invalid'] ? '启用' : '停用';
            }

            $goal_ids[] = $val['goal_id'];

            $newlist[] = $temp;
        }

        //目标；
        $goal = $this->getGoal($goal_ids);
        foreach ($newlist as &$temp) {
            $temp['goal'] = $goal[$temp['goal_id']];
            $temp['account_id'] = intval($temp['account_id']);
            $temp['health_status'] = intval($temp['health_status']);
            $temp['goal_id'] = intval($temp['goal_id']);

            $temp['today_score'] = bcdiv($temp['today_score'], 100, 2);//今日服务总分
            $temp['back_transaction_rate'] = bcdiv($temp['back_transaction_rate'], 100, 2);//成交不卖率
            $temp['not_cargo_dispute_rate'] = bcdiv($temp['not_cargo_dispute_rate'], 100, 2);//未收到货物纠纷提起率；
            $temp['error_cargo_dispute_rate'] = bcdiv($temp['error_cargo_dispute_rate'], 100, 2);//货不对版纠纷提起率；

            $temp['dsr_description'] = bcdiv($temp['dsr_description'], 100, 2);//DSR商品描述；
            $temp['dsr_service'] = bcdiv($temp['dsr_service'], 100, 2);//DSR卖家服务；
            $temp['dsr_shipping'] = bcdiv($temp['dsr_shipping'], 100, 2);//DSR物流；

            $temp['forty_eight_deliver'] = bcdiv($temp['forty_eight_deliver'], 100, 2);//48小时发货率；
            if ($temp['forty_eight_deliver'] < 0) {
                $temp['forty_eight_deliver'] = '不考絯';
            }

            $temp['outlaw_quality'] = bcdiv($temp['outlaw_quality'], 100, 2);//商品信息质量违规；
            $temp['outlaw_property'] = bcdiv($temp['outlaw_property'], 100, 2);//知识产权禁限违规；
            $temp['outlaw_trancation'] = bcdiv($temp['outlaw_trancation'], 100, 2);//交易违规及其他；
            $temp['severity_outlaw_property'] = bcdiv($temp['severity_outlaw_property'], 100, 2);//知识产权严重违规；

            $temp['update_time'] = empty($temp['update_time']) ? '-' : date('Y-m-d H:i:s', $temp['update_time']);//抓取时间；
            $temp['create_time'] = empty($temp['create_time']) ? '-' : date('Y-m-d H:i:s', $temp['create_time']);
        }

        $returnData['data'] = $newlist;

        return $returnData;
    }


    public function accounts()
    {

        $accountList = Cache::store('AliexpressAccount')->getAccounts();
        $data = [];

        $user = Common::getUserInfo();
        $user_id = $user['user_id'];

        if ($this->isAdmin() || $user_id == 0) {
            //如果全部授权了，就找出全部的ID；
            foreach ($accountList as $account) {
                $data[] = [
                    'label' => $account['code'],
                    'value' => $account['id'],
                    'account_name' => $account['account_name'],
                ];
            }
        } else {
            //客服ID过滤器
            $accountIds = [];   //用来装应该显示多少帐号；

            //帐号过滤器
            $accountFilter = new Filter(AliexpressOrderByAccountFilter::class, true);
            if ($accountFilter->filterIsEffective()) {
                $filterAccounts = $accountFilter->getFilterContent();
                if (is_array($filterAccounts)) {
                    $accountIds = array_merge($accountIds, $filterAccounts);
                }
            }
            foreach ($accountList as $account) {
                if (in_array($account['id'], $accountIds)) {
                    $data[] = [
                        'label' => $account['code'],
                        'value' => $account['id'],
                        'account_name' => $account['account_name'],
                    ];
                }
            }
        }

        return $data;
    }


    /**
     * 导出
     * @param $params
     * @return \think\response\Json
     */
    public function export($params)
    {
        $where = $this->getCondition($params);

        $listModel = new AliexpressAccountHealthList();

        $lists = $listModel->where($where)
            ->order(['error_num' => 'DESC', 'update_time' => 'DESC'])
            ->select();

        $cache = Cache::store('AliexpressAccount');
        //帐号ID；
        $goal_ids = [];

        $newlist = [];
        foreach ($lists as $val) {
            $temp = $val->toArray();
            $account = $cache->getAccountById($temp['account_id']);
            $temp['account_name'] = $account['account_name'] ?? '-';
            $temp['code'] = $account['code'] ?? '-';
            $goal_ids[] = $val['goal_id'];

            $temp['download_health'] = '-';
            $temp['account_status'] = '停用';
            if (!empty($account)) {
                $temp['download_health'] = ($account['download_health'] == 0) ? '未启用' : ($account['download_health'] / 60). '小时';
                $temp['account_status'] = $account['is_invalid'] ? '启用' : '停用';
            }

            $newlist[] = $temp;
        }

        $statusArr = ['资料不完整', '有效', '无效', '连不上服务器'];
        $unit = $this->getUnit();
        //目标；
        //$goal = $this->getGoal($goal_ids);
        foreach ($newlist as &$temp) {
            //$temp['goal'] = $goal[$temp['goal_id']];
            $temp['account_id'] = intval($temp['account_id']);


            $temp['today_score'] = bcdiv($temp['today_score'], 100, 2);//今日服务总分
            $temp['back_transaction_rate'] = bcdiv($temp['back_transaction_rate'], 100, 2);//成交不卖率
            $temp['not_cargo_dispute_rate'] = bcdiv($temp['not_cargo_dispute_rate'], 100, 2);//未收到货物纠纷提起率；
            $temp['error_cargo_dispute_rate'] = bcdiv($temp['error_cargo_dispute_rate'], 100, 2);//货不对版纠纷提起率；

            $temp['dsr_description'] = bcdiv($temp['dsr_description'], 100, 2);//DSR商品描述；
            $temp['dsr_service'] = bcdiv($temp['dsr_service'], 100, 2);//DSR卖家服务；
            $temp['dsr_shipping'] = bcdiv($temp['dsr_shipping'], 100, 2);//DSR物流；

            $temp['forty_eight_deliver'] = bcdiv($temp['forty_eight_deliver'], 100, 2);//48小时发货率；
            if ($temp['forty_eight_deliver'] < 0) {
                $temp['forty_eight_deliver'] = '不考絯';
            }

            $temp['outlaw_quality'] = bcdiv($temp['outlaw_quality'], 100, 2);//商品信息质量违规；
            $temp['outlaw_property'] = bcdiv($temp['outlaw_property'], 100, 2);//知识产权禁限违规；
            $temp['outlaw_trancation'] = bcdiv($temp['outlaw_trancation'], 100, 2);//交易违规及其他；
            $temp['severity_outlaw_property'] = bcdiv($temp['severity_outlaw_property'], 100, 2);//知识产权严重违规；

            $temp['health_status_text'] = $statusArr[$temp['health_status']];

            $temp['create_time'] = empty($temp['create_time']) ? '-' : date('Y-m-d', $temp['create_time']);

            foreach ($unit as $key=>$val) {
                if (isset($temp[$key])) {
                    $temp[$key] .= $val;
                }
            }
        }

        try {
            $header = [
                ['title' => '帐号名称', 'key' => 'account_name', 'width' => 20],
                ['title' => '帐号简称', 'key' => 'code', 'width' => 10],
                ['title' => '今日服务总分', 'key' => 'today_score', 'width' => 20],
                ['title' => '成交不卖率', 'key' => 'back_transaction_rate', 'width' => 20],
                ['title' => '未收到货物纠纷提起率', 'key' => 'not_cargo_dispute_rate', 'width' => 20],
                ['title' => '货不对版纠纷提起率', 'key' => 'error_cargo_dispute_rate', 'width' => 20],

                ['title' => 'DSR商品描述', 'key' => 'dsr_description', 'width' => 20],
                ['title' => 'DSR卖家服务', 'key' => 'dsr_service', 'width' => 20],
                ['title' => 'DSR物流', 'key' => 'dsr_shipping', 'width' => 20],

                ['title' => '48小时发货率', 'key' => 'forty_eight_deliver', 'width' => 20],

                ['title' => '商品信息质量违规', 'key' => 'outlaw_quality', 'width' => 20],
                ['title' => '知识产权禁限违规', 'key' => 'outlaw_property', 'width' => 20],
                ['title' => '交易违规及其他', 'key' => 'outlaw_trancation', 'width' => 20],
                ['title' => '知识产权严重违规', 'key' => 'severity_outlaw_property', 'width' => 20],

                ['title' => '同步健康数据', 'key' => 'download_health', 'width' => 20],
                ['title' => '系统状态', 'key' => 'account_status', 'width' => 20],

                ['title' => '抓取时间', 'key' => 'create_time', 'width' => 20],
                ['title' => '登陆验证状态', 'key' => 'health_status_text', 'width' => 20],
            ];

            $name = '速卖通健康';

            if (!empty($where['account_id'])) {
                $account = $cache->getAccountById($where['account_id']);
                if (!empty($account)) {
                    $name .= '('. $account['code']. ')';
                }
            }
            if (isset($where['health_status']) && in_array($where['health_status'], ['0', '1', '2', '3'], true)) {
                $name .= '('. $statusArr[$where['health_status']]. ')';
            }

            if (!empty($where['update_time'])) {
                switch ($where['update_time'][0]) {
                    case '>':
                        $name .= '('. date('Y-m-d', $where['update_time'][1]). '|)';
                        break;
                    case '<':
                        $name .= '(|'. date('Y-m-d', $where['update_time'][1] - 86400). ')';
                        break;
                    case 'between':
                        $name .= '('. date('Y-m-d', $where['update_time'][1][0]). '|'. date('Y-m-d', $where['update_time'][1][1] - 86400). ')';
                }
            }

            $file = [
                'name' => $name,
                'path' => 'index',
                'title' => '速卖通健康数据'
            ];
            $ExcelExport = new DownloadFileService();
            $result = $ExcelExport->export($newlist, $header, $file);
            return $result;

        } catch (Exception $e) {
            return json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 查看历史记录
     * @param $account_id
     * @param $params
     * @return array
     */
    public function gethistory($account_id, $params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;

        $account = Cache::store('AliexpressAccount')->getAccountById($account_id);

        if (empty($account)) {
            throw new Exception('速卖通ID不存在');
        }
        $model = new AliexpressAccountHealth();
        $count = $model->where(['account_id' => $account_id])->count();
        $returnData = [
            'account_id' => $account_id,
            'account_name' => $account['account_name'],
            'code' => $account['code'],
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'unit' => $this->getUnit(),
            'data' => [],
        ];
        $list = $model->where(['account_id' => $account_id])
            ->order('create_time', 'DESC')
            ->page($page, $pageSize)
            ->select();

        $goal_ids = [];
        foreach ($list as $val) {
            $goal_ids[] = $val['goal_id'];
        }

        //目标；
        $goal = $this->getGoal($goal_ids);
        $newList = [];
        foreach ($list as $val) {
            $temp = $val->toArray();
            $temp['goal'] = $goal[$temp['goal_id']];

            $temp['today_score'] = bcdiv($temp['today_score'], 100, 2);//今日服务总分
            $temp['back_transaction_rate'] = bcdiv($temp['back_transaction_rate'], 100, 2);//成交不卖率
            $temp['not_cargo_dispute_rate'] = bcdiv($temp['not_cargo_dispute_rate'], 100, 2);//未收到货物纠纷提起率；
            $temp['error_cargo_dispute_rate'] = bcdiv($temp['error_cargo_dispute_rate'], 100, 2);//货不对版纠纷提起率；

            $temp['dsr_description'] = bcdiv($temp['dsr_description'], 100, 2);//DSR商品描述；
            $temp['dsr_service'] = bcdiv($temp['dsr_service'], 100, 2);//DSR卖家服务；
            $temp['dsr_shipping'] = bcdiv($temp['dsr_shipping'], 100, 2);//DSR物流；

            $temp['forty_eight_deliver'] = bcdiv($temp['forty_eight_deliver'], 100, 2);//48小时发货率；
            if ($temp['forty_eight_deliver'] < 0) {
                $temp['forty_eight_deliver'] = '不考絯';
            }

            $temp['outlaw_quality'] = bcdiv($temp['outlaw_quality'], 100, 2);//商品信息质量违规；
            $temp['outlaw_property'] = bcdiv($temp['outlaw_property'], 100, 2);//知识产权禁限违规；
            $temp['outlaw_trancation'] = bcdiv($temp['outlaw_trancation'], 100, 2);//交易违规及其他；
            $temp['severity_outlaw_property'] = bcdiv($temp['severity_outlaw_property'], 100, 2);//知识产权严重违规；

            $temp['create_time'] = date('Y-m-d H:i:s', $temp['create_time']);
            $newList[] = $temp;
        }
        $returnData['data'] = $newList;
        return $returnData;
    }

    /**
     * 查看付款记录
     */
    public function getpayment($account_id, $type, $params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;
        $typeArr = ['us' => 0, 'cny' => 1, 'cnh' => 2];
        if (!isset($typeArr[$type])) {
            throw new Exception('币种参数type:'. $type. '不是有效值');
        }

        $where['type'] = $typeArr[$type];
        $where['account_id'] = $account_id;

        $account = Cache::store('AliexpressAccount')->getAccountById($account_id);

        if (empty($account)) {
            throw new Exception('速卖通ID不存在');
        }

        $model = new AliexpressAccountHealthPayment();
        $count = $model->where($where)->count();
        $returnData = [
            'account_id' => $account_id,
            'account_name' => $account['account_name'],
            'code' => $account['code'],
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'data' => [],
        ];
        $list = $model->where($where)
            ->order('create_time', 'DESC')
            ->page($page, $pageSize)
            ->select();

        //目标；
        $newList = [];
        foreach ($list as $val) {
            $temp = $val->toArray();
            $temp['money'] = '$'. $temp['money'];
            $temp['trading_time'] = empty($temp['trading_time']) ? '-' : date('Y-m-d', $temp['trading_time']);
            $temp['create_time'] = date('Y-m-d H:i:s', $temp['create_time']);
            $newList[] = $temp;
        }
        $returnData['data'] = $newList;
        return $returnData;
    }

    public function getGoal($goal_ids)
    {

        $base = [
            'today_score' => '0',
            'back_transaction_rate' => '00',
            'not_cargo_dispute_rate' => '0',
            'error_cargo_dispute_rate' => '0',
            'dsr_description' => '0',
            'dsr_service' => '0',
            'dsr_shipping' => '0',
            'forty_eight_deliver' => '0',
            'outlaw_quality' => '0',
            'outlaw_property' => '0',
            'outlaw_trancation' => '0',
            'severity_outlaw_property' => '0'
        ];

        $goaldata = AliexpressAccountHealthGoal::where(['id' => ['in', $goal_ids]])->column('*', 'id');

        $userGoalList = [];
        foreach ($goal_ids as $id) {
            if (!empty($goaldata[$id])) {
                $temp = $goaldata[$id];

                $temp['today_score'] = bcdiv($temp['today_score'], 100, 2);//今日服务总分
                $temp['back_transaction_rate'] = bcdiv($temp['back_transaction_rate'], 100, 2);//成交不卖率
                $temp['not_cargo_dispute_rate'] = bcdiv($temp['not_cargo_dispute_rate'], 100, 2);//未收到货物纠纷提起率；
                $temp['error_cargo_dispute_rate'] = bcdiv($temp['error_cargo_dispute_rate'], 100, 2);//货不对版纠纷提起率；

                $temp['dsr_description'] = bcdiv($temp['dsr_description'], 100, 2);//DSR商品描述；
                $temp['dsr_service'] = bcdiv($temp['dsr_service'], 100, 2);//DSR卖家服务；
                $temp['dsr_shipping'] = bcdiv($temp['dsr_shipping'], 100, 2);//DSR物流；

                $temp['forty_eight_deliver'] = bcdiv($temp['forty_eight_deliver'], 100, 2);//48小时发货率；
                $temp['outlaw_quality'] = bcdiv($temp['outlaw_quality'], 100, 2);//商品信息质量违规；
                $temp['outlaw_property'] = bcdiv($temp['outlaw_property'], 100, 2);//知识产权禁限违规；
                $temp['outlaw_trancation'] = bcdiv($temp['outlaw_trancation'], 100, 2);//交易违规及其他；
                $temp['severity_outlaw_property'] = bcdiv($temp['severity_outlaw_property'], 100, 2);//知识产权严重违规；

                $userGoalList[$id] = $temp;
            } else {
                $userGoalList[$id] = $base;
            }
        }

        return $userGoalList;
    }

    /**
     * 批量设置监控值
     * @param $params
     */
    public function setCommonGoal($params)
    {
        //速卖通集合
        $idArr = explode(',', $params['account_ids']);
        unset($params['account_ids']);
        $time = time();
        $model = new AliexpressAccountHealthGoal();
        $goals = $model->where(['account_id' => ['in', $idArr]])->column('id', 'account_id');

        //param内的目标值转换
        $params['today_score'] = $this->rateToInt($params['today_score']);//今日服务总分
        $params['back_transaction_rate'] = $this->rateToInt($params['back_transaction_rate']);//成交不卖率
        $params['not_cargo_dispute_rate'] = $this->rateToInt($params['not_cargo_dispute_rate']);//未收到货物纠纷提起率；
        $params['error_cargo_dispute_rate'] = $this->rateToInt($params['error_cargo_dispute_rate']);//货不对版纠纷提起率；

        $params['dsr_description'] = $this->rateToInt($params['dsr_description']);//DSR商品描述；
        $params['dsr_service'] = $this->rateToInt($params['dsr_service']);//DSR卖家服务；
        $params['dsr_shipping'] = $this->rateToInt($params['dsr_shipping']);//DSR物流；

        $params['forty_eight_deliver'] = $this->rateToInt($params['forty_eight_deliver']);//48小时发货率；
        $params['outlaw_quality'] = $this->rateToInt($params['outlaw_quality']);//商品信息质量违规；
        $params['outlaw_property'] = $this->rateToInt($params['outlaw_property']);//知识产权禁限违规；
        $params['outlaw_trancation'] = $this->rateToInt($params['outlaw_trancation']);//交易违规及其他；
        $params['severity_outlaw_property'] = $this->rateToInt($params['severity_outlaw_property']);//知识产权严重违规；

        $listModel = new AliexpressAccountHealthList();
        foreach ($idArr as $account_id) {
            $account_id = trim($account_id);
            $tmp = $params;
            $tmp['create_time'] = $time;
            $tmp['account_id'] = $account_id;
            //插入新的goal数据；
            $goal_id = $model->insertGetId($tmp);
            //把当前的list表里面的数据改为当前这条；
            $listModel->update(['goal_id' => $goal_id], ['account_id' => $account_id]);
        }

        return true;
    }

    /**
     * 单帐号设置监控值
     * @param $params
     */
    public function setAccountGoal($params)
    {
        $model = new AliexpressAccountHealthGoal();
        $goal = $model->where(['account_id' => $params['account_id']])->find();

        //param内的目标值转换
        $params['today_score'] = $this->rateToInt($params['today_score']);//今日服务总分
        $params['back_transaction_rate'] = $this->rateToInt($params['back_transaction_rate']);//成交不卖率
        $params['not_cargo_dispute_rate'] = $this->rateToInt($params['not_cargo_dispute_rate']);//未收到货物纠纷提起率；
        $params['error_cargo_dispute_rate'] = $this->rateToInt($params['error_cargo_dispute_rate']);//货不对版纠纷提起率；

        $params['dsr_description'] = $this->rateToInt($params['dsr_description']);//DSR商品描述；
        $params['dsr_service'] = $this->rateToInt($params['dsr_service']);//DSR卖家服务；
        $params['dsr_shipping'] = $this->rateToInt($params['dsr_shipping']);//DSR物流；

        $params['forty_eight_deliver'] = $this->rateToInt($params['forty_eight_deliver']);//48小时发货率；
        $params['outlaw_quality'] = $this->rateToInt($params['outlaw_quality']);//商品信息质量违规；
        $params['outlaw_property'] = $this->rateToInt($params['outlaw_property']);//知识产权禁限违规；
        $params['outlaw_trancation'] = $this->rateToInt($params['outlaw_trancation']);//交易违规及其他；
        $params['severity_outlaw_property'] = $this->rateToInt($params['severity_outlaw_property']);//知识产权严重违规；

        //只新增不设置；
        $params['create_time'] = time();
        $goal_id = $model->insertGetId($params);

        $listModel = new AliexpressAccountHealthList();
        $listModel->update(['goal_id' => $goal_id], ['account_id' => $params['account_id']]);

        return true;
    }

    /**
     * 读取最后一条监控值
     * @param $params
     */
    public function readGoal($account_id)
    {
        $account = Cache::store('AliexpressAccount')->getAccountById($account_id);
        if (empty($account)) {
            throw new Exception('速卖通ID不存在');
        }
        $goalModel = new AliexpressAccountHealthGoal();
        $goal = $goalModel->where(['account_id' => $account_id])->order('create_time', 'DESC')->find();
        if (!$goal) {
            $goal = [
                'account_id' => $account_id,
                'today_score' => '0',
                'back_transaction_rate' => '00',
                'not_cargo_dispute_rate' => '0',
                'error_cargo_dispute_rate' => '0',
                'dsr_description' => '0',
                'dsr_service' => '0',
                'dsr_shipping' => '0',
                'forty_eight_deliver' => '0',
                'outlaw_quality' => '0',
                'outlaw_property' => '0',
                'outlaw_trancation' => '0',
                'severity_outlaw_property' => '0',
                'create_time' => time()
            ];
            $goal['id'] = $goalModel->insertGetId($goal);
        } else {
            $goal['today_score'] = bcdiv($goal['today_score'], 100, 2);
            $goal['back_transaction_rate'] = bcdiv($goal['back_transaction_rate'], 100, 2);
            $goal['not_cargo_dispute_rate'] = bcdiv($goal['not_cargo_dispute_rate'], 100, 2);
            $goal['error_cargo_dispute_rate'] = bcdiv($goal['error_cargo_dispute_rate'], 100, 2);
            $goal['dsr_description'] = bcdiv($goal['dsr_description'], 100, 2);
            $goal['dsr_service'] = bcdiv($goal['dsr_service'], 100, 2);
            $goal['dsr_shipping'] = bcdiv($goal['dsr_shipping'], 100, 2);
            $goal['forty_eight_deliver'] = bcdiv($goal['forty_eight_deliver'], 100, 2);
            $goal['outlaw_quality'] = bcdiv($goal['outlaw_quality'], 100, 2);
            $goal['outlaw_property'] = bcdiv($goal['outlaw_property'], 100, 2);
            $goal['outlaw_trancation'] = bcdiv($goal['outlaw_trancation'], 100, 2);
            $goal['severity_outlaw_property'] = bcdiv($goal['severity_outlaw_property'], 100, 2);
        }
        return $goal;
    }

    //拿取单位
    private function getUnit()
    {
        return [
            'today_score' => '',
            'back_transaction_rate' => '',
            'not_cargo_dispute_rate' => '',
            'error_cargo_dispute_rate' => '',
            'dsr_description' => '',
            'dsr_service' => '',
            'dsr_shipping' => '',
            'forty_eight_deliver' => '',
            'outlaw_quality' => '',
            'outlaw_property' => '',
            'outlaw_trancation' => '',
            'severity_outlaw_property' => ''
        ];
    }

    public function getCondition($params)
    {
        $where = [];
        if (!empty($params['account_id'])) {
            $where['account_id'] = $params['account_id'];
        }
        if (isset($params['health_status']) && in_array($params['health_status'], ['0', '1', '2', '3', '4'])) {
            $where['health_status'] = $params['health_status'];
        }
        if (!empty($params['time_start']) && empty($params['time_end'])) {
            $where['update_time'] = ['>', strtotime($params['time_start'])];
        }
        if (empty($params['time_start']) && !empty($params['time_end'])) {
            $where['update_time'] = ['<', strtotime($params['time_end']) + 86400];
        }
        if (!empty($params['time_start']) && !empty($params['time_end'])) {
            $where['update_time'] = ['between', [strtotime($params['time_start']), strtotime($params['time_end']) + 86400]];
        }
        return $where;
    }

    /**
     * 通过渠道和简称找到帐号密码和IP；
     * @param $channel_id
     * @param $code
     * @return array|bool|false|\PDOStatement|string|\think\Model
     */
    private function getAccountData($channel_id, $code)
    {
        $field = 'a.account_name,a.account_code,a.site_code,a.password,s.ip';
        $where = ['a.channel_id' => $channel_id, 'a.account_code' => $code];

        $accountModel = new Account();
        $data = $accountModel->alias('a')
            ->join(['server' => 's'], 'a.server_id=s.id')
            ->where($where)
            ->field($field)
            ->find();

        if (
            empty($data) ||
            empty($data['account_name']) ||
            empty($data['account_code']) ||
            //empty($data['site_code']) ||
            empty($data['password']) ||
            empty($data['ip'])
        ) {
            return false;
        }
        return $data->toArray();
    }

    /**
     * 生成访问的URL；
     * @param $ip
     * @return string
     */
    private function buildUrl($ip)
    {
        $url = 'http://' . $ip . ':10088/start_reptile';
        return $url;
    }

    /**
     * 组成时post,urlencode编码数组；
     * @param $data
     * @param $lastData
     * @return string
     */
    private function buildPostData($data, $account_id, $lastDate)
    {
        $postAccount = [
//            'account' => $data['account_name'],
//            'abbreviation' => $data['account_code'],
//            'password' => (new Encryption())->decrypt($data['password']),
//            'site' => $data['site_code'],
            //'newTime' => $lastdate,
            //'New$Time' => $lastdate,
            //"NewCNHTime" => $lastdate,
            //"NewCNYTime" => $lastdate
        ];
        if (!empty($lastDate['us'])) {
            $postAccount['New$Time'] = date('Y-m-d', $lastDate['us']);
        }
        if (!empty($lastDate['cny'])) {
            $postAccount['NewCNYTime'] = date('Y-m-d', $lastDate['cny']);
        }
        if (!empty($lastDate['cnh'])) {
            $postAccount['NewCNHTime'] = date('Y-m-d', $lastDate['cnh']);
        }
        return json_encode($postAccount);

        //测试的时候，发送这个数据；
        if (!$this->pattern) {
            $postAccount = [
                'account' => 'jianjianwuwu@outlook.com',
                'abbreviation' => 'orangehjuns',
                'password' => '2X8*/3584F9',
                'site' => '',
                'New$Time' => "2017-12-01",
                "NewCNHTime" => "2017-12-01",
                "NewCNYTime" => "2017-12-01"
            ];
        }

        $post['Aliexpress'] = json_encode([$postAccount]);
        if ($this->pattern == 0) {
            $base_url = $this->test_url;
        } else {
            $base_url = $this->callback_url;
        }
        $post['CallbackUrl'] = $base_url . $this->route_url . $account_id;
        return http_build_query($post);
    }

    /**
     * 发送信息至分布式爬虫服务器
     * @param int $id
     * @param $channel_id
     * @return bool
     * @throws Exception
     */
    public function sendAccount2Spider(int $id)
    {
        $time = time();
        $listModel = new AliexpressAccountHealthList();
        $cache = Cache::store('AliexpressAccount');
        $account = $cache->getAccountById($id);

        if (empty($account)) {
            throw new Exception('速卖通帐号ID不存在');
        }
        $channel_id = ChannelAccountConst::channel_aliExpress;
        //先查看，拿取帐号数据；
        $data = $this->getAccountData($channel_id, $account['code']);

        //拿目录率，如果这是新增的帐号，不存在目录率，则会自动增加一级数据；
        $goal = $this->readGoal($id);

        //查看有无列表记录，无则加，有则改；
        $listId = $listModel->where(['account_id' => $id])->value('id');
        if ($listId) {
            $listModel->update([
                'goal_id' => $goal['id'],
                'update_time' => $time,
                'status' => 1,
                'health_status' => 1,
                'repitle_status' => 0,
            ], ['id' => $listId]);
        } else {
            $listId = $listModel->insertGetId([
                'account_id' => $id,
                'goal_id' => $goal['id'],
                'update_time' => $time,
                'create_time' => $time,
                'status' => 1,
                'health_status' => 1,
                'repitle_status' => 0,
            ]);
        }

        //如果信息不全，返回false;
//        if ($data === false) {
//            //更新表数据；
//            $listModel->update(['repitle_status' => 1,'health_status' => 0], ['id' => $listId]);
//            return false;
//        }

        $paymentModel = new AliexpressAccountHealthPayment();
        $last_time['us'] = $paymentModel->where(['account_id' => $account['id'], 'type' => 0])->max('trading_time');
        $last_time['cny'] = $paymentModel->where(['account_id' => $account['id'], 'type' => 1])->max('trading_time');
        $last_time['cnh'] = $paymentModel->where(['account_id' => $account['id'], 'type' => 2])->max('trading_time');

        $account['other'] = $this->buildPostData($data, $account['id'], $last_time);
        $server = new AccountHealthService();
        if ($this->pattern == 0) {
            $base_url = $this->test_url;
        } else {
            $base_url = $this->callback_url;
        }
        $account['callbackurl'] = $base_url . $this->route_url . $account['id'];
        //去请求执行
        $result = $server->sendAccount2Spider($account, $channel_id);
        //请求结果保存；
        if (isset($result['status'])) {
            if ($result['status'] == 1 && isset($result['message']) && $result['message'] == 'success') {
                $cache->setDownloadHealthTime($account['id'], time());
                return true;
            } else {
                $listModel->update(['repitle_status' => 1,'health_status' => 2], ['id' => $listId]);
                return false;
            }
        }
        //运行到这里，可以确认网络连接出现错误；
        $listModel->update(['repitle_status' => 1,'health_status' => 3], ['id' => $listId]);
        return false;
    }

    /**
     * 处理接收的健康数据；
     * @param $data
     */
    public function saveHealthData($data)
    {
        //ID分隔符；
        $separator = strpos($data, ':');
        //找出ID和json;
        $id = substr($data, 0, $separator);
        $json = substr_replace($data, '', 0, $separator + 1);

        $cache = Cache::store('AliexpressAccount');
        $account = $cache->getAccountById($id);

        if (empty($account)) {
            throw new Exception('保存速卖通健康监控结果时，帐号ID: ' . $id . ' 不存在');
        }

        //找出数据；
        $data = json_decode($json, true);
        if (empty($data) || !is_array($data)) {
            //更新表数据；
            AliexpressAccountHealthList::update(['health_status' => 2, 'repitle_status' => 1], ['account_id' => $account['id']]);
            throw new Exception('保存速卖通健康监控结果时，帐号ID: ' . $id . ' 返回结果JSON为空值或错误值');
        }

        if (isset($data['status']) && $data['status'] != 'Success') {
            //帐号未授权
            AliexpressAccountHealthList::update(['health_status' => 2, 'repitle_status' => 1], ['account_id' => $account['id']]);
            $cache->updateTableRecord($id, 'health_status', 2);
        }

        //取得健康数据；
        $health = isset($data['Aliexpress_zh']) ? $data : $data['data'];
        $this->_saveHealthData($id, $health);

        //经以上检测帐号授权完整
        $updata = ['health_status' => 1, 'repitle_status' => 1];
        AliexpressAccountHealthList::update($updata, ['account_id' => $account['id']]);
    }

    private function _saveHealthData($account_id, $health)
    {
        $time = time();
        $listModel = new AliexpressAccountHealthList();
        $healthModel = new AliexpressAccountHealth();
        $healthPaymentModel = new AliexpressAccountHealthPayment();

        //找出最后一条目标ID,如果没有，就加一条；
        $goal = $this->readGoal($account_id);

        $data['account_id'] = $account_id;
        $data['goal_id'] = intval($goal['id']);
        $data['today_score'] = $this->rateToInt($health['jrfwf']);//今日服务总分
        $data['back_transaction_rate'] = $this->rateToInt($health['cjbml']);//成交不卖率

        $data['not_cargo_dispute_rate'] = $this->rateToInt($health['wsdhwjftql'] ?? 0);//未收到货物纠纷提起率；
        $data['error_cargo_dispute_rate'] = $this->rateToInt($health['hbdbjftql']);//货不对版纠纷提起率；

        $data['dsr_description'] = $this->rateToInt($health['DSRspms']);//DSR商品描述；
        $data['dsr_service'] = $this->rateToInt($health['DSRmjfw']);//DSR卖家服务；
        $data['dsr_shipping'] = $this->rateToInt($health['DSRwl']);//DSR物流；

        $data['forty_eight_deliver'] = $this->rateToInt(trim($health['fhl_48h'] == '不考核')? '-1' : $health['fhl_48h']);//48小时发货率；
        $data['outlaw_quality'] = $this->rateToInt($health['spxxzlwg']);//商品信息质量违规；
        $data['outlaw_property'] = $this->rateToInt($health['zscqjxswg']);//知识产权禁限违规；
        $data['outlaw_trancation'] = $this->rateToInt($health['jywgjqt']);//交易违规及其他；
        $data['severity_outlaw_property'] = $this->rateToInt($health['zscqyzwg']);//知识产权严重违规；

        $data['create_time'] = $time;
        //用为排序；
        $error_num = 0;

        //记录超出目标了几个；
        if ($data['today_score'] > ($goal['today_score'] * 100)) {
            $error_num++;
        }
        if ($data['back_transaction_rate'] < ($goal['back_transaction_rate'] * 100)) {
            $error_num++;
        }
        if ($data['not_cargo_dispute_rate'] > ($goal['not_cargo_dispute_rate'] * 100)) {
            $error_num++;
        }
        if ($data['error_cargo_dispute_rate'] < ($goal['error_cargo_dispute_rate'] * 100)) {
            $error_num++;
        }
        if ($data['dsr_description'] > ($goal['dsr_description'] * 100)) {
            $error_num++;
        }
        if ($data['dsr_service'] < ($goal['dsr_service'] * 100)) {
            $error_num++;
        }
        if ($data['dsr_shipping'] < ($goal['dsr_shipping'] * 100)) {
            $error_num++;
        }
        if ($data['forty_eight_deliver'] < ($goal['forty_eight_deliver'] * 100)) {
            $error_num++;
        }
        if ($data['outlaw_quality'] < ($goal['outlaw_quality'] * 100)) {
            $error_num++;
        }
        if ($data['outlaw_property'] < ($goal['outlaw_property'] * 100)) {
            $error_num++;
        }
        if ($data['outlaw_trancation'] < ($goal['outlaw_trancation'] * 100)) {
            $error_num++;
        }
        if ($data['severity_outlaw_property'] < ($goal['severity_outlaw_property'] * 100)) {
            $error_num++;
        }

        //存储记录；
        $health_id = $healthModel->insertGetId($data);

        //更新速卖通与记录一对一列表；
        $data['account_us'] = $health['account$'] ?? '';//美国帐户；
        $data['account_cnh'] = $health['accountCNH'] ?? '';//cnh帐号；
        $data['account_cny'] = $health['accountCNY'] ?? '';//cny帐号；
        $data['error_num'] = $error_num;
        $data['health_status'] = 1;     //帐号有效；
        $data['repitle_status'] = 1;    //抓取完成；
        $data['update_time'] = $data['create_time'];
        if ($listModel->where(['account_id' => $account_id])->count()) {
            unset($data['create_time']);
            $listModel->update($data, ['account_id' => $account_id]);
        } else {
            $listModel->insert($data);
        }

        //交易记录；
        $paymentlists = [];
        $type = -1; //类型，0美元，cny=>1，cnh=>2
        if (!empty($health['accountUSPresentRecordTable'])) {
            $type = 0;
            $paymentlists[$type] = $health['accountUSPresentRecordTable'];
        }
        if (!empty($health['accountCNYPresentRecordTable'])) {
            $type = 1;
            $paymentlists[$type] = $health['accountCNYPresentRecordTable'];
        }
        if (!empty($health['accountCNHPresentRecordTable'])) {
            $type = 2;
            $paymentlists[$type] = $health['accountCNHPresentRecordTable'];
        }

        //保存交易记录
        $cache = Cache::store('AliexpressAccount');

        foreach ($paymentlists as $type => $payHistoryTable) {
            $payHistoryTable = array_reverse($payHistoryTable);
            $newPayHistoryTable = [];
            foreach ($payHistoryTable as $key => $record) {
                $payment = [];

                $payment['account_id'] = $account_id;
                $payment['type'] = $type;

                $payment['payment_id'] = $record['id'];
                $payment['card_number'] = $record['bank'];

                $payment['money'] = $this->moneyToDecimal($record['amount']);
                $payment['fee'] = $this->moneyToDecimal($record['fee']);

                $payment['trading_time'] = strtotime($record['date']) ? strtotime($record['date']) : $key;
                $payment['trading_status'] = $record['status'];
                $payment['remark'] = $record['memo'];
                //放进去，按时间排下序；
                $newPayHistoryTable[$payment['trading_time']] = $payment;
            }

            ksort($newPayHistoryTable);
            foreach ($newPayHistoryTable as $payment) {

                $token = [$payment['trading_time'], $payment['payment_id'], $payment['money'], $payment['type']];
                $id = $cache->getAccountHealthPaymentRecord($account_id, $token);
                if ($id) {
                    $healthPaymentModel->update($payment, ['id' => $id]);
                } else {
                    $payment['create_time'] = $time;
                    $id = $healthPaymentModel->insertGetId($payment);
                    $cache->setAccountHealthPaymentRecord($account_id, $token, $id);
                }
            }
        }
        return $health_id;
    }

    private function rateToInt($rate)
    {
        //拿到浮点数；
        $rate = floatval(trim($rate, '% '));
        $int = intval($rate * 100);
        return $int;
    }

    private function moneyToDecimal($money)
    {
        $money = str_replace(['$', 'USD', '￥', 'RMB', 'CNY', 'CNH'], '', $money);
        return trim($money);
    }

    /**
     * 开通wishHealth时，新增加一条数据；
     * @param $account_id
     */
    public function openHealth($account_id, $download_health)
    {
        $time = time();
        $goal = $this->readGoal($account_id);

        //大开0则开，否则关
        $status = $download_health > 0 ? 1 : 0;

        $listModel = new AliexpressAccountHealthList();
        $data = $listModel->where(['account_id' => $account_id])->find();
        if (empty($data)) {
            $listModel->insert([
                'account_id' => $account_id,
                'goal_id' => $goal['id'],
                'repitle_status' => 0,
                'health_status' => 1,
                'update_time' => $time,
                'create_time' => $time,
                'status' => $status,
            ]);
        } else {
            $listModel->update(['status' => $status], ['id' => $data['id']]);
        }
    }


    public function httpReader($url, $method = 'GET', $bodyData = [], $extra = [], &$responseHeader = null, &$code = 0, &$protocol = '', &$statusText = '')
    {
        $ci = curl_init();

        if (isset($extra['timeout'])) {
            curl_setopt($ci, CURLOPT_TIMEOUT, $extra['timeout']);
        }
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_HEADER, true);
        curl_setopt($ci, CURLOPT_AUTOREFERER, true);
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, true);

        if (isset($extra['proxyType'])) {
            curl_setopt($ci, CURLOPT_PROXYTYPE, $extra['proxyType']);

            if (isset($extra['proxyAdd'])) {
                curl_setopt($ci, CURLOPT_PROXY, $extra['proxyAdd']);
            }

            if (isset($extra['proxyPort'])) {
                curl_setopt($ci, CURLOPT_PROXYPORT, $extra['proxyPort']);
            }

            if (isset($extra['proxyUser'])) {
                curl_setopt($ci, CURLOPT_PROXYUSERNAME, $extra['proxyUser']);
            }

            if (isset($extra['proxyPass'])) {
                curl_setopt($ci, CURLOPT_PROXYPASSWORD, $extra['proxyPass']);
            }
        }

        if (isset($extra['caFile'])) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, 2); //SSL证书认证
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, true); //严格认证
            curl_setopt($ci, CURLOPT_CAINFO, $extra['caFile']); //证书
        } else {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (isset($extra['sslCertType']) && isset($extra['sslCert'])) {
            curl_setopt($ci, CURLOPT_SSLCERTTYPE, $extra['sslCertType']);
            curl_setopt($ci, CURLOPT_SSLCERT, $extra['sslCert']);
        }

        if (isset($extra['sslKeyType']) && isset($extra['sslKey'])) {
            curl_setopt($ci, CURLOPT_SSLKEYTYPE, $extra['sslKeyType']);
            curl_setopt($ci, CURLOPT_SSLKEY, $extra['sslKey']);
        }

        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'GET');
                if (!empty($bodyData)) {
                    if (is_array($bodyData)) {
                        $url .= (stristr($url, '?') === false ? '?' : '&') . http_build_query($bodyData);
                    } else {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                    }
                }
                break;
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty ($bodyData)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                }
                break;
            case 'PUT':
                //                 curl_setopt ( $ci, CURLOPT_PUT, true );
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty ($bodyData)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $bodyData);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'HEAD':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'HEAD');
                break;
            default:
                throw new \Exception(json_encode(['error' => '未定义的HTTP方式']));
                return ['error' => '未定义的HTTP方式'];
        }

        if (!isset($extra['header']) || !isset($extra['header']['Host'])) {
            $urldata = parse_url($url);
            $extra['header']['Host'] = $urldata['host'];
            unset($urldata);
        }

        $header_array = array();
        foreach ($extra['header'] as $k => $v) {
            $header_array[] = $k . ': ' . $v;
        }

        curl_setopt($ci, CURLOPT_HTTPHEADER, $header_array);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);

        curl_setopt($ci, CURLOPT_URL, $url);

        $response = curl_exec($ci);

        if (false === $response) {
            $http_info = curl_getinfo($ci);
            //throw new \Exception(json_encode(['error' => curl_error($ci), 'debugInfo' => $http_info]));
            return json_encode(['error' => curl_error($ci), 'debugInfo' => $http_info]);
        }

        $responseHeader = [];
        $headerSize = curl_getinfo($ci, CURLINFO_HEADER_SIZE);
        $headerData = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $responseHeaderList = explode("\r\n", $headerData);

        if (!empty($responseHeaderList)) {
            foreach ($responseHeaderList as $v) {
                if (false !== strpos($v, ':')) {
                    list($key, $value) = explode(':', $v, 2);
                    $responseHeader[$key] = ltrim($value);
                } else if (preg_match('/(.+?)\s(\d+)\s(.*)/', $v, $matches) > 0) {
                    $protocol = $matches[1];
                    $code = $matches[2];
                    $statusText = $matches[3];
                }
            }
        }

        curl_close($ci);
        return $body;
    }
}