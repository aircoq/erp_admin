<?php
namespace app\index\service;
use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\daraz\DarazAccount;
use app\common\service\ChannelAccountConst;
use app\common\model\ChannelAccountLog;
use app\index\service\AccountService;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use app\common\service\Common;

class DarazAccountService
{
    protected $darazAccountModel;

    /**
     * 日志配置
     * 字段名称[name] 
     * 格式化类型[type]:空 不需要格式化,time 转换为时间,list
     * 格式化值[value]:array
     */
    public static $log_config = [
        'sales_company_id' => ['name'=>'运营公司','type'=>null],
        'base_account_id' => ['name'=>'账号基础资料名','type'=>null],
        'name' => ['name'=>'账号名称','type'=>null],
        'code' => ['name'=>'账号简称','type'=>null],
        'site' => ['name'=>'站点','type'=>null],
        'seller_id' => ['name'=>'销售员','type'=>null],
        'shop_name' => ['name'=>'店铺名称','type'=>null],
        'api_user' => ['name'=>'API账号','type'=>null],
        'api_key' => ['name'=>'API秘钥','type'=>'key'],
        'status' => [
            'name'=>'系统状态',
            'type'=>'list',
            'value'=>[
                0 =>'停用',
                1 =>'使用' ,
            ],
        ],
        'platform_status' => [
            'name'=>'Daraz状态',
            'type'=>'list',
            'value'=>[
                0 =>'失效',
                1 =>'有效' ,
            ],
        ],
        'is_authorization' => [
            'name'=>'授权状态',
            'type'=>'list',
            'value'=>[
                0 =>'未授权',
                1 =>'已授权' ,
            ],
        ],
        'download_listing' => ['name'=>'抓取AmazonListing时间','type'=>'time'],
        'download_health' => ['name'=>'同步健康数据','type'=>'time'],
        'download_order' => ['name'=>'抓取订单时间','type'=>'time'],
        'sync_delivery' => ['name'=>'同步发货状态时间','type'=>'time'],
        'sync_feedback' => ['name'=>'同步中差评时间','type'=>'time'],
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
        if(is_null($this->darazAccountModel))
        {
            $this->darazAccountModel = new DarazAccount();
        }
    }

