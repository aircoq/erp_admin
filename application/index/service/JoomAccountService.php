<?php
namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\joom\JoomAccount;
use app\common\model\joom\JoomShop as JoomShopModel;
use app\common\cache\Cache;
use think\Exception;
use think\Request;
use think\Db;
use app\common\service\ChannelAccountConst;
use app\common\model\ChannelAccountLog;
use app\index\service\AccountService;
use app\common\service\Common as CommonService;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/4/25
 * Time: 11:17
 */
class JoomAccountService
{
    protected $joomAccountModel;

    /**
     * 日志配置
     * 字段名称[name] 
     * 格式化类型[type]:空 不需要格式化,time 转换为时间,list
     * 格式化值[value]:array
     */
    public static $log_config = [
        'sales_company_id' => ['name'=>'运营公司','type'=>null],
        'account_name' => ['name'=>'账号名','type'=>null],
        'code' => ['name'=>'账号简称','type'=>null],
        'company' => ['name'=>'账号注册公司','type'=>null],
        'is_invalid' => [
            'name'=>'系统状态',
            'type'=>'list',
            'value'=>[
                0 =>'未启用',
                1 =>'启用' ,
            ],
        ],
        'platform_status' => [
            'name'=>'平台状态',
            'type'=>'list',
            'value'=>[
                0 =>'停用',
                1 =>'启用' ,
            ],
        ],
        'site_status' => [
            'name'=>'账号状态',
            'type'=>'list',
            'value'=>[
                0 => '未分配',
                1 => '运营中',
                2 => '回收中',
                3 => '冻结中',
                4 => '申诉中',
            ]
        ],
    ];


    public function __construct()
    {
        if (is_null($this->joomAccountModel)) {
            $this->joomAccountModel = new JoomAccount();
        }
    }

