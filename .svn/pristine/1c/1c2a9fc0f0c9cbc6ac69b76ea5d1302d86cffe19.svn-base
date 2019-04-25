<?php
namespace app\customerservice\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\ebay\EbayFeedback;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayMessage;
use app\common\model\ebay\EbayMessageGroup;
use app\common\service\Common;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\EbayFeedbackByOrderLineItemId;
use app\customerservice\queue\EbayFeedbackLeftQueue;
use app\index\service\DeveloperService;
use DTS\eBaySDK\Trading\Services\TradingService;
use DTS\eBaySDK\Trading\Types\GetFeedbackRequestType;
use DTS\eBaySDK\Trading\Types\LeaveFeedbackRequestType;
use DTS\eBaySDK\Trading\Types\RespondToFeedbackRequestType;
use think\Request;
use think\Db;
use service\ebay\EbayFeedbackApi;
use app\common\model\Order as OrderModel;
use app\common\model\OrderPackage as OrderPackageModel;
use app\common\model\OrderDetail as OrderDetailModel;
use app\common\model\ebay\EbayFeedback as EbayFeedbackModel;
use app\common\model\MsgTemplate as MsgTemplateModel;
use erp\AbsServer;
use app\common\model\OrderSourceDetail as OrderSourceDetailModel;
use app\common\model\ebay\EbayAccount as EbayAccountModel;
use app\common\service\ChannelAccountConst;
use think\Exception;

/**
 * Created by tb
 * User: PHILL
 * Date: 2016/12/6
 * Time: 18:14
 */
class EbayFeedbackHelp extends AbsServer
{

    /** @var string 默认回评方式 */
    public $default_leaved_comment_type = 'Positive';

    public function lists($params, $page, $pageSize)
    {
        //获取where条件；
        $where = $this->getCondition($params);

        $count = EbayFeedbackModel::where($where)->count();
        $field = '*';
        $list = EbayFeedbackModel::field($field)->where($where)->page($page, $pageSize)->order('comment_time desc')->select();

        $data = [];
        if(!empty($list)){
            foreach($list as $k=>$v){
                $tmp = [
                    'id' => $v['id'],
                    'feedback_id' => $v['feedback_id'],
                    'order_number' => $v['order_number'],
                    'item_id'=>$v['item_id'],
                    'comment_buyer' => $v['comment_user'],
                    'pay_time' => empty($v['pay_time'])?'':date('Y-m-d H:i:s',$v['pay_time']),
                    'comment_type' => $v['comment_type'],
                    'comment_time_buyer' => empty($v['comment_time'])?'':date('Y-m-d H:i:s',$v['comment_time']),    //评价时间；
                    'comment_time_seller'=> empty($v['leaved_comment_time'])?'':date('Y-m-d H:i:s',$v['leaved_comment_time']),
                    'comment_text_buyer' => $v['comment_text'], //评价内容
                    'comment_text_seller' => $v['leaved_comment_text'],  //回评；
                    'leaved_follow_up' => $v['leaved_follow_up'],
                    'auto_send_status' => $v['auto_send'],
                    'handel_status' => $v['handel_status'],
                    'handel_time' => empty($v['handel_time'])?'':date('Y-m-d H:i:s',$v['handel_time']),
                    'status' => $v['status'],
                    'response_status' => $v['response_status'],
                    'is_refund' => '-',
                    'is_reissue' => '-',
                    'is_return' => '-',
                    'handel_status_str' => EbayFeedbackModel::$HANDEL_STATUS[$v['handel_status']]
                ];

                if (!empty($v['follow_up'])) {
                    $tmp['comment_text_buyer'] .= '【买家追评】'. $v['follow_up'];
                }

                if (!empty($v['leaved_follow_up'])) {
                    $tmp['comment_text_seller'] .= '【卖家追评】'. $v['leaved_follow_up'];
                }
                $data[] = $tmp;
            }
        }

        //重新统计数量
        unset($where['status']);//不需要刷选自身的值
        $statistics = $this->statusStatistics($where);

        $result = [
            'data' => $data,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
            'statistics' => $statistics
        ];
        return $result;
    }

    public function getCondition($params)
    {

        $where=[];
        $where['feedback_id'] = ['>', 0];
        //回评状态 0：未回评  ，1：已回评   , 2: 回评中 ，3 ：回评失败, 前端给的数据是加了1的；
        if(!empty($params['status']) && in_array($params['status'], [1,2,3,4])){
            $where['status']= $params['status']-1;
        }

        //评价状态 [get-param 1-好评 2-中评 3-差评 4-已修改评价  99-等待买家评价]
        if(!empty(param($params, 'comment_type'))){
            if($params['comment_type']==99){
                $where['feedback_id'] = 0;
            }elseif($params['comment_type']==4){
                $where['comment_replaced']=['EQ',1];
            }else{
                $where['comment_type']=['EQ',$params['comment_type']];
            }
        }
        //跟进状态[get-param 1-需要处理 2-完成处理]
        if(!empty(param($params, 'handel_status')) && in_array($params['handel_status'], [1,2])){
            $where['handel_status']=$params['handel_status'];
        }

        //search
        if(!empty(param($params, 'search_key')) && !empty(param($params, 'search_val'))){
            $where[$params['search_key']]=['EQ',$params['search_val']];
        }

        //买家留评价时间
        $b_time = !empty(param($params, 'date_b'))?strtotime($params['date_b'].' 00:00:00'):'';
        $e_time = !empty(param($params, 'date_e'))?strtotime($params['date_e'].' 23:59:59'):'';

        if($b_time && $e_time){
            $where['comment_time']  =  ['BETWEEN', [$b_time, $e_time]];
        }elseif ($b_time) {
            $where['comment_time']  = ['EGT',$b_time];
        }elseif ($e_time) {
            $where['comment_time']  = ['ELT',$e_time];
        }

        //账号
        if(!empty(param($params, 'customer_id'))){
            //通过客服id找到所管理ebay账号id
            $developerService = new DeveloperService();
            $acountids = Cache::store('User')->getCustomerAccount($params['customer_id'],1);
            if($acountids){
                $where['account_id'] =  $whereMes['account_id']= ['in',$acountids];
            }else{
                $where['account_id'] =  -1;
            }
        }
        return $where;
    }


    /**
     * 拿取评价详情；
     * @param $id
     */
    public function feedbackDetail($id)
    {
        $fModel = new EbayFeedbackModel();
        $feedback = $fModel->where(['id' => $id])->find();
        if (empty($feedback)) {
            throw new Exception('参数无效，评价不存在');
        }

        //把买家追评放在买家评价后面；
        $feedback['comment_text_buyer'] = $feedback['comment_text'];
        if (!empty($feedback['follow_up'])) {
            $feedback['comment_text_buyer'] .= '【买家追评】'. $feedback['follow_up'];
        }

        //评价级别
        $feedback['comment_type_str'] = !empty($feedback['comment_type']) ? EbayFeedbackModel::$COMMENT_TYPE[$feedback['comment_type']] : '';//评价类型文字描述
        $feedback['handel_str'] = EbayFeedbackModel::$HANDEL_STATUS[$feedback['handel_status']];//处理状态文字描述

        //给买家回评
        $feedback['response'] = $feedback['leaved_comment_text'];
        //跟进
        $feedback['followup'] = $feedback['leaved_follow_up'];
        unset($feedback['reply_text'], $feedback['follow_up']);

        $feedback['messageLists'] = $this->getMessageListByFeedback($feedback);

        return $feedback;
    }


