<?php

namespace app\customerservice\service\aliexpress;

use app\common\exception\TaskException;
use erp\AbsServer;
use service\aliexpress\AliexpressApi;
use think\Exception;
use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressMsgRelation;
use app\index\service\DeveloperService;
use app\customerservice\queue\AliexpressMsgQueue;
use app\common\service\UniqueQueuer;

class SynAliexpressMsg extends AbsServer
{
    protected $model = AliexpressMsgRelation::class;
    private $aliexpressAccounts; //速卖通账号信息缓存
    private $redis; //本次执行的redis链接
    private static $ownerIds; //账号负责人
    private $page; //拉取数据的起始页
    private $pageSize; //每页拉取的数量
    private $repeatTime; //可以往前重复抓取多少秒的数据

    /**
     * @desc 构造函数
     * @author Jimmy
     * @datetime 2017-09-23 17:37:11
     */

    public function __construct()
    {
        parent::__construct();
        $this->redis = Cache::handler();
        $this->aliexpressAccounts = Cache::store('AliexpressAccount')->getTableRecord();
        $this->page = 1; //从第一页开始拉取
        $this->pageSize = 50; //每次拉取五十个数量
        $this->repeatTime = 1800; //重复抓取前30分钟的数据
        $this->setAccount();
    }

    /**
     * @desc 检测缓存队列里面有没有账号数据信息，没有数据就往队列里面添加数据
     * @author Jimmy
     * @return boolean true or false 成功就返回true 失败返回false
     * @datetime 2017-09-23 16:50:11
     */
    private function setAccount()
    {
        //如果队列里面没有没有数据信息
        if (!$this->redis->lLen('queue:ali_syn_msg')) {
            //将获取到的账号存储到缓存里面去
            foreach ($this->aliexpressAccounts as $account) {
                if ($account['is_invalid'] && $account['is_authorization'] && $account['download_message'] > 0) {
                    $this->redis->lPush('queue:ali_syn_msg', json_encode([
                        'account_id' => $account['id'],
                    ]));
                }
            }
        }
        return true;
    }

    /**
     * @desc 根据账号抓取信息，循环账号抓取相应的数据信息
     * @author Jimy
     * @datetime 2017-09-26 09:44:11
     */
    public function synMsg()
    {
        //循环账号抓取数据信息
        while ($val = $this->redis->rPop('queue:ali_syn_msg')) {
            $accountId = json_decode($val, true);
            $account = $this->aliexpressAccounts[$accountId['account_id']];
            //账号的配置信息
            $config = [];
            $config['id'] = $account['id'];
            $config['client_id'] = $account['client_id'];
            $config['client_secret'] = $account['client_secret'];
            $config['accessToken'] = $account['access_token'];
            $config['refreshtoken'] = $account['refresh_token'];
            //消息类型
            $msgType = AliexpressMsgRelation::MSG_TYPE;
            $data['config'] = $config;
            $data['msgType'] = $msgType[1];
            (new UniqueQueuer(AliexpressMsgQueue::class))->push($data);
            //$this->downMsg($config, $msgType[1]);
        }
    }

    /**
     * @desc 下载信息
     * @param array $config api接口配置信息
     * @param array $msgType 消息类型，现在只有站内信，没有订单留言
     * @author Jimmy
     * @datetime 2017-09-26 10:53:11
     */
    public function downMsg($config, $msgType)
    {
        set_time_limit(0); //设定不能超时
        $msgObj = AliexpressApi::instance($config)->loader('Message'); //实例化拉取消息的类MSG
        $messageTimeLast = Cache::store('AliexpressMsgDetail')->getLastMessageTime($config['id']); //当前账号最近的messageTime 用来去重
        $tempTime = $messageTimeLast; //临时存储，保证原来从缓存里面取出的数据不变
        do {
            $res = $msgObj->queryMsgRelationList($msgType, $this->page, $this->pageSize); //获取站内信/订单留言关系列表
            if ($res['status'] == 0 || empty($res['response'])) {
                //如果抓取失败或没有抓取到数据信息就跳过
                break;
            }
            //循环获取的数据，插入数据库表中，如果已插入的数据就跳过
            foreach ($res['response'] as $msg) {
                if ($tempTime - $msg['messageTime'] > $this->repeatTime) {
                    //重复抓取的时间过了。
                    break 2; //跳出该账号的数据抓取，跳过两层循环。
                }
                $messageTimeLast = $msg['messageTime'] > $messageTimeLast ? $msg['messageTime'] : $messageTimeLast;
                //如果数据库表中已存在
                if (Cache::store('AliexpressMsgDetail')->isNewest($msg['channelId'], [$msg['readStat'], $msg['dealStat'], substr($msg['messageTime'], 0, 10)])) {
                    continue;
                }
                //对比组装aliexpress_msg_relation 表数据字段
                $data = [];
                $data['relation'] = $this->getMsg($msg, $msgType, $config['id']);
                //API获取详情数据
                $resDetail = $msgObj->getAllDetails($msg['channelId'], $msgType);
                if (empty($resDetail)) {
                    $data['detail'] = [];
                }
                foreach ($resDetail as $detail) {
                    //对比组装aliexpress_msg_detail 表数据字段
                    if (Cache::store('AliexpressMsgDetail')->isHas($detail['id'])) {
                        continue;
                    }
                    switch ($detail['messageType']) {
                        case 'order':
                            $data['relation']['has_order'] = 1;
                            break;
                        case 'product':
                            $data['relation']['has_product'] = 1;
                            break;
                        default:
                            $data['relation']['has_other'] = 1;
                            break;
                    }
                    $data['detail'][] = $this->getMsgDetail($detail, $msg['channelId']);
                }
                $model = new AliexpressMsgRelation();
                $model->add($data);
            }
            $this->page++;
            sleep(10);
        } while (count($res['response']) == $this->pageSize);
        //更新该账号的最近更新时间
        Cache::store('AliexpressMsgDetail')->setLastMessageTime($config['id'], $messageTimeLast);
    }

