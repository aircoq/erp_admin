<?php

namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\Channel;
use app\common\model\VirtualOrderApply;
use app\common\model\VirtualOrderMission;
use app\common\model\VirtualOrderMissionLog;
use app\common\model\VirtualOrderUser;
use app\common\model\VirtualOrderUserPlatform;
use app\common\model\walmart\WalmartAccount;
use app\common\cache\Cache;
use app\order\service\VirtualOrderHelp;
use think\Db;
use think\Exception;
use think\Model;
use think\Request;
use walmart\WalmartAccountApi;

/**
 * Created by PhpStorm.
 * User: libaimin
 * Date: 2018/6/7
 * Time: 11:43
 */
class VirtualUserService
{
    protected $virtualOrderUserModel = null;
    protected $virtualOrderMissionModel = null;
    protected $virtualOrderUserPlatformModel = null;
    protected $virtualOrderHelp = null;
    protected $error = '';

    protected $cnMessage = [
        'succeed' => '处理成功',
        'failed' => '处理失败',
        'notTask' => '任务不存在',
        'TaskNotDone' => '任务未处理，请先处理订单',
        'TaskNotExecuted' => '任务状态不对',
        'passwordError' => '密码错误！无法修改',
        'contactEmail' => '联系邮箱为必须！',
        'contactCountry' => '国家为必须！',
        'contactCity' => '城市为必须！',
        'contactPlatform' => '接收任务平台为必须！',
        'succeedRegister' => '注册成功！',
    ];
    protected $usMessage = [
        'succeed' => 'succeed',
        'failed' => 'failed',
        'notTask' => 'Task does not exist',
        'TaskNotDone' => 'If the task is not processed, please process the order first',
        'TaskNotExecuted' => 'Task status error',
        'passwordError' => "Password error! Can't modify",
        'contactEmail' => "Contact email as necessary!",
        'contactCountry' => "Contact Country as necessary!",
        'contactCity' => "Contact City as necessary!",
        'contactPlatform' => "Contact Receiving Task Platform as necessary!",
        'succeedRegister' => "Registration Successful!",
    ];


    public function __construct()
    {
        if (is_null($this->virtualOrderUserModel)) {
            $this->virtualOrderUserModel = new VirtualOrderUser();
        }

        if (is_null($this->virtualOrderMissionModel)) {
            $this->virtualOrderMissionModel = new VirtualOrderMission();
        }

        if (is_null($this->virtualOrderUserPlatformModel)) {
            $this->virtualOrderUserPlatformModel = new VirtualOrderUserPlatform();
        }

        if (is_null($this->virtualOrderHelp)) {
            $this->virtualOrderHelp = new VirtualOrderHelp();
        }
    }

    /**
     * 得到错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 国外刷手列表
     * @param array $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function userList($params = [], $page = 1, $pageSize = 10)
    {

        $where = $this->getWhere($params);
        $field = 'id,realname,username,email,country,city,create_time,status';

        $sort = "create_time desc";
        //排序刷选
        if (param($params, 'sort_type') && in_array($params['sort_type'], ['account_name', 'code', 'created_at'])) {
            $sort_by = $params['sort_val'] == 2 ? 'DESC' : ' ';
            $sort = $params['sort_type'] . " " . $sort_by . " ,create_time desc";
            unset($sort_by);
        }

        $count = $this->virtualOrderUserModel->where($where)->count();
        $accountList = $this->virtualOrderUserModel->field($field)->where($where)->order($sort)->page($page, $pageSize)->select();

        foreach ($accountList as &$item) {
            $item['platform'] = '';
            $platform = $this->virtualOrderUserPlatformModel->getMyChannelName($item['id']);
            foreach ($platform as $k => $v) {
                $item['platform'] .= $v['channelName'] . ',';
            }
            $item['platform'] = trim($item['platform'], ',');
        }
        $result = [
            'data' => $accountList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /**
     * @title 获取国家信息
     * @return array
     */
    public function getCountry()
    {
        $data = Cache::store('Country')->getCountry();
        $res = [];
        foreach ($data as $v) {
            $res[] = [
                'country_code' => $v['country_code'],
                'country_en_name' => $v['country_en_name'],
                'country_cn_name' => $v['country_cn_name'],
            ];
        }
        return $res;
    }

    /**
     * @title 获取平台信息
     * @return array
     */
    public function getChannel()
    {
        $model = new Channel();
        $filed = 'id,name,title';
        $where['id'] = ['<', 5];
        return $model->field($filed)->where($where)->select();
    }