    /**
     * 根据评价找站内信
     * @param $feedback
     * @return array
     * @throws Exception
     */
    public function getMessageListByFeedback($feedback)
    {
        //找出站内信列表；
        $account = Cache::store('EbayAccount')->getTableRecord($feedback['account_id']);
        if (empty($account)) {
            throw new Exception('Ebay评价记录错误，Ebay帐号为空');
        }
        $where['item_id'] = $feedback['item_id'];
        $where['sender_user'] = $feedback['comment_user'];
        $where['receive_user'] = $account['account_name'];
        $where['account_id'] = $feedback['account_id'];

        $sort = []; //用来排序；
        $itemIds = [];
        $messageLists = []; //装最终结果；
        $message_field = 'id,message_id,sender,account_id,sender,send_to_name,replied,message_type,send_time,item_id,subject,level,flag_id,status,message_text as text,media_info';

        //1. 先查找对应的分组在不在，如果对应的分组存在，则买家信息存在
        $group = EbayMessageGroup::where($where)->field('id')->find();
        $messageModel = new EbayMessage();
        if (!empty($group)) {
            $allMessageLists = $messageModel->where(['group_id' => $group['id']])
                ->field($message_field)
                ->select();
            if (!empty($allMessageLists)) {
                foreach ($allMessageLists as $message) {
                    $sort[$message['message_id']] = $message['send_time'];
                    $itemIds[] = $message['item_id'];
                    $messageLists[$message['message_id']] = $message->toArray();
                }
            }
        }

        //找出卖家发送；
        $buyerMessageLists = $messageModel->where([
            'group_id' => 0,
            'item_id' => $where['item_id'],
            'sender' => $where['receive_user'],
            'send_to_name' => $where['sender_user'],
        ])
            ->field($message_field)
            ->select();
        if (!empty($buyerMessageLists)) {
            $mids = [];
            foreach ($buyerMessageLists as $message) {
                $mids = $message['id'];
                $sort[$message['message_id']] = $message['send_time'];
                $itemIds[] = $message['item_id'];
                $messageLists[$message['message_id']] = $message->toArray();
            }
            //更新卖家评价；
            if (!empty($group)) {
                $messageModel->update(['group_id' => $group['id']], ['id' => ['in', $mids]]);
            }
        }

        $itemIds = array_merge(array_filter(array_unique($itemIds)));
        $itemImgs = [];
        if (!empty($itemIds)) {
            $itemImgs = Db::name("ebay_listing")->field('img')
                ->where(['item_id' => ['in', $itemIds]])
                ->column('img', 'item_id');
        }
        //下面进行排序；
        $newLists = [];
        if (!empty($sort)) {
            arsort($sort);
            foreach ($sort as $message_id=>$send_time) {
                $tmp = $messageLists[$message_id];
                $tmp['item_image'] = $itemImgs[$tmp['item_id']] ?? '';
                $newLists[] = $tmp;
            }
        }

        return $newLists;
    }


    /**
     * 通过交易号，获取订单信息
     *
     * @param string $order_id
     * @param number $detail
     *            0-不获取详细 1-获取相关详细
     */
    public function getOrderinfo($order_id, $detail = 0)
    {
        $channel_id = 1;
        // 不查找详细，只查找order表
        if (! $detail) {
            return OrderModel::field('id,order_number,pay_time,buyer_id')->where([
                'id' => $order_id,
                'channel_id' => $channel_id
            ])->find();
        }

        // 订单信息
        $where['a.id'] = $order_id;
        $field = 'a.id as order_id,a.order_number,a.buyer_id,a.pay_fee,a.shipping_fee,a.insure_fee,a.invoice_fee,
            a.order_amount,b.source_address';
        $orderModel = new OrderModel();
        $order_info = $orderModel->alias('a')
            ->field($field)
            ->join('order_address b', 'a.id = b.order_id', 'left')
            ->where($where)
            ->find();

        if (empty($order_info)) {
            return [];
            throw new Exception('该订单信息不存在');
        }

        $order_info['source_address'] = ! empty($order_info['source_address']) ? json_decode($order_info['source_address'], true) : [];

        // 仓库信息
        $warehouseList = Cache::store('warehouse')->getWarehouse();

        // 查看发货仓库
        $package_list = [];
        $orderPackageModel = new OrderPackageModel();
        $packageList = $orderPackageModel->field('id,shipping_name,warehouse_id')
            ->where([
            'order_id' => $order_info['order_id']
        ])
            ->select();
        foreach ($packageList as $k => $v) {
            $v['id'] = $v['id'] . '';
            $temp = $v;
            $temp['warehouse_name'] = isset($warehouseList[$v['warehouse_id']]['name']) ? ! empty($warehouseList[$v['warehouse_id']]['name']) ? $warehouseList[$v['warehouse_id']]['name'] : '' : '';
            unset($temp['warehouse_id']);
            $package_list[$k] = $temp;
        }

        $order_info['package_list'] = $package_list;

        // 获取商品信息
        $orderDetailModel = new OrderDetailModel();
        $orderDetailModel = new OrderSourceDetailModel();
        $detailList = $orderDetailModel->field('channel_sku as sku,channel_sku_title as sku_title,channel_sku_quantity as sku_quantity,channel_item_id as item_id')
            ->where([
            'order_id' => $order_info['order_id']
        ])
            ->select();
        $order_info['detail_goods'] = $detailList;
        $order_info['order_id'] = strval($order_info['order_id']);
        return $order_info;
    }

    /**
     * 获取评价模板内容
     *
     * @param type $orderId
     * @param type $tmpId
     * @return type
     */
    public function getEvaluateTmpContent($transaction_id = '', $tmpId = '', $isRandom)
    {
        if ($isRandom) {
            $where['template_type'] = 2;
            $where['channel_id'] = 1;
            $tmp = MsgTemplateModel::field('id')->where($where)
                ->order('rand()')
                ->find();

            $tmpId = $tmp['id'];
        }

        $data = [];
        if ($transaction_id) {
            // 获取订单信息
            // new OrderModel()
            $data = OrderModel::field('buyer')->where([
                'transaction_id' => $transaction_id,
                'channel_id' => 1
            ])->find();
        }

        $tmpServer = new MsgTemplateHelp();
        $content = $tmpServer->getTplFieldContent($tmpId, $data);
        return $content;
    }

    /**
     * 分账号获取评价信息
     */
    public function getEbayFeedback()
    {
        $accountList = Cache::store('EbayAccount')->getTableRecord();
        foreach ($accountList as $k => $v) {
            $token = $v['token'];
            if ($v['sync_feedback'] > 0 && ! empty($token) && $v['is_invalid'] == 1) {
                $this->downFeedback($v['id']);
                sleep(2);
            }
        }

        return true;
    }


    /**
     * 根据帐号ID和下载时间设置，算出最后根新的时间范围
     * @param $account_id
     * @param $down_time
     * @return false|int|mixed
     */
    public function getRequestEndTime($account_id, $down_time)
    {
        //有设置下载时间，则使用下载时间，没有则使用缓存的时间；
        if (!empty($down_time)) {
            $endTime = strtotime('-'. $down_time. ' days');
        } else {
            // 最后更新时间
            $last_update = Cache::store('EbayAccount')->ebayLastUpdateTime($account_id, 'feedback');
            // 距离上次时间不能超过7天
            if (!empty(isset($last_update['last_update_time']))) {
                if (time() - strtotime($last_update['last_update_time']) < 3600 * 24 * 7) {
                    $endTime = strtotime($last_update['last_update_time']);
                } else {
                    $endTime = strtotime("-7 day");
                }
            } else {
                //查出此用户最后抓取的消息；
                $endFeed = EbayFeedbackModel::where(['account_id' => $account_id, 'feedback_id' => ['<>', 0]])->order('comment_time desc')->limit(1)->find();
                if (!empty($endFeed)) {
                    $endTime = ($endFeed['comment_time'] == 0)? strtotime("-7 day") : $endFeed['comment_time'];
                } else {
                    $endTime = strtotime("-7 day");
                }
                unset($endFeed);
            }
        }
        return $endTime;
    }


    /** @var array 用来装漏抓回评的orderLineItemId */
    private $oltId = [];