    /**
     * 账号列表
     * lingjiawen
     */
    public function getList(array $req)
    {
        /**
         * 初始化参数
         */
        $operator = ['eq' => '=', 'gt' => '>', 'lt' => '<'];
        $page = isset($req['page']) ? intval($req['page']) : 1;
        $pageSize = isset($req['pageSize']) ? intval($req['pageSize']) : 50;
        $time_type = isset($req['time_type']) && in_array($req['time_type'],['register','fulfill']) ? $req['time_type'] : '';
        $start_time = isset($req['start_time']) ? strtotime($req['start_time']) : 0;
        $end_time = isset($req['end_time']) ? strtotime($req['end_time']) : 0;
        $site = $req['site'] ?? '';
        $status = isset($req['status']) && is_numeric($req['status']) ? intval($req['status']) : -1;
        $site_status = isset($req['site_status']) && is_numeric($req['site_status']) ? intval($req['site_status']) : -1;
        $seller_id = isset($req['seller_id']) ? intval($req['seller_id']) : 0;
        $customer_id = isset($req['customer_id']) ? intval($req['customer_id']) : 0;
        $platform_status = isset($req['platform_status']) && is_numeric($req['platform_status']) ? intval($req['platform_status']) : -1;
        $is_invalid = isset($req['is_invalid']) && is_numeric($req['is_invalid']) ? intval($req['is_invalid']) : -1;
        $snType = !empty($req['snType']) && in_array($req['snType'], ['account_name', 'code']) ? $req['snType'] : '';
        $snText = !empty($req['snText']) ? $req['snText'] : '';
        $taskName = !empty($req['taskName']) && in_array($req['taskName'], ['download_listing', 'download_order', 'sync_delivery', 'download_health']) ? $req['taskName'] : '';
        $taskCondition = !empty($req['taskCondition']) && isset($operator[trim($req['taskCondition'])]) ? $operator[trim($req['taskCondition'])] : '';
        $taskTime = isset($req['taskTime']) && is_numeric($req['taskTime']) ? intval($req['taskTime']) : '';
        //排序
        $sort_type = !empty($req['sort_type']) && in_array($req['sort_type'], ['account_name', 'code']) ? $req['sort_type'] : '';
        $sort = !empty($req['sort_val']) && $req['sort_val'] == 2 ? 'desc' : 'asc';
        $order_by = 'am.id DESC';
        $sort_type && $order_by = "am.{$sort_type} {$sort},{$order_by}";

        /**
         * 参数处理
         */
        if ($time_type && $end_time && $start_time > $end_time) {
            return [
                'count' => 0,
                'data' => [],
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        }
        !$page and $page = 1;
        if ($page > $pageSize) {
            $pageSize = $page;
        }

        /**
         * where数组条件
         */
        $where = [];
        $seller_id and $where['c.seller_id'] = $seller_id;
        $customer_id and $where['c.customer_id'] = $customer_id;
        $is_invalid >= 0 and $where['am.is_invalid'] = $is_invalid;
        $platform_status >= 0 and $where['am.platform_status'] = $platform_status;
        // $site and $where['am.site'] = $site;
        // $status >= 0 and $where['am.status'] = $status;
        $site_status >= 0 and $where['s.site_status'] = $site_status;

        if ($taskName && $taskCondition && !is_string($taskTime)) {
            $where['am.' . $taskName] = [$taskCondition, $taskTime];
        }

        if ($snType && $snText) {
            $where['am.' . $snType] = ['like', '%' . $snText . '%'];
        }

        /**
         * 需要按时间查询时处理
         */
        if ($time_type) {
            /**
             * 处理需要查询的时间类型
             */
            switch ($time_type) {
                case 'register':
                    $time_type = 'a.account_create_time';
                    break;
                case 'fulfill':
                    $time_type = 'a.fulfill_time';
                    break;

                default:
                    $start_time = 0;
                    $end_time = 0;
                    break;
            }
            /**
             * 设置条件
             */
            if ($start_time && $end_time) {
                $where[$time_type] = ['between time', [$start_time, $end_time]];
            } else {
                if ($start_time) {
                    $where[$time_type] = ['>', $start_time];
                }
                if ($end_time) {
                    $where[$time_type] = ['<', $end_time];
                }
            }
        }

        $count = $this->joomAccountModel
            ->alias('am')
            ->where($where)
            ->join('__ACCOUNT__ a', 'a.id=am.base_account_id', 'LEFT')
            ->join('__CHANNEL_USER_ACCOUNT_MAP__ c', 'c.account_id=am.id AND c.channel_id=a.channel_id', 'LEFT')
            ->join('__ACCOUNT_SITE__ s', 's.base_account_id=am.base_account_id AND s.account_code=am.code', 'LEFT')
            ->count();

        //没有数据就返回
        if (!$count) {
            return [
                'count' => 0,
                'data' => [],
                'page' => $page,
                'pageSize' => $pageSize,
            ];
        }

        $field = 'am.id,am.account_name,am.code,am.company,am.is_invalid,am.platform_status,am.create_time,am.update_time,s.site_status,c.seller_id,c.customer_id,a.account_create_time register_time,a.fulfill_time';
        //有数据就取出
        $list = $this->joomAccountModel
            ->alias('am')
            ->field($field)
            ->join('__ACCOUNT__ a', 'a.id=am.base_account_id', 'LEFT')
            ->join('__CHANNEL_USER_ACCOUNT_MAP__ c', 'c.account_id=am.id AND c.channel_id=a.channel_id', 'LEFT')
            ->join('__ACCOUNT_SITE__ s', 's.base_account_id=am.base_account_id AND s.account_code=am.code', 'LEFT')
            ->where($where)
            ->page($page, $pageSize)
            ->order($order_by)
            ->select();

        $counts = $this->accountCounts();
        $site_status_info = new \app\index\service\BasicAccountService();
        foreach ($list as &$val) {
            $seller =  Cache::store('User')->getOneUser($val['seller_id']);
            $val['seller_name'] = $seller ? $seller['realname'] : '';
            $val['seller_on_job'] = $seller ? $seller['on_job'] : '';
            $customer = Cache::store('User')->getOneUser($val['customer_id']);
            $val['customer_name'] = $customer ? $customer['realname'] : '';
            $val['customer_on_job'] = $customer ? $customer['on_job'] : '';
            $val['site_status_str'] = $site_status_info->accountStatusName($val['site_status']);

            $val['total'] = $counts[$val['id']]?? 0;
        }

        return [
            'count' => $count,
            'data' => $list,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * 获取各账户的店辅数量
     * @return array
     */
    public function accountCounts()
    {
        $shopM = new JoomShopModel();
        $list = $shopM->field('joom_account_id,count(id) as total')->group('joom_account_id')->select();
        if(empty($list)) {
            return [];
        }

        $counts = [];
        foreach($list as $val) {
            $counts[$val['joom_account_id']] = $val['total'];
        }
        return $counts;
    }

    /** 保存账号信息
     * @param $data
     * @return array
     */
    public function save($data)
    {
        $ret = [
            'msg' => '',
            'code' => ''
        ];
        $time = time();
        $data['account_name'] = $data['name'];
        unset($data['name']);
        $data['create_time'] = $time;
        $data['update_time'] = $time;
        $data['is_invalid'] = $data['platform_status'] = 1;  //设置为启用
        $res = $this->joomAccountModel->where('code', $data['code'])->field('id')->find();
        if (count($res)) {
            $ret['msg'] = '账户名重复';
            $ret['code'] = 400;
            return $ret;
        }

        $user = CommonService::getUserInfo(Request::instance());
        $operator = [];
        $operator['operator_id'] = $user['user_id'] ?? 0;
        $operator['operator'] = $user['realname'] ?? '';

        Db::startTrans();
        try {
            $old_data = [];
            $new_data = $data;
            $this->joomAccountModel->allowField(true)->isUpdate(false)->save($data);
            //获取最新的数据返回
            $new_id = $this->joomAccountModel->id;
            //新增缓存
            Cache::store('JoomAccount')->setTableRecord($new_id);
            Db::commit();

            $operator['account_id'] = $new_id;
            //插入日志
            self::addLog(
                $operator,
                ChannelAccountLog::INSERT,
                $new_data,
                $old_data
            );

        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
        // $accountInfo = $this->joomAccountModel->field(true)->where(['id' => $new_id])->find();
        $new_data = [];
        $new_data['id'] = $new_id;
        $new_data['name'] = param($data,'account_name', '');
        $new_data['code'] = param($data,'code', '');
        $new_data['company'] = param($data,'company', '');;
        $new_data['status'] = param($data,'is_invalid', '');
        $new_data['platform_status'] = param($data,'platform_status', '');
        $new_data['create_time'] = param($data,'create_time', '');
        $new_data['update_time'] = param($data,'update_time', '');
        return $new_data;
    }

    /** 账号信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function read($id)
    {
        $accountInfo = Cache::store('JoomAccount')->getTableRecord($id);
        $accountInfo['name'] = $accountInfo['account_name'];
        if(empty($accountInfo)){
            throw new JsonErrorException('账号不存在',500);
        }
        $accountInfo['site_status'] = AccountService::getSiteStatus($accountInfo['base_account_id'], $accountInfo['code']);
        return $accountInfo;
    }

    /** 更新
     * @param $id
     * @param $data
     * @return \think\response\Json
     */
    public function update($id, $data)
    {
        Db::startTrans();
        try {
            if (isset($data['name'])) {
                $update_data['account_name'] = $data['name'];
            }
            if (isset($data['company'])) {
                $update_data['company'] = $data['company'];
            }
            if (isset($data['is_invalid']) and in_array($data['is_invalid'], [0, 1])) {
                $update_data['is_invalid'] = intval($data['is_invalid']);
            }
            if (isset($data['platform_status']) and in_array($data['platform_status'], [0, 1])) {
                $update_data['platform_status'] = $data['platform_status'];
            }
            $update_data['updator_id'] = $data['updater_id'] ?? 0;
            $update_data['update_time'] = input('server.REQUEST_TIME');

            $new_data = $update_data;
            $new_data['site_status'] = $data['site_status'] ?? 0;
            $old_data = $this->joomAccountModel->where(['id'=>$id])->find();

            $operator = [];
            $operator['account_id'] = $id;
            $operator['operator_id'] = $data['updater_id'];
            $operator['operator'] = $data['realname'];

            $this->joomAccountModel->allowField(true)->save($update_data, ['id' => $id]);

            if (in_array($new_data['site_status'], [1, 2, 3, 4])) {
                $old_data['site_status'] = AccountService::setSite(
                    ChannelAccountConst::channel_Joom,
                    $old_data,
                    $operator['operator_id'],
                    $new_data['site_status']
                );
            }
            //插入日志
            self::addLog(
                $operator,
                ChannelAccountLog::UPDATE,
                $new_data,
                $old_data
            );

            //修改缓存
            $cache = Cache::store('JoomAccount');
            foreach($update_data as $key=>$val) {
                $cache->updateTableRecord($id, $key, $val);
            }
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    /**
     * 更改状态
     * @author lingjiawen
     * @dateTime 2019-04-26
     * @param    int|integer $id     账号id
     * @param    int|integer $enable 是否启用 0 停用，1 启用
     * @return   true|string         成功返回true,失败返回string 原因
     */
    public function changeStatus(int $id = 0, bool $enable)
    {
        try {
            $accountInfo = $this->joomAccountModel->where('id', $id)->find();
            if (!$accountInfo) {
                throw new Exception('记录不存在');
            }

            /**
             * 判断是否可更改状态
             */
            (new \app\index\service\ChannelAccountService())->checkChangeStatus(ChannelAccountConst::channel_Joom, [$id]);

            if ($accountInfo->is_invalid == $enable) {
                return true;
            }

            $user = CommonService::getUserInfo(Request::instance());
            $operator = [];
            $operator['operator_id'] = $user['user_id'] ?? 0;
            $operator['operator'] = $user['realname'] ?? '';
            $operator['account_id'] = $id;

            $old_data = $accountInfo->toArray();

            $accountInfo->is_invalid = $enable;
            $accountInfo->updator_id = $operator['operator_id'];
            $accountInfo->update_time = time();

            $new_data = $accountInfo->toArray();

            if ($accountInfo->save()) {
                self::addLog(
                    $operator,
                    ChannelAccountLog::UPDATE,
                    $new_data,
                    $old_data
                );

                Cache::store('JoomAccount')->delAccount($id);
            }
            
            return true;
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 400);
        }
    }


    /**
     * 添加日志
     * @author lingjiawen
     */
    public static function addLog(array $base_info = [], int $type = 0, array $new_data = [], $old_data = []): void
    {
        $insert_data = [];
        $remark = [];
        if (ChannelAccountLog::INSERT == $type) {
            $insert_data = $new_data;
            foreach ($new_data as $k => $v) {
                if (isset(self::$log_config[$k])) {
                    $remark[] = ChannelAccountLog::getRemark(self::$log_config[$k], $type, $k, $v);
                }
            }
        }
        if (ChannelAccountLog::DELETE == $type) {
            $insert_data = (array)$old_data;
        }
        if (ChannelAccountLog::UPDATE == $type) {
            foreach ($new_data as $k => $v) {
                if (isset(self::$log_config[$k]) and isset($old_data[$k]) and $v != $old_data[$k]) {
                    $remark[] = ChannelAccountLog::getRemark(self::$log_config[$k], $type, $k, $v, $old_data[$k]);
                    $insert_data[$k] = $old_data[$k];
                }
            }
        }
        $insert_data and ChannelAccountLog::addLog([
            'channel_id' => ChannelAccountConst::channel_Joom,
            'account_id' => $base_info['account_id'],
            'type' => $type,
            'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            'operator_id' => $base_info['operator_id'],
            'operator' => $base_info['operator'],
            'data' => json_encode($insert_data, JSON_UNESCAPED_UNICODE),
            'create_time' => input('server.REQUEST_TIME'),
        ]);
    }

    /**
     * 获取日志
     */
    public function getJoomLog(array $req = [])
    {
        $page = isset($req['page']) ? intval($req['page']) : 1;
        $pageSize = isset($req['pageSize']) ? intval($req['pageSize']) : 10;
        $account_id = isset($req['id']) ? intval($req['id']) : 0;

        return (new ChannelAccountLog)->getLog(ChannelAccountConst::channel_Joom, $account_id, true, $page, $pageSize);
    }
}