    /**
     * @desc 获取站内信详情
     * @param array $detail 站内信详情信息
     * @param int $channelId channelId
     * @author Jimmy
     * @datetime 2017-09-26 13:46:11
     */
    private function getMsgDetail($detail, $channelId)
    {
        $data = [];
        $data['channel_id'] = $channelId;
        $data['msg_id'] = $detail['id'];
        $data['gmt_create'] = substr($detail['gmtCreate'], 0, 10);
        $data['sender_name'] = $detail['senderName'];
        $data['sender_login_id'] = isset($detail['summary']['senderLoginId']) ? $detail['summary']['senderLoginId'] : '';
        $data['receiver_name'] = isset($detail['summary']['receiverName']) ? $detail['summary']['receiverName'] : '';
        $data['message_type'] = $detail['messageType'];
        $data['type_id'] = isset($detail['typeId']) ? number_format($detail['typeId'], 0, '', '') : 0;
        $data['content'] = $detail['content'] ? $detail['content'] : '';
        $data['file_path'] = json_encode($detail['filePath']);
        $data['summary'] = json_encode($detail['summary']);
        return $data;
    }

    /**
     * @desc 根据接口返回的数据信息，对比aliexpress_msg_relation model字段
     * @param array $msg api返回的单条msg数据
     * @return array $data 返回组装好可以插入数据库里面的数组
     * @author Jimmy
     * @datetime 2017-09-26 11:53:11
     */
    private function getMsg($msg, $msgType, $id)
    {
        $data = [];
        $data['channel_id'] = $msg['channelId'];
        $data['aliexpress_account_id'] = $id;
        $data['msg_type'] = $msgType == 'message_center' ? 1 : 2;
        $data['unread_count'] = $msg['unreadCount'];
        $data['read_status'] = $msg['readStat'];
        $data['last_msg_id'] = $msg['lastMessageId'];
        $data['last_msg_content'] = $msg['lastMessageContent'];
        $data['last_is_own'] = $msg['lastMessageIsOwn'] ? 1 : 0;
        $data['child_name'] = isset($msg['childName']) ? $msg['childName'] : '';
        $data['msg_time'] = substr($msg['messageTime'], 0, 10);
        $data['child_id'] = $msg['childId'];
        $data['other_name'] = $msg['otherName'];
        $data['other_login_id'] = $msg['otherLoginId'];
        $data['deal_status'] = isset($msg['dealStat']) ? $msg['dealStat'] : 2;
        $data['rank'] = $msg['rank'];
        $data['owner_id'] = $this->getHolder($id);
        $data['has_order'] = 0;
        $data['has_product'] = 0;
        $data['has_other'] = 0;
        return $data;
    }

    /**
     * @desc 获取账号负责人
     * @param int $id 账号ID
     * @return int $ownerId 账号负责人ID
     * @author Jimmy
     * @datetime 2017-09-26 11:20:11
     */
    private function getHolder($id)
    {
        $server = $this->invokeServer(DeveloperService::class);
        if (!isset(self::$ownerIds[$id])) {
            $res = $server->accountHolder(4, $id, 'customer');
            self::$ownerIds[$id] = isset($res['id']) ?: 0;
        }
        return self::$ownerIds[$id];
    }

