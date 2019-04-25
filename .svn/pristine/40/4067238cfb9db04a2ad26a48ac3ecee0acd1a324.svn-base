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
use app\common\model\amazon\AmazonAccount;
use app\common\model\amazon\AmazonAccountHealth;
use app\common\model\amazon\AmazonAccountHealthGoal;
use app\common\model\amazon\AmazonAccountHealthList;
use app\common\model\Server;
use app\common\service\ChannelAccountConst;
use app\common\service\Encryption;
use think\Exception;

class AmazonAccountHealthService
{

    /** @var int 模式，0测试，1正式 */
    private $pattern = 1;

    private $test_send_ip = '172.19.23.15';

    /** @var string 测试站Url */
    private $test_url = 'http://172.18.8.242';

    /** @var string 正试站接收url */
    private $callback_url = 'http://www.zrzsoft.com:8081';

    /** @var string 接收路由 */
    private $route_url = '/api/health-receive/amazon/';

    public function lists($params)
    {
        $where = $this->getCondition($params);
//        $where['status'] = 1;
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;
        $listModel = new AmazonAccountHealthList();
        $count = $listModel->where($where)->count();
        //需返回数据；
        $returnData = [
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'unit' => $this->getUnit(),
            'data' => []
        ];

        $lists = $listModel->where($where)->page($page, $pageSize)
            ->order(['error_num' => 'DESC', 'update_time' => 'DESC'])
            ->select();

        $cache = Cache::store('AmazonAccount');
        //帐号ID；
        $goal_ids = [];

        $newlist = [];
        foreach ($lists as $val) {
            $temp = $val->toArray();
            $account = $cache->getAccount($temp['amazon_account_id']);
            $temp['account_name'] = $account['account_name'] ?? '-';
            $temp['code'] = $account['code'] ?? '-';
            $temp['site'] = $account['site'] ?? '-';

            $temp['download_health'] = '-';
            $temp['system_status'] = 0;
            if (!empty($account)) {
                $temp['download_health'] = ($account['download_health'] == 0) ? '未启用' : ($account['download_health'] / 60). '小时';
                $temp['system_status'] = (int)$account['status'];
            }
            $goal_ids[] = $val['goal_id'];
            $newlist[] = $temp;
        }

        //目标；
        $goal = $this->getGoal($goal_ids);
        foreach ($newlist as &$temp) {
            $temp['goal'] = $goal[$temp['goal_id']];
            $temp['amazon_account_id'] = intval($temp['amazon_account_id']);
            $temp['health_status'] = intval($temp['health_status']);
            $this->getTempData($temp);
        }

        $returnData['data'] = $newlist;

        return $returnData;
    }

    public function getTempData(&$temp){

        $temp['goal_id'] = intval($temp['goal_id']);
        $temp['order_defect_rate_buyer'] = bcdiv($temp['order_defect_rate_buyer'], 100, 2);
        $temp['order_defect_rate_channel'] = bcdiv($temp['order_defect_rate_channel'], 100, 2);
        $temp['is_hint'] = $temp['hint_msg'] ? '有' : '无';
        $temp['hint_msg'] = $temp['hint_msg'];
        $temp['latest_payment'] = floatval($temp['latest_payment']);
        $temp['balance'] = floatval($temp['balance']);
        $temp['transfer_amount_a'] = floatval($temp['transfer_amount_a']);
        $temp['transfer_amount_a_time'] = date('Y-m-d H:i:s', $temp['transfer_amount_a_time']);
        $temp['transfer_amount_b'] = floatval($temp['transfer_amount_b']);
        $temp['transfer_amount_b_time'] = date('Y-m-d H:i:s', $temp['transfer_amount_b_time']);
        $temp['create_time'] = date('Y-m-d H:i:s', $temp['create_time']);
    }