    /**
     * 下载做为卖家接收到的评价；
     * @param $account_id
     * @param int $down_time
     * @return bool
     * @throws Exception
     */
    public function FeedBackReceivedAsSeller($account_id, $down_time = 0)
    {
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = $this->getAccountParmas($account);
        $service = new TradingService($config);

        $request = new GetFeedbackRequestType([
            'DetailLevel' => ['ReturnAll'], //repeatable 是可重复的，如果为True,则应该是数组；
            'FeedbackType' => 'FeedbackReceivedAsSeller',
            'Pagination' => [
                'PageNumber' => 1,
                'EntriesPerPage' => 200,
            ]
        ]);

        $endTime = $this->getRequestEndTime($account_id, $down_time);

        $commentTime = 0;
        $total = 0;
        $page = 1;
        $this->oltId = [];
        do {
            $request->Pagination->PageNumber = $page;
            $response = $service->getFeedback($request)->toArray();//如果在没有Ack返回，或者返回为失败时抛出异常；

            $res = $this->getFeedbackRequestData($response);
            //为空返回false,停止;
            if (empty($res)) {
                return $total;
            }
            //处理接收到的数据；
            $total += $this->handleReceiveData($account_id, $res, $endTime, $commentTime);

            //页码+1；
            $page++;

            if($commentTime < ($endTime - 3600 * 5) || $commentTime == 0) {
                break;
            }

            //判断还有下一页数据没有；
            if(!isset($response['PaginationResult']['TotalNumberOfPages']) || $response['PaginationResult']['TotalNumberOfPages'] < $page) {
                break;
            }
            unset($res, $response);
        } while(true);

        //被抓一下漏掉的回评；
        //$this->FeedbackMissingByOltId($account_id, $this->oltId);

        return $total;
    }


    /**
     * 下载做为卖家发出去的评价
     * @param $account_id
     * @param int $down_time
     * @return bool
     * @throws Exception
     */
    public function FeedBackLeftAsSeller($account_id, $down_time = 0)
    {
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = $this->getAccountParmas($account);
        $service = new TradingService($config);

        $request = new GetFeedbackRequestType([
            'DetailLevel' => ['ReturnAll'], //repeatable 是可重复的，如果为True,则应该是数组；
            'FeedbackType' => 'FeedbackLeft',
            'Pagination' => [
                'PageNumber' => 1,
                'EntriesPerPage' => 200,
            ]
        ]);

        $endTime = $this->getRequestEndTime($account_id, $down_time);

        //因为是回评，down_time是特地推的，所以将时间提前两个月,正常抓就不用了；
        if ($down_time > 0) {
            $endTime = $endTime - 86400 * 60;
        }

        $commentTime = 0;
        $total = 0;
        $page = 1;
        do {
            $request->Pagination->PageNumber = $page;
            $response = $service->getFeedback($request)->toArray();//如果在没有Ack返回，或者返回为失败时抛出异常；

            $res = $this->getFeedbackRequestData($response);

            //为空返回false,停止;
            if (empty($res)) {
                return $total;
            }

            //处理接收到的数据；
            $total += $this->handleLeftData($account_id, $res, $endTime, $commentTime);

            //页码+1；
            $page++;

            if($commentTime < ($endTime - 3600 * 5) || $commentTime == 0) {
                break;
            }

            //判断还有下一页数据没有；
            if(!isset($response['PaginationResult']['TotalNumberOfPages']) || $response['PaginationResult']['TotalNumberOfPages'] < $page) {
                break;
            }
            unset($res, $response);
        } while(true);

        return $total;
    }


    public function FeedbackMissingByOltId($account_id, $orderLineItemIds = [])
    {
        if (empty($orderLineItemIds)) {
            return 0;
        }

        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = $this->getAccountParmas($account);
        $service = new TradingService($config);

        $total = 0;
        foreach ($orderLineItemIds as $orderLineItemId) {
            //--------------- 发出去的买家评价；
            $request = new GetFeedbackRequestType([
                'DetailLevel' => ['ReturnAll'], //repeatable 是可重复的，如果为True,则应该是数组；
                'FeedbackType' => 'FeedbackLeft',
                'OrderLineItemID' => $orderLineItemId
            ]);

            $response = $service->getFeedback($request)->toArray();//如果在没有Ack返回，或者返回为失败时抛出异常；
            $res = $this->getFeedbackRequestData($response);
            //为空返回false,停止;
            if (!empty($res)) {
                //处理接收到的数据；
                $total += $this->handleLeftData($account_id, $res);
            }
            unset($request, $response, $res);
        }

        return $total;
    }


    /**
     * 根据orderLineItemId来抓取评价；
     * @param $account_id
     * @param $feedback_id
     * @return bool
     * @throws Exception
     */
    public function FeedBackByFeedbackOltId($account_id, $orderLineItemId)
    {
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = $this->getAccountParmas($account);
        $service = new TradingService($config);

        //-------------- 接收到的卖家评价；
        $request = new GetFeedbackRequestType([
            'DetailLevel' => ['ReturnAll'], //repeatable 是可重复的，如果为True,则应该是数组；
            'FeedbackType' => 'FeedbackReceivedAsSeller',
            'OrderLineItemID' => $orderLineItemId
        ]);

        $response = $service->getFeedback($request)->toArray();//如果在没有Ack返回，或者返回为失败时抛出异常；
        $res = $this->getFeedbackRequestData($response);
        //为空返回false,停止;
        if (!empty($res)) {
            //处理接收到的数据；
            $this->handleReceiveData($account_id, $res);
        }


        //--------------- 发出去的买家评价；
        $request = new GetFeedbackRequestType([
            'DetailLevel' => ['ReturnAll'], //repeatable 是可重复的，如果为True,则应该是数组；
            'FeedbackType' => 'FeedbackLeft',
            'OrderLineItemID' => $orderLineItemId
        ]);

        $response = $service->getFeedback($request)->toArray();//如果在没有Ack返回，或者返回为失败时抛出异常；
        $res = $this->getFeedbackRequestData($response);
        //为空返回false,停止;
        if (!empty($res)) {
            //处理接收到的数据；
            $this->handleLeftData($account_id, $res);
        }
    }


    /**
     * 根据orderLineItemId来抓取评价；
     * @param $account_id
     * @param $feedback_id
     * @return bool
     * @throws Exception
     */
    public function FeedBackLeftByOltId($account_id, $orderLineItemId)
    {
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = $this->getAccountParmas($account);
        $service = new TradingService($config);

        //--------------- 发出去的买家评价；
        $request = new GetFeedbackRequestType([
            'DetailLevel' => ['ReturnAll'], //repeatable 是可重复的，如果为True,则应该是数组；
            'FeedbackType' => 'FeedbackLeft',
            'OrderLineItemID' => $orderLineItemId
        ]);

        $response = $service->getFeedback($request)->toArray();//如果在没有Ack返回，或者返回为失败时抛出异常；
        $res = $this->getFeedbackRequestData($response);
        //为空返回false,停止;
        if (!empty($res)) {
            //处理接收到的数据；
            $this->handleLeftData($account_id, $res);
        }
    }


    /**
     * 分析出最后评价的数据；
     * @param $response
     * @return array
     * @throws Exception
     */
    public function getFeedbackRequestData($response) {
        if(!isset($response['Ack']) || $response['Ack'] =='Failure') {
            throw new Exception('抓取ebayFeedback出错：'. json_encode($response));
        }

        //没有feedback反回结束；
        if (empty($response['FeedbackDetailArray']['FeedbackDetail'])) {
            return [];
        }

        $res = isset($response['FeedbackDetailArray']['FeedbackDetail']['FeedbackID'])? [$response['FeedbackDetailArray']['FeedbackDetail']] : $response['FeedbackDetailArray']['FeedbackDetail'];
        return $res;
    }