    public function synMsg_old()
    {
        try {
            //获取所有已授权并启用账号
            $accountList = Cache::store('AliexpressAccount')->getTableRecord();
            //检测设置队列
            $redis = Cache::handler();
            if (!$redis->lLen('queue:ali_syn_msg')) {
                if (!empty($accountList)) {
                    foreach ($accountList as $item) {
                        if ($item['is_invalid'] && $item['is_authorization'] && $item['download_message'] > 0) {
                            $redis->lPush('queue:ali_syn_msg', json_encode([
                                'account_id' => $item['id'],
                            ]));
                        }
                    }
                }
            }
            $account = $redis->rPop('queue:ali_syn_msg');
            $account = json_decode($account, true);
            if (!isset($accountList[$account['account_id']])) {
                throw new TaskException("ID为{$account['account_id']}的账号不存在");
            }
            $account = $accountList[$account['account_id']];
//            $account = $accountList[1];
            $config = [
                'id' => $account['id'],
                'client_id' => $account['client_id'],
                'client_secret' => $account['client_secret'],
                'accessToken' => $account['access_token'],
                'refreshtoken' => $account['refresh_token'],
            ];
//            $config = [
//                'id'                =>  44,
//                'client_id'         =>  '26051047',
//                'client_secret'     =>  'xNgRAzBEUUuK',
//                'accessToken'       =>  '6ce8f775-eb54-4e44-9081-dfb3c22da417',
//                'refreshtoken'      =>  '',
//            ];
            //消息类型
            $msgType = AliexpressMsgRelation::MSG_TYPE;
            //开始去拉取消息
            array_walk($msgType, function($value, $key, $config) {
                $this->downMsg($config, $value);
            }, $config);
        } catch (Exception $ex) {
            throw new TaskException($ex->getMessage());
        }
    }

    public function downMsg_old($config, $msgType)
    {
        set_time_limit(0);
        static $holders = [];
        $server = $this->invokeServer(DeveloperService::class);
        if (isset($holders[$config['id']])) {
            $owner_id = $holders[$config['id']];
        } else {
            $result = $server->accountHolder(4, $config['id'], 'customer');
            $owner_id = isset($result['id']) ? $result['id'] : 0;
            $holders[$config['id']] = $owner_id;
        }
        $page = 1;
        $pageSize = 50;
        $msgObj = AliexpressApi::instance($config)->loader('Message');
        do {
            $res = $msgObj->queryMsgRelationList($msgType, $page, $pageSize);
            if ($res['status'] == 0)
                break;
            $data = $res['response'];
            if (!empty($data)) {
                foreach ($data as $item) {
                    if (Cache::store('AliexpressMsgDetail')->isNewest($item['channelId'], [$item['readStat'], $item['dealStat'], substr($item['messageTime'], 0, 10)])) {
                        continue;
                    }
                    $model = new AliexpressMsgRelation();
                    $relation = [];
                    $relation['channel_id'] = $item['channelId'];
                    $relation['aliexpress_account_id'] = $config['id'];
                    $relation['msg_type'] = $msgType == 'message_center' ? 1 : 2;
                    $relation['unread_count'] = $item['unreadCount'];
                    $relation['read_status'] = $item['readStat'];
                    $relation['last_msg_id'] = $item['lastMessageId'];
                    $relation['last_msg_content'] = $item['lastMessageContent'];
                    $relation['last_is_own'] = $item['lastMessageIsOwn'] ? 1 : 0;
                    $relation['child_name'] = isset($item['childName']) ? $item['childName'] : '';
                    $relation['msg_time'] = substr($item['messageTime'], 0, 10);
                    $relation['child_id'] = $item['childId'];
                    $relation['other_name'] = $item['otherName'];
                    $relation['other_login_id'] = $item['otherLoginId'];
                    $relation['deal_status'] = isset($item['dealStat']) ? $item['dealStat'] : 2;
                    $relation['rank'] = $item['rank'];
                    $relation['owner_id'] = $owner_id;
                    $relation['has_order'] = 0;
                    $relation['has_product'] = 0;
                    $relation['has_other'] = 0;
                    //获取消息明细
                    $detailList = [];
                    $details = $msgObj->getAllDetails($item['channelId'], $msgType);
                    if (!empty($details)) {
                        foreach ($details as $v) {
                            //检测是否已存在
                            /* $detailInfo = AliexpressMsgDetail::where(['msg_id'=>$v['id']])->find();
                              if(!empty($detailInfo)){
                              break;
                              } */
                            if (Cache::store('AliexpressMsgDetail')->isHas($v['id'])) {
                                continue;
                            }
                            switch ($v['messageType']) {
                                case 'order':
                                    $relation['has_order'] = 1;
                                    break;
                                case 'product':
                                    $relation['has_product'] = 1;
                                    break;
                                default:
                                    $relation['has_other'] = 1;
                                    break;
                            }

                            $detailList[] = [
                                'channel_id' => $item['channelId'],
                                'msg_id' => $v['id'],
                                'gmt_create' => substr($v['gmtCreate'], 0, 10),
                                'sender_name' => $v['senderName'],
                                'sender_login_id' => isset($v['summary']['senderLoginId']) ? $v['summary']['senderLoginId'] : '',
                                'receiver_name' => isset($v['summary']['receiverName']) ? $v['summary']['receiverName'] : '',
                                'message_type' => $v['messageType'],
                                'type_id' => isset($v['typeId']) ? number_format($v['typeId'], 0, '', '') : 0,
                                'content' => $v['content'] ? $v['content'] : '',
                                'file_path' => json_encode($v['filePath']),
                                'summary' => json_encode($v['summary']),
                            ];
                        }
                    }
                    $inData['relation'] = $relation;
                    $inData['detail'] = $detailList;
                    $model->add($inData);
                }
                $page++;
                sleep(10);
            }
        } while (count($data) == $pageSize);
    }

}