    /**
     * 导出
     * @param $params
     * @return \think\response\Json
     */
    public function export($params)
    {
        $where = $this->getCondition($params);
        $listModel = new AmazonAccountHealthList();
        $lists = $listModel->where($where)
            ->order(['error_num' => 'DESC', 'update_time' => 'DESC'])
            ->limit(2000)
            ->select();
        $cache = Cache::store('AmazonAccount');
        //帐号ID；
        $goal_ids = [];
        $newlist = [];
        $statusArr = ['资料不完整', '有效', '无效', '连不上服务器','请求成功'];

        foreach ($lists as $val) {
            $temp = $val->toArray();
            $account = $cache->getAccount($temp['amazon_account_id']);
            $temp['account_name'] = $account['account_name'] ?? '-';
            $temp['code'] = $account['code'] ?? '-';
            $temp['download_health'] = '-';
            $temp['system_status'] = '停用';
            if (!empty($account)) {
                $temp['download_health'] = ($account['download_health'] == 0) ? '未启用' : ($account['download_health'] / 60). '小时';
                $temp['system_status'] = $account['status']==1?'启用':'停用';;
            }
            $goal_ids[] = $val['goal_id'];
            $newlist[] = $temp;
        }
        $allhas = ['无','有'];
        $unit = $this->getUnit();
        //目标；
        //$goal = $this->getGoal($goal_ids);
        foreach ($newlist as &$temp) {
//            $temp['goal'] = $goal[$temp['goal_id']];
            $temp['amazon_account_id'] = intval($temp['amazon_account_id']);
            $temp['health_status'] = intval($temp['health_status']);
            $this->getTempData($temp);
            $temp['health_status_text'] = $statusArr[$temp['health_status']];
            foreach ($unit as $key=>$val) {
                if (isset($temp[$key])) {
                    $temp[$key] .= $val;
                }
            }
        }

        try {
            $header = [
                ['title' => 'Amazon帐号', 'key' => 'account_name', 'width' => 20],
                ['title' => '帐号简称', 'key' => 'code', 'width' => 10],
                ['title' => '订单缺陷率（卖方完成）' , 'key' => 'order_defect_rate_buyer', 'width' => 20],
                ['title' => '订单缺陷率（卖方完成）' , 'key' => 'order_defect_rate_channel', 'width' => 20],
                ['title' => '提示信息' , 'key' => 'hint_msg', 'width' => 20],
                ['title' => '最近付款金额' , 'key' => 'latest_payment', 'width' => 20],
                ['title' => '余额' , 'key' => 'balance', 'width' => 20],
                ['title' => '转账金额A' , 'key' => 'transfer_amount_a', 'width' => 20],
                ['title' => '转账金额A时间' , 'key' => 'transfer_amount_a_time', 'width' => 20],
                ['title' => '转账金额B' , 'key' => 'transfer_amount_b', 'width' => 20],
                ['title' => '转账金额B时间' , 'key' => 'transfer_amount_b_time', 'width' => 20],
                ['title' => '同步健康数据' , 'key' => 'download_health', 'width' => 20],
                ['title' => '系统状态' , 'key' => 'system_status', 'width' => 20],
                ['title' => '抓取时间', 'key' => 'create_time', 'width' => 20],
                ['title' => '登陆验证状态', 'key' => 'health_status_text', 'width' => 20],
            ];

            $name = 'amazon健康';

            if (!empty($where['amazon_account_id'])) {
                $account = $cache->getAccount($where['amazon_account_id']);
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
                        $name .= '(|'. date('Y-m-d', $where['update_time'][1]). ')';
                        break;
                    case 'between':
                        $name .= '('. date('Y-m-d', $where['update_time'][1][0]). '|'. date('Y-m-d', $where['update_time'][1][1]). ')';
                }
            }

            $file = [
                'name' => $name,
                'path' => 'index',
                'title' => 'amazon健康数据'
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
     * @param $amazon_account_id
     * @param $params
     * @return array
     */
    public function gethistory($amazon_account_id, $params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;

        $account = Cache::store('AmazonAccount')->getAccount($amazon_account_id);

        if (empty($account)) {
            throw new Exception('amazon帐号ID不存在');
        }
        $model = new AmazonAccountHealth();
        $count = $model->where(['account_id' => $amazon_account_id])->count();
        $returnData = [
            'account_id' => $amazon_account_id,
            'account_name' => $account['account_name'],
            'code' => $account['code'],
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'unit' => $this->getUnit(),
            'data' => [],
        ];
        $list = $model->where(['account_id' => $amazon_account_id])
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
            $this->getTempData($temp);
            $newList[] = $temp;
        }
        $returnData['data'] = $newList;
        return $returnData;
    }




    public function getGoal($goal_ids)
    {
        $base = [
            'order_rate' => '1',
            'order_channel_rate' => '1',
            'balance_amount' => '2000',
        ];

        //公用的；
        $goal = [];//Cache::store('AmazonAccount')->getHealthGoal();
        if (empty($goal)) {
            $goal = [];
        }

        //公用数据；
        $commongoal['order_rate'] = $goal['order_rate']?? $base['order_rate'];
        $commongoal['order_channel_rate'] = $goal['order_channel_rate']?? $base['order_channel_rate'];
        $commongoal['balance_amount'] = $goal['balance_amount']?? $base['balance_amount'];

        $goaldata = AmazonAccountHealthGoal::where(['id' => ['in', $goal_ids]])->column('*', 'id');

        $userGoalList = [];
        foreach ($goal_ids as $id) {
            if (!empty($goaldata[$id])) {
                $temp = $goaldata[$id];

                $temp['order_rate'] = bcdiv($temp['order_rate'], 100, 2);
                $temp['order_channel_rate'] = bcdiv($temp['order_channel_rate'], 100, 2);
                $temp['balance_amount'] = bcdiv($temp['balance_amount'], 100, 2);

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
        //amazon帐号集合
        $idArr = explode(',', $params['amazon_account_ids']);
        unset($params['amazon_account_ids']);
        $time = time();
        $model = new AmazonAccountHealthGoal();
        $goals = $model->where(['amazon_account_id' => ['in', $idArr]])->column('id', 'amazon_account_id');

        $this->getParamsData($params);

        $listModel = new AmazonAccountHealthList();
        foreach ($idArr as $amazon_account_id) {
            $amazon_account_id = trim($amazon_account_id);
            $tmp = $params;
            $tmp['create_time'] = $time;
            $tmp['amazon_account_id'] = $amazon_account_id;
            //插入新的goal数据；
            $goal_id = $model->insertGetId($tmp);
            //把当前的list表里面的数据改为当前这条；
            $listModel->update(['goal_id' => $goal_id], ['amazon_account_id' => $amazon_account_id]);

        }

        return true;
    }

    /**
     * 单帐号设置监控值
     * @param $params
     */
    public function setAccountGoal($params)
    {
        $model = new AmazonAccountHealthGoal();
        $goal = $model->where(['amazon_account_id' => $params['amazon_account_id']])->find();

        $this->getParamsData($params);

        //只新增不设置；
        $params['create_time'] = time();
        $last_goal_id = $model->insert($params);
        AmazonAccountHealthList::update(['goal_id' => $last_goal_id], ['amazon_account_id' => $params['amazon_account_id']]);

        return true;
    }

    /**
     * 读取最后一条监控值
     * @param $params
     */
    public function readGoal($amazon_account_id)
    {
        $account = Cache::store('AmazonAccount')->getAccount($amazon_account_id);
        if (empty($account)) {
            throw new Exception('amazon帐号ID不存在');
        }
        $goalModel = new AmazonAccountHealthGoal();
        $goal = $goalModel->where(['amazon_account_id' => $amazon_account_id])->order('create_time', 'DESC')->find();
        if (!$goal) {
            $goal = [
                'amazon_account_id' => $amazon_account_id,
                'order_rate' => 0,
                'order_channel_rate' => 0,
                'balance_amount' => 0,
                'create_time' => time()
            ];
            $goal['id'] = $goalModel->insertGetId($goal);
        } else {
            $goal['order_channel_rate'] = bcdiv($goal['order_channel_rate'], 100, 2);
            $goal['order_rate'] = bcdiv($goal['order_rate'], 100, 2);
        }

        return $goal;
    }

    //拿取单位
    private function getUnit()
    {
        return [
            'order_defect_rate_buyer' => '%',
            'order_defect_rate_channel' => '%',
            'latest_payment' => ' USD',
            'balance' => ' USD',
            'transfer_amount_a' => ' USD',
            'transfer_amount_b' => ' USD',
        ];
    }

    public function getCondition($params)
    {
        $where = [];
        if (!empty($params['account_id'])) {
            $where['amazon_account_id'] = $params['account_id'];
        }
        if (isset($params['health_status']) && $params['health_status'] != '') {
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
        try{
            $map = (new AccountUserMapService())->getAccountInfo($code,$channel_id);
        }catch(Exception $e){
            return false;
        }
        $data = (new Account())->where('id',$map['id'])->find();
        $data = $data->toArray();
        $data['ip'] = (new Server())->where('id',$data['server_id'])->value('ip');

        if (
            empty($data) ||
            empty($data['account_name']) ||
            empty($data['account_code']) ||
            empty($data['site_code']) ||
            empty($data['password']) ||
            empty($data['ip'])
        ) {
            return false;
        }
        return $data;
    }

    /**
     * 生成访问的URL；
     * @param $ip
     * @return string
     */
    private function buildUrl($ip)
    {
        $url = 'http://' . $ip . ':10088/start_reptile/Amazon';
        return $url;
    }

    /**
     * 组成时post,urlencode编码数组；
     * @param $data
     * @param $account_id
     * @return string
     */
    private function buildPostData($data, $account_id)
    {
        //Amazon:[ {↵       "account": "ludasn@outlook.com",↵        "abbreviation": "简称",↵        "password": "hhje2$2332@3",↵        "site": "UK",↵"accountid":"accountid"↵    }]

        $postAccount = [];
        //测试的时候，发送这个数据；
        if (!$this->pattern) {
            $postAccount = [
                'account' => 'ludasn@outlook.com',
                'abbreviation' => 'huananzheng',
                'password' => 'hhje2$2332@3',
                'site' => 'UK',
                'accountId' => $account_id,
            ];
        }else{
            $postAccount = [
                'account' => $data['account_name'],
                'abbreviation' => $data['account_code'],
                'password' => (new Encryption())->decrypt($data['password']),
                'site' => $data['site_code'],
                'accountId' => $account_id,
            ];
        }




        $post['Amazon'] = json_encode([$postAccount]);
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
    public function sendAccount2Spider($id, $channel_id = ChannelAccountConst::channel_amazon)
    {
        $time = time();
        $listModel = new AmazonAccountHealthList();
        $cache = Cache::store('AmazonAccount');
        $account = $cache->getAccount($id);

        if (empty($account)) {
            throw new Exception('Amazon帐号ID不存在');
        }

        $data = $this->getAccountData($channel_id, $account['code']);

        //拿目录率，如果这是新增的帐号，不存在目录率，则会自动增加一级数据；
        $goal = $this->readGoal($id);

        //查看有无列表记录，无则加，有则改；
        $listId = $listModel->where(['amazon_account_id' => $id])->value('id');
        if ($listId) {
            $listModel->update([
                'goal_id' => $goal['id'],
                'update_time' => $time,
                'repitle_status' => 0,
                'health_status' => 1,
            ], ['id' => $listId]);
        } else {
            $listId = $listModel->insertGetId([
                'amazon_account_id' => $id,
                'goal_id' => $goal['id'],
                'update_time' => $time,
                'create_time' => $time,
                'repitle_status' => 0,
                'health_status' => 1,
            ]);
        }

        //如果信息不全，返回false;
//        if ($data === false && $this->pattern) {
//            //更新表数据；
//            $listModel->update(['repitle_status' => 1,'health_status' => 0], ['id' => $listId]);
//            return false;
//        }


        //如果是测试环境
        if (!$this->pattern) {
            $data['ip'] = $this->test_send_ip;
        }


        if ($this->pattern == 0) {
            $base_url = $this->test_url;
        } else {
            $base_url = $this->callback_url;
        }
        $account['callbackurl'] = $base_url . $this->route_url . $account['id'];
        $send = new AccountHealthService();
        $result = $send->sendAccount2Spider($account, ChannelAccountConst::channel_amazon);

        if (isset($result['status'])) {
            if ($result['status'] == 1 && isset($result['message']) && $result['message'] == 'success') {
                $listModel->update(['repitle_status' => 0,'health_status' => 4,'update_time' => time()], ['id' => $listId]);
                $cache->setAmazonLastDownloadHealthTime($account['id'], time());
                return true;
            } else {
                $listModel->update(['repitle_status' => 1,'health_status' => 2], ['id' => $listId]);
                return false;
            }
        }else{
            //运行到这里，可以确认网络连接出现错误；
            $listModel->update(['repitle_status' => 1,'health_status' => 3], ['id' => $listId]);
        }
        return false;
    }

    /**
     * 处理接收的健康数据；
     * @param $data
     */
    public function saveHealthData($reData)
    {

        if (isset($reData['status']) && $reData['status'] == 'false') {
            throw new Exception('保存Amazon帐号健康监控结果时，抓取出错：'. ($reData['message'] ?? ''));
        }
        //找出数据；
        $healths = json_decode($reData['HealthData'], true);


        $id = $healths['AccountId'];
        $cache = Cache::store('AmazonAccount');
        $account = $cache->getAccount($id);

        if (empty($account)) {
            throw new Exception('保存Amazon帐号健康监控结果时，帐号ID: ' . $id . ' 不存在');
        }


        $data = $healths;
        if (empty($data) || !is_array($data)) {
            //未正常返回数据；
            AmazonAccountHealthList::update(['health_status' => 0, 'repitle_status' => 1], ['amazon_account_id' => $account['id']]);
            throw new Exception('保存Amazon帐号健康监控结果时，帐号ID: ' . $id . ' 返回结果JSON为空值或错误值');
        }

        if (isset($data['LoginStatus']) && $data['LoginStatus'] == 'false') {
            //帐号未授权
            AmazonAccountHealthList::update(['health_status' => 2, 'repitle_status' => 1], ['amazon_account_id' => $account['id']]);
            return;
        }

        //取得健康数据,并保存；
        $this->_saveHealthData($id, $data);

        //经以上检测帐号授权完整
        $updata = ['health_status' => 1, 'repitle_status' => 1];
        AmazonAccountHealthList::update($updata, ['amazon_account_id' => $account['id']]);
    }

    public function getParamsData(&$params){
        //param内的目标值转换
        $params['order_channel_rate'] = $params['order_rate'] = $this->rateToInt($params['order_rate']);//订单缺陷率（卖方完成）
//        $params['order_channel_rate'] = $this->rateToInt($params['order_channel_rate']);//订单缺陷率（平台完成）
        $params['balance_amount'] = $this->moneyToDecimal($params['balance_amount']);//目标确认余额；
    }

    public function getHealthData(&$data,$health){
        $data['order_defect_rate_buyer'] = $this->rateToInt($health['Odrsc']);//订单缺陷率（卖方完成）
        $data['order_defect_rate_channel'] = $this->rateToInt($health['Odrac']);//订单缺陷率（卖方完成）
        $data['hint_msg'] = $this->moneyToDecimal($health['HintInformation']);//提示信息
        $data['latest_payment'] = $this->moneyToDecimal($health['Lpa']);//最近付款金额
        $data['balance'] = $this->moneyToDecimal($health['Balance']);//余额
        $data['transfer_amount_a'] = $this->moneyToDecimal($health['TransferAmountA']);//转账金额A
        $data['transfer_amount_b'] = $this->moneyToDecimal($health['TransferAmountB']);//转账金额B
        $data['transfer_amount_a_time'] = $this->dateToTimetemp($health['TransferAmountAD']);//转账金额A之付款日期
        $data['transfer_amount_b_time'] = $this->dateToTimetemp($health['TransferAmountBD']);//转账金额B之付款日期
        $data['currency'] = $health['Currency'] ?? '' ;//货币符号
        $data['currencyy'] = $health['Currencyy'] ?? '' ;//货币符号
    }

    private function dateToTimetemp($since){
        if(!$since){
            return 0;
        }
        return is_numeric($since) ? $since : strtotime($since);
    }

    private function _saveHealthData($account_id, $health)
    {
        $time = time();
        $listModel = new AmazonAccountHealthList();
        $healthModel = new AmazonAccountHealth();

        //找出最后一条目标ID,如果没有，就加一条；
        $goal = $this->readGoal($account_id);

        $data['account_id'] = $account_id;
        $data['goal_id'] = intval($goal['id']);
        $this->getHealthData($data,$health);
        $data['create_time'] = $time;
        $error_num = 0;

        //记录超出目标了几个；
        if ($data['order_defect_rate_buyer'] < ($goal['order_rate'] * 100)) {
            $error_num++;
        }
        if ($data['order_defect_rate_channel'] > ($goal['order_channel_rate'] * 100)) {
            $error_num++;
        }
        if ($data['balance'] > $goal['balance_amount']) {
            $error_num++;
        }


        //存储记录；
        $health_id = $healthModel->insertGetId($data);
        //更新amazon帐号与记录一对一列表；
        $data['error_num'] = $error_num;
        $data['health_status'] = 1;     //帐号有效；
        $data['repitle_status'] = 1;    //抓取完成；
        $data['update_time'] = $data['create_time'];
        $data['amazon_account_id'] = $data['account_id'];
        unset( $data['account_id']);
        if ($listModel->where(['amazon_account_id' => $account_id])->count()) {
            unset($data['create_time']);
            $listModel->update($data, ['amazon_account_id' => $account_id]);
        } else {
            $listModel->insert($data);
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
     * 开通amazonHealth时，新增加一条数据；
     * @param $account_id
     */
    public function openAmazonHealth($account_id, $down_health)
    {
        $time = time();
        $goal = $this->readGoal($account_id);

        //大于0，则开启，否则关闭；
        $status = $down_health > 0 ? 1 : 0;

        $listModel = new AmazonAccountHealthList();
        $data = $listModel->where(['amazon_account_id' => $account_id])->find();
        if (empty($data)) {
            $listModel->insert([
                'amazon_account_id' => $account_id,
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

    private function getDayWhere($times, $params = [])
    {
        $where = [
            'create_time' => ['between',[strtotime($times." 0:0:0"), strtotime($times." 23:59:59")]],
        ];
        return $where;
    }

    /**
     * 统计某天的数据
     * @param $times
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function balances($times)
    {
        $reData = [
            'accounts' => 0,
            'balanceSum' => 0,
            'transferSum' => 0,
        ];
        $where = $this->getDayWhere($times);
        $field = 'account_id,balance,latest_payment,currency,currencyy';
        $list = (new AmazonAccountHealth())->field($field)->where($where)->select();

        $oldData = [];
        $res = Cache::store('currency')->getCurrency();
        if($list){
            $allAccount = [];
            foreach ($list as $one){
                $one['balance'] = $this->conversionMoney($one['balance'], $one['currency'], $res);
                $one['latest_payment'] = $this->conversionMoney($one['latest_payment'], $one['currencyy'], $res);
                if(in_array($one['account_id'],$allAccount)){
                    $reData['balanceSum'] += $one['balance'] - $oldData[$one['account_id']]['balance'];
                    $reData['transferSum'] += $one['latest_payment'] - $oldData[$one['account_id']]['latest_payment'];
                }else{
                    $allAccount[] = $one['account_id'];
                    $reData['balanceSum'] += $one['balance'];
                    $reData['transferSum'] += $one['latest_payment'];
                    $oldData[$one['account_id']] = [
                        'balance' => $one['balance'],
                        'latest_payment' => $one['latest_payment'],
                    ];
                }
            }
            $reData['accounts'] = count($allAccount);
            $formate_num = 2;
            $requestModel = request()->param("requestModel",'');
            if($requestModel && $requestModel == 'DINGTALK')
            {
                $formate_num = 0;
            }
            $reData['balanceSum'] = number_format($reData['balanceSum'],$formate_num);
            $reData['transferSum'] = number_format($reData['transferSum'],$formate_num);
        }
        return $reData;
    }

    /**
     * 某一天的详情
     * @param $times
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function balanceDetails($times, $page = 1, $pageSize = 20)
    {
        $reData = [
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => 0,
            'data' => [],
        ];
        $where = $this->getDayWhere($times);
        $field = 'account_id,site,balance,transfer_amount_a_time,latest_payment,currency,currencyy';
        $count = (new AmazonAccountHealth())->field($field)->where($where) ->group('account_id')->count();
        if(!$count){
            return $reData;
        }
        $reData['count'] = $count;
        $list = (new AmazonAccountHealth())->field($field)->where($where)
            ->group('account_id')->page($page,$pageSize)->select();
        $res = Cache::store('currency')->getCurrency();
        $formate_num = 2;
        $requestModel = request()->param("requestModel",'');
        if($requestModel && $requestModel == 'DINGTALK')
        {
            $formate_num = 0;
        }
        foreach ($list as $v){
            $one = $v->toArray();
            $one['balance_CNY'] = $this->conversionMoney($one['balance'], $one['currency'], $res);
            $one['latest_payment_CNY'] = $this->conversionMoney($one['latest_payment'], $one['currencyy'], $res);
            $allAccount = Cache::store('AmazonAccount')->getTableRecord($one['account_id']);
            $one['account_name'] = $allAccount['code'] ?? '';
            $one['site'] = $allAccount['site'] ?? '';
            $one['balance'] = number_format($one['balance'],$formate_num);
            $one['balance_CNY'] = number_format($one['balance_CNY'],$formate_num);
            $one['latest_payment_CNY'] = number_format($one['latest_payment_CNY'],$formate_num);
            $one['latest_payment'] = number_format($one['latest_payment'],$formate_num);
            $reData['data'][] = $one;
        }
        return $reData;
    }

    private function conversionMoney($money, $code, $res = [])
    {
        if(!$code || !$money){
            return 0;
        }
        $money *= $res[$code]['system_rate']/$res['CNY']['system_rate'];
        return $money;
    }

}