    /**
     * @title 处理接收到的卖家评价数据入库；
     * @param $account_id
     * @param $lists
     * @param int $endTime
     * @param int $commentTime
     * @return int
     */
    public function handleReceiveData($account_id, $lists, $endTime = 0, &$commentTime = 0)
    {
        //状态映射；
        $commentTypeArr = ['Positive' => 1, 'Neutral' => 2, 'Negative' => 3];
        $statusArr = ['Positive' => 0, 'Neutral' => 1, 'Negative' => 1];

        $ebayFeedbackModel = new EbayFeedbackModel();

        //装交易ID
        $tids = [];
        $result = [];

        foreach ($lists as $vo) {
            //评价时间；
            $commentTime = (int)strtotime($vo['CommentTime']);

            //重复下载2个小时的评论，防止漏单；
            if($commentTime > 0 && $endTime > 0 && $commentTime < ($endTime - 3600 * 5)) {
                break;
            }

            $transaction_id = isset($vo['TransactionID']) ? $vo['TransactionID'] : 0;

            //找出旧数据；
            $old = $ebayFeedbackModel->where([
                'account_id' => $account_id,
                'item_id' => $vo['ItemID'],
                'transaction_id' => $transaction_id,
            ])->find();
            //echo '买评：'. $vo['CommentTime']. '-'. $vo['FeedbackID']. '-'. ($old['id'] ?? 0). "\r\n";

            //评分有无被更换；
            $comment_replaced = 0;
            if (
                isset($vo['CommentReplaced']) &&
                ($vo['CommentReplaced'] == 'ture' || $vo['CommentReplaced'] === 1 || $vo['CommentReplaced'] === true)
            ) {
                $comment_replaced = 1;
            }

            $tmp = [];

            $tmp['id'] = empty($old['id'])? 0 : $old['id'];
            $tmp['feedback_id'] = $vo['FeedbackID'];

            $tmp['comment_user'] = $vo['CommentingUser'];
            //评价相关内容，要更新；
            $tmp['comment_text'] = $vo['CommentText'];
            $tmp['comment_time'] = $commentTime;

            //回复和跟进；
            $tmp['reply_text'] = $vo['FeedbackResponse'] ?? '';
            $tmp['follow_up'] = $vo['Followup'] ?? '';

            $tmp['transaction_id'] = $transaction_id;
            $tmp['item_id'] = $vo['ItemID'];
            $tmp['account_id'] = $account_id;

            $tmp['comment_replaced'] = $comment_replaced;
            $tmp['comment_type'] = $commentTypeArr[$vo['CommentType']] ?? 0;
            $tmp['handel_status'] = $statusArr[$vo['CommentType']] ?? 1;
            $tmp['update_time'] = time();

            //老数据不存在；
            if (empty($old['id'])) {
                //这三个是评价查找的基础，没记录，肯定是不存在的，要加
                $tmp['create_time'] = time();

                //回评状态，没记录，肯定是没有回评的；
                $tmp['status'] = 0;

                array_push($this->oltId, $vo['OrderLineItemID']);
            } else {
                //$tmp['status'] = 1;//有记录，是有无回评状态的，不需要更新；
                if (empty($old['leaved_comment_time'])) {
                    array_push($this->oltId, $vo['OrderLineItemID']);
                }
            }

            $result[] = $tmp;
            if (empty($old['order_id']) && !empty($transaction_id)) {
                $tids[] = $transaction_id;
            }
        }

        # 关联本地订单ID开始
        $systemOrder = [];
        if($tids) {
            $tids = array_merge(array_unique($tids));
            $systemOrder = Db::view('order_source_detail','transaction_id')
                ->view('order','id,order_number,pay_time,buyer_id','order_source_detail.order_id=order.id')
                ->where('order_source_detail.transaction_id','in',$tids)
                ->select();
            $systemOrder = empty($systemOrder)? [] : array_combine(array_column($systemOrder, 'transaction_id'), $systemOrder);
        }

        foreach ($result as $key=>&$val) {
            if (isset($systemOrder[$val['transaction_id']])) {
                $tempArr = $systemOrder[$val['transaction_id']] ?? [];
                $val['order_id'] = param($tempArr, 'id', 0);
                $val['order_number'] = param($tempArr, 'order_number');
                $val['pay_time'] = param($tempArr, 'pay_time', 0);
                $val['comment_user'] = empty($val['comment_user']) ? param($tempArr, 'buyer_id') : $val['comment_user'];
            }
        }
        unset($val);
        # 关联本地订单ID 结束

        //保存数据；
        $ebayFeedbackModel->addAll($result);


        return count($result);
    }


    /**
     * @tital 处理买家发出的评价数据入库
     * @param $account_id
     * @param $lists
     * @param int $endTime
     * @param int $commentTime
     * @return int
     */
    public function handleLeftData($account_id, $lists, $endTime = 0, &$commentTime = 0)
    {
        //状态映射；
        $commentTypeArr = ['Positive' => 1, 'Neutral' => 2, 'Negative' => 3];

        $ebayFeedbackModel = new EbayFeedbackModel();

        //装交易ID
        $tids = [];
        $result = [];

        foreach ($lists as $vo) {

            //紧后时间；
            $commentTime = (int)strtotime($vo['CommentTime']);

            //重复下载2个小时的评论，防止漏单；
            if($commentTime > 0 && $endTime > 0 && $commentTime < ($endTime - 3600 * 5)) {
                break;
            }

            if ($vo['Role'] !== 'Buyer') {
                continue;
            }

            $itemId =  $vo['ItemID'];
            $transaction_id =  isset($vo['TransactionID']) ? $vo['TransactionID'] : 0;

            //找出原买家评论数据；
            $old = $ebayFeedbackModel->where([
                'account_id' => $account_id,
                'transaction_id' => $transaction_id,
                'item_id' => $itemId
            ])->find();
            //echo '卖评：'. $vo['CommentTime']. '-'. $vo['FeedbackID']. '-'. ($old['id'] ?? 0). "\r\n";

            $tmp['leaved_feedback_id'] = $vo['FeedbackID'];
            $tmp['leaved_comment_text'] = $vo['CommentText'];
            $tmp['leaved_comment_type'] = $commentTypeArr[$vo['CommentType']] ?? 0;
            $tmp['leaved_comment_time'] = $commentTime;
            $tmp['transaction_id'] = $transaction_id;
            $tmp['account_id'] = $account_id;
            $tmp['item_id'] = $vo['ItemID'];


            //回复和跟进；
            $tmp['leaved_reply_text'] = $vo['FeedbackResponse'] ?? '';
            $tmp['leaved_follow_up'] = $vo['Followup'] ?? '';

            //当前抓到的时回评，所以status状态是1；
            $tmp['status'] = EbayFeedbackModel::FINSH_EVALUATE;
            //如果有追评，则为2，没追评，则是1；
            if (!empty($vo['Followup'])) {
                $tmp['response_status'] = 2;
            } else {
                $tmp['response_status'] = 1;
            }

            $tmp['id'] = empty($old['id'])? 0 : $old['id'];

            $tmp['update_time'] = time();



            //老数据不存在；
            if (empty($old['id'])) {
                //这三个是评价查找的基础，没记录，肯定是不存在的，要加
                //$tmp['item_id'] = $vo['ItemID'];
                //$tmp['comment_user'] = $vo['CommentingUser']; //有时会对不上，有时是买家，有时是卖家；
                //$tmp['transaction_id'] = $transaction_id;
                $tmp['create_time'] = time();
            }

            $result[] = $tmp;
            if (empty($old['order_id']) && !empty($transaction_id)) {
                $tids[] = $transaction_id;
            }
        }

        # 关联本地订单ID开始
        $systemOrder = [];
        if($tids) {
            $tids = array_merge(array_unique($tids));
            $systemOrder = Db::view('order_source_detail','transaction_id')
                ->view('order','id,order_number,pay_time,buyer_id','order_source_detail.order_id=order.id')
                ->where('order_source_detail.transaction_id','in',$tids)
                ->select();
            $systemOrder = empty($systemOrder)? [] : array_combine(array_column($systemOrder, 'transaction_id'), $systemOrder);
        }

        foreach ($result as $key=>&$val) {
            if (isset($systemOrder[$val['transaction_id']])) {
                $tempArr = $systemOrder[$val['transaction_id']] ?? [];
                $val['order_id'] = param($tempArr, 'id', 0);
                $val['order_number'] = param($tempArr, 'order_number');
                $val['pay_time'] = param($tempArr, 'pay_time', 0);
                $val['comment_user'] = empty($val['comment_user']) ? param($tempArr, 'buyer_id') : $val['comment_user'];
            }
        }
        unset($val);
        # 关联本地订单ID 结束

        //保存数据；
        $ebayFeedbackModel->addAll($result);

        return count($result);
    }

