<?php

namespace app\index\service;

use app\common\exception\JsonErrorException;
use app\common\model\AccountSite;
use app\common\model\aliexpress\AliexpressAccount;
use app\common\model\amazon\AmazonAccount;
use app\common\model\cd\CdAccount;
use app\common\model\ChannelUserAccountManager;
use app\common\model\ChannelUserAccountMap;
use app\common\model\ChannelUserAccountMapLog;
use app\common\model\ebay\EbayAccount;
use app\common\model\fummart\FummartAccount;
use app\common\model\joom\JoomAccount;
use app\common\model\joom\JoomShop;
use app\common\model\jumia\JumiaAccount;
use app\common\model\lazada\LazadaAccount;
use app\common\model\LogExportDownloadFiles;
use app\common\model\newegg\NeweggAccount;
use app\common\model\oberlo\OberloAccount;
use app\common\model\pandao\PandaoAccount;
use app\common\model\paypal\PaypalAccount;
use app\common\model\paytm\PaytmAccount;
use app\common\model\pdd\PddAccount;
use app\common\model\Channel as channelModel;
use app\common\model\shoppo\ShoppoAccount;
use app\common\model\souq\SouqAccount;
use app\common\service\CommonQueuer;
use app\common\model\TeamChannelUserAccountMap;
use app\common\model\umka\UmkaAccount;
use app\common\model\User as UserModel;
use app\common\model\User;
use app\common\model\vova\VovaAccount;
use app\common\model\walmart\WalmartAccount;
use app\common\model\wish\WishAccount;
use app\common\model\yandex\YandexAccount;
use app\common\model\zoodmall\ZoodmallAccount;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\service\OrderType;
use app\common\service\UniqueQueuer;
use app\order\service\AuditOrderService;
use app\index\queue\AccountUserMapQueue;
use app\index\queue\ChannelUserMapExportQueue;
use app\report\model\ReportExportFiles;
use app\report\validate\FileExportValidate;
use app\index\service\AmazonAccountService;
use app\publish\interfaces\AliexpressMember;
use app\publish\interfaces\WishMember;
use think\Request;
use think\Db;
use think\Exception;
use app\order\service\OrderService;
use app\common\cache\Cache;
use app\common\service\Common as CommonService;
use think\Loader;
use app\common\traits\Export;
use app\common\service\Excel;


Loader::import('phpExcel.PHPExcel', VENDOR_PATH);

/** 渠道账号人员关系表
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/5/8
 * Time: 17:20
 */
class MemberShipService
{
    use Export;
    protected $channelUserAccountMapModel;
    protected $channelUserAccountManagerModel;

    public function __construct()
    {
        if (is_null($this->channelUserAccountMapModel)) {
            $this->channelUserAccountMapModel = new ChannelUserAccountMap();
        }
        if (is_null($this->channelUserAccountManagerModel)) {
            $this->channelUserAccountManagerModel = new ChannelUserAccountManager();
        }
    }

    /**
     * @title  标题
     * return array
     */
    public function titles()
    {
        $title = [
            'channel_id' => [
                'title' => 'channel_id',
                'remark' => '平台',
                'is_show' => 1
            ],
            'code' => [
                'title' => 'code',
                'remark' => '账号简称',
                'is_show' => 1
            ],
            'seller_id' => [
                'title' => 'seller_id',
                'remark' => '销售员',
                'is_show' => 1
            ],
            'warehouse_type' => [
                'title' => 'warehouse_type',
                'remark' => '仓库类型',
                'is_show' => 1
            ],
        ];
        return $title;


    }

    protected $colMaps = [

        'account' => [
            'title' => [
                'A' => ['title' => '平台.', 'width' => 10],
                'B' => ['title' => '账号简称', 'width' => 25],
                'C' => ['title' => '销售员', 'width' => 20],
                'D' => ['title' => '仓库类型', 'width' => 15],
            ],
            'data' => [
                'channel_id' => ['col' => 'A', 'type' => 'str'],
                'code' => ['col' => 'B', 'type' => 'str'],
                'seller_id' => ['col' => 'C', 'type' => 'str'],
                'warehouse_type' => ['col' => 'D', 'type' => 'str'],
            ],
        ],
    ];