    /**
     * 获取账号列表
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
        $is_authorization = isset($req['authorization']) && is_numeric($req['authorization']) ? intval($req['authorization']) : -1;
        $is_invalid = isset($req['is_invalid']) && is_numeric($req['is_invalid']) ? intval($req['is_invalid']) : -1;
        $snType = !empty($req['snType']) && in_array($req['snType'], ['name', 'code']) ? $req['snType'] : '';
        $snText = !empty($req['snText']) ? $req['snText'] : '';
        $taskName = !empty($req['taskName']) && in_array($req['taskName'], ['download_listing', 'download_order', 'sync_delivery', 'download_health']) ? $req['taskName'] : '';
        $taskCondition = !empty($req['taskCondition']) && isset($operator[trim($req['taskCondition'])]) ? $operator[trim($req['taskCondition'])] : '';
        $taskTime = isset($req['taskTime']) && is_numeric($req['taskTime']) ? intval($req['taskTime']) : '';

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
        $is_authorization >= 0 and $where['am.is_authorization'] = $is_authorization;
        $site and $where['am.site'] = $site;
        $status >= 0 and $where['am.status'] = $status;
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

        $count = $this->darazAccountModel
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

        $field = 'am.id,am.sales_company_id,am.base_account_id,am.name,am.code,am.site,am.status,am.download_order,am.create_time,am.create_id,am.update_time,am.update_id,am.platform_status,am.sync_delivery,am.is_authorization,am.shop_name,am.download_listing,s.site_status,c.seller_id,c.customer_id,a.account_create_time register_time,a.fulfill_time';
        //有数据就取出
        $list = $this->darazAccountModel
            ->alias('am')
            ->field($field)
            ->join('__ACCOUNT__ a', 'a.id=am.base_account_id', 'LEFT')
            ->join('__CHANNEL_USER_ACCOUNT_MAP__ c', 'c.account_id=am.id AND c.channel_id=a.channel_id', 'LEFT')
            ->join('__ACCOUNT_SITE__ s', 's.base_account_id=am.base_account_id AND s.account_code=am.code', 'LEFT')
            ->where($where)
            ->page($page, $pageSize)
            ->order('am.id DESC')
            ->select();

        $site_status_info = new \app\index\service\BasicAccountService();
        foreach ($list as &$val) {
            $seller =  Cache::store('User')->getOneUser($val['seller_id']);
            $val['seller_name'] = $seller ? $seller['realname'] : '';
            $val['seller_on_job'] = $seller ? $seller['on_job'] : '';
            $customer = Cache::store('User')->getOneUser($val['customer_id']);
            $val['customer_name'] = $customer ? $customer['realname'] : '';
            $val['customer_on_job'] = $customer ? $customer['on_job'] : '';
            $val['site_status_str'] = $site_status_info->accountStatusName($val['site_status']);
        }

        return [
            'count' => $count,
            'data' => $list,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * 账号列表(已废弃)2019-4-18
     * @param Request $request
     * @return array
     */
    public function accountList(Request $request)
    {
        $params = $request->param();

        $where = $this->getWhere($params);

        $page = param($params,"page",1);
        $pageSize = param($params,"pageSize",50);

        $count = $this->darazAccountModel->where($where)->count();
        $accountList = $this->darazAccountModel->where($where)->fetchSql(false)->page($page, $pageSize)->select();
        $result = [
            'data' => $accountList,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /**
     * 封装where条件(已废弃)2019-4-18
     * @param array $params
     * @return array
     */
    function getWhere($params = [])
    {
        $where = [];
        if (isset($params['status']) && $params['status'] != '' ) {
            $params['status'] = $params['status'] == 1 ? 1 : 0;
            $where['status'] = ['eq', $params['status']];
        }
        if (isset($params['authorization']) && $params['authorization'] != '') {
            $where['is_authorization'] = ['eq', $params['authorization']];
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'account_name':
                    $where['name'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                case 'code':
                    $where['code'] = ['like', '%' . $params['snText'] . '%'];
                    break;
                default:
                    break;
            }
        }

        if(isset($params['site']) && !empty($params['site']))
        {
            $where['site'] = $params['site'];
        }

        if (isset($params['download_order']) && $params['download_order'] > -1) {
            if (empty($params['download_order'])) {
                $where['download_order'] = ['eq', 0];
            } else {
                $where['download_order'] = ['>', 0];
            }
        }
        if (isset($params['download_listing']) && $params['download_listing'] > -1) {
            if (empty($params['download_listing'])) {
                $where['download_listing'] = ['eq', 0];
            } else {
                $where['download_listing'] = ['>', 0];
            }
        }
        if (isset($params['sync_delivery']) && $params['sync_delivery'] > -1) {
            if (empty($params['sync_delivery'])) {
                $where['sync_delivery'] = ['eq', 0];
            } else {
                $where['sync_delivery'] = ['>', 0];
            }
        }

        if (isset($params['taskName']) && isset($params['taskCondition']) && isset($params['taskTime']) && $params['taskName'] !== '' && $params['taskTime'] !== '') {
            $where[$params['taskName']] = [trim($params['taskCondition']), $params['taskTime']];
        }
        return $where;
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
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['name'] = $data['account_name'];
        $data['seller_id'] = isset($data['seller_id']) ?? '';
        unset($data['account_name']);

        $validateAccount = validate('DarazAccount');
        if (!$validateAccount->check($data)) {
            $ret['msg'] = $validateAccount->getError();
            $ret['code'] = 400;
            return $ret;
        }
        \app\index\service\BasicAccountService::isHasCode(ChannelAccountConst::channel_Daraz, $data['code'], $data['site']);
        Db::startTrans();
        try {
            $new_data = $data;
            $darazModel = new DarazAccount();
            $darazModel->allowField(true)->isUpdate(false)->save($data);
            //获取最新的数据返回
            $new_id = $darazModel->id;
            //删除缓存
            Cache::store('DarazAccount')->delAccount();
            Db::commit();

            /**
             * 插入日志
             */
            $user = Common::getUserInfo(Request::instance());
            $operator = [];
            $operator['operator_id'] = $user['user_id'] ?? 0;
            $operator['operator'] = $user['realname'] ?? '';
            $operator['account_id'] = $new_id;
            self::addLog(
                $operator,
                ChannelAccountLog::INSERT,
                $new_data,
                []
            );
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
        $accountInfo = $this->darazAccountModel->field(true)->where(['id' => $new_id])->find();
        return $accountInfo;
    }

    /**
     * 账号信息
     * @return mixed
     * @throws \think\Exception
     */
    public function read($id)
    {
        if(intval($id) <= 0)
        {
            throw new JsonErrorException('账号不存在',500);
        }
        $accountInfo = Cache::store('DarazAccount')->getTableRecord($id);
        if(empty($accountInfo)){
            throw new JsonErrorException('账号不存在',500);
        }
        return $accountInfo;
    }

    /**
     * 更新资源
     * @param $id
     * @param $data
     */
    public function update($id,$data)
    {
        if ($this->darazAccountModel->isHas($id, $data['code'], '')) {
            throw new JsonErrorException('代码或者用户名已存在', 400);
        }
        $model = $this->darazAccountModel->get($id);

        $old_data = $model->toArray();

        $operator = [];
        $operator['operator_id'] = $data['update_id'];
        $operator['operator'] = $data['realname'];
        $operator['account_id'] = $model->id;

        Db::startTrans();
        try {
            //赋值
            // $model->code = isset($data['code'])?$data['code']:'';
            if(isset($data['name']))
            {
                $model->name = $data['name'];
            }
            // $model->site = isset($data['site'])?$data['site']:'';
            $model->download_order = isset($data['download_order'])?$data['download_order']:0;
            $model->download_listing = isset($data['download_listing'])?$data['download_listing']:0;
            $model->sync_delivery = isset($data['sync_delivery'])?$data['sync_delivery']:0;
            $model->update_time =time();

            $new_data = $model->toArray();

            $new_data['site_status'] = isset($data['site_status']) ? intval($data['site_status']) : 0;
            unset($data['id']);
            //更新数据
            $model->allowField(true)->isUpdate(true)->save();

            if (in_array($new_data['site_status'], [1, 2, 3, 4])) {
                $old_data['site_status'] = AccountService::setSite(
                    ChannelAccountConst::channel_Daraz,
                    $old_data,
                    $operator['operator_id'],
                    $new_data['site_status']
                );
                $model->site_status = $new_data['site_status'];
            }

            /**
             * 插入日志
             */
            self::addLog(
                $operator,
                ChannelAccountLog::UPDATE,
                $new_data,
                $old_data
            );

            //删除缓存
            Cache::store('DarazAccount')->delAccount();
            Db::commit();
            return $model;
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    /**
     * 保存授权信息
     * @param $data
     */
    public function authorization($data)
    {
        $rule = [
            'id'  => 'require|number|gt:0',
            'api_user'   => 'require',
            'api_key' => 'require',
            'seller_id' => 'require',
            'shop_name' => 'require'
        ];
        $msg = [
            'id.require' => '账号不存在',
            'id.number'  => '账号ID不合法',
            'id.gt'   => '账号ID不合法',
            'api_user.require'  => 'API账号不能为空',
            'api_key.require'   => 'API秘钥不能为空',
            'seller_id.require' => '销售员ID不能空',
            'shop_name.require' => '店铺名称不能空'
        ];

        $validae = new Validate($rule,$msg);
        if(!$validae->check($data))
        {
            throw new Exception($validae->getError());
        }

        $model = $this->darazAccountModel->where("id",$data['id'])->find();
        if(!$model)
        {
           throw new Exception('该账号不存在');
        }

        $old_data = $model->toArray();

        $model->api_user = $data['api_user'];
        $model->api_key = $data['api_key'];
        $model->seller_id = $data['seller_id'];
        $model->is_authorization = 1;
        $model->shop_name = $data['shop_name'];
        $res = $model->isUpdate(true)->allowField(true)->save();

        $new_data = $model->toArray();

        /**
         * 插入日志
         */
        $operator = [];
        $operator['operator_id'] = $data['update_id'];
        $operator['operator'] = $data['realname'];
        $operator['account_id'] = $model->id;
        $res and self::addLog(
            $operator,
            ChannelAccountLog::UPDATE,
            $new_data,
            $old_data
        );

        //删除缓存
        Cache::store('DarazAccount')->delAccount();
        return true;
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
            $accountInfo = $this->darazAccountModel->where('id', $id)->find();
            if (!$accountInfo) {
                throw new Exception('记录不存在');
            }

            /**
             * 判断是否可更改状态
             */
            (new \app\index\service\ChannelAccountService())->checkChangeStatus(ChannelAccountConst::channel_Daraz, [$id]);

            if ($accountInfo->status == $enable) {
                return true;
            }

            $user = Common::getUserInfo(Request::instance());
            $operator = [];
            $operator['operator_id'] = $user['user_id'] ?? 0;
            $operator['operator'] = $user['realname'] ?? '';
            $operator['account_id'] = $id;

            $old_data = $accountInfo->toArray();

            $accountInfo->status = $enable;
            $accountInfo->update_id = $operator['operator_id'];
            $accountInfo->update_time = time();

            $new_data = $accountInfo->toArray();

            if ($accountInfo->save()) {
                self::addLog(
                    $operator,
                    ChannelAccountLog::UPDATE,
                    $new_data,
                    $old_data
                );
                //删除缓存
                Cache::store('DarazAccount')->delAccount($id);
            }

            return true;
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 400);
        }
    }

    public function batchUpdate($ids, $data)
    {
        $updateData = [];
        isset($data['status']) && $updateData['status'] = intval($data['status']) ? 1 : 0;
        isset($data['download_listing']) && $updateData['download_listing'] = intval($data['download_listing']);
        isset($data['download_order']) && $updateData['download_order'] = intval($data['download_order']);
        isset($data['download_message']) && $updateData['download_message'] = intval($data['download_message']);
        isset($data['sync_delivery']) && $updateData['sync_delivery'] = intval($data['sync_delivery']);
        $updateData['update_time'] = time();
        $updateData['update_id'] = $data['user_id'] ?? 0;

        $new_data = $updateData;
        $new_data['site_status'] = isset($data['site_status']) ? intval($data['site_status']) : 0;

        $operator = [];
        $operator['operator_id'] = $data['user_id'];
        $operator['operator'] = $data['realname'] ?? '';

        $old_data_list = $this->darazAccountModel->where('id', 'in', $ids)->select();
        if (empty($old_data_list)) {
            return []; 
        }

        /**
         * 判断是否可更改状态
         */
        if (isset($data['status'])) {
            (new \app\index\service\ChannelAccountService())->checkChangeStatus(ChannelAccountConst::channel_Daraz, $ids);
        }

        $this->darazAccountModel->allowField(true)->where('id', 'in', $ids)->update($updateData);

        //删除缓存
        $cache = Cache::store('DarazAccount');
        foreach ($old_data_list as $old_data) {
            if (in_array($new_data['site_status'], [1, 2, 3, 4])) {
                $old_data['site_status'] = AccountService::setSite(
                    ChannelAccountConst::channel_Daraz,
                    $old_data,
                    $operator['operator_id'],
                    $new_data['site_status']
                );
            }
            $operator['account_id'] = $old_data['id'];

            /**
             * 插入日志
             */
            self::addLog(
                $operator,
                ChannelAccountLog::UPDATE,
                $new_data,
                $old_data
            );
            $cache->delAccount($old_data['id']);
        }
        return $new_data;
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
            'channel_id' => ChannelAccountConst::channel_Daraz,
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
    public function getDarazLog(array $req = [])
    {
        $page = isset($req['page']) ? intval($req['page']) : 1;
        $pageSize = isset($req['pageSize']) ? intval($req['pageSize']) : 10;
        $account_id = isset($req['id']) ? intval($req['id']) : 0;

        return (new ChannelAccountLog)->getLog(ChannelAccountConst::channel_Daraz, $account_id, true, $page, $pageSize);
    }
}