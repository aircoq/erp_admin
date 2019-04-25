<?php

namespace app\customerservice\service;

use app\common\cache\Cache;
use app\common\model\ChannelUserAccountMap;
use app\common\model\customerservice\MessageTransferRecord;
use app\common\model\ebay\EbayMessageGroup;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\service\OrderStatusConst;
use app\common\traits\User as UserTraits;
use app\index\service\User;
use app\common\model\User as UserModel;
use think\Exception;
use app\common\model\customerservice\AmazonEmail;
use app\common\model\customerservice\AmazonEmailGroup;
use app\common\model\aliexpress\AliexpressMsgRelation;
use app\common\model\customerservice\EmailAccounts;

/**
 * @module 客服管理
 * @title 站内信转让服务；
 */
class MessageTransferService
{

    use UserTraits;

    private $channel_id = 0;

    private $uid = 0;

    private $msg_type_des = [
        1 => '产品留言',
        2 => '订单留言',
        3 => '待配货订单留言',
        4 => '48小时未处理',
        5 => '其他'
    ];

    private $amazon_msg_type_des = [
//        1 => '系统邮件',
//        2 => '客服邮件',
        1 => '24小时内未回复',
        2 => '超24小时内未回复',
//        5 => '待配货',
//        6 => '退货申请',
//        7 => '待发货',
//        8 => '取消订单'
    ];
    
    private $aliexpress_msg_type_des = [
        1 => '产品留言',
        2 => '订单留言',
        3 => '待配货订单留言',
        4 => '48小时未处理'
    ];
    

    public function __construct()
    {
    }