    /*
     * 根据帐号组成请求授权参数；
     */
    public function getAccountParmas($account)
    {
        if (empty($account)) {
            throw new Exception('Ebay帐号为空');
        }
        if (empty($account['token'])) {
            throw new Exception('Ebay帐号token为空，请检查授权');
        }
        $config = [
            'apiVersion'  => '1019',
            'siteId' => 0,
            'authToken' => $account['token'],
            'credentials' => [
                'appId'  => $account['app_id'],
                'certId' => $account['cert_id'],
                'devId'  => $account['dev_id'],
            ]
        ];
        return $config;
    }


    /**
     * 更新店铺评分
     *
     * @param number $account_id
     * @param number $feedback_score
     * @param number $positive_feedback_percent
     */
    function updateAccountScore($account_id = 0, $feedback_score = 0, $positive_feedback_percent = 0)
    {
        $model = new EbayAccountModel();
        $data['feedback_score'] = $feedback_score;
        $data['positive_feedback_percent'] = $positive_feedback_percent;
        return $model->update($data, [
            'id' => $account_id
        ]);
    }

    /**
     * 自动留评价
     *
     * @param unknown $data
     * @return boolean
     */
    function leaveFeedbackAuto($id)
    {
        if (empty($id)) {
            return false;
        }
        $feedbackmodel = new EbayFeedbackModel();

        // 获取评论信息
        $field = '*';
        $feedback = $feedbackmodel->where(['id' => $id])->field($field)->find();

        if (empty($feedback)) {
            return false;
        }
        if (empty($feedback['account_id']) || empty($feedback['account_id']) || empty($feedback['item_id']) || empty($feedback['transaction_id'])) {
            return $feedbackmodel->update(['status' => EbayFeedback::FAIL_EVALUATE], ['id' => $id]);
        }
        //回评完成的
        if ($feedback['status'] === 1) {
            return true;
        }
        //回复内容为空的；
        if (empty($feedback['leaved_comment_text'])) {
            return $feedbackmodel->update(['status' => EbayFeedback::WAIT_EVALUATE], ['id' => $id]);
        }
        $commentTypeArr = [1 => 'Positive', 'Neutral', 'Negative'];
        $leaved_data = [
            'id' => $id,
            'transaction_id' => $feedback['transaction_id'],
            'comment_user' => $feedback['comment_user'],
            'item_id' => $feedback['item_id'],
            'leaved_comment_text' => $feedback['leaved_comment_text'],
            'leaved_comment_type' => $commentTypeArr[$feedback['leaved_comment_text']] ?? 'Positive'
        ];

        $result = $this->leaveFeedbackApi($feedback['account_id'], $leaved_data);

        $update['update_time'] = time();
        $userInfo = Common::getUserInfo();
        $update['update_id'] = $userInfo['user_id'];
        $cache = [
            'user' => Common::getUserInfo(),
            'leaved_data' => $leaved_data,
            'result' => $result,
        ];

        if ($result['Ack'] == 'Success') {
            $update['leaved_feedback_id'] = $result['FeedbackID'];
            $update['leaved_comment_time'] = strtotime($result['Timestamp']);
            $update['status'] = EbayFeedbackModel::FINSH_EVALUATE;
            $update['response_status'] = 1;
            $feedbackmodel->update($update, ['id' => $id]);
            return true;
        } else {
            $update['status'] = EbayFeedbackModel::FAIL_EVALUATE;
            $feedbackmodel->update($update, ['id' => $id]);
            if (!empty($result['Errors'][0]['ErrorCode']) && $result['Errors'][0]['ErrorCode'] == 55) {
                return false;
            }
            return false;
        }
    }


    /**
     * @title 加锁执行回评；
     */
    public function leaveFeedbackLockRun($data)
    {
        $lockCache = Cache::store('Lock');
        if ($lockCache->uniqueLock($data)) {
            try {
                $result = $this->leaveFeedback($data);
            } catch (Exception $e) {
                $lockCache->unlock($data);
                throw new Exception($e->getMessage());
            }
            $lockCache->unlock($data);
            return $result;
        } else {
            throw new Exception('正在执行中，不可以重复提交');
        }
    }