    /** 成员列表
     * @param array $where
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function memberList(array $where, $page = 1, $pageSize = 10)
    {
        try {
            $field = 'id,channel_id,account_id,seller_id,warehouse_type,customer_id,create_time';
            $count = $this->channelUserAccountMapModel->field($field)->where($where)->count();
            $list = $this->channelUserAccountMapModel->field($field)->where($where)->order('create_time desc,id asc')->page($page,
                $pageSize)->select();
            $list = $this->merge($list, true);
            $result = [
                'data' => $list,
                'page' => $page,
                'pageSize' => $pageSize,
                'count' => $count,
            ];
            return $result;
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /** 新增记录
     * @param $detail
     * @return bool
     * @throws \Exception
     */
    public function add($detail)
    {
        $ok = true;
        $groupDetail = [];
        $repeat = [];
        $addQ = [];
        $user = CommonService::getUserInfo();
        //启动事务
        Db::startTrans();
        try {
            foreach ($detail as $k => $v) {
                if (empty($v['channel_id']) || empty($v['account_id'])) {
                    throw new JsonErrorException('平台账号为必填项', 400);
                }
                foreach ($v['info'] as $key => $value) {
                    if (empty($value['seller_id'])) {
                        throw new JsonErrorException('销售员为必填项', 400);
                    }
                    $temp['channel_id'] = $v['channel_id'];
                    $temp['account_id'] = $v['account_id'];
                    $temp['customer_id'] = $v['customer_id'];
                    $temp['seller_id'] = $value['seller_id'];
                    $temp['warehouse_type'] = $value['warehouse_type'];
                    $temp['create_time'] = time();
                    $temp['creator_id'] = $user['user_id'];
                    $temp['update_time'] = time();
                    array_push($groupDetail, $temp);

                }
                $bool = $this->channelUserAccountMapModel->checkRepeat($v['channel_id'], $v['account_id']);
                if (!$bool) {
                    $ok = false;
                    $repeat['channel_id'] = $v['channel_id'];
                    $repeat['account_id'] = $v['account_id'];
                    break;
                }

                $addQ[] = [
                    'data' => [
                        'channel_id' => $v['channel_id'],
                        'account_id' => $v['account_id'],
                        'customer_id' => $v['customer_id'],
                    ],
                    'info' => $v['info'],
                ];
            }
            if (!$ok) {
                Db::rollback();
                $orderService = new OrderService();
                $channel_name = Cache::store('channel')->getChannelName($repeat['channel_id']);
                $account_name = $orderService->getAccountName($repeat['channel_id'], $repeat['account_id']);
                throw new JsonErrorException($channel_name . '渠道' . $account_name . '账号已经被其他记录绑定了！', 500);
            }
            $ids = [];
            foreach ($groupDetail as $key => $value) {
                $model = new ChannelUserAccountMap();
                $model->allowField(true)->isUpdate(false)->save($value);
                array_push($ids, $model->id);
            }
            foreach ($addQ as $q) {
                $this->addQueuer($q['data'], $q['info'], [], $user);
                //添加日志
                ChannelUserAccountMapLog::addLog(ChannelUserAccountMapLog::add, $q['data'], $q['info']);
                //修改账号状态
                (new BasicAccountService())->changeAccountSiteStatus($q['data']['channel_id'], $q['data']['account_id'],AccountSite::status_in_operation, ['新增平台账号绑定']);
            }
            Db::commit();
            $this->delCache();
            $where['id'] = ['in', $ids];
            return $this->memberList($where)['data'];
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    public function getLog($channel_id, $account_id)
    {
        return ChannelUserAccountMapLog::getLog($channel_id, $account_id);
    }

    /** 更新
     * @param $data
     * @param $infoList
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function update($data, $infoList, $id)
    {
        $idArr = explode('_', $id);
        $groupDetail = [];
        $data['update_time'] = time();
        $diff = count($idArr) - count($infoList);
        $user = CommonService::getUserInfo();
        $ids = [];
        unset($data['info']);
        unset($data['channel_name']);
        unset($data['account_name']);
        unset($data['customer_name']);
        $oldData = $this->channelUserAccountMapModel->field('seller_id,customer_id')->where('id', 'in', $idArr)->select();
        //启动事务info
        Db::startTrans();
        try {
            $data['updater_id'] = $user['user_id'];
            switch ($diff) {
                case 0:  //修改
                    foreach ($infoList as $key => $value) {
                        $temp = $data;
                        $temp['seller_id'] = $value['seller_id'];
                        $temp['warehouse_type'] = $value['warehouse_type'];
                        $temp['id'] = $value['id'];
                        array_push($groupDetail, $temp);
                    }
                    $this->channelUserAccountMapModel->isUpdate(true)->saveAll($groupDetail);
                    $ids = $idArr;
                    break;
                case $diff > 0:  //减少
                    foreach ($infoList as $key => $value) {
                        if (empty($value['id'])) {
                            $temp = $data;
                            $temp['seller_id'] = $value['seller_id'];
                            $temp['warehouse_type'] = $value['warehouse_type'];
                            $temp['id'] = $idArr[0];
                            $this->channelUserAccountMapModel->isUpdate(true)->save($temp);
                            array_push($ids, $temp['id']);
                            //删除
                            $this->channelUserAccountMapModel->where(['id' => $idArr[1]])->delete();
                        } else {
                            if (in_array($value['id'], $idArr)) {  //存在，更新
                                $temp = $data;
                                $temp['seller_id'] = $value['seller_id'];
                                $temp['warehouse_type'] = $value['warehouse_type'];
                                $temp['id'] = $value['id'];
                                $this->channelUserAccountMapModel->isUpdate(true)->save($temp);
                                array_push($ids, $temp['id']);
                                //删除另一个
                                foreach ($idArr as $k => $v) {
                                    if ($v != $value['id']) {
                                        $this->channelUserAccountMapModel->where(['id' => $v])->delete();
                                    }
                                }
                            } else {
                                throw new JsonErrorException('参数错误', 400);
                            }
                        }
                    }
                    break;
                case $diff < 0: //增加
                    foreach ($infoList as $key => $value) {
                        if (!isset($value['id'])) {
                            $temp = $data;
                            $temp['seller_id'] = $value['seller_id'];
                            $temp['warehouse_type'] = $value['warehouse_type'];
                            $temp['create_time'] = time();
                            $temp['update_time'] = time();
                            unset($temp['id']);
                            $this->channelUserAccountMapModel->isUpdate(false)->save($temp);
                            array_push($ids, $this->channelUserAccountMapModel->id);

                        } else {
                            if (in_array($value['id'], $idArr)) {  //存在，更新
                                $temp = $data;
                                $temp['seller_id'] = $value['seller_id'];
                                $temp['warehouse_type'] = $value['warehouse_type'];
                                unset($temp['id']);
                                $this->channelUserAccountMapModel->isUpdate(true)->save($temp, ['id' => $value['id']]);
                                array_push($ids, $value['id']);

                            } else {
                                throw new JsonErrorException('参数错误', 400);
                            }
                        }
                    }
                    break;
            }
            //修改对应的账号基础资料
            $this->addQueuer($data, $infoList, $oldData, $user);
            $this->updateLog($data, $infoList, $oldData);
            //添加日志
            ChannelUserAccountMapLog::addLog(ChannelUserAccountMapLog::update, $data, $infoList);
            Db::commit();
            $this->delCache();
            $where['id'] = ['in', $ids];
            return $this->memberList($where)['data'];
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    /**
     * 翟雪莉要求的回调
     * @param $data
     * @param $infoList
     * @param array $oldData
     * @return bool
     */
    private function updateLog($data, $infoList, $oldData = [])
    {
        if ($data['channel_id'] != ChannelAccountConst::channel_amazon || !$oldData) {
            return false;
        }
        $oldIds = [];
        foreach ($oldData as $k => $v) {
            $oldIds[] = $v['seller_id'];
        }
        $newIds = [];
        if ($infoList) {
            foreach ($infoList as $key => $value) {
                $newIds[] = $value['seller_id'];
            }
        }
        if ($oldIds && $newIds) {
            $addIds = array_diff($newIds, $oldIds);
            $delIds = array_diff($oldIds, $newIds);
            if (count($addIds) > 0 || count($delIds) > 0) {
                $amazonAccountService = new AmazonAccountService();
                $amazonAccountService->reAge($data['account_id']);
            }
        }
    }

    public function addQueuer($data, $infoList, $oldData, $user)
    {
        //修改 平台账号绑定，并且更新到服务器成员
        if ($infoList) {
            $newIds[] = $data['customer_id'];
            foreach ($infoList as $key => $value) {
                $newIds[] = $value['seller_id'];
            }
        } else {
            $newIds = [];
        }

        $oldCustomer = [];
        $oldSeller = [];

        $oldIds = [];
        foreach ($oldData as $k => $v) {
            $oldIds[] = $v['customer_id'];
            $oldIds[] = $v['seller_id'];
            $oldCustomer[] = $v['customer_id'];
            $oldSeller[] = $v['seller_id'];
        }

        $addIds = array_diff($newIds, $oldIds);
        $delIds = array_diff($oldIds, $newIds);
        //
        $this->checkDelUser($delIds, $data['channel_id'], $data['account_id'], $oldCustomer, $oldSeller);
        if ($addIds || $delIds) {
            if (strpos($user['realname'], '平台账号绑定') === false) {
                $user['realname'] = '[平台账号绑定]'. $user['realname'];
            }
            $info = [
                'channel_id' => $data['channel_id'],
                'account_id' => $data['account_id'],
                'addIds' => $addIds,
                'delIds' => $delIds,
                'user' => $user,
            ];

            (new AccountUserMapService())->writeBackNew($info);
//            (new UniqueQueuer(AccountUserMapNewQueue::class))->push($info);
        }

    }

    /**
     * 检查是否可以删除
     * @param $delIds
     * @param $channelId
     * @param $accountId
     * @param $oldCustomer
     * @param $oldSeller
     * @return bool
     */
    private function checkDelUser(&$delIds, $channelId, $accountId, $oldCustomer, $oldSeller)
    {
        $otherBAI = (new BasicAccountService())->getBasicAccountOtherId($channelId, $accountId);
        if (!$otherBAI) {
            return false;
        }
        //3.看是否绑定了其他平台账号ID
        foreach ($delIds as $k => $userId) {
            $where = [
                'channel_id' => $channelId,
                'account_id' => ['in', $otherBAI],
            ];
            if (in_array($userId, $oldCustomer)) {
                $where['customer_id'] = $userId;
            }
            if (in_array($userId, $oldSeller)) {
                $where['seller_id'] = $userId;
            }
            $isHas = $this->channelUserAccountMapModel->where($where)->value('id');
            if ($isHas) {
                unset($delIds[$k]);
            }
        }
        array_values($delIds);
    }

    /**
     * 删除
     * @param $id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete($id)
    {
        $teamChannelUserAccountMapModel = new TeamChannelUserAccountMap();
        $idArr = explode('_', $id);
        foreach ($idArr as $k => $v) {
            if (!$this->channelUserAccountMapModel->isHas($id)) {
                throw new JsonErrorException('该记录不存在', 500);
            }
            $info = $teamChannelUserAccountMapModel->field('team_id')->where(['channel_user_account_id' => $v])->find();
            if (!empty($info['team_id'])) {
                throw new JsonErrorException('记录已被分组绑定，请先解绑', 500);
            }
        }
        $user = Common::getUserInfo();
        //启动事务
        Db::startTrans();
        try {
            foreach ($idArr as $k => $v) {

                $old = $this->channelUserAccountMapModel->where('id', $v)->find();
                $this->addQueuer($old, [], [$old], $user);
                //添加日志
                $old['customer_id'] = '';
                ChannelUserAccountMapLog::addLog(ChannelUserAccountMapLog::update, $old, []);
                $this->channelUserAccountMapModel->where(['id' => $v])->delete();
            }
            //修改账号状态
            (new BasicAccountService())->changeAccountSiteStatus($old['channel_id'], $old['account_id'],AccountSite::status_in_recovery, ['删除平台账号绑定']);
            Db::commit();
            $this->delCache();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage(), 500);
        }
    }

    /**
     * 读取信息
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function info($id)
    {
        $idArr = explode('_', $id);
        $infoList = $this->channelUserAccountMapModel->field('id,channel_id,account_id,seller_id,warehouse_type,customer_id')->where('id',
            'in', $idArr)->order('create_time desc,id asc')->select();
        $result = $this->merge($infoList, true);
        $result[0]['id'] = $id;
        return $result;
    }

    /**
     * 组合
     * @param $list
     * @param bool $conversion
     * @return array
     * @throws Exception
     */
    public function merge($list, $conversion = false)
    {
        $data = [];
        $temp = [];
        $orderService = new OrderService();
        foreach ($list as $key => $value) {
            $key = $value['channel_id'] . $value['account_id'] . $value['customer_id'];
            $seller = Cache::store('user')->getOneUser($value['seller_id'])['realname'] ?? '';

            if (isset($temp[$key])) {
                $info = [
                    'id' => $value['id'],
                    'seller_id' => $value['seller_id'],
                    'seller_name' => $conversion ? $seller : $value['seller_id'],
                    'warehouse_type' => $value['warehouse_type']
                ];
                $temp[$key]['id'] = $temp[$key]['id'] . '_' . $value['id'];
                array_push($temp[$key]['info'], $info);
            } else {
                $customer = Cache::store('user')->getOneUser($value['customer_id'])['realname'] ?? '';
                $temp[$key] = [
                    'id' => $value['id'],
                    'channel_id' => $value['channel_id'],
                    'channel_name' => $conversion ? Cache::store('channel')->getChannelName($value['channel_id']) : $value['channel_id'],
                    'account_id' => $value['account_id'],
                    'account_name' => $value['account_id'],
                    'code' => $orderService->getAccountName($value['channel_id'], $value['account_id']),
                    'customer_id' => $value['customer_id'],
                    'customer_name' => $conversion ? $customer : $value['customer_id'],
                    'info' => [
                        0 => [
                            'id' => $value['id'],
                            'seller_id' => $value['seller_id'],
                            'seller_name' => $conversion ? $seller : $value['seller_id'],
                            'warehouse_type' => $value['warehouse_type']
                        ]
                    ]
                ];
                if ($value['channel_id'] == ChannelAccountConst::channel_Joom) {
                    $shop = Cache::store('JoomShop')->getTableRecord($temp[$key]['account_id']);
                    $temp[$key]['shop_id'] = $temp[$key]['account_id'];
                    $temp[$key]['account_id'] = intval($shop['joom_account_id']);
                }
                if (isset($value['create_time']) && $value['create_time']) {
                    $temp[$key]['create_time'] = $value['create_time'];
                }
            }
        }
        foreach ($temp as $key => $value) {
            array_push($data, $value);
        }
        return $data;
    }

    /** 批量删除
     * @param Request $request
     * @return \think\response\Json
     */
    public function batch(Request $request)
    {
        $params = $request->param();
        $type = $params['type'];
        $data = $request->post('data', 0);
        if (empty($data)) {
            throw new JsonErrorException('请至少选择一条记录', 400);
        }
        $data = json_decode($data, true);
        switch ($type) {
            case 'delete':
                Db::startTrans();
                try {
                    foreach ($data as $key => $value) {
                        $this->delete($value);
                    }
                    Db::commit();
                    $this->delCache();
                    return true;
                } catch (Exception $e) {
                    Db::rollback();
                    throw new JsonErrorException('删除失败', 500);
                }
                break;
            case 'update':
                try {
                    foreach ($data as $key => $value) {
                        $this->update($value, $value['info'], $value['id']);
                    }
                } catch (Exception $e) {
                    throw new JsonErrorException('更新失败', 500);
                }
                break;
        }
    }

    /**
     * 删除缓存
     */
    private function delCache()
    {
        Cache::handler()->del('cache:channelCustomerAccount');
        Cache::handler()->del('cache:channelCustomer');
    }

    /**
     * 通过渠道，账号查询成员关系
     * @param $channel_id
     * @param $account_id
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function infoByChannel($channel_id, $account_id)
    {
        if (empty($channel_id) || empty($account_id)) {
            throw new JsonErrorException('渠道或账号信息不正确');
        }
        $where['channel_id'] = ['=', $channel_id];
        $where['account_id'] = ['=', $account_id];
        $field = 'id,channel_id,account_id,seller_id,warehouse_type,customer_id';
        $list = $this->channelUserAccountMapModel->field($field)->where($where)->order('create_time desc')->select();
        $list = $this->merge($list);
        return $list;
    }

    /** 通过渠道获取账号绑定的销售员，客服信息
     * @param $channel_id
     * @param $account_id
     * @param $type
     * @return array
     * @throws Exception
     */
    public function member($channel_id, $account_id, $type)
    {
        if (empty($channel_id)) {
            throw new JsonErrorException('渠道信息不正确');
        }
        $where['channel_id'] = ['=', $channel_id];
        $field = 'a.id';
        $join = [];
        switch ($type) {
            case JobService::Sales:
                $field .= ',seller_id';
                $join[] = ['user u', 'a.seller_id = u.id', 'left'];
                $groupBy = 'seller_id';
                break;
            case JobService::Customer:
                $field .= ',customer_id';
                $join[] = ['user u', 'a.customer_id = u.id', 'left'];
                $groupBy = 'customer_id';
                break;
        }
        if (!empty($account_id)) {
            $where['account_id'] = ['=', $account_id];
            $groupBy = '';
        }
        $where['u.status'] = ['=', 1];
        $list = (new channelUserAccountMap())->alias('a')->field($field)->join($join)->where($where)->group($groupBy)->select();

        $new_array = [];
        foreach ($list as $key => $value) {
            $value = $value->toArray();
            if (isset($value['seller_id'])) {

                $name = Cache::store('user')->getOneUser($value['seller_id']);
                $value['realname'] = isset($name['realname']) ? $name['realname'] : '';
                $value['username'] = isset($name['username']) ? $name['username'] : '';
            }
            if (isset($value['customer_id'])) {

                $name = Cache::store('user')->getOneUser($value['customer_id']);
                $value['realname'] = isset($name['realname']) ? $name['realname'] : '';
                $value['username'] = isset($name['username']) ? $name['username'] : '';
            }
            array_push($new_array, $value);
        }
        return $new_array;
    }

    /** 刊登选择销售人员接口
     * @param $warehouse_type 【仓库类型】
     * @param $channel_id 【渠道】
     * @param $type 【获取内容类型  sales 销售员  customer 客服】
     * @param $spu 【spu信息】
     * @param $category_id 【分类id】
     * @param $where
     * @return array
     */
    public function memberByPublish($warehouse_type, $channel_id, $type, $spu, $where = [], $category_id = 0)
    {
        $join = [];
        $field = 'channel_id,account_id,seller_id,customer_id,code,account_name';
        $helper = null;
        if (!empty($warehouse_type)) {
            $where['warehouse_type'] = ['=', $warehouse_type];
        }
        switch ($channel_id) {
            case 1:
                $where['b.is_invalid'] = ['=', 1];
                $join[] = ['ebay_account b', 'c.account_id = b.id', 'left'];
                break;
            case 2:
                $where['b.status'] = ['=', 1];
                $join[] = ['amazon_account b', 'c.account_id = b.id', 'left'];
                break;
            case 3:
                $where['b.wish_enabled'] = ['=', 1];
                $join[] = ['wish_account b', 'c.account_id = b.id', 'left'];
                $helper = new WishMember();
                break;
            case 4:
                $where['b.aliexpress_enabled'] = ['=', 1];
                $join[] = ['aliexpress_account b', 'c.account_id = b.id', 'left'];
                if (!empty($category_id)) {
                    $where['a.category_id'] = ['=', $category_id];
                    $join[] = ['aliexpress_account_category_power a', 'a.account_id = b.id', 'left'];
                }
                $helper = new AliexpressMember();
                break;
            default:
                throw new JsonErrorException('该渠道还没有开放', 500);
                break;
        }
        $where['channel_id'] = $channel_id;
        switch ($type) {
            case JobService::Sales:
                $join[] = ['user u', 'u.id = c.seller_id', 'left'];
                $field .= ',u.realname';
                break;
            case JobService::Customer:
                $join[] = ['user u', 'u.id = c.customer_id', 'left'];
                $field .= ',u.realname';
                break;
        }
        $memberList = $this->channelUserAccountMapModel->alias('c')->field($field)->where($where)->join($join)->order('account_id')->select();
        $new_array = [];
        foreach ($memberList as $key => $value) {
            $value = $value->toArray();
            if (!is_null($helper) && !empty($spu) && !$helper->filterSeller($value['account_id'], $spu)) {
                continue;
            }
            if (isset($new_array[$value['account_id']])) {
                $new_array[$value['account_id']]['realname'] .= ',' . $value['realname'];
            } else {
                $new_array[$value['account_id']] = $value;
            }
        }
        $new_array = array_values($new_array);
        return $new_array;
    }

    /**
     * 通过用户id去查找账号id
     * @param $user_id
     * @param int $channel_id
     * @param bool|false $is_virtual
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAccountIDByUserId($user_id, $channel_id = 0, $is_virtual = false)
    {
        $is_admin = false;
        if ((new Role())->isAdmin($user_id)) {
            $is_admin = true;
        }
        $userModel = new UserModel();
        $userInfo = $userModel->field('job')->where(['id' => $user_id])->find();
        $accountId = [];
        if (!empty($userInfo)) {
            $where = [];
            if (!empty($channel_id)) {
                $where['channel_id'] = ['eq', $channel_id];
            }
            $accountList = [];
            //切换成db 模式，防止在过滤器中多次调用，造成死循环
            $table = Db::table('channel_user_account_map');
            switch ($userInfo['job']) {
                case JobService::Purchase:
                    $accountList = $table->field('channel_id,account_id')->where(['seller_id' => $user_id])->where($where)->select();
                    break;
                case JobService::Sales:
                    $accountList = $table->field('channel_id,account_id')->where(['seller_id' => $user_id])->where($where)->select();
                    break;
                case JobService::Customer:
                    $accountList = $table->field('channel_id,account_id')->where(['customer_id' => $user_id])->where($where)->select();
                    break;
                default:
                    if ($is_admin || $userInfo['job'] == 'IT') {
                        $accountList = $table->field('channel_id,account_id')->where($where)->select();
                    }
                    break;
            }
            $accountManager = $this->getAccountIdByManager($user_id, $channel_id);
            $accountList = array_merge($accountList, $accountManager);
            if (!empty($accountList)) {
                foreach ($accountList as $a => $account) {
                    if ($is_virtual) {
                        switch ($account['channel_id']) {
                            default:
                                $virtual = $account['channel_id'] * OrderType::ChannelVirtual + $account['account_id'];
                                if (!in_array($virtual, $accountId)) {
                                    array_push($accountId, $virtual);
                                }
                                break;
                        }
                    } else {
                        switch ($account['channel_id']) {
                            default:
                                if (!in_array($account['account_id'], $accountId)) {
                                    array_push($accountId, $account['account_id']);
                                }
                                break;
                        }
                    }
                }
            }
        }
        return $accountId;
    }

    /**
     * 获取平台账号成员信息--与绑定的销售不一样的作用，这里是用来做权限的的，不参与统计
     * @param $user_id
     * @param int $channel_id
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAccountIdByManager($user_id, $channel_id = 0)
    {
        if (!empty($channel_id)) {
            $where['channel_id'] = ['eq', $channel_id];
        }
        $where['user_id'] = ['eq', $user_id];
        $accountList = $this->channelUserAccountManagerModel->field('channel_id,account_id')->where($where)->select();
        return !empty($accountList) ? $accountList : [];
    }

    /**
     * 通过销售员用户id去查找账号总数
     * @param $user_id
     * @param int $channel_id
     * @param bool|false $is_virtual
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAccountBySellerUserId($user_ids)
    {
        $userAccountData = [];
        if (!is_array($user_ids)) {
            $userIds = [$user_ids];
        } else {
            $userIds = $user_ids;
        }
        foreach ($userIds as $k => $user_id) {
            $userAccountData[$user_id] = 0;
        }
        $userAccountList = $this->channelUserAccountMapModel->field('count(seller_id) as count,seller_id')->where('seller_id', 'in', $userIds)->group('seller_id')->select();
        foreach ($userAccountList as $k => $value) {
            $userAccountData[$value['seller_id']] = $value['count'];
        }
        return !is_array($user_ids) ? $userAccountData[$user_ids] : $userAccountData;
    }

    /**
     * 导入数据  【简称，销售，客服】
     * @param $channel_id
     * @param $file_name
     * @throws Exception
     */
    public function import($channel_id, $file_name)
    {
        try {
            $fp = @fopen($file_name, 'r');
            $userModel = new User();
            $wishAccountModel = new WishAccount();
            $amazonAccountModel = new AmazonAccount();
            $ebayAccountModel = new EbayAccount();
            $aliAccountModel = new AliexpressAccount();
            while ($data = fgetcsv($fp)) {
                $data = eval('return ' . iconv('gbk', 'utf-8', var_export($data, true)) . ';');
                $insertData = [];
                //查询账号
                switch ($channel_id) {
                    case ChannelAccountConst::channel_ebay:
                        $account = $ebayAccountModel->field('id')->where(['code' => trim($data[0])])->find();
                        break;
                    case ChannelAccountConst::channel_amazon:
                        $account = $amazonAccountModel->field('id')->where(['code' => trim($data[0])])->find();
                        break;
                    case ChannelAccountConst::channel_wish:
                        $account = $wishAccountModel->field('id')->where(['code' => trim($data[0])])->find();
                        break;
                    case ChannelAccountConst::channel_aliExpress:
                        $account = $aliAccountModel->field('id')->where(['code' => trim($data[0])])->find();
                        break;
                    default:
                        $account = [];
                        break;
                }
                $data[1] = str_pad($data[1], 4, "0", STR_PAD_LEFT);
                if (!empty($account)) {
//                    $insertData['channel_id'] = $channel_id;
//                    $insertData['account_id'] = $account['id'];
                    $userInfo = $userModel->field('id')->where(['job_number' => trim($data[1])])->find();
                    if (!empty($userInfo)) {
                        $insertData['customer_id'] = $userInfo['id'];
                    }
//                    $insertData['warehouse_type'] = 1;
//                    if (isset($data[2])) {
//                        $userInfo = $userModel->field('id')->where(['realname' => trim($data[2])])->find();
//                        if (!empty($userInfo)) {
//                            $insertData['customer_id'] = $userInfo['id'];
//                        }
//                    }
//                    $insertData['create_time'] = time();
//                    $insertData['creator_id'] = 1;
                    $insertData['update_time'] = time();
                    $model = new ChannelUserAccountMap();
                    $model->where(['channel_id' => $channel_id, 'account_id' => $account['id']])->update($insertData);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 根据用户获取渠道帐号
     * @param $channel_id
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getChannelAccountsByUsers($channel_id, $where)
    {
        $accounts = ChannelUserAccountMap::where('channel_id', $channel_id)
            ->where($where)->select();
        $return = [];
        if ($accounts) {
            foreach ($accounts as $account) {
                if ($account['account_id']) {
                    array_push($return, $account['account_id']);
                }
            }
        }
        return $return;

    }

    /**
     * 获取销售员所管理的仓库类型  【0-所有 1-本地  2-海外】
     * @param $channel_id
     * @param $account_id
     * @param $user_id
     * @return mixed
     * @throws Exception
     */
    public function warehouseTypeBySales($channel_id, $account_id, $user_id)
    {
        $where['channel_id'] = ['eq', $channel_id];
        $where['account_id'] = ['eq', $account_id];
        $where['seller_id'] = ['eq', $user_id];
        $info = $this->channelUserAccountMapModel->field('warehouse_type')->where($where)->find();
        if (empty($info)) {
            return -1;
        }
        return $info['warehouse_type'];
    }

    /**
     * 已绑定的用户加入队列
     */
    public function joinQueue()
    {
        $where = [];
        //$where['channel_id'] = ['eq', ChannelAccountConst::channel_wish];
        $userMapList = $this->channelUserAccountMapModel->field('channel_id,account_id,seller_id,customer_id')->where($where)->select();
        foreach ($userMapList as $k => $value) {
            $value = $value->toArray();
            (new UniqueQueuer(AccountUserMapQueue::class))->push($value);
        }
    }

    /**
     * 导出所有平台账号绑定数据
     * @return bool
     * @throws Exception
     */
    public function getAllMemberShip()
    {
        $allChannel = Cache::store('channel')->getChannel();
        $downLoadDir = '/download/member_ship/*';
        $saveDir = ROOT_PATH . 'public' . $downLoadDir;
        @unlink($saveDir);
        foreach ($allChannel as $channel) {
            $this->getMemberShip($channel['id']);
        }
        return true;
    }

    /**
     * 导出不同平台账号绑定
     * @param int $page
     * @param int $pageSize
     * @param array $title
     * @param int $pagination
     * @return array $newData
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMemberShip($where, $page = 1, $pageSize = 20, $title = [], $pagination = 0)
    {
        set_time_limit(0);
        $field = $this->field();
        if (empty($pagination)) {
            $list = $this->channelUserAccountMapModel->field($field)->where($where)->order('create_time desc,id asc')->page($page, $pageSize)->select();
        } else {
            $list = $this->channelUserAccountMapModel->field($field)->where($where)->order('create_time desc,id asc')->select();
        }
        $list = $this->merge($list, true);
        $i = 1;
        if ($list) {
            $data = [];
            $allWarehouseType = ['全部', '本地', '海外'];
            foreach ($list as $k => $v) {
                $channelName = Cache::store('Channel')->getChannelName($v['channel_id']);
                foreach ($v['info'] as $info) {
                    $data [] = [
                        'channel_id' => $channelName,
                        'code' => $v['code'],
                        'seller_id' => Cache::store('User')->getOneUserRealname($info['seller_id']),
                        'warehouse_type' => $allWarehouseType[$info['warehouse_type']],
                    ];

                }
            }
            $pp = [];
            $newData = [];
            foreach ($data as $tt) {
                foreach ($title as $value) {
                    $pp[$value] = $tt[$value];
                }
                array_push($newData, $pp);
                $i++;
            }
            return $newData;
        }
    }

    /**
     * 更新该目录下的文件的渠道账号人员关系表的仓库类型
     * @return array
     */
    public function saveAllDir()
    {
        set_time_limit(0);
        $path = 'download/member_ship_save/';
        $dir = ROOT_PATH . 'public/' . $path;
        $filenames = [];
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    $file_arr = explode('.', $file);
                    if (isset($file_arr[1]) && in_array($file_arr[1], ['xlsx', 'xls', 'csv'])) {
                        $filename = $path . $file_arr[0] . '.' . $file_arr[1];
                        array_push($filenames, $filename);
                    }
                }
                closedir($dh);
            }
        }
        //更新
        foreach ($filenames as $filename) {
            $this->saveChannelMember($filename);
            @unlink(ROOT_PATH . 'public/' . $filename);
        }
        return $filenames;
    }

    /**
     * 更新某个文件的渠道账号人员关系表的仓库类型
     * @param $filename
     * @return bool
     * @throws Exception
     */
    public function saveChannelMember($filename)
    {
        $result = Excel::readExcel($filename);
        $date = $this->checkAndBuildData($result);
        return true;
    }

    /**
     * 转换数据
     * @param $result
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function checkAndBuildData($result)
    {
        if ($result[0]) {
            $channel_id = Cache::store('Channel')->getChannelId($result[0]['平台']);
        } else {
            throw new Exception('数据为空', 500);
        }
        if (!$channel_id) {
            throw new Exception('平台数据错误', 500);
        }
        $allCount = Cache::store('account')->getAccountByChannel($channel_id);
        foreach ($result as $v) {
            $row = array_filter($v);
            if (!$row) {
                continue;
            }
            $error = $this->checkRequire($row);
            if ($error) {
                throw new Exception($error, 500);
            }
            $info = [];
            $info['channel_id'] = $channel_id;

            $accountId = 0;
            foreach ($allCount as $account) {
                if ($account['code'] == $row['账号简称']) {
                    $accountId = $account['id'];
                    break;
                }
            }
            if (!$accountId) {
                continue;
            }
            $info['account_id'] = $accountId;
            $info['user_name'] = $row['销售员'];
            $info['warehouse_type'] = isset($row['仓库类型']) ? $this->hasWarehouseType($row['仓库类型']) : '';
            $this->updateUserMap($info);
            unset($info);
        }
        return true;
    }

    /**
     * 更新数据
     * @param $v
     * @return bool|false|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateUserMap($v)
    {
        $seller_id = (new User())->where(['realname' => $v['user_name']])->value('id');
        if (!$seller_id) {
            return false;
        }
        $where['channel_id'] = $v['channel_id'];
        $where['account_id'] = $v['account_id'];
        $where['seller_id'] = $seller_id;
        $oldData = $this->channelUserAccountMapModel->where($where)->field('id,warehouse_type')->find();
        if ($oldData && $oldData['warehouse_type'] != $v['warehouse_type']) {
            $save['warehouse_type'] = $v['warehouse_type'];
            $save['update_time'] = time();
            return (new ChannelUserAccountMap())->save($save, ['id' => $oldData['id']]);
        }
        return false;
    }

    /**
     * 仓库类型转换
     * @param $key
     * @return mixed
     */
    private function hasWarehouseType($key)
    {
        // 仓库类型
        $all = [
            '全部' => 0,
            '本地' => 1,
            '海外' => 2,
        ];
        return $all[$key];
    }

    /**
     * 防止数据不对
     * @param $row
     * @return string
     */
    private function checkRequire($row)
    {
        $aRequire = [
            '平台',
            '账号简称',
            '销售员',
            '仓库类型',
        ];
        $error = '';
        foreach ($aRequire as $v) {
            if (!isset($row[$v]) || !$row[$v]) {
                $error = $v . "不能为空";
                break;
            }
        }
        return $error;
    }

    /**
     * 搜索条件
     * @param $params
     * @return array
     */
    public function getWhere($params)
    {
        $where = [];
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            $snText = $params['snText'];
            $snText = json_decode($snText, true);
            if ($snText) {
                switch (trim($params['snType'])) {
                    case 'sales':
                        $where['seller_id'] = ['in', $snText];
                        break;
                    case 'customer':
                        $where['customer_id'] = ['in', $snText];
                        break;
                    default:
                        break;
                }
            }

        }
        if ($channel_id = param($params, 'channel_id')) {
            $where['channel_id'] = ['=', $channel_id];
            if ($shop_id = param($params, 'shop_id')) {
                $where['account_id'] = ['=', $shop_id];
            }
        }
        if (isset($params['account_id']) && $params['account_id'] != '') {
            $accountType = param($params, 'account_type');
            if ($params['account_id'] && $accountType && $channel_id) {

                $account_id = json_decode($params['account_id'], true);
                if ($account_id) {
                    if ($accountType == 2) { //文本
                        $account_id = $this->getAccountIdByCode($channel_id, $account_id);
                    } else {
                        if ($channel_id == ChannelAccountConst::channel_Joom) {
                            $account_id = (new \app\common\model\joom\JoomShop())->where('joom_account_id', 'in', $account_id)->column('id');
                        }
                    }
                    $where['account_id'] = ['in', $account_id];
                }
            }
        }
        if (isset($params['shop_id']) && !empty($params['shop_id'])) {

            $where['account_id'] = $params['shop_id'];

        }

        return $where;
    }

    /**
     *查询字段
     * @return array
     */
    protected function field()
    {
        $field = ['id', 'channel_id', 'account_id', 'seller_id', 'warehouse_type', 'customer_id', 'create_time'];
        return $field;
    }

    /**
     * 申请导出
     * @param $params
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function applyExport($params)
    {
        $page = $params['page'] ?? '1';
        $pageSize = $params['pageSize'] ?? '20';
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cacher = Cache::handler();
        try {
            $lastApplyTime = $cacher->hget('hash:export_channel', $userId);
            if ($lastApplyTime && time() - $lastApplyTime < 5) {
                throw new Exception('请求过于频繁', 400);
            } else {
                $cacher->hset('hash:export_channel_apply', $userId, time());
            }
            $fileName = $this->createExportFileName($params['channel_id'], $userId);
            $downLoadDir = '/download/export_channel_user_map/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $titleMap = $this->colMaps['account']['title'];
            $title = [];
            $titleData = $this->colMaps['account']['data'];
            foreach ($titleData as $k => $v) {
                array_push($title, $k);
            }
            $titleOrderData = [];
            foreach ($titleMap as $t => $tt) {
                $titleOrderData[$tt['title']] = 'string';
            }
            $fullName = $saveDir . $fileName;
            foreach ($params as &$v) {
                $v = trim($v);
            }
            $where = $this->getWhere($params);
            $fields = $this->field();
            $count = $this->channelUserAccountMapModel->field($fields)->where($where)->count();
            if ($count > 100) {
                Db::startTrans();
                try {
                    $model = new ReportExportFiles();
                    $model->applicant_id = $userId;
                    $model->apply_time = time();
                    $model->export_file_name = $this->createExportFileName($params['channel_id'], $model->applicant_id);
                    $model->status = 0;
                    if (!$model->save()) {
                        throw new Exception('导出请求创建失败', 500);
                    }
                    $params['file_name'] = $model->export_file_name;
                    $params['apply_id'] = $model->id;
                    $queuer = new CommonQueuer(ChannelUserMapExportQueue::class);
                    $queuer->push($params);
                    Db::commit();
                    return ['join_queue' => 1, 'message' => '成功加入队列'];
                } catch (\Exception $ex) {
                    Db::rollback();
                    throw new JsonErrorException('申请导出失败');
                }
            } else {
                $writer = new \XLSXWriter();
                $writer->writeSheetHeader('Sheet1', $titleOrderData);
                $data = $this->getMemberShip($where, $page, $pageSize, $title, 1);
                foreach ($data as $a => $r) {
                    $writer->writeSheetRow('Sheet1', $r);
                }
                $writer->writeToFile($fullName);
                $auditOrderService = new AuditOrderService();
                $result = $auditOrderService->record($fileName, $fullName);
                return $result;
            }
        } catch (Exception $e) {
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());


        }

    }

    /**
     * 队列导出
     * @param array
     * @return bool
     * @throws Exception
     */
    public function export($params)
    {
        set_time_limit(0);
        try {
            ini_set('memory_limit', '4096M');
            if (!isset($params['apply_id']) || empty($params['apply_id'])) {
                throw new Exception('导出申请id获取失败');
            }
            if (!isset($params['file_name']) || empty($params['file_name'])) {
                throw new Exception('导出文件名未设置');
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/export_channel_user_queue/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            //统计需要导出的数据行
            $pageSize = 10000;
            $where = $this->getWhere($params);
            $field = $this->field();
            $count = $this->channelUserAccountMapModel->field($field)->where($where)->count();
            $loop = ceil($count / $pageSize);
            //创建excel对象
            $writer = new \XLSXWriter();
            $col = [];
            $title = [];
            $titleMap = $this->colMaps['account']['title'];
            $titleData = $this->colMaps['account']['data'];

            foreach ($titleData as $k => $v) {
                array_push($title, $k);
            }
            $titleOrderData = [];
            foreach ($titleMap as $t => $tt) {
                $titleOrderData[$tt['title']] = 'string';
            }
            $writer->writeSheetHeader('sheet1', $titleOrderData);
            //分批导出
            for ($i = 0; $i < $loop; $i++) {
                $data = $this->getMemberShip($where, $i + 1, $pageSize, $title);
                foreach ($data as $r) {
                    $writer->writeSheetRow('sheet1', $r);
                }
                unset($data);
            }
            $writer->writeToFile($fullName);
            if (is_file($fullName)) {
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir . $fileName;
                $applyRecord['status'] = 1;
                (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
            (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            Cache::handler()->hset(
                'hash:report_channel_user',
                $params['apply_id'] . '_' . time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage() . ',错误行数：' . $ex->getLine());
        }
    }

    /**
     * 创建导出文件名
     * @param int $channel_id
     * @return string $report_type
     * @return int $user_id
     * @throws Exception
     */
    protected function createExportFileName($channel_id, $user_id)
    {
        $report_str = '账号绑定数据';
        $channelName = Cache::store('Channel')->getChannelName($channel_id);
        if (empty($channelName)) {
            $fileName = '全部平台' . $report_str . '报表';
        } else {
            $fileName = $channelName . $report_str . '报表';
        }
        //确保文件名称唯一
        $lastID = (new ReportExportFiles())->order('id desc')->value('id');
        $fileName .= ($lastID + 1);
        $fileName .= '_' . $user_id;
        $fileName .= '_' . date('Y-m-d', time());
        $fileName .= '.xlsx';
        return $fileName;

    }

    /**
     * 下载全部平台的信息
     */
    public function downloadAll()
    {
        //生成导出文件
        $this->getAllMemberShip();
        //导出文件
        return $this->downloadZip();
    }

    public function downloadZip()
    {
        //获取列表
        $downLoadDir = '/download/member_ship/';
        $saveDir = ROOT_PATH . 'public' . $downLoadDir;
        $datalist = $this->list_dir($saveDir);
        $fileName = 'member_ship' . date('Y_m_d') . '.zip';
        $filename = ROOT_PATH . 'public' . "/download/" . $fileName; //最终生成的文件名（含路径）
        if (!file_exists($filename)) {
            //重新生成文件
            $zip = new  \ZipArchive();//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
            if ($zip->open($filename, \ZipArchive::CREATE) !== TRUE) {
                throw new Exception('无法打开文件，或者文件创建失败', 400);
            }

            foreach ($datalist as $val) {
                if (file_exists($val)) {
                    $zip->addFile($val, basename($val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
                }
            }
            $zip->close();//关闭
        }
        if (!file_exists($filename)) {
            throw new Exception("无法找到文件", 400); //即使创建，仍有可能失败。。。。
        }

        $result = $this->record($fileName, $filename);
        return $result;
    }

    /**
     * 记录导出记录
     * @param $filename
     * @param $path
     * @return array
     */
    public function record($filename, $path)
    {
        $model = new LogExportDownloadFiles();
        $temp['file_code'] = date('YmdHis');
        $temp['created_time'] = time();
        $temp['download_file_name'] = $filename;
        $temp['type'] = 'member_ship';
        $temp['file_extionsion'] = 'zip';
        $temp['saved_path'] = $path;
        $model->allowField(true)->isUpdate(false)->save($temp);
        return ['file_code' => $temp['file_code'], 'file_name' => $temp['download_file_name']];
    }

    /**
     * 读取文件目录下的全部文件
     * @param $dir
     * @return array
     */
    private function list_dir($dir)
    {
        $result = array();
        if (is_dir($dir)) {
            $file_dir = scandir($dir);
            foreach ($file_dir as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                } elseif (is_dir($dir . $file)) {
                    $result = array_merge($result, list_dir($dir . $file . '/'));
                } else {
                    array_push($result, $dir . $file);
                }
            }
        }
        return $result;
    }

    public function getAccountIdByCode($channel_id, $codes)
    {
        $mode = '';
        switch ($channel_id) {
            case ChannelAccountConst::channel_ebay:
                $mode = new EbayAccount();
                break;
            case ChannelAccountConst::channel_amazon:
                $mode = new AmazonAccount();
                break;
            case  ChannelAccountConst::channel_wish:
                $mode = new WishAccount();
                break;
            case ChannelAccountConst::channel_aliExpress:
                $mode = new AliexpressAccount();
                break;
            case ChannelAccountConst::channel_CD:
                $mode = new CdAccount();
                break;
            case ChannelAccountConst::channel_Lazada:
                $mode = new LazadaAccount();
                break;
            case ChannelAccountConst::channel_Joom:
                $mode = new JoomAccount();
                $ids = $mode->where('code', 'in', $codes)->column('id');
                return (new JoomShop())->where('joom_account_id', 'in', $ids)->column('id');
                break;
            case ChannelAccountConst::channel_Pandao:
                $mode = new PandaoAccount();
                break;
            case ChannelAccountConst::channel_Shopee:
                $mode = new ShoppoAccount();
                break;
            case ChannelAccountConst::channel_Paytm:
                $mode = new PaytmAccount();
                break;
            case  ChannelAccountConst::channel_Walmart:
                $mode = new WalmartAccount();
                break;
            case ChannelAccountConst::channel_Vova:
                $mode = new VovaAccount();
                break;
            case ChannelAccountConst::Channel_Jumia:
                $mode = new JumiaAccount();
                break;
            case ChannelAccountConst::Channel_umka:
                $mode = new UmkaAccount();
                break;
            case ChannelAccountConst::channel_Newegg:
                $mode = new NeweggAccount();
                break;
            case ChannelAccountConst::channel_Oberlo:
                $mode = new OberloAccount();
                break;
            case ChannelAccountConst::channel_Shoppo:
                $mode = new ShoppoAccount();
                break;
            case ChannelAccountConst::channel_Zoodmall:
                $mode = new ZoodmallAccount();
                break;
            case ChannelAccountConst::channel_Pdd:
                $mode = new PddAccount();
                break;
            case ChannelAccountConst::channel_Yandex:
                $mode = new YandexAccount();
                break;
            case ChannelAccountConst::channel_Paypal:
                $mode = new PaypalAccount();
                break;
            case ChannelAccountConst::channel_Fummart:
                $mode = new FummartAccount();
                break;
            case ChannelAccountConst::channel_Souq:
                $mode = new SouqAccount();
                break;
        }
        if ($mode) {
            return $mode->where('code', 'in', $codes)->column('id');
        }
        return [];
    }

    /**
     * 获取用户所管理的平台信息
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBelongChannel($user_id)
    {
        if (is_array($user_id)) {
            $sellerWhere['seller_id'] = ['in', $user_id];
            $customerWhere['customer_id'] = ['in', $user_id];
        } else {
            $sellerWhere['seller_id'] = ['eq', $user_id];
            $customerWhere['customer_id'] = ['eq', $user_id];
        }
        $sellerMapList = Db::table('channel_user_account_map')->field('channel_id')->where($sellerWhere)->group('channel_id')->select();
        $customerMapList = Db::table('channel_user_account_map')->field('channel_id')->where($customerWhere)->group('channel_id')->select();
        $sellerMapData = array_column($sellerMapList, 'channel_id');
        $customerMapData = array_column($customerMapList, 'channel_id');
        $channel = array_merge($sellerMapData, $customerMapData);
        $channel = array_unique($channel);
        return $channel;
    }

    /**
     * 账号列表
     * @param $where
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getChannelUserAccount($where)
    {
        $departmentService = new Department();
        $departmentUserMapService = new DepartmentUserMapService();
        $field = 'a.id,a.channel_id,a.account_id,u.username,u.realname,a.user_id';
        try {
            $list = $this->channelUserAccountManagerModel->alias('a')->field($field)->where($where)->join('user u', 'a.user_id = u.id',
                'left')->select();
            foreach ($list as $key => &$value) {
                $department = '';
                $department_ids = $departmentUserMapService->getDepartmentByUserId($value['user_id']);
                foreach ($department_ids as $k => $v) {
                    $department .= $departmentService->getDepartmentNames($v) . ',';
                }
                $department = rtrim($department, ',');
                $value['department'] = $department;
                $value['department_id'] = $department_ids;
                $value['realname'] = !empty($value['realname']) ? $value['realname'] : '';
                $value['username'] = !empty($value['username']) ? $value['username'] : '';
            }
            return $list;
        } catch (Exception $ex) {
            throw new JsonErrorException($ex->getMessage());
        }
    }

    /**
     * 添加成员管理数据
     * @param $channel_id
     * @param $account_id
     * @param array $userList
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addChannelUserAccount($channel_id, $account_id, array $userList = [])
    {
//        if (empty($userList)) {
//            throw new JsonErrorException('用户记录不能为空', 400);
//        }
        $userInfo = Common::getUserInfo();
        $accountList = $this->channelUserAccountManagerModel->field('user_id')->where(['channel_id' => $channel_id, 'account_id' => $account_id])->select();
        $accountUserList = [];
        foreach ($accountList as $key => $value) {
            $value = $value->toArray();
            array_push($accountUserList, $value['user_id']);
        }
        //需要删除的用户
        $deleteUserList = array_diff($accountUserList, $userList);
        //新增用户
        $addUserList = array_diff($userList, $accountUserList);
        Db::startTrans();
        try {
            if (!empty($deleteUserList)) {
                //删除用户
                $where['channel_id'] = ['eq', $channel_id];
                $where['account_id'] = ['eq', $account_id];
                $where['user_id'] = ['in', $deleteUserList];
                $this->channelUserAccountManagerModel->where($where)->delete();
            }
            //新增用户
            foreach ($addUserList as $key => $value) {
                $temp['channel_id'] = $channel_id;
                $temp['account_id'] = $account_id;
                $temp['user_id'] = $value;
                (new ChannelUserAccountManager())->allowField(true)->isUpdate(false)->save($temp);
            }
            $code = (new OrderService())->getAccountName($channel_id, $account_id);
            (new AccountUserMapService())->relationNew($code, $channel_id, $addUserList, $deleteUserList, $userInfo);
            //添加修改日志
            $data = [
                'channel_id' => $channel_id,
                'account_id' => $account_id,
            ];
            $type = ChannelUserAccountMapLog::add;  //添加
            if (empty($addUserList)) {
                $type = ChannelUserAccountMapLog::delete;   //删除
            }
            if (!empty($deleteUserList) && !empty($addUserList)) {
                $type = ChannelUserAccountMapLog::update;   //修改
            }
            $this->addLog($type, $data, $userList, $accountUserList);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    /**
     * 添加日志
     * @param $type
     * @param $data
     * @param $newData
     * @param $oldData
     * @throws Exception
     */
    public function addLog($type, $data, $newData, $oldData)
    {
        $newData = $newData ? $newData : [];
        $oldData = $oldData ? $oldData : [];
        $remark = $this->getRemarkUser($newData, $oldData);
        $temp['remark'] = json_encode($remark, JSON_UNESCAPED_UNICODE);
        $temp['data'] = '';
        $userInfo = Common::getUserInfo();
        $temp['account_id'] = $data['account_id'] ?? 0;
        $temp['channel_id'] = $data['channel_id'] ?? 0;
        $temp['type'] = $type;
        $temp['operator_id'] = $userInfo['user_id'] ?? 0;
        $temp['operator'] = $userInfo['realname'] ?? '';
        $temp['create_time'] = time();
        (new ChannelUserAccountMapLog())->allowField(true)->isUpdate(false)->save($temp);
    }

    /**
     * 日志内容
     * @param $type
     * @param array $userList
     * @return array
     * @throws Exception
     */
    public function getRemark($type, $userList = [])
    {
        $remarks = [];
        foreach ($userList as $key => $new) {
            $remark = '';
            switch ($type) {
                case ChannelUserAccountMapLog::add:
                    $remark .= '添加:' . $this->getUserName($new);
                    break;
                case ChannelUserAccountMapLog::update:
                    $remark .= '修改:' . $this->getUserName($new);
                    break;
            }
            if ($remark) {
                $remarks[] = "【成员管理】" . $remark;
            }
        }
        return $remarks;
    }

    /**
     * 获取成员日志
     * @param array $newIds
     * @param array $oldIds
     * @return array
     * @throws Exception
     */
    public function getRemarkUser($newIds = [], $oldIds = [])
    {
        $remarks = [];
        foreach ($newIds as $id) {
            if (!in_array($id, $oldIds)) {
                $remarks[] = '【新增成员】' . $this->getUserName($id);
            }
        }
        foreach ($oldIds as $id) {
            if (!in_array($id, $newIds)) {
                $remarks[] = '【移除成员】' . $this->getUserName($id);
            }
        }
        return $remarks;
    }

    /**
     * 用户姓名
     * @param $userId
     * @return string
     * @throws Exception
     */
    public function getUserName($userId)
    {
        $userInfo = Cache::store('User')->getOneUser($userId);
        return $userInfo['realname'] ?? '';
    }

    public function joomChannelUserMap()
    {
        $channelId = ChannelAccountConst::channel_Joom;
        $oldWhere = [
            'channel_id' => $channelId,
        ];
        $oldList = $this->channelUserAccountMapModel->where($oldWhere)->select();
        $add = [];
        $time = time();
        foreach ($oldList as $v) {
            $accountIds = (new JoomShop())->where('', $v['id'])->column('id');
            foreach ($accountIds as $accountId) {
                $add[] = [
                    'channel_id' => $channelId,
                    'account_id' => $accountId,
                    'seller_id' => $v['seller_id'],
                    'create_time' => $time,
                ];
            }
        }
        Db::startTrans();
        try {
            //1.删除原来的数据
            $this->channelUserAccountMapModel->where($oldWhere)->delete();
            //2.新增数据
            (new ChannelUserAccountMap())->saveAll($add);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception('更新失败');
        }
    }

}