    /**
     * 麽术方法，防止方法不存在时被调用了报错；
     * @param $name
     * @param $arguments
     * @return array
     */
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        return [];
    }

    public function getChannelId($params = [])
    {
        //没传渠道ID，根据uid来取；
        $user = Common::getUserInfo();
        $this->uid = $user['user_id'];

        //传了渠道ID
        if (!empty($params['channel_id'])) {
            $this->channel_id = $params['channel_id'];
            return $this->channel_id;
        }

        //如果uid为0，则设为ebay；
        if ($this->uid == 0) {
            $this->channel_id = ChannelAccountConst::channel_ebay;
            return $this->channel_id;
        }
        //如果是超级管理员；
        if ($this->isAdmin($this->uid)) {
            $this->channel_id = ChannelAccountConst::channel_ebay;
            return $this->channel_id;
        }

        //剩下不是超管的；
        $userservice = new User();
        $userInfo = $userservice->info($this->uid);
        if (empty($userInfo['roleList']) || !is_array($userInfo['roleList'])) {
            $this->channel_id = 0;
            return $this->channel_id;
        }
        $roles = Cache::store('role')->getRole();
        foreach ($roles as $role) {
            if (stripos($role['name'], 'Ebay') !== false) {
                foreach ($userInfo['roleList'] as $val) {
                    if ($val['id'] == $role['id']) {
                        $this->channel_id = ChannelAccountConst::channel_ebay;
                        return $this->channel_id;
                    }
                }
            }
            if (stripos($role['name'], 'Amazon') !== false || strpos($role['name'], '亚马逊') !== false) {
                foreach ($userInfo['roleList'] as $val) {
                    if ($val['id'] == $role['id']) {
                        $this->channel_id = ChannelAccountConst::channel_amazon;
                        return $this->channel_id;
                    }
                }
            }
            if (stripos($role['name'], 'Aliexpress') !== false || strpos($role['name'], '速卖通') !== false) {
                foreach ($userInfo['roleList'] as $val) {
                    if ($val['id'] == $role['id']) {
                        $this->channel_id = ChannelAccountConst::channel_aliExpress;
                        return $this->channel_id;
                    }
                }
            }
        }

        $this->channel_id = 0;
        return $this->channel_id;
    }


    /**
     * 当前登用户及下面的客服和帐号；
     *
     */
    public function getUderCustomer()
    {
        if (!$this->channel_id) {
            throw new Exception('平台ID不能为空');
        }
        $channelUserAccountMapModel = new ChannelUserAccountMap();

        $channelUserAccount = $channelUserAccountMapModel->where(['channel_id' => $this->channel_id])->column('customer_id');
        if (empty($channelUserAccount)) {
            return [];
        }

        $newMap = [];

        //以下为不是test进来；
        if ($this->uid == 0 || $this->isAdmin($this->uid)) {
            $newMap = $channelUserAccount;
        } else {
            //如果下属没人，则返回空；
            $unders = $this->getUnderlingInfo($this->uid);
            $unders[] = $this->uid;
            foreach ($channelUserAccount as $customer_id) {
                if (in_array($customer_id, $unders)) {
                    $newMap[] = $customer_id;
                }
            }
        }

        return array_merge(array_unique($newMap));
    }

    public function getChannelAccount($account_ids = [])
    {
        $where['channel_id'] = $this->channel_id;
        if (!empty($account_ids)) {
            $where['account_id'] = ['in', $account_ids];
        }
        $channelUserAccountMapModel = new ChannelUserAccountMap();
        $account_sellers = $channelUserAccountMapModel->where($where)->field('account_id,seller_id,customer_id')->select();
        if (empty($account_sellers)) {
            return [];
        }
        $new = [];
        $accountCache = Cache::store('Account');
        $accounts = $accountCache->getAccountByChannel($this->channel_id);
        foreach ($account_sellers as $val) {
            $tmp = $val->toArray();
            $account = $accounts[$tmp['account_id']] ?? [];
            $tmp['code'] = $account['code'] ?? '-';
            $new[$tmp['account_id']] = $tmp;
        }
        return $new;
    }


    /**
     * 哪个平台调用哪个平台的方法；
     * @param $params
     * @return mixed
     */
    public function lists($params)
    {
        $channel_id = $this->getChannelId($params);
        switch ($channel_id) {
            case ChannelAccountConst::channel_ebay:
                $data = $this->getEbayTransferList($params);
                break;
            case ChannelAccountConst::channel_amazon:
                $data = $this->getAmazonTransferList($params);
                break;
            case ChannelAccountConst::channel_aliExpress:
                $data = $this->getAliexpressTransferList($params);
                break;
            default:
                $data = [];
                break;
        }
        return $this->buildReturnData($channel_id, $data);
    }


    /**
     * 拿取title的帐号列表及数量；
     * @param $params
     * @return mixed
     */
    public function accountMessageTotal($params)
    {
        $channel_id = $this->getChannelId($params);
        switch ($channel_id) {
            case ChannelAccountConst::channel_ebay:
                $data = $this->getEbayAccountMessageTotal($params);
                break;
            case ChannelAccountConst::channel_amazon:
                $data = $this->getAmazonAccountMessageTotal($params);
                break;
            case ChannelAccountConst::channel_aliExpress:
                $data = $this->getAliexpressAccountMessageTotal($params);
                break;
            default:
                $data = [];
                break;
        }
        return $this->buildReturnData($channel_id, $data);
    }


    /**
     * 转发站内信；
     * @param $params
     * @return mixed
     */
    public function transfer($params)
    {
        $channel_id = $this->getChannelId($params);
        switch ($channel_id) {
            case ChannelAccountConst::channel_ebay:
                $data = $this->ebayTransfer($params);
                break;
            case ChannelAccountConst::channel_amazon:
                $data = $this->amazonTransfer($params);
                break;
            case ChannelAccountConst::channel_aliExpress:
                $data = $this->aliexpressTransfer($params);
                break;
            default:
                $data = [];
                break;
        }

        return $data;
    }


    public function record($params)
    {
        $channel_id = $this->getChannelId($params);
        $where = [];
        if (!empty($params['from_customer_id'])) {
            $where['from_customer_id'] = $params['from_customer_id'];
        }
        if (!empty($params['to_customer_id'])) {
            $where['to_customer_id'] = $params['to_customer_id'];
        }
        if (!empty($params['account_id'])) {
            $where['account_id'] = $params['account_id'];
        }
        if (!empty($params['create_id'])) {
            $where['create_id'] = $params['create_id'];
        }
        if (!empty($params['channel_id'])) {
            $where['channel_id'] = $params['channel_id'];
        }

        //以下时间筛选；
        $time_start = empty($params['time_start']) ? 0 : strtotime($params['time_start']);
        $time_end = empty($params['time_end']) ? 0 : strtotime($params['time_end']);
        if (!empty($time_start) && empty($time_start)) {
            $where['create_time'] = ['>', $time_start];
        }
        if (empty($time_end) && !empty($time_end)) {
            $where['create_time'] = ['<', $time_end + 86400];
        }
        if (!empty($time_start) && !empty($time_end)) {
            $where['create_time'] = ['between', [$time_start, $time_end + 86400]];
        }
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 50;

        $recordModel = new MessageTransferRecord();
        $count = $recordModel->where($where)->count();

        //集合返回的数据；
        $return = [
            'channel_id' => $channel_id,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'data' => [],
        ];

        $list = $recordModel->where($where)->page($page, $pageSize)->order('create_time', 'desc')->select();
        if (empty($list)) {
            return $return;
        }
        $newData = [];
        $uids = [];
        foreach ($list as $val) {
            $uids[] = $val['from_customer_id'];
            $uids[] = $val['to_customer_id'];
            $uids[] = $val['create_id'];
        }

        $users = UserModel::where(['id' => ['in', array_merge(array_unique($uids))]])->column('realname', 'id');
        $accounts = Cache::store('Account')->getAccountByChannel($channel_id);

        foreach ($list as $val) {
            $tmp = $val->toArray();
            $tmp['from_customer'] = $users[$tmp['from_customer_id']] ?? '-';
            $tmp['to_customer'] = $users[$tmp['to_customer_id']] ?? '-';
            $tmp['account_code'] = !empty($accounts[$tmp['account_id']]) ? $accounts[$tmp['account_id']]['code'] : '-';
            $tmp['creator'] = !empty($users[$tmp['create_id']]) ? $users[$tmp['create_id']] : '-';

            $newData[] = [
                'creator' => $tmp['creator'],
                'create_time' => date('Y-m-d H:i:s', $tmp['create_time']),
                'record' => sprintf(
                    '将帐号：%s(%d)中的 %d条待处理站内信转指派给：%s',
                    $tmp['account_code'], $tmp['all_quantity'], $tmp['message_quantity'], $tmp['to_customer']
                ),
                'remark' => $tmp['remark'],
            ];
        }

        $return['data'] = $newData;
        return $return;
    }


    public function creator($params)
    {
        $channel_id = $this->getChannelId($params);
        $uids = MessageTransferRecord::where(['channel_id' => $channel_id])->group('create_id')->column('create_id');
        $return = [
            'channel_id' => $channel_id,
            'data' => [],
        ];
        $uids = array_merge(array_filter($uids));

        if (empty($uids)) {
            return $return;
        }
        $users = UserModel::where(['id' => ['in', $uids]])->column('realname', 'id');
        $new = [];
        foreach ($uids as $uid) {
            $new[] = [
                'value' => $uid,
                'label' => $users[$uid] ?? '-',
            ];
        }
        $return['data'] = $new;
        return $return;
    }


    /**
     * 组成统一的返回格式；
     * @param $channel_id
     * @param $data
     * @return mixed
     */
    public function buildReturnData($channel_id, $data)
    {
        $return['channel_id'] = $channel_id;
        $return['data'] = $data['data'] ?? [];
        return $return;
    }


    /**
     * ebay待转发列表
     * @param $params
     * @return array
     */
    public function getEbayTransferList($params)
    {
        $where = $this->getEbayCondition($params);
        //拿取当前人下面的customer_id;
        $customers = $this->getUderCustomer();

        $groupModel = new EbayMessageGroup();
        $groupList = $groupModel->where($where)->group('account_id,customer_id')->field('count(id) total,account_id,customer_id')->select();
        if (empty($groupList)) {
            return [];
        }
        $account_ids = [];
        foreach ($groupList as $group) {
            $account_ids[] = $group['account_id'];
        }
        //找到各帐号绑定的锁售；
        $accounts = $this->getChannelAccount(array_merge(array_unique($account_ids)));

        //如果只设置了客服ID，则只用遍历这一个；
        if (!empty($where['customer_id'])) {
            $customers = [$where['customer_id']];
        }
        $accountCache = Cache::store('EbayAccount');

        //所有用户；
        $uids = [];
        $news = [];
        $sort = [];
        foreach ($customers as $cid) {
            //统记user_id;
            $uids[] = $cid;
            $tmp = [];
            $total = 0;
            $groupAccounts = [];
            foreach ($groupList as $group) {
                if ($group['customer_id'] == $cid) {
                    $tmp2 = [];
                    $total += $group['total'];
                    $tmp2['account_id'] = $group['account_id'];
                    $tmp2['total'] = $group['total'];
                    if (empty($accounts[$group['account_id']])) {
                        $account = $accountCache->getTableRecord($group['account_id']);
                    } else {
                        $account = $accounts[$group['account_id']];
                    }
                    $tmp2['account_code'] = $account['code'];
                    $seller_id = $account['seller_id'] ?? 0;
                    $tmp2['seller_id'] = $seller_id;
                    $groupAccounts[] = $tmp2;
                    $uids[] = $seller_id;
                }
            }
            $tmp['total'] = $total;
            $tmp['accounts'] = $groupAccounts;
            if (!empty($where['account_id']) && empty($total)) {
                continue;
            }
            $news[$cid] = $tmp;
            $sort[$cid] = $total;
        }

        //以数量留键倒序；
        arsort($sort);
        $users = UserModel::where(['id' => ['in', $uids]])->column('realname', 'id');

        //最后数据；
        $returnData = [];
        foreach ($sort as $cid=>$total) {
            if ($cid == 0 && $total == 0) {
                continue;
            }
            $tmp = [];
            $tmp['customer_id'] = $cid;
            $tmp['customer_name'] = $users[$cid] ?? $cid;
            $tmp['total'] = $total;
            $tmp['accounts'] = [];
            if (!empty($news[$cid]['accounts'])) {
                foreach ($news[$cid]['accounts'] as $account) {
                    if ($account['seller_id'] != 0) {
                        $account['seller_name'] = $users[$account['seller_id']] ?? $account['seller_id'];
                    } else {
                        $account['seller_name'] = '未绑定';
                    }
                    $tmp['accounts'][] = $account;
                }
            }

            $returnData[] = $tmp;
        }
        return ['data' => $returnData];
    }

    /**
     * amazon待转发列表
     * @param $params
     * @return array
     */
    public function getAmazonTransferList($params)
    {
        $where = $this->getAmazonCondition($params);
        //拿取当前人下面的customer_id;
        $customers = $this->getUderCustomer();

        $groupModel = new AmazonEmailGroup();
        $groupList = $groupModel->where($where)->group('account_id,customer_id')->field('count(id) total,account_id,customer_id')->select();
        if (empty($groupList)) {
            return [];
        }
        $account_ids = [];
        foreach ($groupList as $group) {
            $account_ids[] = $group['account_id'];
        }
        //找到各帐号绑定的锁售；
        $accounts = $this->getChannelAccount(array_merge(array_unique($account_ids)));

        //如果只设置了客服ID，则只用遍历这一个；
        if (!empty($where['customer_id'])) {
            $customers = [$where['customer_id']];
        }
        $accountCache = Cache::store('AmazonAccount');

        //所有用户；
        $uids = [];
        $news = [];
        $sort = [];
        foreach ($customers as $cid) {
            //统记user_id;
            $uids[] = $cid;
            $tmp = [];
            $total = 0;
            $groupAccounts = [];
            foreach ($groupList as $group) {
                if ($group['customer_id'] == $cid) {
                    $tmp2 = [];
                    $total += $group['total'];
                    $tmp2['account_id'] = $group['account_id'];
                    $tmp2['total'] = $group['total'];
                    if (empty($accounts[$group['account_id']])) {
                        $account = $accountCache->getTableRecord($group['account_id']);
                    } else {
                        $account = $accounts[$group['account_id']];
                    }
                    $tmp2['account_code'] = $account['code'];
                    $seller_id = $account['seller_id'] ?? 0;
                    $tmp2['seller_id'] = $seller_id;
                    $groupAccounts[] = $tmp2;
                    $uids[] = $seller_id;
                }
            }
            $tmp['total'] = $total;
            $tmp['accounts'] = $groupAccounts;
            if (!empty($where['account_id']) && empty($total)) {
                continue;
            }
            $news[$cid] = $tmp;
            $sort[$cid] = $total;
        }

        //以数量留键倒序；
        arsort($sort);
        $users = UserModel::where(['id' => ['in', $uids]])->column('realname', 'id');

        //最后数据；
        $returnData = [];
        foreach ($sort as $cid=>$total) {
            if ($cid == 0 && $total == 0) {
                continue;
            }
            $tmp = [];
            $tmp['customer_id'] = $cid;
            $tmp['customer_name'] = $users[$cid] ?? $cid;
            $tmp['total'] = $total;
            $tmp['accounts'] = [];
            if (!empty($news[$cid]['accounts'])) {
                foreach ($news[$cid]['accounts'] as $account) {
                    if ($account['seller_id'] != 0) {
                        $account['seller_name'] = $users[$account['seller_id']] ?? $account['seller_id'];
                    } else {
                        $account['seller_name'] = '未绑定';
                    }
                    $tmp['accounts'][] = $account;
                }
            }

            $returnData[] = $tmp;
        }
        return ['data' => $returnData];
    }
    
    /**
     * aliexpress待转发列表
     * @param $params
     * @return array
     */
    public function getAliexpressTransferList($params)
    {
        $condition = $this->getAliexpressCondition($params);
        $where = $condition['where'];
        //账号支持多选
        if (!empty($params['account_id'])) {
            $account_id_arr = explode(',', $params['account_id']);
            if(count($account_id_arr)==1){
                $where['r.aliexpress_account_id'] = $account_id_arr[0];
            }else{
                $where['r.aliexpress_account_id'] = ['in',$account_id_arr];
            }
        }
        $join = $condition['join'];
        
        //拿取当前人下面的customer_id;
        $customers = $this->getUderCustomer();
        
        //以下为aliexpress帐号；
        $groupModel = new AliexpressMsgRelation();
        $groupModel->where($where)->alias('r');
        
        $join && $groupModel->join($join);

        $groupList = $groupModel->group('r.aliexpress_account_id,r.owner_id')
        ->field('count(1) total, aliexpress_account_id account_id, owner_id customer_id')
        ->select();

        if (empty($groupList)) {
            return [];
        }
        $account_ids = [];
        foreach ($groupList as $group) {
            $account_ids[] = $group['account_id'];
        }
        //找到各帐号绑定的锁售；
        $accounts = $this->getChannelAccount(array_merge(array_unique($account_ids)));
        //如果只设置了客服ID，则只用遍历这一个；
        if (!empty($params['customer_id'])) {
            $customers = [$params['customer_id']];
        }
        $accountCache = Cache::store('AliexpressAccount');
        
        //所有用户；
        $uids = [];
        $news = [];
        $sort = [];
        foreach ($customers as $cid) {
            //统记user_id;
            $uids[] = $cid;
            $tmp = [];
            $total = 0;
            $groupAccounts = [];
            foreach ($groupList as $group) {
                if ($group['customer_id'] == $cid) {
                    $tmp2 = [];
                    $total += $group['total'];
                    $tmp2['account_id'] = $group['account_id'];
                    $tmp2['total'] = $group['total'];
                    if (empty($accounts[$group['account_id']])) {
                        $account = $accountCache->getTableRecord($group['account_id']);
                    } else {
                        $account = $accounts[$group['account_id']];
                    }
                    $tmp2['account_code'] = $account['code'];
                    $seller_id = $account['seller_id'] ?? 0;
                    $tmp2['seller_id'] = $seller_id;
                    $groupAccounts[] = $tmp2;
                    $uids[] = $seller_id;
                }
            }
            $tmp['total'] = $total;
            $tmp['accounts'] = $groupAccounts;
            if (!empty($where['account_id']) && empty($total)) {
                continue;
            }
            $news[$cid] = $tmp;
            $sort[$cid] = $total;
        }
        
        //以数量留键倒序；
        arsort($sort);
        $users = UserModel::where(['id' => ['in', $uids]])->column('realname', 'id');
        $users['0'] = '未绑定';
        
        //最后数据；
        $returnData = [];
        foreach ($sort as $cid=>$total) {
            if ($cid == 0 && $total == 0) {
                continue;
            }
            $tmp = [];
            $tmp['customer_id'] = $cid;
            $tmp['customer_name'] = isset($users[$cid]) ? $users[$cid] : '未知人员';
            $tmp['total'] = $total;
            $tmp['accounts'] = [];
            if (!empty($news[$cid]['accounts'])) {
                foreach ($news[$cid]['accounts'] as $account) {
                    if ($account['seller_id'] != 0) {
                        $account['seller_name'] = $users[$account['seller_id']] ?? $account['seller_id'];
                    } else {
                        $account['seller_name'] = '未绑定';
                    }
                    $tmp['accounts'][] = $account;
                }
            }
            
            $returnData[] = $tmp;
        }
        return ['data' => $returnData];
    }

    /**
     * 返回ebay查询参数；
     * @param $params
     */
    public function getEbayCondition($params)
    {
        $where = [];
        $where['status'] = 0;
        if (!empty($params['account_id'])) {
            $accountids = explode(',', $params['account_id']);
            $where['account_id'] = ['in', $accountids];
        }
        if (!empty($params['customer_id'])) {
            $where['customer_id'] = $params['customer_id'];
        }

        //以下时间筛选；
        $time_start = empty($params['time_start']) ? 0 : strtotime($params['time_start']);
        $time_end = empty($params['time_end']) ? 0 : strtotime($params['time_end']);
        if (!empty($time_start) && empty($time_start)) {
            $where['last_receive_time'] = ['>', $time_start];
        }
        if (empty($time_end) && !empty($time_end)) {
            $where['last_receive_time'] = ['<', $time_end + 86400];
        }
        if (!empty($time_end) && !empty($time_end)) {
            $where['last_receive_time'] = ['between', [$time_start, $time_end + 86400]];
        }

        //类型筛选；
        if (!empty($params['msg_type']) && !empty($this->msg_type_des[$params['msg_type']])) {
            switch ($params['msg_type']) {
                case 1:
                    $where['item_id'] = ['<>', ''];
                    break;
                case 2:
                    $where['last_transaction_id'] = ['<>', ''];
                    break;
                case 3:
                    $where['last_order_status'] = OrderStatusConst::ForDistribution;
                    break;
                case 4:
                     $where['last_receive_time'] = ['<', time() - 3600 * 48];
                     $where['untreated_count'] = ['>', 0];
                    break;
                case 5:
                    $where['item_id'] = '';
                    break;
                default:
                    break;
            }
        }

        return $where;
    }

    /**
     * 返回amazon查询参数；
     * @param $params
     */
    public function getAmazonCondition($params)
    {
        $where = [];

        //未回复，客服邮件
        $where['is_replied'] = 2;
        $where['box_id'] = 2;

//        $where['status'] = 0;
        if (!empty($params['account_id'])) {
            $accountids = explode(',', $params['account_id']);
            $where['account_id'] = ['in', $accountids];
        }

        if (!empty($params['customer_id'])) {
            $where['customer_id'] = $params['customer_id'];
        }

        //以下时间筛选；
        $time_start = empty($params['time_start']) ? 0 : strtotime($params['time_start']);
        $time_end = empty($params['time_end']) ? 0 : strtotime($params['time_end']);
        if (!empty($time_start) && empty($time_start)) {
            $where['last_receive_time'] = ['>', $time_start];
        }
        if (empty($time_end) && !empty($time_end)) {
            $where['last_receive_time'] = ['<', $time_end + 86400];
        }
        if (!empty($time_start) && !empty($time_end)) {
            $where['last_receive_time'] = ['between', [$time_start, $time_end + 86400]];
        }

        //类型筛选；
        if (!empty($params['msg_type']) && !empty($this->amazon_msg_type_des[$params['msg_type']])) {
            switch ($params['msg_type']) {
                case 1:
                    $where['last_receive_time'] = ['>=', time() - 3600 * 24];
                    break;
                case 2:
                    $where['last_receive_time'] = ['<', time() - 3600 * 24];
                    break;
                default:
                    break;
            }
        }

        return $where;
    }
    
    /**
     * 返回aliexpress查询参数；
     * @param $params
     */
    public function getAliexpressCondition($params)
    {
        $condition = [];
        $where = [];
        $join = [];
        $where['r.deal_status'] = 0;
        if (!empty($params['account_id'])) {
            $where['r.aliexpress_account_id'] = $params['account_id'];
        }
        //客服负责人id
        if (!empty($params['customer_id'])) {
            $where['r.owner_id'] = $params['customer_id'];
        }
        //以下时间筛选；
        $time_start = empty($params['time_start']) ? 0 : strtotime($params['time_start']);
        $time_end = empty($params['time_end']) ? 0 : strtotime($params['time_end']);
        if (!empty($time_start) && empty($time_start)) {
            $where['r.msg_time'] = ['>', $time_start];
        }
        if (empty($time_end) && !empty($time_end)) {
            $where['r.msg_time'] = ['<', $time_end + 86400];
        }
        if (!empty($time_end) && !empty($time_end)) {
            $where['r.msg_time'] = ['between', [$time_start, $time_end + 86400]];
        }
        //类型筛选；
        if (!empty($params['msg_type']) && !empty($this->aliexpress_msg_type_des[$params['msg_type']])) {
            switch ($params['msg_type']) {
                case 1:
                    $where['r.has_product'] = 1;
                    break;
                case 2:
                    $where['r.has_order'] = 1;
                    break;
                case 3:
                    $where['r.has_order'] = 1;
                    $where['o.status'] = OrderStatusConst::ForDistribution;
                    $join['aliexpress_msg_detail'] = ['aliexpress_msg_detail d','r.id=d.aliexpress_msg_relation_id','left'];
                    $join['order'] = ['order o','d.type_id=o.channel_order_number','left'];
                    break;
                case 4:
                    $where['r.msg_time'] = ['<', time() - 3600 * 48];
                    break;
                default:
                    break;
            }
        }
        $condition['where'] = $where;
        $condition['join'] = array_values($join);
        return $condition;
    }

    /**
     * ebay帐号数量；
     */
    public function getEbayAccountMessageTotal($params) {
        //不需要根据订单的类型来求值，会分别去查；
        if (isset($params['msg_type'])) {
            unset($params['msg_type']);
        }
        //要返回的数据；
        $returnData = [
            'accounts' => [],
            'types' => []
        ];
        $where = $this->getEbayCondition($params);

        //当不是test用户登录时；
        $accountIds = [];
        //测试用户和超级管理员用户；
        if ($this->uid == 0 || $this->isAdmin($this->uid)) {
            $accountIds = ChannelUserAccountMap::where([
                'channel_id' => $this->channel_id
            ])->column('account_id');
        } else {
            $uids = $this->getUnderlingInfo($this->uid);
            $accountIds = ChannelUserAccountMap::where([
                'customer_id' => ['in', $uids],
                'channel_id' => $this->channel_id
            ])->column('account_id');
        }

        if (empty($where['account_id'])) {
            $where['account_id'] = ['in', $accountIds];
        }

        //以下为ebay帐号；
        $groupModel = new EbayMessageGroup();
        $groupList = $groupModel->where($where)
            ->group('account_id')
            ->field('count(id) count, account_id')
            ->order('count', 'desc')
            ->select();
        if (!empty($groupList)) {
            $newAccounts = [];
            $sort = [];
            foreach ($accountIds as $accountId) {
                $sort[$accountId] = 0;
            }
            foreach ($groupList as $group) {
                $sort[$group['account_id']] = $group['count'];
            }
            arsort($sort);
            $cache = Cache::store('EbayAccount');
            $allTotal = 0;
            foreach ($sort as $account_id => $total) {
                $account = $cache->getTableRecord($account_id);
                $tmp = [];
                $tmp['value'] = $account_id;
                $tmp['label'] = $account['code'] ?? $account_id;
                $tmp['count'] = $total;
                $newAccounts[] = $tmp;
                $allTotal += $total;
            }
            array_unshift($newAccounts, [
                'value' => 0,
                'label' => '全部',
                'count' => $allTotal,
            ]);
            $returnData['accounts'] = $newAccounts;
        }

        //以下为各类型的；
        $typeData = [];
        //$allTotal = 0;
        foreach ($this->msg_type_des as $type=>$label) {
            $where2 = $where;
            $tmp = [];
            $tmp['value'] = $type;
            $tmp['label'] = $label;
            switch ($type) {
                case 1:
                    $where2['item_id'] = ['<>', ''];
                    break;
                case 2:
                    $where2['last_transaction_id'] = ['<>', ''];
                    break;
                case 3:
                    $where2['last_order_status'] = OrderStatusConst::ForDistribution;
                    break;
                case 4:
                    $where2['untreated_count'] = ['>', 0];
                    if (!empty($where['last_receive_time'])) {
                        $groupModel->where(['last_receive_time' => $where['last_receive_time']]);
                    }
                    $where2['last_receive_time'] = ['<', time() - 3600 * 48];
                    break;
                case 5:
                    $where2['item_id'] = '';
                    break;
            }
            $tmp['count'] = $groupModel->where($where2)->count();
            $typeData[] = $tmp;
            //$allTotal += $tmp['count'];
        }
        array_unshift($typeData, [
            'value' => 0,
            'label' => '全部',
            'count' => '',
        ]);
        $returnData['types'] = $typeData;

        return ['data' => $returnData];
    }

    /**
     * amazon帐号数量；
     */
    public function getAmazonAccountMessageTotal($params) {
        //不需要根据订单的类型来求值，会分别去查；
        if (isset($params['msg_type'])) {
            unset($params['msg_type']);
        }
        //要返回的数据；
        $returnData = [
            'accounts' => [],
            'types' => []
        ];
        $where=[];
        $where = $this->getAmazonCondition($params);

        //当不是test用户登录时；
        $accountIds = [];
        //测试用户和超级管理员用户；
        if ($this->uid == 0 || $this->isAdmin($this->uid)) {
            $accountIds = ChannelUserAccountMap::where([
                'channel_id' => $this->channel_id
            ])->column('account_id');
        } else {
            $uids = $this->getUnderlingInfo($this->uid);
            $accountIds = ChannelUserAccountMap::where([
                'customer_id' => ['in', $uids],
                'channel_id' => $this->channel_id
            ])->column('account_id');
        }

        if (empty($where['account_id'])) {
            $where['account_id'] = ['in', $accountIds];
        }


        //以下为amazon帐号；
        $groupMode = new AmazonEmailGroup();
        $groupList = $groupMode->where($where)
            ->group('account_id')
            ->field('count(id) count, account_id')
            ->order('count', 'desc')
            ->select();
        if (!empty($groupList)) {
            $newAccounts = [];
            $sort = [];
            foreach ($accountIds as $accountId) {
                $sort[$accountId] = 0;
            }
            foreach ($groupList as $group) {
                $sort[$group['account_id']] = $group['count'];
            }
            arsort($sort);
            $cache = Cache::store('AmazonAccount');
            $allTotal = 0;
            foreach ($sort as $account_id => $total) {
                $account = $cache->getTableRecord($account_id);
                $tmp = [];
                $tmp['value'] = $account_id;
                $tmp['label'] = $account['code'] ?? $account_id;
                $tmp['count'] = $total;
                $newAccounts[] = $tmp;
                $allTotal += $total;
            }
            array_unshift($newAccounts, [
                'value' => 0,
                'label' => '全部',
                'count' => $allTotal,
            ]);
            $returnData['accounts'] = $newAccounts;
        }

        //以下为各类型的；
        $typeData = [];
        //$allTotal = 0;
        foreach ($this->amazon_msg_type_des as $type=>$label) {

            $where2 = $where;
            $tmp = [];
            $tmp['value'] = $type;
            $tmp['label'] = $label;
            switch ($type) {
                case 1:
                    $where2['last_receive_time'] = ['>=', time() - 3600 * 24];
                    break;
                case 2:
                    $where2['last_receive_time'] = ['<', time() - 3600 * 24];
                    break;
                default:
                    break;
            }
            $tmp['count'] = $groupMode->where($where2)->count();
            $typeData[] = $tmp;
            //$allTotal += $tmp['count'];
        }
        array_unshift($typeData, [
            'value' => 0,
            'label' => '全部',
            'count' => '',
        ]);
        $returnData['types'] = $typeData;

        return ['data' => $returnData];
    }
    
    /**
     * 速卖通帐号数量；
     */
    public function getAliexpressAccountMessageTotal($params) {
        //不需要根据订单的类型来求值，会分别去查；
        if (isset($params['msg_type'])) {
            unset($params['msg_type']);
        }
        //要返回的数据；
        $returnData = [
            'accounts' => [],
            'types' => []
        ];
        
        $condition = $this->getAliexpressCondition($params);
        $where = $condition['where'];
        $join = $condition['join'];
        
        //当不是test用户登录时；
        $accountIds = [];
        //测试用户和超级管理员用户；
        if ($this->uid == 0 || $this->isAdmin($this->uid)) {
            $accountIds = ChannelUserAccountMap::where([
                'channel_id' => $this->channel_id
            ])->column('account_id');
        } else {
            $uids = $this->getUnderlingInfo($this->uid);
            $accountIds = ChannelUserAccountMap::where([
                'customer_id' => ['in', $uids],
                'channel_id' => $this->channel_id
            ])->column('account_id');
        }
        
        $where['r.aliexpress_account_id'] = ['in', $accountIds];
        
        //以下为aliexpress帐号；
        $groupModel = new AliexpressMsgRelation();
        $groupModel->where($where)->alias('r');
        
        $join && $groupModel->join($join);
        
        $msgRelationList = $groupModel->group('r.aliexpress_account_id')
        ->field('count(1) count, aliexpress_account_id account_id')
        ->order('count', 'desc')
        ->select();
        if (!empty($msgRelationList)) {
            $newAccounts = [];
            $sort = [];
            foreach ($accountIds as $accountId) {
                $sort[$accountId] = 0;
            }
            foreach ($msgRelationList as $group) {
                $sort[$group['account_id']] = $group['count'];
            }
            arsort($sort);
            $cache = Cache::store('AliexpressAccount');
            $allTotal = 0;
            foreach ($sort as $account_id => $total) {
                $account = $cache->getTableRecord($account_id);
                $tmp = [];
                $tmp['value'] = $account_id;
                $tmp['label'] = $account['code'] ?? $account_id;
                $tmp['count'] = $total;
                $newAccounts[] = $tmp;
                $allTotal += $total;
            }
            array_unshift($newAccounts, [
                'value' => 0,
                'label' => '全部',
                'count' => $allTotal,
            ]);
            $returnData['accounts'] = $newAccounts;
        }
        
        //以下为各类型的；
        $typeData = [];
        //$allTotal = 0;
        foreach ($this->aliexpress_msg_type_des as $type=>$label) {
            $where2 = $where;
            $tmp = [];
            $tmp['value'] = $type;
            $tmp['label'] = $label;
            $join = [];
            switch ($type) {
                case 1:
                    $where2['r.has_product'] = 1;
                    break;
                case 2:
                    $where2['r.has_order'] = 1;
                    break;
                case 3:
                    $where2['r.has_order'] = 1;
                    $where2['o.status'] = OrderStatusConst::ForDistribution;
                    $join = [];
                    $join[] = ['aliexpress_msg_detail d','r.id=d.aliexpress_msg_relation_id','left'];
                    $join[] = ['order o','d.type_id=o.channel_order_number','left'];
                    break;
                case 4:
                    $where2['r.msg_time'] = ['<', time() - 3600 * 48];
                    break;
                default:
                    break;
            }
            $groupModel->where($where2)->alias('r');
            
            $join && $groupModel->join($join);
            
            $tmp['count'] = $groupModel->count();
            $typeData[] = $tmp;
        }
        array_unshift($typeData, [
            'value' => 0,
            'label' => '全部',
            'count' => '',
        ]);
        $returnData['types'] = $typeData;
        
        return ['data' => $returnData];
    }

    /**
     * ebay转发
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function ebayTransfer($params)
    {
        if (!empty($params['from_customer_id'])) {
            $params['customer_id'] = $params['from_customer_id'];
        }
        $where = $this->getEbayCondition($params);
        $groupModel = new EbayMessageGroup();
        $ids = $groupModel->where($where)->field('id')->order('id')->limit($params['total'])->column('id');
        if (count($ids) < $params['total']) {
            throw new Exception('转发站内信不成功，当前条件下条数为 '. count($ids). ' 条');
        }

        $groupModel->update(['customer_id' => $params['to_customer_id']], ['id' => ['in', $ids]]);
        $insert = [
            'channel_id' => $this->channel_id,
            'account_id' => $params['account_id'],
            'from_customer_id' => $params['from_customer_id'],
            'to_customer_id' => $params['to_customer_id'],
            'all_quantity' => $params['account_total'],
            'message_quantity' => $params['total'],
            'remark' => $params['remark'] ?? '',
            'create_id' => $this->uid,
            'create_time' => time(),
        ];
        $mrmodel = new MessageTransferRecord();
        $mrmodel->insert($insert);
        return true;
    }


    /**
     * amazon转发
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function amazonTransfer($params)
    {
        if (!empty($params['from_customer_id'])) {
            $params['customer_id'] = $params['from_customer_id'];
        }
        $where = $this->getAmazonCondition($params);

        $groupModel = new AmazonEmailGroup();
        $ids = $groupModel->where($where)->field('id')->order('id')->limit($params['total'])->column('id');
        if (count($ids) < $params['total']) {
            throw new Exception('转发站内信不成功，当前条件下条数为 '. count($ids). ' 条');
        }

        $groupModel->update(['customer_id' => $params['to_customer_id']], ['id' => ['in', $ids]]);
        $insert = [
            'channel_id' => $this->channel_id,
            'account_id' => $params['account_id'],
            'from_customer_id' => $params['from_customer_id'],
            'to_customer_id' => $params['to_customer_id'],
            'all_quantity' => $params['account_total'],
            'message_quantity' => $params['total'],
            'remark' => $params['remark'] ?? '',
            'create_id' => $this->uid,
            'create_time' => time(),
        ];
        $mrmodel = new MessageTransferRecord();
        $mrmodel->insert($insert);
        return true;
    }
    
    /**
     * aliexpress转发
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function aliexpressTransfer($params)
    {
        if (!empty($params['from_customer_id'])) {
            $params['customer_id'] = $params['from_customer_id'];
        }
        if($params['to_customer_id']==$params['from_customer_id']){
            throw new Exception('当前处理人与转发处理人相同，无需转发');
        }
        $condition = $this->getAliexpressCondition($params);
        $where = $condition['where'];
        //账号支持多选
        if (!empty($params['account_id'])) {
            $account_id_arr = explode(',', $params['account_id']);
            if(count($account_id_arr)==1){
                $where['r.aliexpress_account_id'] = $account_id_arr[0];
            }else{
                $where['r.aliexpress_account_id'] = ['in',$account_id_arr];
            }
        }
        $join = $condition['join'];
        
        //以下为aliexpress帐号；
        $groupModel = new AliexpressMsgRelation();
        $groupModel->where($where)->alias('r');
        
        $join && $groupModel->join($join);
        
        $ids = $groupModel->field('r.id')->order('r.id')->limit($params['total'])->column('r.id');
        
        if (count($ids) < $params['total']) {
            throw new Exception('转发站内信不成功，当前条件下条数为 '. count($ids). ' 条');
        }
        $groupModel->update(['owner_id' => $params['to_customer_id']], ['id' => ['in', $ids]]);
        $insert = [
            'channel_id' => $this->channel_id,
            'account_id' => $params['account_id'],
            'from_customer_id' => $params['from_customer_id'],
            'to_customer_id' => $params['to_customer_id'],
            'all_quantity' => $params['account_total'],
            'message_quantity' => $params['total'],
            'remark' => $params['remark'] ?? '',
            'create_id' => $this->uid,
            'create_time' => time(),
        ];
        $mrmodel = new MessageTransferRecord();
        $mrmodel->insert($insert);
        return true;
    }
    
}