    /**
     * 评论
     * @param array $data
     *            [id, transaction_id,order_id,text]
     * @throws Exception
     * @return boolean
     */
    function leaveFeedback($data)
    {
        if (empty($data['text'])) {
            throw new Exception('评论内容为空');
        }
        if (mb_strlen($data['text']) > 80) {
            throw new Exception('评论内容太长，不能超过80个字符');
        }
        $ebayFeedbackModel = new EbayFeedbackModel();
        $account_id = 0;
        $err_item = [];
        $success_item = [];
        $leaved_datas = [];

        if (!empty($data['order_id'])) {
            // 回评系统订单
            $orderDetailModel = new OrderSourceDetailModel();
            $field = 'd.transaction_id,d.channel_item_id as item_id,o.id,o.buyer_id,o.channel_account_id as account_id,o.order_number,o.pay_time';
            $join[] = ['order o', 'o.id = d.order_id'];
            $where['d.order_id'] = ['EQ', $data['order_id']];
            $order_datas = $orderDetailModel->alias('d')
                ->field($field)
                ->join($join)
                ->where($where)
                ->select();
            if (empty($order_datas)) {
                throw new Exception('订单不存在。');
            }
            $account_id = $order_datas[0]['account_id'];
            foreach ($order_datas as $order) {
                // 刷选是否有相同记录
                $check_feedback = EbayFeedbackModel::field('id,status')->where([
                    'transaction_id' => $order['transaction_id'],
                    'item_id' => $order['item_id']
                ])->find();

                $account_id = $order['account_id'];

                //如果有评价存在，则检查评价的状态；
                if (!empty($check_feedback['status']) && ($check_feedback['status'] > 0)) {
                    $err_item[$order['item_id']] = '已评价，不能重复评价。';
                    continue;
                }

                $leaved_datas[$order['item_id']] = [
                    'id' => empty($check_feedback) ? 0 : $check_feedback['id'],
                    'transaction_id' => $order['transaction_id'],
                    'comment_user' => $order['buyer_id'],
                    'item_id' => $order['item_id'],
                    'leaved_comment_text' => $data['text'],
                ];
            }
        } else {
            $where = [];
            if (!empty($data['id'])) {
                $where['id'] = $data['id'];
            } else if (!empty($data['id'])) {
                $where['transaction_id'] = $data['transaction_id'];
            } else {
                throw new Exception('评价参数有误。');
            }
            if (empty($where)) {
                throw new Exception('回评参数有误。');
            }
            $feedbakcInfos = EbayFeedbackModel::field('id,account_id,feedback_id,transaction_id,item_id,comment_user,status')
                ->where($where)->select();
            if (empty($feedbakcInfos)) {
                throw new Exception('评价信息不存在。');
            }

            foreach ($feedbakcInfos as $feedbakcInfo) {
                $account_id = $feedbakcInfo['account_id'];
                if ($feedbakcInfo['status'] > 0) {
                    $err_item[$feedbakcInfo['item_id']] = '已评价，不能重复评价。';
                    continue;
                }
                $leaved_datas[$feedbakcInfo['item_id']] = [
                    'id' => empty($feedbakcInfo) ? 0 : $feedbakcInfo['id'],
                    'transaction_id' => $feedbakcInfo['transaction_id'],
                    'comment_user' => $feedbakcInfo['comment_user'],
                    'item_id' => $feedbakcInfo['item_id'],
                    'leaved_comment_text' => $data['text']
                ];
            }
        }

        if (empty($leaved_datas)) {
            if (!empty($err_item)) {
                $msg = '';
                foreach ($err_item as $item_id=>$val) {
                    $msg .= 'item_id '. $item_id. ':'. $val. '';
                }
                throw new Exception($msg);
            } else {
                throw new Exception('评价失败，没有找着评价参数。');
            }
        }

        if (empty($account_id)) {
            throw new Exception('卖家账号不存在。');
        }

        $request = Request::instance();
        $userInfo = Common::getUserInfo($request);

        $commentTypeArr = ['Positive' => 1, 'Neutral' => 2, 'Negative' => 3];
        try {

            foreach ($leaved_datas as $leaved_data) {
                //调用评价接口,并且在调用后，更新一下listing；
                try {
                    //回评类别为空，默认为好评；
                    if (empty($leaved_data['leaved_comment_type'])) {
                        $leaved_data['leaved_comment_type'] = $this->default_leaved_comment_type;
                    }

                    $result = $this->leaveFeedbackApi($account_id, $leaved_data);

                    if ($result['Ack'] == 'Success') {
                        $leaved_data['leaved_feedback_id'] = $result['FeedbackID'];
                        $leaved_data['leaved_comment_time'] = strtotime($result['Timestamp']);
                        $leaved_data['status'] = EbayFeedbackModel::FINSH_EVALUATE;//已回评；
                        $update['response_status'] = 1;
                    } else {
                        //回评失败;
                        $leaved_data['status'] = EbayFeedbackModel::FAIL_EVALUATE;
                    }

                    $leaved_data['leaved_comment_type'] = $commentTypeArr[$leaved_data['leaved_comment_type']];
                    $leaved_data['update_id'] = $userInfo['user_id'];
                    $leaved_data['update_time'] = time();
                    if (empty($leaved_data['id'])) {
                        $ebayFeedbackModel->insert($leaved_data);
                    } else {
                        $ebayFeedbackModel->update($leaved_data, ['id' => $leaved_data['id']]);
                    }

                    //根据回评结果返回数据；
                    if ($result['Ack'] == 'Success') {
                        $success_item[$leaved_data['item_id']] = '回评成功。';
                    } else {
                        if (!empty($result['Errors'][0]['ErrorCode']) && $result['Errors'][0]['ErrorCode'] == 55) {
                            throw new Exception('回评失败，可能已经评价过了,正在更新本条记录。');
                        } else {
                            if (!empty($result['Errors'][0]['ShortMessage'])) {
                                throw new Exception($result['Errors'][0]['ShortMessage']);
                            } else {
                                throw new Exception('回评失败。');
                            }
                        }
                    }
                } catch (Exception $e) {
                    $err_item[$leaved_data['item_id']] = $e->getMessage();
                }
            }

            if (empty($err_item)) {
                return true;
            } else {
                $msg = '';
                foreach ($success_item as $item_id=>$val) {
                    $msg .= 'item_id '. $item_id. ':'. $val. ' ';
                }
                foreach ($err_item as $item_id=>$val) {
                    $msg .= 'item_id '. $item_id. ':'. $val. ' ';
                }
                throw new Exception($msg);
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }


    /**
     * 追评，回复评价
     * @desc 回复评价，是回复买家的评阶，追评，是追加卖家自已给买家的评价,所以feedback_id是不一样的；
     * @param int $account_id 回评的帐号ID
     * @param array $data 回评的参数；
     * @param string $type 回评的类型 Reply回复评价，FollowUp跟进；跟进好像暂时不能用；
     * @return bool
     */
    public function respondToFeedbackApi($account_id, $data, $response_type = 'Reply')
    {
        try {
            $account = Cache::store('EbayAccount')->getTableRecord($account_id);
            $config = $this->getAccountParmas($account);
            $service = new TradingService($config);

            $text = '';
            $feedback_id = '';
            switch($response_type) {
                case 'Reply':
                    $text = $data['reply_text'];
                    $feedback_id = $data['feedback_id'];
                    break;
                case 'FollowUp':
                    $text = $data['leaved_follow_up'];
                    $feedback_id = $data['leaved_feedback_id'];
                    break;
                default:
                    throw new Exception('未知回评追评类型');
            }
            $request = new RespondToFeedbackRequestType([
                'FeedbackID' => (string)$feedback_id,
                'ItemID' => (string)$data['item_id'],
                'TransactionID' => (string)$data['transaction_id'],
                'TargetUserID' => $data['comment_user'],
                'ResponseType' => $response_type,
                'ResponseText' => $text,
            ]);

            $result = $service->respondToFeedback($request)->toArray();

            if (empty($result['Ack'])) {
                throw new Exception('调用回评接口失败');
            }

            //去更新一次评价；
            $this->updateFeedbackQueue($account_id, $data);

            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    /**
     * 回评
     * @param int $account_id 回评的帐号ID
     * @param array $data 回评的参数；
     * @param string $type 回评的类型 Reply回评，FollowUp跟进；跟进好像暂时不能用；
     * @return bool
     */
    public function leaveFeedbackApi($account_id, $data)
    {
        try {
            $account = Cache::store('EbayAccount')->getTableRecord($account_id);
            $config = $this->getAccountParmas($account);
            $service = new TradingService($config);

            $request = new LeaveFeedbackRequestType([
                'CommentType' => $data['leaved_comment_type'] ?? 'Positive',
                'CommentText' => $data['leaved_comment_text'],
                'OrderLineItemID' => $data['item_id']. '-'. $data['transaction_id'],
            ]);

            $result = $service->leaveFeedback($request)->toArray();

            if (empty($result['Ack'])) {
                throw new Exception('调用回评接口失败');
            }

            //去更新一次评价；
            $this->updateFeedbackQueue($account_id, $data);

            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    /**
     * 评价成功后，重新去更新一下评价；
     * @param $account_id
     * @param $feedback_id
     */
    public function updateFeedbackQueue($account_id, $data)
    {
        $queueparam = [
            'account_id' => $account_id,
            'OrderLineItemID' => $data['item_id']. '-'. $data['transaction_id']
        ];
        (new UniqueQueuer(EbayFeedbackByOrderLineItemId::class))->push($queueparam);
    }

    /**
     * @title 加锁执行重发评价；
     */
    public function repeatFeedbackLockRun($id)
    {
        $lockCache = Cache::store('Lock');
        if ($lockCache->uniqueLock($id)) {
            try {
                $result = $this->repeatFeedback($id);
            } catch (Exception $e) {
                $lockCache->unlock($id);
                throw new Exception($e->getMessage());
            }
            $lockCache->unlock($id);
            return $result;
        } else {
            throw new Exception('正在执行中，不可以重复提交');
        }
    }


    /**
     * 重发评价
     * @param array $data [id,account_id,transaction_id,item_id,target_user,text]
     * @throws Exception
     * @return boolean
     */
    function repeatFeedback($id)
    {
        if (empty($id)) {
            throw new Exception('参数错误, 自增ID为空');
        }
        $feedbackmodel = new EbayFeedbackModel();

        // 获取评论信息
        $field = '*';
        $feedback = $feedbackmodel->where(['id' => $id])->field($field)->find();
        if (empty($feedback)) {
            throw new Exception('该评价信息不存在');
        }
        if (empty($feedback['account_id']) || empty($feedback['item_id']) || empty($feedback['transaction_id'])) {
            throw new Exception('刊登号、交易号或者买家id错误，不能进行评论。');
        }
        if ($feedback['status'] !== 3) {
            throw new Exception('回评状态不是失败，只能重新回评发送失败的内空');
        }
        if (empty($feedback['leaved_comment_text'])) {
            $feedbackmodel->update(['status' => 0], ['id' => $id]);
            throw new Exception('回评内容为空，已更新状态为未回评请刷新后回评');
        }

        $commentTypeArr = [1 => 'Positive', 'Neutral', 'Negative'];
        $leaved_data = [
            'id' => empty($feedback) ? 0 : $feedback['id'],
            'transaction_id' => $feedback['transaction_id'],
            'comment_user' => $feedback['comment_user'],
            'item_id' => $feedback['item_id'],
            'leaved_comment_text' => $feedback['leaved_comment_text'],
            'leaved_comment_type' => $commentTypeArr[$feedback['leaved_comment_text']] ?? 'Positive'
        ];

        $result = $this->leaveFeedbackApi($feedback['account_id'], $leaved_data);

        $update['update_time'] = time();
        $userInfo = Common::getUserInfo();
        $update['update_id'] = $userInfo['user_id'];

        if ($result['Ack'] == 'Success') {
            $update['leaved_feedback_id'] = $result['FeedbackID'];
            $update['leaved_comment_time'] = strtotime($result['Timestamp']);
            $update['status'] = EbayFeedbackModel::FINSH_EVALUATE;
            $update['response_status'] = 1;

            $feedbackmodel->update($update, ['id' => $id]);
            return true;
        } else {
            $update['status'] = EbayFeedbackModel::FAIL_EVALUATE;
            $feedbackmodel->update($update, ['id' => $id]);
            if (!empty($result['Errors'][0]['ErrorCode']) && $result['Errors'][0]['ErrorCode'] == 55) {
                throw new Exception('重新回评失败，可能已经评价过了,正在更新本条记录');
            } elseif (!empty($result['Errors'][0]['ShortMessage'])) {
                throw new Exception($result['Errors'][0]['ShortMessage']);
            } else {
                throw new Exception('回评失败。');
            }
            return false;
        }
    }

    /**
     * 批量评价 （卖家评价）
     *
     * @param type $ids
     * @param type $score
     * @param type $content
     * @throws Exception
     */
    public function batchleaveFeedback($ids, $text)
    {
        try {
            $where['status'] = EbayFeedbackModel::WAIT_EVALUATE;
            if ($ids == 'all') {
                //全评价只能评价好中评；
                $where['comment_type'] = ['<', 3];
            } else {
                $where['id'] = ['in', explode(',', $ids)];
            }

            $queue = new UniqueQueuer(EbayFeedbackLeftQueue::class);
            $feedbackModel = new EbayFeedbackModel();
            $limit = 1000;
            $start = 0;

            //用来收集需加入队列的元素；
            $idArr = [];
            while(true) {
                $idTmp = $feedbackModel->where($where)->limit($start * $limit, $limit)->column('id');
                if (empty($ids)) {
                    break;
                }
                $feedbackModel->update([
                    'leaved_comment_text' => $text,
                    'status' => EbayFeedbackModel::PADDING_EVALUATE
                ], ['id' => ['in', $idTmp]]);
                $idArr = array_merge($idArr, $idTmp);
                if (count($idTmp) < $limit) {
                    break;
                }
                $start++;
            }

            //加入队列；
            if (!empty($idArr)) {
                foreach ($idArr as $id) {
                    $queue->push($id);
                }
            }
            return true;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }


    /**
     * @title 加锁执行重发评价；
     */
    public function followUpFeedbackLockRun($data)
    {
        $lockCache = Cache::store('Lock');
        if ($lockCache->uniqueLock($data)) {
            try {
                $result = $this->followUpFeedback($data);
            } catch (Exception $e) {
                $lockCache->unlock($data);
                throw new Exception($e->getMessage());
            }
            $lockCache->unlock($data);
            return $result;
        } else {
            throw new Exception('正在执行中，不可以重复提交');
        }
    }

    /**
     * 追评
     * @param array $data
     * @throws Exception
     */
    function followUpFeedback($data)
    {
        if (empty($data['id']) || empty($data['text'])) {
            throw new Exception('追评失败，参数错误');
        }

        if (!is_int($data['id']) && json_decode($data['id']) !== false) {
            $idArr = json_decode($data['id'], true);
            if (empty($idArr)) {
                throw new Exception('追评失败，参数id为空');
            }
            $data['id'] = $idArr[0];
        }

        $feedbackModel = new EbayFeedbackModel();

        // 获取评论信息
        $field = 'account_id,feedback_id,transaction_id,item_id,comment_user,reply_text,follow_up,status,response_status,leaved_feedback_id';
        $feedback = $feedbackModel->where(['id' => $data['id']])->field($field)->find();
        if (empty($feedback)) {
            throw new Exception('追评失败，该评价信息不存在');
        }

        if (empty($feedback['account_id']) || empty($feedback['item_id']) || empty($feedback['transaction_id'])) {
            throw new Exception('追评失败，刊登号、交易号或者买家id错误，不能进行追评。');
        }
        if ($feedback['status'] !== 1) {
            throw new Exception('追评失败，没有回复评价，不能进行追评');
        }

        if ($feedback['response_status'] == 2) {
            throw new Exception('追评失败，已超过限制追评次数！');
        }

        if (empty($feedback['comment_user'])) {
            throw new Exception('追评失败，不可以追评没有给卖家评价的买家');
        }

        $user = Common::getUserInfo();
        try {
            $feedback['leaved_follow_up'] = $data['text'];
            //回复评价，是回复买家的评阶，追评，是追加卖家自已给买家的评价
            $result = $this->respondToFeedbackApi($feedback['account_id'], $feedback, 'FollowUp');

            // 更新
            $update = [];
            // 修改回评
            if ($result['Ack'] == 'Success') {
                $update['leaved_follow_up'] = $data['text'];
                $update['response_status'] = 2;
                $update['update_time'] = time();
                $update['update_id'] = $user['user_id'];

                $feedbackModel->update($update, [
                    'id' => $data['id']
                ]);
                return true;
            } else {
                if (!empty($result['Errors'][0]['ErrorCode']) && $result['Errors'][0]['ErrorCode'] == 21112) {
                    $update['response_status'] = 2;
                    $update['update_time'] = time();
                    $update['update_id'] = $user['user_id'];
                    $feedbackModel->update($update, ['id' => $data['id']]);
                    throw new Exception('追评失败，可能已经追评过了，正在更新记录进行核实！');
                }
                return false;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    /**
     * @title 加锁执行重发评价；
     */
    public function sendMessageLockRun($data)
    {
        $lockCache = Cache::store('Lock');
        if ($lockCache->uniqueLock($data)) {
            try {
                $result = $this->sendMessage($data);
            } catch (Exception $e) {
                $lockCache->unlock($data);
                throw new Exception($e->getMessage());
            } catch (JsonErrorException $e) {
                $lockCache->unlock($data);
                throw new JsonErrorException($e->getMessage());
            }
            $lockCache->unlock($data);
            return $result;
        } else {
            throw new Exception('正在执行中，不可以重复提交');
        }
    }


    public function reply($data)
    {
        $feedbackModel = new EbayFeedbackModel();
        $feedback = $feedbackModel->where(['id' => $data['id']])->find();

        if (empty($feedback)) {
            throw new Exception('回复评价失败，卖家评价不存在');
        }

        if (empty($feedback['account_id']) || empty($feedback['item_id']) || empty($feedback['transaction_id'])) {
            throw new Exception('回复评价失败，刊登号、交易号或者买家id错误，不能进行追评。');
        }
        if (empty($feedback['feedback_id'])) {
            throw new Exception('回复评价失败，没有收到评价不能进行回复');
        }

        if (empty($feedback['comment_user'])) {
            throw new Exception('回复评价失败，卖家名称未知不能进行回复');
        }

        $user = Common::getUserInfo();
        try {
            $feedback['reply_text'] = $data['text'];
            //回复评价，是回复买家的评阶，追评，是追加卖家自已给买家的评价
            $result = $this->respondToFeedbackApi($feedback['account_id'], $feedback, 'Reply');

            // 更新
            $update = [];
            // 修改回评
            if ($result['Ack'] == 'Success') {
                $update['reply_text'] = $data['text'];
                $update['update_time'] = time();
                $update['update_id'] = $user['user_id'];

                $feedbackModel->update($update, [
                    'id' => $data['id']
                ]);
                return true;
            } else {
                if (!empty($result['Errors'][0]['ErrorCode']) && $result['Errors'][0]['ErrorCode'] == 21111) {
                    throw new Exception('回复评价失败，可能已经回复评价过了，正在更新记录进行核实！');
                }
                return false;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }


    /**
     * 跟进消息
     *
     * @param array $data
     * @throws Exception
     */
    function sendMessage($data)
    {
        if (empty($data['id']) || empty($data['text'])) {
            throw new Exception('参数错误');
        }
        // 获取评论信息
        $feedback = EbayFeedbackModel::field('account_id,transaction_id,item_id,comment_user,seller_first_msg')->find($data['id']);
        if (empty($feedback)) {
            throw new Exception('该评价信息不存在');
        }
        if (empty($feedback['account_id']) || empty($feedback['item_id']) || empty($feedback['transaction_id']) || empty($feedback['comment_user'])) {
            throw new Exception('刊登号、交易号或者买家id错误，不能发送消息。');
        }

        $msgService = new EbayMessageHelp();
        $send_data = [
            'account_id' => $feedback['account_id'],
            'subject' => $feedback['transaction_id'] . '-' . $feedback['item_id'],
            'text' => $data['text'],
            'question_type' => 'General',
            'recipient_id' => $feedback['comment_user'],
            'item_id' => $feedback['item_id']
        ];
        $res = $msgService->sendMessage($send_data); // 调用发送消息接口
        if ($res['status'] == 1) {
            $message_field = 'id,message_id,sender,account_id,sender,send_to_name,replied,message_type,send_time,item_id,subject,level,flag_id,status,message_text as text,media_info';
            $mesage = EbayMessage::where(['id' => $res['mid']])->field($message_field)->find();
            $message['item_img'] = '';
            if (!empty($message['item_id'])) {
                $mesage['item_img'] = EbayListing::field('img')->where(['item_id' => $mesage['item_id']])->value('img');
            }
            return ['message' => '站内信发送成功', 'data' => $mesage];
        } else {
            throw new Exception($res['message']);
        }
    }


    /**
     * 修改状态
     *
     * @param array $data
     * @throws Exception
     * @return boolean
     */
    function changeStatus($data)
    {
        if (empty($data['id']) || empty($data['field']) || empty($data['status'])) {
            throw new Exception('参数错误');
        }
        // 获取评论信息
        $feedback = EbayFeedbackModel::field('id')->find($data['id']);
        if (empty($feedback)) {
            throw new Exception('该评价信息不存在');
        }

        $user = Common::getUserInfo();
        $update = [];
        $update[$data['field']] = $data['status'];
        $update['handel_by'] = $user['user_id'];
        $update['handel_time'] = time();
        $result = EbayFeedbackModel::update($update, [
            'id' => $data['id']
        ]);
        if ($result) {
            return true;
        } else {
            throw new Exception('操作失败');
        }
    }

    /**
     * 统计状态-数量
     *
     * @return unknown
     */
    function statusStatistics($where = [])
    {
        $where['feedback_id'] = ['>', 0];
        $model = new EbayFeedbackModel();
        $counts = $model->where($where)->group('status')->field('count(id) total,status')->select();
        foreach (EbayFeedbackModel::$STATUS as $key => $status) {
            $result[$key]['id'] = $key + 1;
            $result[$key]['name'] = $status;
            $result[$key]['count'] = 0;
            foreach ($counts as $val) {
                if ($val['status'] == $key) {
                    $result[$key]['count'] = $val['total'];
                    break;
                }
            }
        }
        return $result;
    }


    /**
     * 分账号获取评价信息
     */
    public function getFeedbackScore()
    {
        $accountList = Cache::store('EbayAccount')->getTableRecord();
        foreach ($accountList as $k => $v) {
            $token = $v['token'];
            if (! empty($token[0]) && $v['is_invalid'] == 1) {
                // if (in_array($v['id'], [35, 36, 37])) { //test by tb
                
                $data = [
                    'userToken' => $token,
                    'siteID' => 0,
                    'account_id' => $v['id'],
                    'account_name' => $v['account_name']
                ];
                
                $ebay = new EbayFeedbackApi($data);
                $res = $ebay->getFeedbackScore();
                sleep(3);
                // return $res;exit;
            }
        }
        
        return true;
    }

    /**
     * 检查买家是否有差评
     *
     * @param string $buyer_id            
     * @return boolean
     */
    function checkBuyerNegative($buyer_id = '')
    {
        // 是否发起差评
        $res = EbayFeedbackModel::field('id')->where([
            'comment_text_buyer' => $buyer_id,
            'comment_type' => 3
        ])->find();
        if ($res) {
            return true; // 该用户发起过差评
        }
        return false;
    }

    /**
     * 获取订单买家评价类型（中差好评）
     *
     * @param string $order_id            
     * @param string $item_id            
     * @return mixed
     */
    function getOrderCommentType($order_id = '', $item_id = '')
    {
        $results = EbayFeedbackModel::field('id,comment_type,comment_text,leaved_comment_type,leaved_comment_text,status')->where([
            'order_id' => $order_id,
            'item_id' => $item_id
        ])->limit(2)->select();
        $feedback = [
            'comment_type' => 0,
            'comment_text' => '',
            'leaved_comment_type' => 0,
            'leaved_comment_text' => '',
            'status' => 0,
            'order_id' => $order_id,
            'item_id' => $item_id,
        ];
        if (!empty($results)) {
            foreach ($results as $result) {
                $feedback = [
                    'comment_type' => empty($feedback['comment_type']) ? $result['comment_type'] : $feedback['comment_type'],
                    'comment_text' => empty($feedback['comment_text']) ? $result['comment_text'] : $feedback['comment_text'],
                    'leaved_comment_type' => empty($feedback['leaved_comment_type']) ? $result['leaved_comment_type'] : $feedback['leaved_comment_type'],
                    'leaved_comment_text' => empty($feedback['leaved_comment_text']) ? $result['leaved_comment_text'] : $feedback['leaved_comment_text'],
                    'status' => empty($feedback['status']) ? $result['status'] : $feedback['status'],
                    'order_id' => $order_id,
                    'item_id' => $item_id,
                ];
            }
        }
        return $feedback;
    }

    /**
     * 触发好评或差评事件
     * @param $result
     */
    public function trigger_assessment_event($data, $feedback_id): void
    {
        try {
            //json_encode后，有的带引号，有的不带，转成字符串后，统一带引号
            $feedback_id = strval($feedback_id);

            if (isset($data['comment_type']) && $data['comment_type'] == 3) {
                $event_name = 'F2';
                $order_data = [
                    'channel_id' => ChannelAccountConst::channel_ebay,//Y 渠道id
                    'account_id' => $data['account_id'],//Y 账号id
                    'channel_order_number' => '',//Y 渠道订单号
                    'receiver' => $data['comment_user'],
                    'extra_params' => [
                        'feedback_id' => $feedback_id,
                    ],
                    'ebay_message_data' => [
                        'comment_time' => $data['comment_time'],
                    ]
                ];
                (new MsgRuleHelp())->triggerEvent($event_name, $order_data);
            } elseif (isset($data['comment_type']) && $data['comment_type'] == 1) {
                $event_name = 'F3';
                $order_data = [
                    'channel_id' => ChannelAccountConst::channel_ebay,//Y 渠道id
                    'account_id' => $data['account_id'],//Y 账号id
                    'channel_order_number' => '',//Y 渠道订单号
                    'receiver' => $data['comment_user'],
                    'extra_params' => [
                        'feedback_id' => $feedback_id,
                    ],
                    'ebay_message_data' => [
                        'comment_time' => $data['comment_time'],
                    ]
                ];
                (new MsgRuleHelp())->triggerEvent($event_name, $order_data);
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage(). '|'. $ex->getLine(). '|'. $ex->getFile());
        }
    }
}