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
use app\common\model\wish\WishAccount;
use app\common\model\wish\WishAccountHealth;
use app\common\model\wish\WishAccountHealthGoal;
use app\common\model\wish\WishAccountHealthList;
use app\common\model\wish\WishAccountHealthPayment;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\service\Encryption;
use app\common\service\Filter;
use app\common\traits\User;
use app\order\filter\WishOrderByAccountFilter;
use think\Exception;

class WishAccountHealthService
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
    private $route_url = '/api/health-receive/wish/';

    public function lists($params)
    {
        $where = $this->getCondition($params);
        //$where['status'] = 1;
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;

        $listModel = new WishAccountHealthList();
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

        $cache = Cache::store('WishAccount');
        //帐号ID；
        $goal_ids = [];
        $account_ids = [];

        $newlist = [];
        foreach ($lists as $val) {
            $temp = $val->toArray();
            $account_ids[] = $val['wish_account_id'];

            $temp['account_name'] = '-';
            $temp['code'] = '-';

            $goal_ids[] = $val['goal_id'];

            $newlist[] = $temp;
        }

        $accounts = WishAccount::where(['id' => ['in', $account_ids]])->column('account_name,code,download_health,is_invalid', 'id');

        //目标；
        $goal = $this->getGoal($goal_ids);
        foreach ($newlist as &$temp) {
            $temp['goal'] = $goal[$temp['goal_id']];
            $temp['wish_account_id'] = intval($temp['wish_account_id']);

            $temp['download_health'] = '-';
            $temp['account_status'] = '停用';
            if (!empty($accounts[$temp['wish_account_id']])) {
                $account = $accounts[$temp['wish_account_id']];
                $temp['account_name'] = $account['account_name'];
                $temp['code'] = $account['code'];

                $temp['download_health'] = ($account['download_health'] == 0) ? '未启用' : ($account['download_health'] / 60). '小时';
                $temp['account_status'] = $account['is_invalid'] ? '启用' : '停用';
            }

            $temp['health_status'] = intval($temp['health_status']);
            $temp['goal_id'] = intval($temp['goal_id']);
            $temp['imitation_rate'] = bcdiv($temp['imitation_rate'], 100, 2);
            $temp['tracking_rate'] = bcdiv($temp['tracking_rate'], 100, 2);
            $temp['delay_shipment_rate'] = bcdiv($temp['delay_shipment_rate'], 100, 2);
            $temp['refund_rate'] = bcdiv($temp['refund_rate'], 100, 2);

            $temp['thirty_score'] = bcdiv($temp['thirty_score'], 100, 2);
            $temp['onway_amount'] = floatval($temp['onway_amount']);
            $temp['unconfirm_amount'] = floatval($temp['unconfirm_amount']);

            $temp['finish_date'] = empty($temp['finish_date']) ? '-' : date('Y-m-d', $temp['finish_date']);
            $temp['create_time'] = empty($temp['update_time']) ? '-' : date('Y-m-d H:i:s', $temp['update_time']);
        }
        unset($temp);

        $returnData['data'] = $newlist;

        return $returnData;
    }


    public function accounts()
    {
        $accountList = Cache::store('WishAccount')->getAccount();
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
            $accountFilter = new Filter(WishOrderByAccountFilter::class, true);
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

        $listModel = new WishAccountHealthList();

        $lists = $listModel->where($where)
            ->order(['error_num' => 'DESC', 'update_time' => 'DESC'])
            ->limit(2000)
            ->select();

        $cache = Cache::store('WishAccount');
        //帐号ID；
        $goal_ids = [];

        $newlist = [];
        foreach ($lists as $val) {
            $temp = $val->toArray();
            $account = $cache->getAccount($temp['wish_account_id']);
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

        $statusArr = ['资料不完整', '有效', '无效', '连不上服务器'];
        $unit = $this->getUnit();
        //目标；
        //$goal = $this->getGoal($goal_ids);
        foreach ($newlist as &$temp) {
            //$temp['goal'] = $goal[$temp['goal_id']];
            $temp['wish_account_id'] = intval($temp['wish_account_id']);
            $temp['health_status'] = intval($temp['health_status']);
            $temp['goal_id'] = intval($temp['goal_id']);
            $temp['imitation_rate'] = bcdiv($temp['imitation_rate'], 100, 2);
            $temp['tracking_rate'] = bcdiv($temp['tracking_rate'], 100, 2);
            $temp['delay_shipment_rate'] = bcdiv($temp['delay_shipment_rate'], 100, 2);
            $temp['refund_rate'] = bcdiv($temp['refund_rate'], 100, 2);

            $temp['thirty_score'] = bcdiv($temp['thirty_score'], 100, 2);
            $temp['onway_amount'] = floatval($temp['onway_amount']);
            $temp['unconfirm_amount'] = floatval($temp['unconfirm_amount']);
            $temp['health_status_text'] = $statusArr[$temp['health_status']];

            $temp['finish_date'] = empty($temp['finish_date']) ? '-' : date('Y-m-d', $temp['finish_date']);
            $temp['create_time'] = empty($temp['update_time']) ? '-' : date('Y-m-d', $temp['update_time']);

            foreach ($unit as $key=>$val) {
                if (isset($temp[$key])) {
                    $temp[$key] .= $val;
                }
            }
        }

        try {
            $header = [
                ['title' => 'Wish帐号', 'key' => 'account_name', 'width' => 20],
                ['title' => '帐号简称', 'key' => 'code', 'width' => 10],
                ['title' => '仿品率', 'key' => 'imitation_rate', 'width' => 20],
                ['title' => '有效跟踪率', 'key' => 'tracking_rate', 'width' => 20],
                ['title' => '延迟发货率', 'key' => 'delay_shipment_rate', 'width' => 20],
                ['title' => '30天平均评分', 'key' => 'thirty_score', 'width' => 20],
                ['title' => '在63天到93天内的退款率', 'key' => 'refund_rate', 'width' => 20],
                ['title' => '在途金额', 'key' => 'onway_amount', 'width' => 20],
                ['title' => '待确认配送的金额', 'key' => 'unconfirm_amount', 'width' => 20],

                ['title' => '同步健康数据', 'key' => 'download_health', 'width' => 20],
                ['title' => '系统状态', 'key' => 'account_status', 'width' => 20],

                ['title' => '抓取时间', 'key' => 'create_time', 'width' => 20],
                ['title' => '登陆验证状态', 'key' => 'health_status_text', 'width' => 20],
            ];

            $name = 'wish健康';

            if (!empty($where['wish_account_id'])) {
                $account = $cache->getAccount($where['wish_account_id']);
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
                'title' => 'wish健康数据'
            ];
            $ExcelExport = new DownloadFileService();
            $result = $ExcelExport->export($newlist, $header, $file);
            return $result;

        } catch (Exception $e) {
            return ['message' => $e->getMessage()];
        }
    }

    /**
     * 查看历史记录
     * @param $wish_account_id
     * @param $params
     * @return array
     */
    public function gethistory($wish_account_id, $params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;

        $account = Cache::store('WishAccount')->getAccount($wish_account_id);

        if (empty($account)) {
            throw new Exception('wish帐号ID不存在');
        }
        $model = new WishAccountHealth();
        $count = $model->where(['wish_account_id' => $wish_account_id])->count();
        $returnData = [
            'wish_account_id' => $wish_account_id,
            'account_name' => $account['account_name'],
            'code' => $account['code'],
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'unit' => $this->getUnit(),
            'data' => [],
        ];
        $list = $model->where(['wish_account_id' => $wish_account_id])
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
            $temp['imitation_rate'] = bcdiv($temp['imitation_rate'], 100, 2);
            $temp['tracking_rate'] = bcdiv($temp['tracking_rate'], 100, 2);
            $temp['delay_shipment_rate'] = bcdiv($temp['delay_shipment_rate'], 100, 2);
            $temp['refund_rate'] = bcdiv($temp['refund_rate'], 100, 2);

            $temp['thirty_score'] = bcdiv($temp['thirty_score'], 100, 2);
            $temp['onway_amount'] = floatval($temp['onway_amount']);
            $temp['unconfirm_amount'] = floatval($temp['unconfirm_amount']);

            $temp['finish_date'] = empty($temp['finish_date']) ? '-' : date('Y-m-d', $temp['finish_date']);
            $temp['create_time'] = date('Y-m-d H:i:s', $temp['create_time']);
            $newList[] = $temp;
        }
        $returnData['data'] = $newList;
        return $returnData;
    }

    /**
     * 查看付款记录
     */
    public function getpayment($wish_account_id, $params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;

        $account = Cache::store('WishAccount')->getAccount($wish_account_id);

        if (empty($account)) {
            throw new Exception('wish帐号ID不存在');
        }

        $model = new WishAccountHealthPayment();
        $count = $model->where(['wish_account_id' => $wish_account_id])->count();
        $returnData = [
            'wish_account_id' => $wish_account_id,
            'account_name' => $account['account_name'],
            'code' => $account['code'],
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'data' => [],
        ];
        $list = $model->where(['wish_account_id' => $wish_account_id])
            ->order('trading_time', 'DESC')
            ->page($page, $pageSize)
            ->select();

        //目标；
        $newList = [];
        foreach ($list as $val) {
            $temp = $val->toArray();
            $temp['money'] = '$'. $temp['money'];
            $temp['trading_time'] = ($temp['trading_time'] < 86400 * 365) ? '-' : date('Y-m-d', $temp['trading_time']);
            $temp['create_time'] = date('Y-m-d H:i:s', $temp['create_time']);
            $newList[] = $temp;
        }
        $returnData['data'] = $newList;
        return $returnData;
    }

    public function getGoal($goal_ids)
    {
        $base = [
            'imitation_rate' => '0.50',
            'tracking_rate' => '95.00',
            'delay_shipment_rate' => '10.00',
            'thirty_score' => '4.00',

            'refund_rate' => '10.00',
            'onway_amount' => '0',
            'unconfirm_amount' => '0',
        ];

        //公用的；
        $goal = [];//Cache::store('WishAccount')->getHealthGoal();
        if (empty($goal)) {
            $goal = [];
        }

        //公用数据；
        $commongoal['imitation_rate'] = $goal['imitation_rate']?? $base['imitation_rate'];
        $commongoal['tracking_rate'] = $goal['tracking_rate']?? $base['tracking_rate'];
        $commongoal['delay_shipment_rate'] = $goal['delay_shipment_rate']?? $base['delay_shipment_rate'];
        $commongoal['thirty_score'] = $goal['thirty_score']?? $base['thirty_score'];
        $commongoal['refund_rate'] = $goal['refund_rate']?? $base['refund_rate'];
        $commongoal['onway_amount'] = $goal['onway_amount']?? $base['onway_amount'];
        $commongoal['unconfirm_amount'] = $goal['unconfirm_amount']?? $base['unconfirm_amount'];

        $goaldata = WishAccountHealthGoal::where(['id' => ['in', $goal_ids]])->column('*', 'id');

        $userGoalList = [];
        foreach ($goal_ids as $id) {
            if (!empty($goaldata[$id])) {
                $temp = $goaldata[$id];

                $temp['imitation_rate'] = bcdiv($temp['imitation_rate'], 100, 2);
                $temp['tracking_rate'] = bcdiv($temp['tracking_rate'], 100, 2);
                $temp['delay_shipment_rate'] = bcdiv($temp['delay_shipment_rate'], 100, 2);
                $temp['refund_rate'] = bcdiv($temp['refund_rate'], 100, 2);
                $temp['thirty_score'] = bcdiv($temp['thirty_score'], 100, 2);

                $userGoalList[$id] = $temp;
            } else {
                $userGoalList[$id] = $commongoal;
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
        //wish帐号集合
        $idArr = explode(',', $params['wish_account_ids']);
        unset($params['wish_account_ids']);
        $time = time();
        $model = new WishAccountHealthGoal();
        $goals = $model->where(['wish_account_id' => ['in', $idArr]])->column('id', 'wish_account_id');

        //param内的目标值转换
        $params['imitation_rate'] = $this->rateToInt($params['imitation_rate']);//仿品率
        $params['tracking_rate'] = $this->rateToInt($params['tracking_rate']);//有效跟踪率
        $params['delay_shipment_rate'] = $this->rateToInt($params['delay_shipment_rate']);//延迟发货率；
        $params['thirty_score'] = $this->rateToInt($params['thirty_score']);//30天内评分；
        $params['refund_rate'] = $this->rateToInt($params['refund_rate']);//63到93天退款；
        $params['onway_amount'] = $this->moneyToDecimal($params['onway_amount']);//在途金额；
        $params['unconfirm_amount'] = $this->moneyToDecimal($params['unconfirm_amount']);//待确认金额；

        $listModel = new WishAccountHealthList();
        foreach ($idArr as $wish_account_id) {
            $wish_account_id = trim($wish_account_id);
            $tmp = $params;
            $tmp['create_time'] = $time;
            $tmp['wish_account_id'] = $wish_account_id;
            //插入新的goal数据；
            $goal_id = $model->insertGetId($tmp);
            //把当前的list表里面的数据改为当前这条；
            $listModel->update(['goal_id' => $goal_id], ['wish_account_id' => $wish_account_id]);

        }

        //if (!empty($insertAll)) {
        //    $model->insertAll($insertAll);
        //}

        return true;
    }

    /**
     * 单帐号设置监控值
     * @param $params
     */
    public function setAccountGoal($params)
    {
        $model = new WishAccountHealthGoal();
        $goal = $model->where(['wish_account_id' => $params['wish_account_id']])->find();

        //param内的目标值转换
        $params['imitation_rate'] = $this->rateToInt($params['imitation_rate']);//仿品率
        $params['tracking_rate'] = $this->rateToInt($params['tracking_rate']);//有效跟踪率
        $params['delay_shipment_rate'] = $this->rateToInt($params['delay_shipment_rate']);//延迟发货率；
        $params['thirty_score'] = $this->rateToInt($params['thirty_score']);//30天内评分；
        $params['refund_rate'] = $this->rateToInt($params['refund_rate']);//63到93天退款；
        $params['onway_amount'] = $this->moneyToDecimal($params['onway_amount']);//在途金额；
        $params['unconfirm_amount'] = $this->moneyToDecimal($params['unconfirm_amount']);//待确认金额；

        //只新增不设置；
        $params['create_time'] = time();
        $last_goal_id = $model->insert($params);
        WishAccountHealthList::update(['goal_id' => $last_goal_id], ['wish_account_id' => $params['wish_account_id']]);

        return true;
    }

    /**
     * 读取最后一条监控值
     * @param $params
     */
    public function readGoal($wish_account_id)
    {
        $account = Cache::store('WishAccount')->getAccount($wish_account_id);
        if (empty($account)) {
            throw new Exception('wish帐号ID不存在');
        }
        $goalModel = new WishAccountHealthGoal();
        $goal = $goalModel->where(['wish_account_id' => $wish_account_id])->order('create_time', 'DESC')->find();
        if (!$goal) {
            $goal = [
                'wish_account_id' => $wish_account_id,
                'imitation_rate' => '0.50',
                'tracking_rate' => '95.00',
                'delay_shipment_rate' => '10.00',
                'thirty_score' => '4.00',
                'refund_rate' => '10.00',
                'onway_amount' => '0',
                'unconfirm_amount' => '0',
                'create_time' => time()
            ];
            $goal['id'] = $goalModel->insertGetId($goal);
        } else {
            $goal['imitation_rate'] = bcdiv($goal['imitation_rate'], 100, 2);
            $goal['tracking_rate'] = bcdiv($goal['tracking_rate'], 100, 2);
            $goal['delay_shipment_rate'] = bcdiv($goal['delay_shipment_rate'], 100, 2);
            $goal['refund_rate'] = bcdiv($goal['refund_rate'], 100, 2);

            $goal['thirty_score'] = bcdiv($goal['thirty_score'], 100, 2);
        }

        return $goal;
    }

    //拿取单位
    private function getUnit()
    {
        return [
            'imitation_rate' => '%',
            'tracking_rate' => '%',
            'delay_shipment_rate' => '%',
            'thirty_score' => '',

            'refund_rate' => '%',

            'onway_amount' => ' USD',
            'unconfirm_amount' => ' USD',
        ];
    }

    public function getCondition($params)
    {
        $where = [];
        if (!empty($params['account_id'])) {
            $where['wish_account_id'] = $params['account_id'];
        }
        if (isset($params['health_status']) && in_array($params['health_status'], ['0', '1', '2', '3'])) {
            $where['health_status'] = $params['health_status'];
        }
        if (!empty($params['time_start'])) {
            $params['time_start'] = trim($params['time_start'], ' "');
        }
        if (!empty($params['time_end'])) {
            $params['time_end'] = trim($params['time_end'], ' "');
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
            'account' => $data['account_name'],
            'abbreviation' => $data['account_code'],
            'password' => (new Encryption())->decrypt($data['password']),
            'site' => $data['site_code']
        ];

        if (!empty($lastdate)) {
            $postAccount['newTime'] = date('Y-m-d', $lastDate);
        }

        //测试的时候，发送这个数据；
        if (!$this->pattern) {
            $postAccount = [
                'account' => 'LoraCale@outlook.com',
                'abbreviation' => '6143cl',
                'password' => '5halkCCsfly',
                'site' => '',
                'newTime' => '2017-12-01'
            ];
        }

        $post['Wish'] = json_encode([$postAccount]);
        if ($this->pattern == 0) {
            $base_url = $this->test_url;
        } else {
            $base_url = $this->callback_url;
        }
        $post['CallbackUrl'] = $base_url . $this->route_url . $account_id;
        return http_build_query($post);
    }

    /**
     * 发送信息至分布式爬虫服务器；
     * @param $id
     * @param $channel_id
     * @return bool
     */
    public function sendAccount2Spider($id)
    {
        $time = time();
        $listModel = new WishAccountHealthList();
        $cache = Cache::store('WishAccount');
        $account = $cache->getAccount($id);

        if (empty($account)) {
            throw new Exception('WISH帐号ID不存在');
        }

        $channel_id = ChannelAccountConst::channel_wish;
        $data = $this->getAccountData($channel_id, $account['code']);

        //拿目录率，如果这是新增的帐号，不存在目录率，则会自动增加一级数据；
        $goal = $this->readGoal($id);

        //查看有无列表记录，无则加，有则改；
        $listId = $listModel->where(['wish_account_id' => $id])->value('id');
        if ($listId) {
            $listModel->update([
                'status' => 1,
                'goal_id' => $goal['id'],
                'update_time' => $time,
                'repitle_status' => 0,
                'health_status' => 1,
            ], ['id' => $listId]);
        } else {
            $listId = $listModel->insertGetId([
                'wish_account_id' => $id,
                'goal_id' => $goal['id'],
                'update_time' => $time,
                'create_time' => $time,
                'status' => 1,
                'repitle_status' => 0,
                'health_status' => 1,
            ]);
        }

        //如果信息不全，返回false;
//        if ($data === false) {
//            //更新表数据；
//            $listModel->update(['repitle_status' => 1,'health_status' => 0], ['id' => $listId]);
//            return false;
//        }


        if ($this->pattern == 0) {
            $base_url = $this->test_url;
        } else {
            $base_url = $this->callback_url;
        }
        $account['callbackurl'] = $base_url . $this->route_url . $account['id'];
        $send = new AccountHealthService();
        $result = $send->sendAccount2Spider($account, $channel_id);
        if (isset($result['status'])) {
            if ($result['status'] == 1 && isset($result['message']) && $result['message'] == 'success') {
                $cache->setWishLastDownloadHealthTime($account['id'], time());
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

        $cache = Cache::store('WishAccount');
        $account = $cache->getAccount($id);

        if (empty($account)) {
            throw new Exception('保存WISH帐号健康监控结果时，帐号ID: ' . $id . ' 不存在');
        }

        //找出数据；
        $data = json_decode($json, true);
        if (empty($data) || !is_array($data)) {
            //未正常返回数据；
            WishAccountHealthList::update(['health_status' => 0, 'repitle_status' => 1], ['wish_account_id' => $account['id']]);
            throw new Exception('保存WISH帐号健康监控结果时，帐号ID: ' . $id . ' 返回结果JSON为空值或错误值');
        }

        if (isset($data['status']) && !in_array($data['status'], ['Success', 'Sucess'])) {
            //帐号未授权
            WishAccountHealthList::update(['health_status' => 2, 'repitle_status' => 1], ['wish_account_id' => $account['id']]);
            return;
        }

        //取得健康数据,并保存；
        $health = isset($data['wish_zh']) ? $data : $data['data'];
        $this->_saveHealthData($id, $health);

        //经以上检测帐号授权完整
        $updata = ['health_status' => 1, 'repitle_status' => 1];
        WishAccountHealthList::update($updata, ['wish_account_id' => $account['id']]);
    }

    private function _saveHealthData($account_id, $health)
    {
        $time = time();
        $listModel = new WishAccountHealthList();
        $healthModel = new WishAccountHealth();
        $healthPaymentModel = new WishAccountHealthPayment();

        //找出最后一条目标ID,如果没有，就加一条；
        $goal = $this->readGoal($account_id);

        $data['wish_account_id'] = $account_id;
        $data['goal_id'] = intval($goal['id']);
        $data['imitation_rate'] = $this->rateToInt($health['fpl']);//仿品率
        $data['tracking_rate'] = $this->rateToInt($health['yxgzl']);//有效跟踪率

        $data['delay_shipment_rate'] = $this->rateToInt($health['ycfhl']);//延迟发货率；
        $data['thirty_score'] = $this->rateToInt($health['pjpf_30t']);//30天内评分；
        $data['refund_rate'] = $this->rateToInt($health['tkl_63d93']);//63到93天退款；

        $data['onway_amount'] = $this->moneyToDecimal($health['ztje']);//在途金额；
        $data['unconfirm_amount'] = $this->moneyToDecimal($health['dqrpsje']);//待确认金额；

        $data['finish_date'] = strtotime($health['dksj']);//到款日期；
        $data['create_time'] = $time;
        $error_num = 0;

        //记录超出目标了几个；
        if ($data['imitation_rate'] > ($goal['imitation_rate'] * 100)) {
            $error_num++;
        }
        if ($data['tracking_rate'] < ($goal['tracking_rate'] * 100)) {
            $error_num++;
        }
        if ($data['delay_shipment_rate'] > ($goal['delay_shipment_rate'] * 100)) {
            $error_num++;
        }
        if ($data['thirty_score'] < ($goal['thirty_score'] * 100)) {
            $error_num++;
        }
        if ($data['refund_rate'] > ($goal['refund_rate'] * 100)) {
            $error_num++;
        }
        if ($data['imitation_rate'] > $goal['imitation_rate']) {
            $error_num++;
        }
        if ($data['imitation_rate'] > $goal['imitation_rate']) {
            $error_num++;
        }

        //存储记录；
        $health_id = $healthModel->insertGetId($data);
        //更新wish帐号与记录一对一列表；
        $data['error_num'] = $error_num;
        $data['health_status'] = 1;     //帐号有效；
        $data['repitle_status'] = 1;    //抓取完成；
        $data['update_time'] = $data['create_time'];
        if ($listModel->where(['wish_account_id' => $account_id])->count()) {
            unset($data['create_time']);
            $listModel->update($data, ['wish_account_id' => $account_id]);
        } else {
            $listModel->insert($data);
        }

        //交易记录；
        if (!is_array($health['payHistoryTable']) || empty($health['payHistoryTable'])) {
            return false;
        }

        $payHistoryTable = [];

        //保存交易记录
        $cache = Cache::store('WishAccount');
        foreach ($health['payHistoryTable'] as $key => $record) {
            $payment = [];

            $payment['wish_account_id'] = $account_id;
            $payment['trading_time'] = strtotime($record['His_date']) ? strtotime($record['His_date']) : $key;
            $payment['payment_id'] = $record['paymentID'];
            $payment['money'] = $this->moneyToDecimal($record['money']);
            $payment['supplier'] = $record['Pay_Provider'];
            $payment['trading_status'] = $record['StateOfPay'];

            $payHistoryTable[$payment['trading_time']] = $payment;
        }

        //以时间排一下序，再循环保存；
        ksort($payHistoryTable);
        foreach ($payHistoryTable as $payment) {
            $token = [$payment['trading_time'], $payment['payment_id'], $payment['money']];
            $id = $cache->getWishAccountHealthPaymentRecord($account_id, $token);
            if ($id) {
                $healthPaymentModel->update($payment, ['id' => $id]);
            } else {
                $payment['create_time'] = $time;
                $id = $healthPaymentModel->insertGetId($payment);
                $cache->setWishAccountHealthPaymentRecord($account_id, $token, $id);
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
        $money = str_replace(['$', 'USD', ','], '', $money);
        return trim($money);
    }

    /**
     * 开通wishHealth时，新增加一条数据；
     * @param $account_id
     */
    public function openWishHealth($account_id, $down_health)
    {
        $time = time();
        $goal = $this->readGoal($account_id);

        //大于0，则开启，否则关闭；
        $status = $down_health > 0 ? 1 : 0;

        $listModel = new WishAccountHealthList();
        $data = $listModel->where(['wish_account_id' => $account_id])->find();
        if (empty($data)) {
            $listModel->insert([
                'wish_account_id' => $account_id,
                'goal_id' => $goal['id'],
                'update_time' => $time,
                'create_time' => $time,
                'repitle_status' => 0,
                'health_status' => 1,
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