    /**
     * @title 新增注册用户
     * @param $data
     * @return bool
     */
    public function add($data)
    {

        if ($this->virtualOrderUserModel->isHas(['username' => $data['username']])) {
            throw new JsonErrorException('用户名：' . $data['username'] . '已经注册，请重新填写', 400);
        }
        Db::startTrans();
        try {

            $time = time();
            $data['create_time'] = $time;
            $data['update_time'] = $time;
            $data['realname'] = $data['username'];
            $data['salt'] = $this->virtualOrderUserModel::getSalt();
            $data['password'] = $this->virtualOrderUserModel::getHashPassword($data['password'], $data['salt']);

            $data['register_ip'] = Request::instance()->ip();
            $this->virtualOrderUserModel->allowField(true)->isUpdate(false)->save($data);
            $virtualOrderUserId = $this->virtualOrderUserModel->id;
            $platform = json_decode($data['platform'], true);
            foreach ($platform as $v) {
                $v['create_time'] = $time;
                $v['virtual_order_user_id'] = $virtualOrderUserId;
                $this->virtualOrderUserPlatformModel->insert($v);
            }
            Db::commit();
            return $this->getOne($virtualOrderUserId);
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage());
        }
    }


    /**
     * @title 更新用户
     * @param $data
     * @return bool
     */
    public function Fupdate($data, $userId)
    {

        Db::startTrans();
        try {
            $time = time();
            $save_data['update_time'] = $time;
            $save_data['email'] = $data['email'];
            $save_data['country'] = $data['country'];
            $save_data['city'] = $data['city'];
            $save_data['refund_name'] = $data['refund_name'] ?? '';
            $save_data['refund_account'] = $data['refund_account'] ?? '';
            $save_data['refund_type'] = $data['refund_type'] ?? '';
            $save_data['refund_currency'] = $data['refund_currency'] ?? '';
            $this->virtualOrderUserModel->save($save_data, ['id' => $userId]);
            $platform = json_decode($data['platform'], true);
            $where['virtual_order_user_id'] = $userId;
            foreach ($platform as $v) {
                $where['channel_id'] = $v['channel_id'];
                $v['virtual_order_user_id'] = $userId;
                $this->virtualOrderUserPlatformModel->add($where, $v);
            }
            Db::commit();
            return $this->getOne($userId);
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage());
        }
    }

    /**
     * @title 更新用户状态
     * @param $status
     * @param $userId
     * @return bool
     */
    public function updateStatus($status, $userId)
    {
        $time = time();
        $save_data['update_time'] = $time;
        $save_data['status'] = $status == 1 ? 1 : 0;
        return $this->virtualOrderUserModel->save($save_data, ['id' => $userId]);
    }


    /**
     * @title 更新用户密码
     * @param $userId
     * @param $oldPwd
     * @param $newPwd
     * @param $param
     * @return bool
     */
    public function setUserPwd($userId, $newPwd, $oldPwd = '',$param = '')
    {
        $allMessage = $this->getAllMessage($param);
        $message = $allMessage['succeed'];
        if ($oldPwd) { // 判断旧密码是否正确
            $user = $this->virtualOrderUserModel->where('id', $userId)->find();
            if ($user['password'] != $this->virtualOrderUserModel::getHashPassword($oldPwd, $user['salt'])) {
                throw new JsonErrorException($allMessage['passwordError']);
            }
        }
        $data['update_time'] = time();
        $data['salt'] = $this->virtualOrderUserModel::getSalt();
        $data['password'] = $this->virtualOrderUserModel::getHashPassword($newPwd, $data['salt']);
        if(!$this->virtualOrderUserModel->save($data, ['id' => $userId])){
            $message = $allMessage['failed'];
        }
        return $message;
    }

    /**
     * 获取国外刷手信息
     * @param $id
     * @return array
     */
    public function getOne($id)
    {
        $field = 'id,username,email,country,city,refund_name,refund_account,refund_type,refund_currency';
        $user = $this->virtualOrderUserModel->where('id', $id)->field($field)->find();
        if ($user) {
            $user['platform'] = [];
            $field = 'channel_id,account_name,account_user,is_vip_prime';
            $platform = $this->virtualOrderUserPlatformModel->where('virtual_order_user_id', $user['id'])->field($field)->select();
            foreach ($platform as &$v) {
                $v['channel_name'] = Cache::store('Channel')->getChannelName($v['channel_id']);
            }
            $user['platform'] = $platform;
        }
        return $user;
    }


    /**
     * 封装where条件
     * @param array $params
     * @return array
     */
    public function getWhere($params = [])
    {
        $where = [];

        if (isset($params['username']) && $params['username'] != '') {
            $where['username'] = ['eq', $params['username']];
        }
        if (isset($params['email']) && $params['email'] != '') {
            $where['email'] = ['eq', $params['email']];
        }
        $param['date_type'] = 'create_time';
        //时间选择
        if (!empty($param['date_type'])) {
            if (!empty($param['date_start']) || !empty($param['date_end'])) {
                if (isset($param['date_start']) && $param['date_start']) {
                    $where[$param['date_type']] = ['>=', strtotime($param['date_start'])];
                }
                if (isset($param['date_end']) && $param['date_end']) {
                    $where[$param['date_type']] = ['<=', strtotime($param['date_end'] . " 23:59:59")];
                }
            }
        }

        return $where;
    }

    /**
     * 获取刷单任务列表
     * @param int $page
     * @param int $pageSize
     * @param array $param
     * @return array
     * @autor libaimin
     */
    public function getSingleTaskList($page = 1, $pageSize = 50, $param = []): array
    {
        $user = VirtualOrderUser::getUserInfo();
        $where['t.task_id'] = $user['user_id'];
        $param['type'] = 3;
        $statusP = $param['status'] ?? '';
        $statusR = $param['refund_status'] ?? '';
        if($statusR != -1){
            if($statusP == 4){
                $where = 't.task_id = '.$user['user_id'] . ' and t.status = 4 and ( t.msg_time = 0 or (t.msg_time != 0 and msg_true_time != 0) )';
            }elseif($statusP >= 3){

            }else{
                $where['t.status'] = ['>', VirtualOrderMission::status_distributed];
            }
        }


        $virtualOrderHelp = new VirtualOrderHelp();
        $message = $virtualOrderHelp->getSingleTaskList($page, $pageSize, $param, $where);
        if ($message['data']) {
            $message['data'] = $this->getData($message['data'],$param);
        }
        return $message;
    }

    /**
     * 获取刷单任务状态列表
     * @param array $param
     * @return array
     * @autor libaimin
     */
    public function getSingleTaskStatus($param = []): array
    {
        $param['type'] = 3;
        $txt = $this->getAllMisstionStatus($param);

        $user = VirtualOrderUser::getUserInfo();
        $whereAnd['t.task_id'] = $user['user_id'];
        $whereAnd['t.status'] = ['>=', VirtualOrderMission::status_executed];
        $virtualOrderHelp = new VirtualOrderHelp();
        $join = $virtualOrderHelp->getTaskJoin();
        $where = [];
        $virtualOrderHelp->getTaskWhere($param,$where);
        $result = $this->virtualOrderMissionModel->where($where)->where($whereAnd)->alias('t')->join($join)->field('t.status as status,count(*) as num')->group('t.status')->select();

        $ret = [];
        $total = 0;
        for ($i = 3; $i <= 5; $i++) {
            $row['val'] = $i;
            $row['txt'] = $txt[$i];
            $row['total'] = 0;
            $ret[$i] = $row;
        }

        foreach ($result as $v) {
            $row = [];
            $row['val'] = $v->status;
            $row['txt'] = $txt[$v->status];
            $row['total'] = $v->num;
            $total += $v->num;
            $ret[$v->status] = $row;
        }

        //统计没有留评的
        $whereAnd['msg_time'] = ['<>',0];
        $whereAnd['msg_true_time'] = 0;
        $whereAnd['t.status'] = $this->virtualOrderMissionModel::status_done;
        $count = $this->virtualOrderMissionModel->where($where)->where($whereAnd)->alias('t')->join($join)->count();

        array_push($ret, [
            'val' => 7,
            'txt' => $txt[7],
            'total' => $count
        ]);

        $ret[$this->virtualOrderMissionModel::status_done]['total'] -= $count;

        array_unshift($ret, [
            'val' => 0,
            'txt' => $txt[-1],
            'total' => $total
        ]);
        return $ret;
    }


    private function getAllMisstionStatus($param)
    {
        return (isset($param['language']) && $param['language'] == 'US') ? $this->virtualOrderMissionModel::STATUS_US : $this->virtualOrderMissionModel::STATUS;

    }

    public function getAllMessage($param)
    {
        return (isset($param['language']) && $param['language'] == 'US') ? $this->usMessage : $this->cnMessage;
    }


    public function getData($data,$param)
    {
        $res = [];

        $txt = $this->getAllMisstionStatus($param);

        foreach ($data as $k => $v) {
            $one = [
                'task_number' => $v['task_number'],
                'order_time' => $v['order_time'],
                'msg_time' => $v['msg_time'],
                'msg_true_time' => $v['msg_true_time'],
                'channel' => $v['channel'],
                'site' => $v['site'],
                'keyword' => $v['keyword'],
                'asin' => $v['asin'],
                'product_location' => $v['product_location'],
                'seller_cost' => $v['seller_cost'],
                'order_cost' => $v['order_cost'],
                'quantity' => $v['quantity'],
                'remark' => $v['remark'],
                'thumb' => $v['thumb'],
                'status' => $txt[$v['status']],
                'order_number' => $v['order_number'],
                'order_time' => $v['order_time'],
                'task_time' => $v['task_time'],
                'shipping_type' => $v['shipping_type'],
                'is_add_shopping_cart' => $v['is_add_shopping_cart'],
                'is_add_wishlist' => $v['is_add_wishlist'],
                'refund_status' => $v['refund_status'],
            ];
            if ($v['msg_time'] > 0 &&$v['status'] == VirtualOrderMission::status_done && $v['msg_true_time'] == 0) {
                $one['status'] = $txt[7];
            }
            $res[] = $one;
        }
        return $res;
    }

    /**
     * 刷单任务处理
     * @param string $taskNumber
     * @param array $param
     * @param int $userId
     * @return array
     * @autor libaimin
     */
    public function setDispose($taskNumber, $param, $userId)
    {
        $allMessage = $this->getAllMessage($param);
        $message = $allMessage['succeed'];

        $where = [
            'task_id' => $userId,
            'task_number' => $taskNumber,
        ];
        $task = $this->virtualOrderMissionModel->where($where)->find();
        if (!$task) {
            throw new JsonErrorException($allMessage['notTask'], 400);
        }
        if ($task['status'] != VirtualOrderMission::status_executed) {
            throw new JsonErrorException($allMessage['TaskNotExecuted'], 400);
        }

        $id = $task['id'];
        $save = [
            'order_number' => $param['order_number'],
            'order_cost' => $param['order_cost'],
            'task_currency' => $param['task_currency'],
        ];
        if (!$this->virtualOrderHelp->updateTaskOK($id, $save)) {
            $message = $allMessage['failed'];
        } else {
            VirtualOrderMissionLog::addLog($id, VirtualOrderMissionLog::update, $save, '国外买手回填订单号' . $param['order_number'], true);
        }
        return $message;
    }

    /**
     * 刷单任务留评
     * @param string $taskNumber
     * @param int $userId
     * @return array
     * @autor libaimin
     */
    public function setReview($taskNumber, $userId,$param = '')
    {
        $allMessage = $this->getAllMessage($param);
        $message = $allMessage['succeed'];

        $where = [
            'task_id' => $userId,
            'task_number' => $taskNumber,
        ];
        $task = $this->virtualOrderMissionModel->where($where)->find();
        if (!$task) {
            throw new JsonErrorException($allMessage['notTask'], 400);
        }
        if ($task['status'] != VirtualOrderMission::status_done) {
            throw new JsonErrorException($allMessage['TaskNotDone'], 400);
        }
        $id = $task['id'];
        if (!$this->virtualOrderHelp->updateTaskMsg($id)) {
            $message = $allMessage['failed'];
        } else {
            VirtualOrderMissionLog::addLog($id, VirtualOrderMissionLog::update, time(), '国外买手回评', true);
        }
        return $message;
    }

    /**
     * 关于我们
     * @param $language
     */
    public function aboutUs($language)
    {
        $reData = [
            'language' => $language,
        ];
        switch ($language){
            case 'CN': //中国
                $reData['user_notice'] = '1，按关键词搜索进去，货比三家，找到相对应的店铺浏览几分钟，再添加购物车和心愿单，最后再下单。
2，接到任务后，必须3天内完成。未在规定时间内完成任务，将会取消任务。
3，任务完成后，1周内安排返款。返款账号会默认注册时填写的返款账号。';
                $reData['contact_us'] = '请联系邮箱：thisejkqu@hotmail.com';
                break;
            case 'US': //美国
                $reData['user_notice'] = '1. Search in by keywords, compare three stores, find the corresponding stores to browse for a few minutes, 
add shopping carts and wishlist, and then order.
2. After receiving the task, you must be completed within 3 days. Otherwise, we will cancel this task.
3. After the completion of the task, we will arrange the refund within 1 weeks.Refund account is you registered.';
                $reData['contact_us'] = 'Please contact email:thisejkqu@hotmail.com';
                break;
        }
        return $reData;
    }

}