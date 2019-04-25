<?php
namespace app\customerservice\service;

use app\common\cache\Cache;
use app\common\model\AfterSaleService;
use app\common\model\ebay\EbayCase;
use app\common\model\Order;
use app\common\service\ChannelAccountConst;
use app\common\service\Report;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\EbayCancelByIdQueue;
use app\customerservice\queue\EbayCaseByIdQueue;
use app\customerservice\queue\EbayQuiriesByIdQueue;
use app\customerservice\queue\EbayReturnByIdQueue;
use app\customerservice\queue\EbayReturnFilesQueue;
use app\customerservice\task\EbayCancel;
use app\order\queue\DownPaypalOrderByTxnId;
use app\order\service\OrderHelp;
use app\publish\service\CommonService;
use DTS\eBaySDK\PostOrder\Services\PostOrderService;
use DTS\eBaySDK\PostOrder\Types\GetCancelDetailResponse;
use DTS\eBaySDK\PostOrder\Types\GetCancellationRestRequest;
use DTS\eBaySDK\PostOrder\Types\SearchCancellationsRestRequest;
use DTS\eBaySDK\PostOrder\Types\SearchCasesRestRequest;
use DTS\eBaySDK\Trading\Services\TradingService;
use DTS\eBaySDK\Trading\Types\GetDisputeRequestType;
use think\Exception;
use erp\AbsServer;
use app\common\exception\JsonErrorException;
use service\ebay\EbayPostorderApi;
use app\common\model\Order as OrderModel;
use app\common\model\ebay\EbayCase as EbayCaseModel;
use app\common\model\ebay\EbayRequest as EbayRequestModel;
use app\common\model\ebay\EbayOrder as EbayOrderModel;
use app\common\model\ebay\EbayOrderDetail as EbayOrderDetailModel;
use app\common\model\ebay\EbayRequestExtend as EbayRequestExtendModel;
use think\File;
use think\Db;
use app\common\model\ebay\EbayRequest;
use app\order\service\OrderService;
use app\common\service\OrderStatusConst;
use app\common\model\OrderSourceDetail as OrderSourceDetailModel;
use app\common\service\Common;

/**
 * Created by tanbin.
 * User: PHILL
 * Date: 2017/04/6
 * Time: 18:14
 */
class EbayDisputeHelp extends AbsServer
{

    /**
     * ebay API配置数据
     *
     * @param unknown $account_id
     */
    public function ebayApiConfig($account_id)
    {
        $accountList = Cache::store('EbayAccount')->getTableRecord($account_id);
        $token = $accountList['token'];
        return [
            'userToken' => $token,
            'siteID' => 0,
            'account_id' => $account_id,
            'account_name' => $accountList['account_name'],
            'appID' => $accountList['app_id'],
            'certID' => $accountList['cert_id'],
            'devID' => $accountList['dev_id'],
        ];
    }

    /**
     * 获取request 里面的数据
     *
     * @param unknown $where
     *            查询条件
     * @param unknown $pagination
     *            分页数据 ['page'=>1,'pageSize'=>1]
     * @param string $field
     *            查询字段
     * @param string $is_handel
     *            是否封装处理数据， true-封装处理 ，false-不分装处理
     */
    public function getRequest($where = [], $pagination = [], $sort, $field = '', $is_handel = true)
    {
        $where['update_time'] = ['>', 0];
        $RequestModel = new EbayRequestModel();
        $count = $RequestModel::where($where)->count();
        $field_default = 'id,request_id as dispute_id,after_sale_id,account_id,local_order_id as link_order_id,order_number as order_num,buyer_account,seller_account,flow_type as buyer_expected,initiates_time as dispute_time,reason,update_time,
            status,state,response_due';
        // 赋值
        if ($field) {
            $field_default = $field;
        }
        $sort_type = $sort['type'] ?? 'initiates_time';
        $sort_val = $sort['val'] ?? 'desc';

        $list = $RequestModel::field($field_default)->where($where)
            ->page($pagination['page'], $pagination['pageSize'])
            ->order($sort_type, $sort_val)
            ->select();

        if ($is_handel) {
            $list = $this->handelRequestList($list);
        }

        $result = [
            'datas' => $list,
            'count' => $count
        ];
        return $result;
    }

    /**
     * 封装处理Request数据
     *
     * @param array $list
     * @return array $list
     */
    function handelRequestList($list = [])
    {
        if (empty($list))
            return [];

        $after_ids = [];
        foreach ($list as $k => $v) {
            // $oid = 0;
            // //获取ebay_order 订单id
            // if($v['order_num']){
            // //*****s 从缓存 获取ebay订单id
            // $cacheOrderInfo = [];
            // $cacheOrderInfo = Cache::store('EbayOrder')->orderUpdateTime($v['order_num']);
            // $oid = empty(param($cacheOrderInfo, 'id'))? 0 : $cacheOrderInfo['id'];
            // //*****s 从缓存 获取ebay订单id
            // }
            $after_ids[] = $v['after_sale_id'];
            $list[$k]['after_sale_id'] = strval($v['after_sale_id']);
            $list[$k]['link_order_id'] = strval($v['link_order_id']);
            $list[$k]['dispute_time'] = !empty($v['dispute_time']) ? date('Y-m-d H:i:s', $v['dispute_time']) : '';
            $list[$k]['response_due'] = !empty($v['response_due']) ? date('Y-m-d H:i:s', $v['response_due']) : '';
            $list[$k]['approve_status'] = 0;
            $list[$k]['refund_status'] = 0;
        }

        $after_sales = $this->getAfterSales($after_ids);
        if (!empty($after_sales)) {
            foreach ($list as $k => $v) {
                if (!empty($v['after_sale_id']) && !empty($after_sales[$v['after_sale_id']])) {
                    $list[$k]['approve_status'] = $after_sales[$v['after_sale_id']]['approve_status'];
                    $list[$k]['refund_status'] = $after_sales[$v['after_sale_id']]['refund_status'];
                }
            }
        }

        return $list;
    }


    public function getAfterSales($after_ids)
    {
        $after_ids = array_merge(array_filter($after_ids));
        if (empty($after_ids)) {
            return [];
        }

        return AfterSaleService::where(['id' => ['in', $after_ids]])->column('approve_status,refund_status', 'id');
    }

    /**
     * 获取Case里面的数据
     *
     * @param array $where
     * @param array $pagination
     */
    /**
     * 获取Case里面的数据
     *
     * @param unknown $where
     *            查询条件
     * @param unknown $pagination
     *            分页数据 ['page'=>1,'pageSize'=>1]
     * @param string $field
     *            查询字段
     * @param string $is_handel
     *            是否封装处理数据， true-封装处理 ，false-不分装处理
     */
    public function getCase($where, $pagination, $sort, $field = '', $is_handel = true)
    {
        //update_time是0的，是还没有下载详情的；
        $where['update_time'] = ['>', 0];
        $caseModel = new EbayCaseModel();
        $count = $caseModel->where($where)->count();
        $field_default = 'id,case_id as dispute_id,account_id,after_sale_id,local_order_id as link_order_id,order_number as order_num,buyer_account,seller_account,reason,buyer_expected,initiates_time as dispute_time,update_time,status,state,response_due';
        if ($field) {
            $field_default = $field;
        }
        $sort_type = $sort['type'] ?? 'initiates_time';
        $sort_val = $sort['val'] ?? 'desc';
        $list = $caseModel->field($field_default)->where($where)
            ->page($pagination['page'], $pagination['pageSize'])
            ->order($sort_type, $sort_val)
            ->select();

        if ($is_handel) {
            $list = $this->handelCaseList($list);
        }

        $result = [
            'datas' => $list,
            'count' => $count
        ];
        return $result;
    }

    /**
     * 封装处理Case数据
     *
     * @param array $list
     * @return array $list
     */
    function handelCaseList($list = [])
    {
        if (empty($list))
            return [];
        $after_ids = [];
        foreach ($list as $k => $v) {
            // $oid = 0;
            // //获取ebay_order 订单id
            // if($v['order_num']){
            // //*****s 从缓存 获取ebay订单id
            // $cacheOrderInfo = [];
            // $cacheOrderInfo = Cache::store('EbayOrder')->orderUpdateTime($v['order_num']);
            // $oid = empty(param($cacheOrderInfo, 'id'))? 0 : $cacheOrderInfo['id'];
            // //*****s 从缓存 获取ebay订单id
            // }


            $after_ids[] = strval($v['after_sale_id']);
            $list[$k]['after_sale_id'] = strval($v['after_sale_id']);
            $list[$k]['link_order_id'] = strval($v['link_order_id']);
            $list[$k]['dispute_time'] = !empty($v['dispute_time']) ? date('Y-m-d H:i:s', $v['dispute_time']) : '';
            $list[$k]['response_due'] = !empty($v['response_due']) ? date('Y-m-d H:i:s', $v['response_due']) : '';
            $list[$k]['approve_status'] = 0;
            $list[$k]['refund_status'] = 0;
        }

        $after_sales = $this->getAfterSales($after_ids);
        if (!empty($after_sales)) {
            foreach ($list as $k => $v) {
                if (!empty($v['after_sale_id']) && !empty($after_sales[$v['after_sale_id']])) {
                    $list[$k]['approve_status'] = $after_sales[$v['after_sale_id']]['approve_status'];
                    $list[$k]['refund_status'] = $after_sales[$v['after_sale_id']]['refund_status'];
                }
            }
        }
        return $list;
    }

    /**
     * 获取request 里面详细数据
     *
     * @param array $where
     *            查询条件
     * @param string $dispute_type
     *            纠纷类型 CANCEL、NOTRECIVE、RETURN、NOTPAID、ESCALATE ...
     */
    public function getRequestDetail($where, $dispute_type)
    {
        if (isset($where['case_id'])) {
            $where['request_id'] = $where['case_id'];
            unset($where['case_id']);
        }
        $RequestModel = new EbayRequestModel();
        $field = 'id,account_id,request_id as dispute_id,order_number as order_num,buyer_account,seller_account,flow_type as buyer_expected,initiates_time as dispute_time,reason,update_time,
            status,state,response_due,seller_total_refund as total_refund,refund_currency,response_history';
        //if ($dispute_type == EbayRequestModel::EBAY_DISPUTE_RETURN) {
        //    $field .= ',name,street1,street2,city,country,province,postal_code';
        //}
        $result = $RequestModel::field($field)->where($where)->find();
        if (empty($result)) {
            throw new JsonErrorException('此信息不存在！');
        }
        $result = $result->toArray();
        $result['dispute_time'] = !empty($result['dispute_time']) ? date('Y-m-d H:i:s', $result['dispute_time']) : '';
        $result['response_due'] = !empty($result['response_due']) ? date('Y-m-d H:i:s', $result['response_due']) : '';

        $lock = Cache::store('Lock');
        //$config = $this->ebayApiConfig($result['account_id']);
        //$api = new EbayPostorderApi($config);

        if ($dispute_type == EbayRequestModel::EBAY_DISPUTE_CANCEL || $dispute_type == EbayRequestModel::EBAY_DISPUTE_NOTPAID) {
            $result['response_history'] = $this->handelCancelHistory($result['response_history']);
            //if ($lock->uniqueLock(['cancel_id' => $result['dispute_id']], 120)) {
            //    $remote_detail = $api->getCancelDetail($result['dispute_id']);
            //    $update = $this->handleRemoteCancel($remote_detail);
            //    EbayRequestModel::update($update, [
            //        'id' => $result['id']
            //    ]);
            //    $result['response_history'] = $this->handelCancelHistory($update['response_history']);
            //} else {
            //    $result['response_history'] = $this->handelCancelHistory($result['response_history']);
            //}
        } else {
            $result['response_history'] = $this->handelReturnHistory($result['response_history']);
            //if ($lock->uniqueLock(['return_id' => $result['dispute_id']], 120)) {
            //    $remote_detail = $api->getReturnDetail($result['dispute_id']);
            //    $update = $this->handleRemoteReturn($remote_detail);
            //    EbayRequestModel::update($update, [
            //        'id' => $result['id']
            //    ]);
            //    $result['response_history'] = $this->handelReturnHistory($update['response_history']);
            //} else {
            //    $result['response_history'] = $this->handelReturnHistory($result['response_history']);
            //}
            $images = [];
            $result['base_url'] = 'http://www.zrzsoft.com:8081/';
            $requestFiles = EbayRequestExtendModel::where(['request_id' => $result['dispute_id']])->field('id,extend_value')->select();
            if (!empty($requestFiles)) {
                foreach ($requestFiles as $val) {
                    $images[] = 'download/ebay_return/'. $val['extend_value'];
                }
            }
            $result['images'] = $images;
        }

        return $result;
    }

    /**
     * 获取case 里面详细数据
     *
     * @param array $where
     *            查询条件
     * @param string $dispute_type
     *            纠纷类型 CANCEL、NOTRECIVE、RETURN、NOTPAID、ESCALATE ...
     */
    public function getCaseDetail($where, $dispute_type)
    {
        if (isset($where['request_id'])) {
            $where['case_id'] = $where['request_id'];
            unset($where['request_id']);
        }
        $caseModel = new EbayCaseModel();
        $count = $caseModel::where($where)->count();
        $field = 'id,case_id as dispute_id,order_number as order_num,buyer_account,seller_account,reason,buyer_expected,initiates_time as dispute_time,update_time,
            state,response_due,return_id,seller_total_refund as total_refund,refund_currency,response_history';
        $result = $caseModel::field($field)->where($where)->find();

        if (empty($result)) {
            throw new JsonErrorException('此信息不存在！');
        }

        // return 的升级需要返回退货地址
        if ($result['return_id']) {
            $return_detail = EbayRequestModel::field('name,country,province,city,street1 as street,postal_code')->where([
                'request_id' => $result['return_id']
            ])->find();
            if ($return_detail) {
                $result['seller_address'] = [
                    'name' => $return_detail['name'],
                    'country' => $return_detail['country'],
                    'province' => $return_detail['province'],
                    'city' => $return_detail['city'],
                    'street' => $return_detail['street'],
                    'postal_code' => $return_detail['postal_code']
                ];
            }
        } else {
        }

        $result['dispute_time'] = !empty($result['dispute_time']) ? date('Y-m-d H:i:s', $result['dispute_time']) : '';
        $result['response_due'] = !empty($result['response_due']) ? date('Y-m-d H:i:s', $result['response_due']) : '';

        $result['response_history'] = $this->handelCaseHistory($result['response_history']);

        // if($dispute_type==EbayRequestModel::EBAY_DISPUTE_ESCALATE && !empty($result['return_id']))
        // {
        // $return_history = EbayRequestModel::field('response_history')->where(['request_id'=>$result['return_id']])->find();
        // $result['response_history'] = $this->handelReturnHistory(unserialize($return_history['response_history']));
        // unset($result['return_id']);
        // }

        return $result;
    }

    /**
     * 处理取消数据History
     *
     * @param array $lists
     * @return array $result
     */
    function handelCancelHistory($history_lists)
    {
        if (!is_array($history_lists)) {
            $lists = json_decode($history_lists, true);
            if (!is_array($lists)) {
                $lists = unserialize($history_lists);
            }
        }
        if (empty($lists)) {
            return [];
        }
        foreach ($lists as $key => $history) {
            $result[] = [
                'auther' => $history['activityParty'],
                'activity' => $history['activityType'],
                'notes' => $history['activityType'],
                'creation_time' => strtotime($history['actionDate']['value'])
            ];
        }
        return $result;
    }

    /**
     * 处理退货换货数据History
     *
     * @param array $lists
     * @return array $result
     */
    function handelReturnHistory($history_lists)
    {
        $lists = json_decode($history_lists, true);
        if (!is_array($lists)) {
            $lists = unserialize($history_lists);
        }
        if (empty($lists)) {
            return [];
        }
        foreach ($lists as $key => $history) {
            $result[] = [
                'auther' => $history['author'],
                'activity' => $history['activity'],
                'notes' => param($history, 'notes'),
                'creation_time' => strtotime($history['creationDate']['value']),
                'seller_address' => isset($history['attributes']['sellerReturnAddress']) ? $history['attributes']['sellerReturnAddress'] : ''
            ];
        }
        return $result;
    }

    /**
     * 处理未收到货数据History
     *
     * @param $lists
     * @return array
     */
    function handelCaseHistory($history_lists)
    {
        $lists = json_decode($history_lists, true);
        if (!is_array($lists)) {
            $lists = unserialize($history_lists);
        }
        if (empty($lists)) {
            return [];
        }
        foreach ($lists as $key => $history) {
            $result[] = [
                'auther' => $history['actor'],
                'activity' => $history['action'],
                'notes' => empty(param($history, 'description')) ? param($history, 'action') : $history['description'],
                'creation_time' => strtotime($history['date']['value'])
            ];
        }
        return $result;
    }

    /**
     * 处理未收到货数据History_response
     *
     * @param $lists
     * @return array
     */
    function handelCaseResponseHistory($lists)
    {
        if (empty($lists)) {
            return [];
        }
        foreach ($lists as $key => $history) {
            $lists[$key]['creation_time'] = date('Y-m-d H:i:s', $history['creation_time']);
        }
        return $lists;
    }


    public function recordReport()
    {
        $userInfo = Common::getUserInfo();
        //统计数据；
        Report::statisticMessage(ChannelAccountConst::channel_ebay, $userInfo['user_id'], time(), [
            'dispute_quantity' => 1
        ]);
    }

    /**
     * 操作取消订单纠纷
     *
     * @param number $id 纠纷id
     * @param string $operate 操作code : 同意-approve ， 拒绝-reject
     * @param array $data 操作数据
     * @throws JsonErrorException
     * @return boolean
     */
    function operateCancel($id = 0, $operate = '', $data = [])
    {
        if (empty($id)) {
            throw new JsonErrorException('参数错误');
        }
        $info = EbayRequestModel::field('id,order_id,account_id,request_id,state')->where([
            'id' => $id
        ])->find();
        if (empty($info)) {
            throw new JsonErrorException('该数据不存在');
        }
        // 本地排查纠纷状态
        if ($info['state'] == 'CLOSED') {
            throw new JsonErrorException('操作失败，此纠纷已经关闭，不能进行操作！');
        }
        $config = $this->ebayApiConfig($info['account_id']);
        $api = new EbayPostorderApi($config);

        // 远程排查纠纷状态 （调用查看接口）
        $remote_detail = $api->getCancelDetail($info['request_id']);
        if (isset($remote_detail['cancelState']) && $remote_detail['cancelState'] == 'CLOSED') {
            // 执行更新操作
            $update = $this->handleRemoteCancel($remote_detail);
            EbayRequestModel::update($update, [
                'id' => $id
            ]);
            throw new JsonErrorException('操作失败，此纠纷已经关闭，不能进行操作！');
        }

        // 是否作废
        if ($data['invalid'] == 1) {
            // 作废订单
            $order_service = new OrderService();
            $order_info = OrderModel::field('id')->where([
                'channel_order_number' => $info['order_id']
            ])->find();
            if ($order_info) {
                $order_param = [
                    'order_id' => $order_info['id'],
                    'value' => OrderStatusConst::SaidInvalid,
                    'reason' => '取消订单纠纷'
                ];
                $order_service->status($order_param);
            }
        }

        try {
            switch ($operate) {
                case 'approve':
                    $res = $api->approveCancel($info['request_id']);
                    break;
                case 'reject':
                    $res = $api->rejectCancel($info['request_id'], $data);
                    break;
                default:
                    throw new Exception('未知操作方式');
                    break;
            }
            if ($res) {
                (new UniqueQueuer(EbayCancelByIdQueue::class))->push(['account_id' => $info['account_id'], 'cancel_id' => $info['request_id']]);
                $this->recordReport();
                return true;
            } else {
                $err = empty($api->getError()) ? '请求失败' : $api->getError();
                throw new Exception($err);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 操作退货退款订单纠纷
     *
     * @param number $id 纠纷id
     * @param string $operate
     *            操 作code： 取消-cancel ，decline-拒绝 ，升级-escalate ，全额退款-refund ，部分退款-part_refund ，退货-return，补发货-replenishment， 发送消息-message
     * @param array $data 操作数据
     * @throws JsonErrorException
     * @return boolean
     */
    function operateReturn($id = 0, $operate = '', $data = [])
    {
        if (empty($id)) {
            throw new JsonErrorException('参数错误');
        }
        $info = EbayRequestModel::field('id,account_id,request_id,state,seller_total_refund,refund_currency')->where([
            'id' => $id
        ])->find();
        if (empty($info)) {
            throw new JsonErrorException('该数据不存在');
        }
        // 本地排查纠纷状态
        if ($info['state'] == 'CLOSED') {
            throw new JsonErrorException('操作失败，此纠纷已经关闭，不能进行操作！');
        }
        // 退款金额确认
        if ($operate == 'refund' && empty($info['seller_total_refund'])) {
            throw new JsonErrorException('退款金额错误，不能进行操作！');
        }

        $config = $this->ebayApiConfig($info['account_id']);
        $api = new EbayPostorderApi($config);

        // 操作前先查看和更新状态 （调用查看接口）
        $remote_detail = $api->getReturnDetail($info['request_id']);
        if ($remote_detail['summary']['state'] == 'CLOSED') {
            // 执行更新操作
            $update = $this->handleRemoteReturn($remote_detail);
            $res = EbayRequestModel::update($update, [
                'id' => $id
            ]);
            throw new JsonErrorException('操作失败，此纠纷已经关闭！');
        }

        $data['currency'] = $info['refund_currency'];

        try {
            switch ($operate) {
                case 'approve':
                case 'return':
                    $res = $api->processReturn($info['request_id'], 'APPROVE', $data); // OFFER_RETURN
                    // $res = $api->sendShippingLabel($info['request_id'],$data['buyer_email']); //提供退货运输标签
                    break;
                case 'refund':  //全额退款
                    $data['total_refund'] = $info['seller_total_refund'];
                    if (!empty($data['message'])) {
                        $res = $api->sendMessageReturn($info['request_id'], ['message' => $data['message']]);
                        if ($res === false) {
                            throw new Exception('退款前发送留言失败:'. $api->getError());
                        }
                    }
                    $res = $api->issueRefundReturn($info['request_id'], $data);
                    break;
                case 'part_refund': //退款金额
                    $res = $api->processReturn($info['request_id'], 'OFFER_PARTIAL_REFUND', $data);
                    break;
                case 'decline':
                    $res = $api->processReturn($info['request_id'], 'DECLINE', $data);
                    break;
                case 'cancel':
                    $res = $api->cancelReturn($info['request_id']);
                    break;
                case 'escalate':
                    $res = $api->escalateReturn($info['request_id'], $data);
                    break;
                case 'message':
                    $res = $api->sendMessageReturn($info['request_id'], $data);
                    break;
                case 'replenishment':
                    $res = $api->sendMessageReturn($info['request_id'], $data);
                    break;
                default:
                    throw new Exception('未知操作方式');
                    break;
            }
            if ($res) {
                (new UniqueQueuer(EbayReturnByIdQueue::class))->push(['account_id' => $info['account_id'], 'return_id' => $info['request_id']]);
                $this->recordReport();
                return true;
            } else {
                $err = empty($api->getError()) ? '请求失败' : $api->getError();
                throw new Exception($err);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 操作未收到货订单纠纷
     *
     * @param number $id
     *            纠纷id
     * @param string $operate
     *            操作code ：close-关闭 message-发送消息 escalate-升级 refund-全额退款
     * @throws JsonErrorException 操作数据
     * @return boolean
     */
    function operateInquiry($id = 0, $operate = '', $data = [])
    {
        if (empty($id)) {
            throw new JsonErrorException('参数错误');
        }
        $info = EbayCaseModel::field('id,account_id,case_id,state')->where([
            'id' => $id
        ])->find();
        if (empty($info)) {
            throw new JsonErrorException('该数据不存在');
        }
        // 本地排查纠纷状态
        if ($info['state'] == 'CLOSED') {
            throw new JsonErrorException('操作失败，此纠纷已经关闭，不能进行操作！');
        }

        $config = $this->ebayApiConfig($info['account_id']);
        $api = new EbayPostorderApi($config);

        // 操作前先查看和更新状态 （调用查看接口）
        $remote_detail = $api->getInquiryDetail($info['case_id']);
        if ($remote_detail['state'] == 'CLOSED') {
            // 执行更新操作
            $update = $this->handleRemoteInquiry($remote_detail);
            EbayCaseModel::update($update, [
                'id' => $id
            ]);
            throw new JsonErrorException('操作失败，此纠纷已经关闭！');
        }

        try {
            switch ($operate) {
                case 'close':
                    $res = $api->closeInquiry($info['case_id']);
                    break;
                case 'escalate':
                    $res = $api->escalateInquiry($info['case_id']);
                    break;
                case 'message':
                    $res = $api->sendMessageInquiry($info['case_id'], $data);
                    break;
                case 'refund':
                    if (!empty($data['message'])) {
                        $res = $api->sendMessageInquiry($info['case_id'], ['message' => $data['message']]);
                        if ($res === false) {
                            throw new Exception('退款前发送留言失败:'. $api->getError());
                        }
                    }
                    $res = $api->issueRefundInquiry($info['case_id'], $data);
                    break;
                case 'shipment':
                    $res = $api->provideShipmentInquiry($info['case_id'], $data);
                    break;
                default:
                    throw new Exception('未知操作方式');
                    break;
            }

            if ($res) {
                (new UniqueQueuer(EbayQuiriesByIdQueue::class))->push(['account_id' => $info['account_id'], 'inquiry_id' => $info['case_id']]);
                $this->recordReport();
                return true;
            } else {
                $err = empty($api->getError()) ? '请求失败' : $api->getError();
                throw new Exception($err);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     *
     * 操作升级case纠纷
     *
     * @param number $id
     *            纠纷id
     * @param string $operate
     *            操作code： refund-全额退款 address-提供退货地址
     * @throws JsonErrorException 操作数据
     * @return boolean
     */
    function operateCase($id = 0, $operate = '', $data = [])
    {
        if (empty($id)) {
            throw new JsonErrorException('参数错误');
        }
        $info = EbayCaseModel::field('id,account_id,case_id,return_id,state')->where([
            'id' => $id
        ])->find();
        if (empty($info)) {
            throw new JsonErrorException('该数据不存在');
        }
        // 本地排查纠纷状态
        if ($info['state'] == 'CLOSED') {
            throw new JsonErrorException('操作失败，此纠纷已经关闭，不能进行操作！');
        }
        $config = $this->ebayApiConfig($info['account_id']);
        $api = new EbayPostorderApi($config);

        // 操作前先查看和更新状态 （调用查看接口）
        $remote_detail = $api->getCaseDetail($info['case_id']);
        if ($remote_detail['caseStateEnum'] == 'CLOSED') {
            // 执行更新操作
            $update = $this->handleRemoteCase($remote_detail);
            EbayCaseModel::update($update, [
                'id' => $id
            ]);
            throw new JsonErrorException('操作失败，此纠纷已经关闭！');
        }
        try {
            switch ($operate) {
                case 'refund':
                    $res = $api->refundCaes($info['case_id'], $data);
                    break;
                case 'address':
                    if (empty($info['return_id'])) {
                        throw new Exception('只有退换货升级的Case才能执行此操作');
                    }
                    // 通过return_id获取返回地址
                    // $data = EbayRequestModel::field('name,street1,street2,city,country_name,province,country,postal_code')
                    // ->where(['request_id'=>$info['return_id'],'request_type'=>EbayRequestModel::EBAY_REQUEST_RETURN])->find();
                    $res = $api->providesAddressCase($info['case_id'], $data);
                    break;
                default:
                    throw new Exception('未知操作方式');
                    break;
            }

            if ($res) {
                (new UniqueQueuer(EbayCancelByIdQueue::class))->push(['account_id' => $info['account_id'], 'case_id' => $info['case_id']]);
                $this->recordReport();
                // 只更新状态 ，操作记录用更新按钮
                return true;
            } else {
                $err = empty($api->getError()) ? '请求失败' : $api->getError();
                throw new Exception($err);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 根据id更新纠纷
     *
     * @param number $id
     *            纠纷id
     * @param string $type
     *            类型 Cancel 、 Case 、 Return 、 Inquiry
     * @throws JsonErrorException
     * @return boolean
     */
    function updateDispute($id = 0, $type = '')
    {
        $dispute = [];
        switch (EbayRequestModel::$EBAY_DATA_TABLE[$type]) {
            case 'request':
                $dispute = EbayRequestModel::field('request_id as dispute_id,account_id,state,local_order_id')->where([
                    'id' => $id
                ])->find();
                break;
            case 'case':
                $dispute = EbayCaseModel::field('case_id as dispute_id,account_id as account_id,state,local_order_id')->where([
                    'id' => $id
                ])->find();
                break;
            default:
                break;
        }

        //找不到纠纷数据，可能会重新下载，所以要验证account_id；
        if (empty($dispute)) {
            throw new Exception('此纠纷不存在');
        }

        //手动更新时，另外匹配订单号；
        $dispute->save(['local_order_id' => 0]);

        //按类型进行弄新；
        switch ($type) {
            case 'Return':
                //下载return的图片文件；
                (new UniqueQueuer(EbayReturnFilesQueue::class))->push(['account_id' => $dispute['account_id'], 'return_id' => $dispute['dispute_id']]);
                $res = $this->downReturnById($dispute['account_id'], $dispute['dispute_id']);
                break;
            case 'Cancel':
                $res = $this->downCancelById($dispute['account_id'], $dispute['dispute_id']);
                break;
            case 'Inquiry':
                $res = $this->downInquiriesById($dispute['account_id'], $dispute['dispute_id']);
                break;
            case 'Case':
                $res = $this->downCaseById($dispute['account_id'], $dispute['dispute_id']);
                break;
            default:
                throw new Exception('未知纠纷类型');
                break;
        }

        if ($res) {
            return true;
        } else {
            throw new Exception('更新失败');
        }
    }

    /**
     * 批量更新纠纷
     *
     * @param number $id
     *            纠纷id
     * @param string $type
     *            类型 Cancel 、 Case 、 Return 、 Inquiry
     * @return boolean
     */
    function batchUpdateDispute($ids, $type = '')
    {
        if (empty($ids)) {
            throw new exception('更新参数ids为空');
        }
        $idArr = explode(',', $ids);
        $where['id'] = ['in', $idArr];

        $disputes = [];
        switch (EbayRequestModel::$EBAY_DATA_TABLE[$type]) {
            case 'request':
                $disputes = EbayRequestModel::field('request_id as dispute_id,account_id,state,local_order_id')->where($where)->select();
                break;
            case 'case':
                $disputes = EbayCaseModel::field('case_id as dispute_id,account_id as account_id,state,local_order_id')->where($where)->select();
                break;
            default:
                break;
        }

        //按类型进行弄新；
        foreach ($disputes as $dispute) {
            switch ($type) {
                case 'Return':
                    //下载return的图片文件；
                    $params = ['account_id' => $dispute['account_id'], 'return_id' => $dispute['dispute_id']];
                    (new UniqueQueuer(EbayReturnByIdQueue::class))->push($params);
                    (new UniqueQueuer(EbayReturnFilesQueue::class))->push($params);
                    break;
                case 'Cancel':
                    $params = ['account_id' => $dispute['account_id'], 'cancel_id' => $dispute['dispute_id']];
                    (new UniqueQueuer(EbayCancelByIdQueue::class))->push($params);
                    break;
                case 'Inquiry':
                    $params = ['account_id' => $dispute['account_id'], 'inquiry_id' => $dispute['dispute_id']];
                    (new UniqueQueuer(EbayQuiriesByIdQueue::class))->push($params);
                    break;
                case 'Case':
                    $params = ['account_id' => $dispute['account_id'], 'case_id' => $dispute['dispute_id']];
                    (new UniqueQueuer(EbayCaseByIdQueue::class))->push($params);
                    break;
                default:
                    throw new Exception('未知纠纷类型');
                    break;
            }
        }

        return true;
    }

    /**
     * 封装远程Cancel数据
     *
     * @param array $data
     *            远程取消订单数据
     * @return bool
     */
    function handleRemoteCancel($data = [])
    {
        if (empty($data) || !isset($data['cancelDetail'])) {
            throw new JsonErrorException('远程获取数据错误');
        }
        $data = $data['cancelDetail'];

        $is_close = 0;
        if ($data['cancelStatus'] == 'CLOSED') {
            $is_close = 1;
        }
        $userInfo = Common::getUserInfo();
        $update = [
            'state' => $data['cancelState'],
            'status' => $data['cancelStatus'],
            'close_reason' => param($data, 'cancelCloseReason'),
            'is_close' => $is_close,
            'seller_total_refund' => isset($data['requestRefundAmount']['value']) ? $data['requestRefundAmount']['value'] : 0,
            'refund_currency' => isset($data['requestRefundAmount']['currency']) ? $data['requestRefundAmount']['currency'] : '',
            'response_history' => isset($data['activityHistories']) ? serialize($data['activityHistories']) : '',
            'update_time' => time(),
            'update_id' => $userInfo['user_id']
        ];
        return $update;
    }

    /**
     * 封装远程Return数据
     *
     * @param array $data
     *            远程退货退款数据
     * @return bool
     */
    function handleRemoteReturn($data = [])
    {
        if (empty($data) || !isset($data['summary'])) {
            throw new JsonErrorException('远程获取数据错误');
        }

        // 退款金额
        $seller_total_refund = 0;
        $refund_currency = '';
        if (isset($data['summary']['sellerTotalRefund']['actualRefundAmount']['value'])) {
            $seller_total_refund = $data['summary']['sellerTotalRefund']['actualRefundAmount']['value']; // 实际退款金额
            $refund_currency = $data['summary']['sellerTotalRefund']['actualRefundAmount']['currency'];
        } else {
            if (isset($data['summary']['sellerTotalRefund']['estimatedRefundAmount']['value'])) {
                $seller_total_refund = $data['summary']['sellerTotalRefund']['estimatedRefundAmount']['value']; // 将要退款金额
                $refund_currency = $data['summary']['sellerTotalRefund']['estimatedRefundAmount']['currency'];
            }
        }
        // 是否关闭
        $is_close = 0;
        if ($data['summary']['state'] == 'CLOSED') {
            $is_close = 1;
        }
        $userInfo = Common::getUserInfo();
        $update = [
            'state' => $data['summary']['state'],
            'status' => $data['summary']['status'],
            'is_close' => $is_close,
            'close_reason' => isset($data['detail']['closeInfo']['returnCloseReason']) ? $data['detail']['closeInfo']['returnCloseReason'] : '',
            'seller_total_refund' => $seller_total_refund,
            'refund_currency' => $refund_currency,
            'response_history' => isset($data['detail']['responseHistory']) ? serialize($data['detail']['responseHistory']) : '',
            'update_time' => time(),
            'update_id' => $userInfo['user_id']
        ];
        return $update;
    }

    /**
     * 封装远程case数据
     *
     * @param array $data
     *            远程升级case数据
     * @return bool
     */
    function handleRemoteCase($data = [])
    {
        if (empty($data) || !isset($data['caseStateEnum'])) {
            throw new JsonErrorException('远程获取数据错误');
        }
        $is_close = 0;
        if ($data['caseStateEnum'] == 'CLOSED') {
            $is_close = 1;
        }
        $userInfo = Common::getUserInfo();
        $update = [
            'status' => $data['status'],
            'state' => $data['caseStateEnum'],
            'response_history' => isset($data['caseHistoryDetails']['history']) ? serialize($data['caseHistoryDetails']['history']) : '',
            'is_close' => $is_close,
            'seller_total_refund' => isset($data['claimAmount']['value']) ? $data['claimAmount']['value'] : 0,
            'refund_currency' => isset($data['claimAmount']['currency']) ? $data['claimAmount']['currency'] : '',
            'update_time' => time(),
            'update_id' => $userInfo['user_id']
        ];
        return $update;
    }

    /**
     * 封装远程未收到货数据
     *
     * @param array $data
     *            远程未收到货数据
     * @return bool
     */
    function handleRemoteInquiry($data = [])
    {
        if (empty($data) || !isset($data['status'])) {
            throw new JsonErrorException('远程获取数据错误');
        }

        // //操作记录表
        // if(isset($data['inquiryHistoryDetails']['history']) && !empty($data['inquiryHistoryDetails']['history'])){
        // $history = $data['inquiryHistoryDetails']['history'];
        // //删除原来记录
        // $model = new EbayCaseResponseHistoryModel();
        // $res = $model->where('uid = 5')->delete();
        // }

        $is_close = 0;
        if ($data['status'] == 'CLOSED') {
            $is_close = 1;
        }
        $userInfo = Common::getUserInfo();
        $update = [
            'status' => $data['status'],
            'state' => $data['state'],
            'is_close' => $is_close,
            'response_history' => isset($data['inquiryHistoryDetails']['history']) ? serialize($data['inquiryHistoryDetails']['history']) : '',
            'seller_total_refund' => isset($data['claimAmount']['value']) ? $data['claimAmount']['value'] : 0,
            'refund_currency' => isset($data['claimAmount']['currency']) ? $data['claimAmount']['currency'] : '',
            'update_time' => time(),
            'update_id' => $userInfo['user_id']
        ];
        return $update;
    }

    /**
     * 获取纠纷类型
     *
     * @return unknown[]|string[]
     */
    function getDisputeType()
    {
        foreach (EbayRequestModel::$DISPUTE_TYPE as $key => $vo) {
            $data[] = [
                'type' => $key,
                'value' => $vo
            ];
        }

        return $data;
    }


    /**
     * 检查售后传过来的纠纷单号对否，或更新；
     * @param $disputeOrder
     * @param array $data
     * @return array
     */
    public function updateDisputOrder($disputeOrder, $data = [])
    {
        $status = ['status' => 1, 'message' => ''];
        $disputeArr = explode('-', $disputeOrder);
        if (count($disputeArr) != 2) {
            $status['status'] = 0;
            $status['message'] = '纠纷单号错误';
            return $status;
        }
        $type = $disputeArr[0];
        $dispute_id = $disputeArr[1];
        //服务类
        switch ($type) {
            case EbayRequest::EBAY_DISPUTE_CANCEL :
                $disputModel = new EbayRequest();
                $where['request_id'] = $dispute_id;
                break;
            case EbayRequest::EBAY_DISPUTE_NOTPAID :
                $disputModel = new EbayRequest();
                $where['request_id'] = $dispute_id;
                break;
            case EbayRequest::EBAY_DISPUTE_RETURN :
                $disputModel = new EbayRequest();
                $where['request_id'] = $dispute_id;
                break;
            case EbayRequest::EBAY_DISPUTE_NOTRECIVE :
                $disputModel = new EbayCase();
                $where['case_id'] = $dispute_id;
                break;
            case EbayRequest::EBAY_DISPUTE_ESCALATE :
                $disputModel = new EbayCase();
                $where['case_id'] = $dispute_id;
                break;
            default:
                $status['status'] = 0;
                $status['message'] = '纠纷类别错误';
                return $status;
                break;
        }
        $dispute = $disputModel->where($where)->field('id')->find();
        if (empty($dispute)) {
            $status['status'] = 0;
            $status['message'] = '纠纷单号错误,纠纷类型ID不存在';
            return $status;
        }

        //更新数据；
        if (!empty($data)) {
            $disputModel->update($data, $where);
        }

        return $status;
    }


    /**
     * 建售后单时，查询一下纠纷单号和类型存不存在，可不可以建售后单；
     * @param $disputeOrder
     * @return array
     */
    public function checkDisputOrder($disputeOrder)
    {
        return $this->updateDisputOrder($disputeOrder);
    }


    /**
     * 建好售后单后，把售后单ID返回来记录，或者是删除时传0来清除ID；
     * @param $disputeOrder
     * @param $after_sale_id
     * @return array
     */
    public function recordAfterSales($disputeOrder, $after_sale_id)
    {
        $update = ['after_sale_id' => $after_sale_id];
        return $this->updateDisputOrder($disputeOrder, $update);
    }


    /**
     * 建好售后单后，把售后单ID返回来记录，或者是删除时传0来清除ID；
     * @param $disputeOrder
     * @param $extra ['note' => '退款备忘',...'amount' => 金额，不填为全额退款，'currency' => 货币单位']；
     * @param $after_sale_id
     * @return array
     */
    public function afterSaleAutoRefund($disputeOrder, $extra = [], $after_sale_id)
    {
        $status = ['status' => 1, 'message' => ''];
        $disputeArr = explode('-', $disputeOrder);
        if (count($disputeArr) != 2) {
            $status['status'] = 0;
            $status['message'] = '纠纷单号错误';
            return $status;
        }

        $type = $disputeArr[0];
        $dispute_id = $disputeArr[1];
        //服务类
        try {
            switch ($type) {
                //取消交易退款；
                case EbayRequest::EBAY_DISPUTE_CANCEL :
                    return $this->cancelRefund($dispute_id, $extra, $after_sale_id);
                case EbayRequest::EBAY_DISPUTE_NOTPAID :
                    throw new Exception('未付款的订单，不可以退款');
                //退货退款；
                case EbayRequest::EBAY_DISPUTE_RETURN :
                    return $this->returnRefund($dispute_id, $extra, $after_sale_id);
                //未收到货退款
                case EbayRequest::EBAY_DISPUTE_NOTRECIVE :
                    return $this->inquiryRefund($dispute_id, $extra, $after_sale_id);
                //升级退款；
                case EbayRequest::EBAY_DISPUTE_ESCALATE :
                    return $this->caseRefund($dispute_id, $extra, $after_sale_id);
                default:
                    $status['status'] = 0;
                    $status['message'] = '纠纷类别错误';
                    return $status;
                    break;
            }
        } catch (\Exception $e) {
            return ['status' => 0, 'message' => $e->getMessage()];
        }
    }


    public function cancelRefund($dispute_id, $extra = [], $after_sale_id)
    {
        $requestModel = new EbayRequestModel();
        $info = $requestModel->where([
            'request_id' => $dispute_id
        ])->find();
        if (empty($info)) {
            throw new Exception('该数据不存在');
        }
        if ($info['after_sale_id'] != $after_sale_id) {
            throw new Exception('纠纷记录的售后处理单ID错误');
        }
        if (!empty($extra['amount']) && $extra['amount'] != $info['seller_total_refund']) {
            throw new Exception('取消交易纠纷退款金额与纠纷单金额：' . $info['seller_total_refund'] . ' 不一致');
        }
        // 本地排查纠纷状态
        if ($info['state'] == 'CLOSED') {
            throw new Exception('操作失败，此纠纷已经关闭，不能进行操作！');
        }

        $config = $this->ebayApiConfig($info['account_id']);
        $api = new EbayPostorderApi($config);

        // 超过3小时没有更新，则远程排查纠纷状态 （调用查看接口）
        if (time() - $info['update_time'] > 3600 * 3) {
            $remote_detail = $api->getCancelDetail($info['request_id']);
            if (isset($remote_detail['cancelState']) && $remote_detail['cancelState'] == 'CLOSED') {
                // 执行更新操作
                $update = $this->handleRemoteCancel($remote_detail);
                $requestModel->update($update, [
                    'id' => $info['id']
                ]);
                throw new Exception('操作失败，此纠纷已经关闭，不能进行操作！');
            }
        }

        //开始批准cancel取消，取消后就会退款；
        $res = $api->approveCancel($info['request_id']);

        if ($res) {
            // 执行更新操作
            (new UniqueQueuer(EbayCancelByIdQueue::class))->push(['account_id' => $info['account_id'], 'cancel_id' => $info['request_id']]);
            $this->recordReport();
            return ['status' => 1, 'message' => '退款成功'];
        } else {
            $err = empty($api->getError()) ? '请求失败' : $api->getError();
            throw new Exception('退款失败:' . $err);
        }
    }


    public function caseRefund($dispute_id, $extra = [], $after_sale_id)
    {
        $caseModel = new EbayCaseModel();
        $info = $caseModel->where([
            'case_id' => $dispute_id
        ])->find();
        if (empty($info)) {
            throw new Exception('该数据不存在');
        }
        if ($info['after_sale_id'] != $after_sale_id) {
            throw new Exception('纠纷记录的售后处理单ID错误');
        }
        if (!empty($extra['amount']) && $extra['amount'] != $info['seller_total_refund']) {
            throw new Exception('升级纠纷退款金额与纠纷单金额：' . $info['seller_total_refund'] . ' 不一致');
        }
        // 本地排查纠纷状态
        if ($info['state'] == 'CLOSED') {
            throw new Exception('操作失败，此纠纷已经关闭，不能进行操作！');
        }
        $config = $this->ebayApiConfig($info['account_id']);
        $api = new EbayPostorderApi($config);

        // 操作前先查看和更新状态 （调用查看接口）
        if (time() - $info['update_time'] > 3600 * 3) {
            $remote_detail = $api->getCaseDetail($info['case_id']);
            if ($remote_detail['caseStateEnum'] == 'CLOSED') {
                // 执行更新操作
                $update = $this->handleRemoteCase($remote_detail);
                $caseModel->update($update, [
                    'id' => $info['id']
                ]);
                throw new Exception('操作失败，此纠纷已经关闭！');
            }
        }

        $data['message'] = $extra['note'] ?? '';
        $res = $api->refundCaes($info['case_id'], $data);

        if ($res) {
            (new UniqueQueuer(EbayCancelByIdQueue::class))->push(['account_id' => $info['account_id'], 'case_id' => $info['case_id']]);
            $this->recordReport();
            // 只更新状态 ，操作记录用更新按钮
            return true;
        } else {
            $err = empty($api->getError()) ? '请求失败' : $api->getError();
            throw new Exception('退款失败:' . $err);
        }
    }


    public function returnRefund($dispute_id, $extra = [], $after_sale_id)
    {
        $requestModel = new EbayRequestModel();
        $info = $requestModel->where([
            'request_id' => $dispute_id
        ])->find();
        if (empty($info)) {
            throw new Exception('该数据不存在');
        }
        if ($info['after_sale_id'] != $after_sale_id) {
            throw new Exception('纠纷记录的售后处理单ID错误');
        }
        if (!empty($extra['amount']) && $extra['amount'] > $info['seller_total_refund']) {
            throw new Exception('退货退款纠纷退款金额超出纠纷单金额：' . $info['seller_total_refund']);
        }

        // 本地排查纠纷状态
        if ($info['state'] == 'CLOSED') {
            throw new Exception('操作失败，此纠纷已经关闭，不能进行操作！');
        }
        // 退款金额确认
        if (empty($info['seller_total_refund'])) {
            throw new Exception('退款金额错误，不能进行操作！');
        }

        $config = $this->ebayApiConfig($info['account_id']);
        $api = new EbayPostorderApi($config);

        // 操作前先查看和更新状态 （调用查看接口）
        if (time() - $info['update_time'] > 3600 * 3) {
            $remote_detail = $api->getReturnDetail($info['request_id']);
            if ($remote_detail['summary']['state'] == 'CLOSED') {
                // 执行更新操作
                $update = $this->handleRemoteReturn($remote_detail);
                $requestModel->update($update, [
                    'id' => $info['id']
                ]);
                throw new Exception('操作失败，此纠纷已经关闭！');
            }
        }

        //* @param $extra ['note' => '退款备忘',...'amount' => 金额，不填为全额退款，'currency' => 货币单位']；
        if (empty($extra['amount']) || $extra['amount'] == $info['seller_total_refund']) {
            $data['currency'] = $info['refund_currency'];
            $data['total_refund'] = $info['seller_total_refund'];
            $data['message'] = $extra['note'] ?? '';
            //先调留言接口，发送纠纷留言；
            if (!empty($data['message'])) {
                $res = $api->sendMessageReturn($info['request_id'], ['message' => $data['message']]);
                if ($res === false) {
                    throw new Exception('退款前发送留言失败:'. $api->getError());
                }
            }
            $res = $api->issueRefundReturn($info['request_id'], $data);
            if ($res) {
                (new UniqueQueuer(EbayReturnByIdQueue::class))->push(['account_id' => $info['account_id'], 'return_id' => $info['request_id']]);
                $this->recordReport();
                return ['status' => 1, 'message' => '退款成功'];
            } else {
                $err = empty($api->getError()) ? '请求失败' : $api->getError();
                throw new Exception('退款失败:' . $err);
            }
        } else {
            if ($extra['amount'] > $info['seller_total_refund']) {
                throw new Exception('退款金额超出申请的卖家退款金额');
            }
            $data['currency'] = $extra['currency'];
            $data['refund_amount'] = $extra['amount'];
            $data['message'] = $extra['note'] ?? '';
            $res = $api->processReturn($info['request_id'], 'OFFER_PARTIAL_REFUND', $data);
            if ($res) {
                (new UniqueQueuer(EbayReturnByIdQueue::class))->push(['account_id' => $info['account_id'], 'return_id' => $info['request_id']]);
                $this->recordReport();
                return ['status' => 2, 'message' => '申请部分退款成功'];
            } else {
                $err = empty($api->getError()) ? '请求失败' : $api->getError();
                throw new Exception('退款失败:' . $err);
            }
        }
    }


    public function inquiryRefund($dispute_id, $extra = [], $after_sale_id)
    {
        $caseModel = new EbayCaseModel();
        $info = $caseModel->where([
            'case_id' => $dispute_id
        ])->find();
        if (empty($info)) {
            throw new Exception('该数据不存在');
        }
        if ($info['after_sale_id'] != $after_sale_id) {
            throw new Exception('纠纷记录的售后处理单ID错误');
        }
        if (!empty($extra['amount']) && $extra['amount'] != $info['seller_total_refund']) {
            throw new Exception('未收到货纠纷退款金额与纠纷单金额：' . $info['seller_total_refund'] . ' 不一致');
        }
        // 本地排查纠纷状态
        if ($info['state'] == 'CLOSED') {
            throw new Exception('操作失败，此纠纷已经关闭，不能进行操作！');
        }

        $config = $this->ebayApiConfig($info['account_id']);
        $api = new EbayPostorderApi($config);

        // 操作前先查看和更新状态 （调用查看接口）
        if (time() - $info['update_time'] > 3600 * 3) {
            $remote_detail = $api->getInquiryDetail($info['case_id']);
            if ($remote_detail['state'] == 'CLOSED') {
                // 执行更新操作
                $update = $this->handleRemoteInquiry($remote_detail);
                $caseModel->update($update, [
                    'id' => $info['id']
                ]);
                throw new Exception('操作失败，此纠纷已经关闭！');
            }
        }

        //留言
        $data['message'] = $extra['note'] ?? '';
        if (!empty($data['message'])) {
            $res = $api->sendMessageInquiry($info['case_id'], ['message' => $data['message']]);
            if ($res === false) {
                throw new Exception('退款前发送留言失败:'. $api->getError());
            }
        }
        $res = $api->issueRefundInquiry($info['case_id'], $data);

        if ($res) {
            (new UniqueQueuer(EbayQuiriesByIdQueue::class))->push(['account_id' => $info['account_id'], 'inquiry_id' => $info['case_id']]);
            $this->recordReport();

            return ['status' => 1, 'message' => '退款成功'];
        } else {
            $err = empty($api->getError()) ? '请求失败' : $api->getError();
            throw new Exception('退款失败:' . $err);
        }
    }


    /**
     * 获取纠纷状态
     *
     * @param string $type
     * @return array[] $data;
     */
    function getDisputeStatus($type)
    {
        if (empty($type)) {
            throw new JsonErrorException('参数错误');
        }
        $status = [];
        switch ($type) {
            case 'CANCEL':
            case 'NOTPAID':
            case 'ESCALATE':
                $status['OPEN'] = EbayRequestModel::$DISPUTE_STATE['OPEN'];
                $status['CLOSED'] = EbayRequestModel::$DISPUTE_STATE['CLOSED'];
                break;
            case 'NOTRECIVE':
            case 'RETURN':
                $status['SELLER_WATTING'] = EbayRequestModel::$DISPUTE_STATE['SELLER_WATTING'];
                $status['OTHER'] = EbayRequestModel::$DISPUTE_STATE['OTHER'];
                $status['CLOSED'] = EbayRequestModel::$DISPUTE_STATE['CLOSED'];
                break;
            default:
                break;
        }
        foreach ($status as $key => $vo) {
            $data[] = [
                'status' => $key,
                'value' => $vo
            ];
        }

        return $data;
    }

    /**
     * 获取搜索键值对
     *
     * @param string $type
     * @return array[] $data;
     */
    function getSearchField($type)
    {
        if (empty($type)) {
            throw new JsonErrorException('参数错误');
        }
        $fields = [];

        switch ($type) {
            case 'CANCEL':
            case 'NOTPAID':
                $fields = [
                    'request_id' => self::typeIds('CANCEL'),
                    'order_number' => '订单号',
                    'transaction_id' => '交易号',
                    'item_id' => 'Item Id'
                ];
                break;
            case 'RETURN':
                $fields = [
                    'request_id' => self::typeIds('RETURN'),
                    'order_number' => '订单号',
                    'transaction_id' => '交易号',
                    'item_id' => 'Item Id',
                    'buyer_account' => '买家ID'
                ];
                break;
            case 'NOTRECIVE':
                $fields = [
                    'case_id' => self::typeIds('NOTRECIVE'),
                    'order_number' => '订单号',
                    'transaction_id' => '交易号',
                    'item_id' => 'Item Id',
                    'buyer_account' => '买家ID'
                ];
                break;
            case 'ESCALATE':
                $fields = [
                    'case_id' => self::typeIds('ESCALATE'),
                    'order_number' => '订单号',
                    'transaction_id' => '交易号',
                    'item_id' => 'Item Id',
                    'buyer_account' => '买家ID'
                ];
                break;
            default:
                break;
        }
        $fields['buyer_account'] = '买家账号';
        foreach ($fields as $key => $vo) {
            $data[] = [
                'field' => $key,
                'value' => $vo
            ];
        }

        return $data;
    }

    /**
     * 纠纷类型对应的ID值
     *
     * @param unknown $type
     * @return string|string[]
     */
    function typeIds($type)
    {
        $return = [
            'CANCEL' => 'Cancel id',
            'NOTRECIVE' => 'Inquiry id',
            'RETURN' => 'Return id',
            'NOTPAID' => 'Cancel id',
            'ESCALATE' => 'Case id'
        ];
        if ($type) {
            return $return[$type];
        } else {
            return $return;
        }
    }

    /**
     * 获取状态条件
     *
     * @param string $type
     * @param string $status
     * @return array $where
     */
    function getStatusCondition($type, $status)
    {
        $where = [];
        switch ($type) {
            case 'CANCEL':
            case 'NOTPAID':
                if ($status != 'OPEN') {
                    $where['state'] = [
                        'EQ',
                        EbayRequestModel::$CANCEL_STATE[$status]
                    ];
                } else {
                    $where['state'] = [
                        'NEQ',
                        EbayRequestModel::$CANCEL_STATE['CLOSED']
                    ];
                }
                break;
            case 'RETURN':
                if ($status == 'CLOSED') {
                    $where['status'] = ['in', EbayRequestModel::$RETURN_STATE['CLOSED']];
                } else if ($status == 'SELLER_WATTING') {
                    $where['status'] = ['in', EbayRequestModel::$RETURN_STATE['SELLER_WATTING']];
                } else {
                    $allstatus = array_merge(EbayRequestModel::$RETURN_STATE['CLOSED'], EbayRequestModel::$RETURN_STATE['SELLER_WATTING']);
                    $where['status'] = ['NOT IN', $allstatus];
                }

                break;
            case 'NOTRECIVE':
                if ($status == 'CLOSED') {
                    $where['status'] = ['IN', EbayCaseModel::$NOTRECIVE_STATE['CLOSED']];

                } else if ($status == 'SELLER_WATTING') {
                    $where['status'] = ['IN', EbayCaseModel::$NOTRECIVE_STATE['SELLER_WATTING']];

                } else {
                    $allstatus = array_merge(EbayCaseModel::$NOTRECIVE_STATE['CLOSED'], EbayCaseModel::$NOTRECIVE_STATE['SELLER_WATTING']);
                    $where['status'] = ['NOT IN', $allstatus];
                }
                break;
            case 'ESCALATE':
                if ($status != 'OPEN') {
                    $where['state'] = [
                        'EQ',
                        EbayCaseModel::$CASE_STATE[$status]
                    ];
                } else {
                    $where['state'] = [
                        'NEQ',
                        EbayCaseModel::$CASE_STATE['CLOSED']
                    ];
                }
                break;

            default:
                break;
        }

        return $where;
    }

    /**
     * 获取原因数据列表-下拉框
     *
     * @param string $code
     * @param string $dispute_type
     * @return string[]
     */
    function getReasons($code = '', $dispute_type = '')
    {
        if ($code == 'escalate') {
            switch ($dispute_type) {
                case EbayRequestModel::EBAY_DISPUTE_NOTRECIVE:
                    return [
                        'BUYER_TROUBLE',
                        'BUYER_UNHAPPY',
                        // 'NO_REFUND',
                        // 'NOT_RECEIVED',
                        // 'SELLER_NORESPONSE',
                        // 'SHIPPED_ITEM',
                        'TROUBLE_COMMUNICATION',
                        'OTHERS'
                    ];
                    break;
                case EbayRequestModel::EBAY_DISPUTE_RETURN:
                    RETURN [
                        'BUYER_NORESPONSE',
                        'BUYER_RETURNED_WRONG_ITEM',
                        'DISAGREE_WITH_RETURN_REASON',
                        'ITEM_NOT_RECEIVED',
                        'ITEM_RECEIVED_IN_DIFFERENT_CONDITION',
                        'NO_REFUND_FOR_RETURN_SHIPPING',
                        'NO_REFUND_RECEIVED',
                        'OTHER',
                        'SELLER_NO_RESPONSE',
                        'TROUBLE_COMMUNICATION_WITH_BUYER',
                        'TROUBLE_COMMUNICATION_WITH_SELLER'
                    ];
            }
        } elseif ($code == 'close' && $dispute_type == EbayRequestModel::EBAY_DISPUTE_ESCALATE) {
            return [
                'ITEM_ARRIVED',
                'WORKED_OUT_WITH_SELLER',
                'WOULD_RATHER_KEEP_THE_ITEM',
                'OTHERS'
            ];
        }

        return [];
    }

    /**
     * @node ebay获取case
     */
    public function getCaseList()
    {
        $accountList = Cache::store('EbayAccount')->getTableRecord();
        foreach ($accountList as $k => $v) {
            //开发者信息不全的,直接跳过;
            if (empty($v['dev_id']) || empty($v['app_id']) || empty($v['cert_id']) || empty($v['token'])) {
                continue;
            }
            $token = $v['token'];
            if (!empty($token) && $v['is_invalid'] == 1) {
                $res = $this->downCase($v['id']);
                sleep(10);
            }
        }

        return false;
    }

    function downCase($account_id, $down_time = 0)
    {
        set_time_limit(0);
        $execute_start = time(); //执行开始时间

        $createTimeFrom = time() - 3600 * 24 * 15;
        $createTimeTo = time();
        // 最后更新时间
        if (!empty($down_time)) {
            $createTimeFrom = strtotime('-' . $down_time . ' days');
        } else {
            $last_update = Cache::store('EbayAccount')->ebayLastUpdateTime($account_id, 'case');
            // 距离上次时间不能超过15天（暂时）
            if (!empty(isset($last_update['last_update_time']))) {
                if (time() - strtotime($last_update['last_update_time']) < 3600 * 24 * 15) {
                    $createTimeFrom = strtotime($last_update['last_update_time']);
                } else {
                    $createTimeFrom = strtotime("-15 day");
                }
            }
        }
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = [
            'userToken' => $account['token'],
            'account_id' => $account['id'],
            'account_name' => $account['account_name'],

            //开发者帐号相关信息；
            'devID' => $account['dev_id'],
            'appID' => $account['app_id'],
            'certID' => $account['cert_id'],
        ];
        $ebayApi = new EbayPostorderApi($config);
        $res = $ebayApi->serchCaseList($createTimeFrom, $createTimeTo);

        if ($res['state'] && !empty($res['datas'])) {
            $ebayCaseModel = new EbayCaseModel();
            $queue = new UniqueQueuer(EbayCaseByIdQueue::class);
            $lock = Cache::store('Lock');

            foreach ($res['datas'] as $vo) {
                $lockParam = ['account_id' => $account_id, 'case_id' => $vo['caseId']];
                if ($lock->lockParams($lockParam)) {
                    try {
                        $cache = $ebayCaseModel->where($lockParam)->field('id,case_id,local_order_id')->find();

                        $data = [];
                        $data['case_id'] = $vo['caseId'];
                        $data['transaction_id'] = $vo['transactionId'];
                        $data['item_id'] = $vo['itemId'];

                        $data['account_id'] = $account_id;
                        $data['seller_account'] = $vo['seller'];
                        $data['buyer_account'] = $vo['buyer'];
                        $data['initiates_time'] = strtotime($vo['creationDate']['value']);
                        $data['status'] = $vo['caseStatusEnum'];
                        $data['case_type'] = '01';

                        $data['response_due'] = strtotime($vo['respondByDate']['value']);
                        if ($vo['caseStatusEnum'] == 'CLOSED' || $vo['caseStatusEnum'] == 'CS_CLOSED') {
                            $data['is_close'] = '1';
                        } else {
                            $data['is_close'] = '0';
                        }
                        $data['seller_total_refund'] = $vo['claimAmount']['value'] ?? 0;
                        $data['refund_currency'] = $vo['claimAmount']['currency'] ?? '';


                        /** 添加 本地订单号 **/
                        if ((!empty($data['transaction_id']) || !empty($data['buyer_account'])) && empty($cache['local_order_id'])) {
                            $systemOrder = $this->gerSystemOrder($data['transaction_id'], $data['item_id'], $data['buyer_account'], $account_id, $account['code']);
                            if (!empty($systemOrder)) {
                                $data['local_order_id'] = $systemOrder['id'];
                                $data['order_id'] = $systemOrder['channel_order_number'];
                                $data['order_number'] = $systemOrder['order_number'];
                                $data['buyer_account'] = $systemOrder['buyer_id'];
                            }
                        }

                        if (empty($cache)) {
                            $data['created_time'] = $execute_start;
                            $ebayCaseModel->insert($data);
                        } else {
                            $ebayCaseModel->update($data, ['id' => $cache['id']]);
                        }

                        $lock->unlockParams($lockParam);
                    } catch (Exception $e) {
                        $lock->unlockParams($lockParam);
                        throw new Exception($e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
                    }
                }
                $queue->push(['account_id' => $account_id, 'case_id' => $vo['caseId']]);
                //(new EbayCaseByIdQueue(['account_id' => $account_id, 'case_id' => $vo['caseId']]))->execute();
            }

            $time = ($createTimeTo ? strtotime($createTimeTo) : time()) - 7200;
            $newStartTime = date('Y-m-d H:i:s', $time);
            $time_array = [
                'last_update_time' => $newStartTime,
                'last_download_time' => date('Y-m-d H:i:s'),
                'download_number' => count($res['datas']),
                'download_execute_time' => time() - $execute_start
            ];
            Cache::store('EbayAccount')->ebayLastUpdateTime($account_id, 'case', $time_array);
            return true;
        }
        return false;
    }

    function downCaseById($account_id, $case_id)
    {
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = [
            'userToken' => $account['token'],
            'account_id' => $account['id'],
            'account_name' => $account['account_name'],

            //开发者帐号相关信息；
            'devID' => $account['dev_id'],
            'appID' => $account['app_id'],
            'certID' => $account['cert_id'],
        ];
        $execute_start = time();

        $ebayApi = new EbayPostorderApi($config);
        $caseDetail = $ebayApi->getCaseDetail($case_id);
        if (empty($caseDetail['caseId'])) {
            return false;
        }
        $ebayCaseModel = new EbayCaseModel();
        $lock = Cache::store('Lock');

        $lockParam = ['account_id' => $account_id, 'case_id' => $caseDetail['caseId']];
        if ($lock->lockParams($lockParam)) {
            try {
                $cache = $ebayCaseModel->where($lockParam)->field('id,case_id,local_order_id')->find();

                $data['case_id'] = $case_id;
                $data['transaction_id'] = $caseDetail['transactionId'];
                $data['item_id'] = $caseDetail['itemId'];

                $data['item_title'] = empty($caseDetail['itemDetails']['itemTitle']) ? '' : $caseDetail['itemDetails']['itemTitle'];
                $data['case_type'] = '';
                if ($caseDetail['caseType'] == 'ITEM_NOT_RECEIVED') {
                    $data['case_type'] = '01';
                } elseif ($caseDetail['caseType'] == 'RETURN') {
                    $data['case_type'] = '11';
                }
                $data['return_id'] = $caseDetail['returnId'];
                $data['account_id'] = $account_id;
                $data['seller_account'] = $caseDetail['seller'];
                $data['buyer_account'] = $caseDetail['buyer'];
                $data['initiates_time'] = strtotime($caseDetail['creationDate']['value']);
                $data['status'] = $caseDetail['status'];
                $data['state'] = $caseDetail['caseStateEnum'];
                $data['reason'] = $caseDetail['caseHistoryDetails']['buyerrequested'] ?? '';
                $data['buyer_expected'] = $caseDetail['caseHistoryDetails']['buyerrequested'] ?? '';
                $data['response_due'] = !empty($caseDetail['respondByDate']) ? strtotime($caseDetail['respondByDate']['value']) : strtotime($caseDetail['lastModifiedDate']['value']);
                $data['next_active'] = ''; // add value
                if ($caseDetail['status'] == 'CLOSED' || $caseDetail['status'] == 'CS_CLOSED') {
                    $data['is_close'] = '1';
                } else {
                    $data['is_close'] = '0';
                }
                $data['seller_total_refund'] = $caseDetail['claimAmount']['value'] ?? 0;
                $data['refund_currency'] = $caseDetail['claimAmount']['currency'] ?? '';
                $data['response_history'] = json_encode($caseDetail['caseHistoryDetails']['history'], true);
                $data['update_time'] = $execute_start;

                /** 添加 本地订单号 **/
                if ((!empty($data['transaction_id']) || !empty($data['buyer_account'])) && empty($cache['local_order_id'])) {
                    $systemOrder = $this->gerSystemOrder($data['transaction_id'], $data['item_id'], $data['buyer_account'], $account_id, $account['code']);
                    if (!empty($systemOrder)) {
                        $data['local_order_id'] = $systemOrder['id'];
                        $data['order_id'] = $systemOrder['channel_order_number'];
                        $data['order_number'] = $systemOrder['order_number'];
                        $data['buyer_account'] = $systemOrder['buyer_id'];
                    }
                }

                if (empty($cache)) {
                    $data['created_time'] = $execute_start;
                    $ebayCaseModel->insert($data);
                } else {
                    $ebayCaseModel->update($data, ['id' => $cache['id']]);
                }
                $lock->unlockParams($lockParam);
            } catch (Exception $e) {
                $lock->unlockParams($lockParam);
                throw new Exception($e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
            }
        }
        return true;
    }

    /**
     * ebay 获取 cancel
     */
    public function getCancelList()
    {
        $accountList = Cache::store('EbayAccount')->getTableRecord();
        foreach ($accountList as $k => $v) {
            //开发者信息不全的,直接跳过;
            if (empty($v['dev_id']) || empty($v['app_id']) || empty($v['cert_id']) || empty($v['token'])) {
                continue;
            }
            if ($v['is_invalid'] == 1) {
                $res = $this->downCancel($v['id']);
                sleep(10);
            }
        }
        return false;
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
            'apiVersion' => 'v2',
            'siteId' => 0,
            'authToken' => $account['token'],
            'credentials' => [
                'appId' => $account['app_id'],
                'certId' => $account['cert_id'],
                'devId' => $account['dev_id'],
            ]
        ];
        return $config;
    }


    /**
     * 下载
     *
     * @param unknown $data
     * @return boolean
     *
     */
    function downCancel($account_id, $down_time = 0)
    {
        set_time_limit(0);
        $execute_start = time(); // 执行开始时间

        $createTimeFrom = time() - 3600 * 24;
        $createTimeTo = time();

        // 最后更新时间
        if (!empty($down_time)) {
            $createTimeFrom = strtotime('-' . $down_time . ' days');
        } else {
            $last_update = Cache::store('EbayAccount')->ebayLastUpdateTime($account_id, 'cancel');
            // 距离上次时间不能超过15天（暂时）
            if (!empty(isset($last_update['last_update_time']))) {
                if (time() - strtotime($last_update['last_update_time']) < 3600 * 24 * 15) {
                    $createTimeFrom = strtotime($last_update['last_update_time']);
                } else {
                    $createTimeFrom = strtotime("-15 day");
                }
            }
        }
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);

        $res = $this->searchCancelList($account, $createTimeFrom, $createTimeTo);

        $accountName = $account['account_name'];

        if (!empty($res)) {
            $ebayRequestModel = new EbayRequestModel();
            $queue = new UniqueQueuer(EbayCancelByIdQueue::class);
            $lock = Cache::store('Lock');
            foreach ($res as $vo) {
                $lockParam = ['account_id' => $account_id, 'request_id' => $vo['cancelId']];
                if ($lock->lockParams($lockParam)) {
                    try {
                        $cache = $ebayRequestModel->where($lockParam)->field('id,request_id,local_order_id')->find();

                        $data = [];
                        $data['request_id'] = $vo['cancelId'];
                        $data['account_id'] = $account_id;
                        $data['seller_account'] = $accountName;
                        $data['order_id'] = $vo['legacyOrderId'];
                        $orderids = explode('-', $data['order_id']);
                        $data['item_id'] = $orderids[0];
                        $data['transaction_id'] = $orderids[1] ?? 0;

                        //用来集体查找本地单号；
                        $tids[] = $data['transaction_id'];

                        $data['reason'] = '';
                        if (!empty($vo['cancelReason'])) {
                            $data['reason'] = $vo['cancelReason'];
                        }
                        $data['initiates_time'] = !empty($vo['cancelRequestDate']['value']) ? strtotime($vo['cancelRequestDate']['value']) : 0;
                        $data['status'] = $vo['cancelStatus'];
                        $data['state'] = $vo['cancelState'];

                        $data['is_close'] = ($data['state'] == 'CLOSED') ? 1 : 0;

                        $data['request_type'] = EbayRequest::EBAY_REQUEST_CANCEL;
                        $data['response_due'] = !empty($vo['sellerResponseDueDate']['value']) ? strtotime($vo['sellerResponseDueDate']['value']) : 0;
                        $data['close_date'] = !empty($vo['cancelCloseDate']['value']) ? strtotime($vo['cancelCloseDate']['value']) : 0;
                        $data['close_reason'] = $vo['cancelCloseReason'] ?? '';
                        $data['seller_total_refund'] = $vo['requestRefundAmount']['value'] ?? '';
                        $data['refund_currency'] = $vo['requestRefundAmount']['currency'] ?? '';

                        /** 添加 本地订单号 **/
                        if (!empty($data['order_id']) && empty($cache['local_order_id'])) {
                            $systemOrder = OrderModel::where([
                                'channel_order_number' => $data['order_id'],
                                'channel_id' => ChannelAccountConst::channel_ebay,
                                'channel_account_id' => $account_id
                            ])->field('id,order_number,buyer_id,transaction_id,status')->find();

                            if (!empty($systemOrder)) {
                                $data['local_order_id'] = $systemOrder['id'];
                                $data['order_number'] = $systemOrder['order_number'];
                                $data['buyer_account'] = $systemOrder['buyer_id'];
                                //把订单状态传到后台验证，如果是未发货订单，则放到人工审核
                                $this->markSysemOrderReview($data['local_order_id'], $data['reason']);
                            } else {
                                $order = EbayOrderModel::where(['order_id' => $data['order_id']])->field('buyer_user_id,record_number')->find();
                                if (!empty($order)) {
                                    $data['order_number'] = $account['code'] . '-' . $order['record_number'];
                                    $data['buyer_account'] = $order['buyer_user_id'];
                                }
                            }
                        }

                        if (empty($cache)) {
                            $data['created_time'] = time();
                            $ebayRequestModel->insert($data);
                        } else {
                            $ebayRequestModel->update($data, ['id' => $cache['id']]);
                        }

                        $lock->unlockParams($lockParam);
                    } catch (Exception $e) {
                        $lock->unlockParams($lockParam);
                        throw new Exception($e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
                    }
                }
                $queue->push(['account_id' => $account_id, 'cancel_id' => $vo['cancelId']]);
                //(new EbayCancelByIdQueue(['account_id' => $account_id, 'cancel_id' => $vo['cancelId']]))->execute();
            }

            $time = ($createTimeTo ? strtotime($createTimeTo) : time()) - 7200;
            $newStartTime = date('Y-m-d H:i:s', $time);
            $time_array = [
                'last_update_time' => $newStartTime,
                'last_download_time' => date('Y-m-d H:i:s'),
                'download_number' => count($res),
                'download_execute_time' => time() - $execute_start
            ];
            Cache::store('EbayAccount')->ebayLastUpdateTime($account_id, 'cancel', $time_array);
        }

        return true;
    }


    /**
     * 根据cancel_id下载取消订单；
     * @param $account_id
     * @param $cencel_id
     */
    function downCancelById($account_id, $cencel_id)
    {
        set_time_limit(0);
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);

        //下载详情，不存在，则跳出；
        $cancelDetail = $this->getCancelDetail($account, $cencel_id);
        if (empty($cancelDetail['cancelDetail'])) {
            return false;
        }

        $checkRefund = false;
        $cancelDetail = $cancelDetail['cancelDetail'];
        $lockParam = ['account_id' => $account_id, 'request_id' => $cencel_id];
        $lock = Cache::store('Lock');
        if ($lock->lockParams($lockParam)) {
            try {
                $ebayRequestModel = new EbayRequestModel();
                $cache = $ebayRequestModel->where($lockParam)->field('id,request_id,local_order_id')->find();

                $data = [];
                //开始组装数据；
                $data['request_id'] = $cancelDetail['cancelId'];
                $data['account_id'] = $account_id;
                $data['seller_account'] = $account['account_name'];
                $data['order_id'] = $cancelDetail['legacyOrderId'];
                $orderids = explode('-', $data['order_id']);
                $data['item_id'] = $orderids[0];
                $data['transaction_id'] = empty($orderids[1]) ? 0 : $orderids[1];

                $data['reason'] = param($cancelDetail, 'cancelReason');
                $data['initiates_time'] = !empty($cancelDetail['cancelRequestDate']['value']) ? strtotime($cancelDetail['cancelRequestDate']['value']) : 0;
                $data['status'] = $cancelDetail['cancelStatus'];
                $data['state'] = $cancelDetail['cancelState'];
                $data['is_close'] = 0;
                // 是否关闭
                if ($data['state'] == 'CLOSED') {
                    $data['is_close'] = 1;
                }

                if ($cancelDetail['activityHistories']) {
                    //是否检查退款；
                    if ($cancelDetail['activityHistories'][0]['activityType'] == 'SELLER_CREATE_CANCEL') {
                        $checkRefund = true;
                    }
                    $data['response_history'] = json_encode($cancelDetail['activityHistories'], JSON_UNESCAPED_UNICODE);
                }

                $data['request_type'] = EbayRequest::EBAY_REQUEST_CANCEL;
                $data['response_due'] = !empty($cancelDetail['sellerResponseDueDate']['value']) ? strtotime($cancelDetail['sellerResponseDueDate']['value']) : 0;
                $data['close_date'] = !empty($cancelDetail['cancelCloseDate']['value']) ? strtotime($cancelDetail['cancelCloseDate']['value']) : 0;
                $data['close_reason'] = $cancelDetail['cancelCloseReason'] ?? '';
                $data['seller_total_refund'] = $cancelDetail['requestRefundAmount']['value'] ?? '';
                $data['refund_currency'] = $cancelDetail['requestRefundAmount']['currency'] ?? '';
                $data['update_time'] = time();

                /** 添加 本地订单号 **/
                if (!empty($data['order_id']) && empty($cache['local_order_id'])) {
                    $systemOrder = OrderModel::where([
                        'channel_order_number' => $data['order_id'],
                        'channel_id' => ChannelAccountConst::channel_ebay,
                        'channel_account_id' => $account_id
                    ])->field('id,order_number,buyer_id,transaction_id,status')->find();

                    if (!empty($systemOrder)) {
                        $data['local_order_id'] = $systemOrder['id'];
                        $data['order_number'] = $systemOrder['order_number'];
                        $data['buyer_account'] = $systemOrder['buyer_id'];
                        //把订单状态传到后台验证，如果是未发货订单，则放到人工审核
                        $this->markSysemOrderReview($data['local_order_id'], $data['reason']);
                    } else {
                        $order = EbayOrderModel::where(['order_id' => $data['order_id']])->field('buyer_user_id,record_number')->find();
                        if (!empty($order)) {
                            $data['order_number'] = $account['code'] . '-' . $order['record_number'];
                            $data['buyer_account'] = $order['buyer_user_id'];
                        }
                    }
                }

                if (!empty($cache['local_order_id'])) {
                    $data['local_order_id'] = $cache['local_order_id'];
                }

                if (empty($cache)) {
                    $data['created_time'] = time();
                    $data['id'] = $ebayRequestModel->insertGetId($data);
                } else {
                    $ebayRequestModel->update($data, ['id' => $cache['id']]);
                }

                //当需要检查时，才进检查方法；
                if ($checkRefund && !empty($data['local_order_id'])) {
                    $this->checkCancelOrderRefund($data);
                }
                $lock->unlockParams($lockParam);
            } catch (Exception $e) {
                $lock->unlockParams($lockParam);
                throw new Exception($e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
            }
        }
        return true;
    }


    /**
     * 检查订单状态；
     * @param $data
     * @return bool
     */
    public function checkCancelOrderRefund($data)
    {
        //检查历史记录
        $history = json_decode($data['response_history'], true);
        if (empty($history)) {
            return false;
        }

        if ($history[0]['activityType'] !== 'SELLER_CREATE_CANCEL') {
            return false;
        }

        //系统订单ID；
        if (empty($data['local_order_id'])) {
            return false;
        }

        //如果纠纷状态没关闭，且创建时间小于一小时则跳过，只检查关闭了的或者创建时间超过1小时的纠纷；
        if ($data['state'] != 'CLOSED' && $data['initiates_time'] > time() - 3600) {
            return false;
        }

        //退款状态0失败，1成功；
        $status = 0;
        if (!isset($history[2]) && $data['initiates_time'] <= time() - 86400 - 3600) {
            $status = 0;
        } else if (!empty($history[2]['activityType']) && $history[2]['activityType'] == 'UNKNOWN') {
            $status = 0;
        } else if (!empty($history[2]['activityType']) && $history[2]['activityType'] == 'SYSTEM_NOTIFY_REFUND_STATUS') {
            $status = 1;
        } else if ($data['state'] == 'CLOSED' && (empty($history[1]['activityType']) || $history[1]['activityType'] !== 'SYSTEM_REFUND')) {
            $status = 0;
        }

        try {
            $order = Order::where(['id' => $data['local_order_id']])->field('collection_account account,pay_code txn_id')->find();
            if (empty($order)) {
                return false;
            }
            $params = $order->toArray();
            (new UniqueQueuer(DownPaypalOrderByTxnId::class))->push($params);

            $orderService = new OrderService();
            $orderService->cancelOrderCallBack($data['local_order_id'], $status);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(). '|'. $e->getLine(). '|'. $e->getFile());
        }
    }


    /**
     * 标记系统订单需要人工审核
     * @param $local_order_id 订单ID
     * @param string $reason 原因
     */
    public function markSysemOrderReview($local_order_id, $reason = '')
    {
        try {
            (new OrderHelp())->signYouReviewThe($local_order_id, $reason);
        } catch (\Exception $e) {
            return false;
        }
    }


    public function searchCancelList($account, $createTimeFrom, $createTimeTo)
    {
        $config = $this->getAccountParmas($account);
        $service = new PostOrderService($config);

        $request = new SearchCancellationsRestRequest([
            'creation_date_range_from' => gmdate("Y-m-d\TH:i:s.000\Z", $createTimeFrom),
            'creation_date_range_to' => gmdate("Y-m-d\TH:i:s.000\Z", $createTimeTo),
            'limit' => (string)100,
            'offset' => (string)1,
        ]);
        $offset = 1;
        $datas = [];
        do {
            $request->offset = (string)$offset;
            $response = $service->searchCancellations($request)->toArray();
            if (empty($response['cancellations'])) {
                break;
            }
            $datas = array_merge($datas, $response['cancellations']);
            $offset++;
        } while (count($response['cancellations']) > 0 && $offset <= $response['paginationOutput']['totalPages']);

        return $datas;
    }


    public function searchCaseList($account, $createTimeFrom, $createTimeTo)
    {
        $config = $this->getAccountParmas($account);
        $service = new PostOrderService($config);

        $request = new SearchCasesRestRequest([
            'case_creation_date_range_from' => gmdate("Y-m-d\TH:i:s.000\Z", $createTimeFrom),
            'case_creation_date_range_to' => gmdate("Y-m-d\TH:i:s.000\Z", $createTimeTo),
            'limit' => (string)100,
            'offset' => (string)1,
        ]);
        $offset = 1;
        $datas = [];
        do {
            $request->offset = (string)$offset;
            $response = $service->searchCases($request)->toArray();
            if (empty($response['members'])) {
                break;
            }
            $datas = array_merge($datas, $response['members']);
            $offset++;
        } while (count($response['members']) > 0 && $offset <= $response['paginationOutput']['totalPages']);

        return $datas;
    }


    public function getCancelDetail($account, $cancel_id)
    {
        $config = $this->getAccountParmas($account);
        $service = new PostOrderService($config);

        $request = new GetCancellationRestRequest([
            'cancelId' => (string)$cancel_id
        ]);

        $res = $service->getCancellation($request)->toArray();
        return $res;
    }

    /**
     * ebay 获取 Return
     */
    public function getReturnList()
    {
        $accountList = Cache::store('EbayAccount')->getTableRecord();
        foreach ($accountList as $k => $v) {
            //开发者信息不全的,直接跳过;
            if (empty($v['dev_id']) || empty($v['app_id']) || empty($v['cert_id']) || empty($v['token'])) {
                continue;
            }
            if ($v['is_invalid'] == 1) {
                $res = $this->downReturn($v['id']);
                sleep(10);
            }
        }
        return false;
    }


    /**
     * 拿取纠纷对应的系统单号
     * @param $transaction_id 交易ID
     * @param $item_id 项目ID
     * @param $account_id 帐号ID
     */
    public function gerSystemOrder($transaction_id, $item_id, $buyer, $account_id, $code)
    {
        //返回空值
        if (empty($transaction_id) && empty($item_id)) {
            return [];
        }
        $orderIdArr = [];
        //先找出这个交易ID对应的所有订单
        if (!empty($transaction_id) && !empty($item_id)) {
            $orderIdArr = EbayOrderDetailModel::where([
                'transaction_id' => $transaction_id,
                'item_id' => $item_id
            ])->field('order_id')->column('order_id');
        } else if (!empty($buyer) && !empty($item_id) && !empty($account_id)) {
            $orderIdArr = EbayOrderDetailModel::alias('d')
                ->join(['ebay_order' => 'o'], 'd.oid=o.id')
                ->where([
                    'o.buyer_user_id' => $buyer,
                    'o.account_id' => $account_id,
                    'd.item_id' => $item_id
                ])->field('order_id')->column('d.order_id');
        }

        if (empty($orderIdArr)) {
            return [];
        }
        //找出系统订单
        $systemOrder = OrderModel::where([
            'channel_order_number' => ['in', $orderIdArr],
            'channel_id' => ChannelAccountConst::channel_ebay,
            'channel_account_id' => $account_id
        ])->field('id,order_number,buyer_id,channel_order_number')->find();

        if (empty($systemOrder)) {
            $order = EbayOrderModel::where([
                'order_id' => ['in', $orderIdArr],
                'account_id' => $account_id
            ])->field('order_id,buyer_user_id,record_number')->find();
            if (!empty($order)) {
                $data['id'] = 0;
                $data['order_number'] = $code . '-' . $order['record_number'];
                $data['buyer_id'] = $order['buyer_user_id'];
                $data['channel_order_number'] = $order['order_id'];
                return $data;
            } else {
                return [];
            }
        } else {
            return $systemOrder->toArray();
        }
    }


    function downReturn($account_id, $down_time = 0)
    {
        set_time_limit(0);
        $execute_start = time(); // 执行开始时间

        $createTimeFrom = time() - 3600 * 24;
        $createTimeTo = time();

        // 最后更新时间
        if (!empty($down_time)) {
            $createTimeFrom = strtotime('-' . $down_time . ' days');
        } else {
            $last_update = Cache::store('EbayAccount')->ebayLastUpdateTime($account_id, 'return');
            // 距离上次时间不能超过15天（暂时）
            if (!empty(isset($last_update['last_update_time']))) {
                if (time() - strtotime($last_update['last_update_time']) < 3600 * 24 * 15) {
                    $createTimeFrom = strtotime($last_update['last_update_time']);
                } else {
                    $createTimeFrom = strtotime("-15 day");
                }
            }
        }
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = [
            'userToken' => $account['token'],
            'account_id' => $account['id'],
            'account_name' => $account['account_name'],

            //开发者帐号相关信息；
            'devID' => $account['dev_id'],
            'appID' => $account['app_id'],
            'certID' => $account['cert_id'],
        ];
        $ebayApi = new EbayPostorderApi($config);
        $res = $ebayApi->serchReturnList($createTimeFrom, $createTimeTo);

        if ($res['state'] && !empty($res['datas'])) {
            //下载return的图片文件；
            $filesQueue = new UniqueQueuer(EbayReturnFilesQueue::class);
            $queue = new UniqueQueuer(EbayReturnByIdQueue::class);
            $ebayRequestModel = new EbayRequestModel();
            $lock = Cache::store('Lock');

            foreach ($res['datas'] as $vo) {
                $lockParam = ['account_id' => $account_id, 'request_id' => $vo['returnId']];
                if ($lock->lockParams($lockParam)) {
                    $filesQueue->push(['account_id' => $account_id, 'return_id' => $vo['returnId']]);
                    try {
                        $cache = $ebayRequestModel->where($lockParam)->field('id,request_id,local_order_id')->find();

                        $data = [];
                        $data['request_id'] = $vo['returnId'];
                        $data['account_id'] = $account_id;
                        $data['reason'] = isset($vo['creationInfo']['reason']) ? $vo['creationInfo']['reason'] : '';
                        $data['initiates_time'] = isset($vo['creationInfo']['creationDate']['value']) ? strtotime($vo['creationInfo']['creationDate']['value']) : '';
                        $data['status'] = $vo['status'];
                        $data['state'] = $vo['state'];
                        $data['is_close'] = 0;
                        // 是否关闭
                        if ($data['state'] == 'CLOSED') {
                            $data['is_close'] = 1;
                        }
                        $data['seller_account'] = $vo['sellerLoginName'];
                        $data['buyer_account'] = $vo['buyerLoginName'];
                        $data['flow_type'] = $vo['creationInfo']['type'];
                        $data['item_id'] = isset($vo['creationInfo']['item']['itemId']) ? $vo['creationInfo']['item']['itemId'] : '';
                        $data['transaction_id'] = isset($vo['creationInfo']['item']['transactionId']) ? $vo['creationInfo']['item']['transactionId'] : '';

                        $data['request_type'] = EbayRequest::EBAY_REQUEST_RETURN;
                        $data['response_due'] = isset($vo['sellerResponseDue']['respondByDate']['value']) ? strtotime($vo['sellerResponseDue']['respondByDate']['value']) : '';

                        $data['comments'] = isset($vo['creationInfo']['comments']['content']) ? $vo['creationInfo']['comments']['content'] : '';
                        $data['case_id'] = isset($vo['escalationInfo']['caseId']) ? $vo['escalationInfo']['caseId'] : '';
                        //$data['close_reason'] = isset($returnDetail['closeInfo']['returnCloseReason']) ? $returnDetail['closeInfo']['returnCloseReason'] : '';
                        $data['current_type'] = isset($vo['currentType']) ? $vo['currentType'] : '';

                        // 通过交易号从缓存里面获取平台订单号
                        //if ($data['transaction_id']) {
                        //    $cacheOrderInfo = Cache::store('EbayOrder')->orderByTransid($data['transaction_id']);
                        //    $data['order_id'] = param($cacheOrderInfo, 'order_id');
                        //}

                        // 退款金额
                        $seller_total_refund = 0;
                        $refund_currency = '';
                        if (isset($vo['sellerTotalRefund']['actualRefundAmount']['value'])) {
                            $seller_total_refund = $vo['sellerTotalRefund']['actualRefundAmount']['value']; // 实际退款金额
                            $refund_currency = $vo['sellerTotalRefund']['actualRefundAmount']['currency'];
                        } else {
                            if (isset($vo['sellerTotalRefund']['estimatedRefundAmount']['value'])) {
                                $seller_total_refund = $vo['sellerTotalRefund']['estimatedRefundAmount']['value']; // 将要退款金额
                                $refund_currency = $vo['sellerTotalRefund']['estimatedRefundAmount']['currency'];
                            }
                        }
                        $data['seller_total_refund'] = $seller_total_refund;
                        $data['refund_currency'] = $refund_currency;

                        /** 添加 本地订单号 **/
                        if ((!empty($data['transaction_id']) || !empty($data['buyer_account'])) && empty($cache['local_order_id'])) {
                            $systemOrder = $this->gerSystemOrder($data['transaction_id'], $data['item_id'], $data['buyer_account'], $account_id, $account['code']);
                            if (!empty($systemOrder)) {
                                $data['local_order_id'] = $systemOrder['id'];
                                $data['order_id'] = $systemOrder['channel_order_number'];
                                $data['order_number'] = $systemOrder['order_number'];
                                $data['buyer_account'] = $systemOrder['buyer_id'];
                            }
                        }

                        if (empty($cache)) {
                            $data['created_time'] = time();
                            $ebayRequestModel->insert($data);
                        } else {
                            $ebayRequestModel->update($data, ['id' => $cache['id']]);
                        }

                        $lock->unlockParams($lockParam);
                    } catch (Exception $e) {
                        $lock->unlockParams($lockParam);
                        throw new Exception($e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
                    }
                }
                $queue->push(['account_id' => $account_id, 'return_id' => $vo['returnId']]);
                //(new EbayReturnByIdQueue(['account_id' => $account_id, 'return_id' => $vo['returnId']]))->execute();
            }

            $time = ($createTimeTo ? strtotime($createTimeTo) : time()) - 7200;
            $newStartTime = date('Y-m-d H:i:s', $time);
            $time_array = [
                'last_update_time' => $newStartTime,
                'last_download_time' => date('Y-m-d H:i:s'),
                'download_number' => count($res['datas']),
                'download_execute_time' => time() - $execute_start
            ];
            Cache::store('EbayAccount')->ebayLastUpdateTime($account_id, 'return', $time_array);

            return true;
        }
    }


    /**
     * 根据退款ID拉退款纠纷
     * @param $account_id
     * @param int $down_time
     * @return bool
     */
    function downReturnById($account_id, $return_id)
    {
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = [
            'userToken' => $account['token'],
            'account_id' => $account['id'],
            'account_name' => $account['account_name'],

            //开发者帐号相关信息；
            'devID' => $account['dev_id'],
            'appID' => $account['app_id'],
            'certID' => $account['cert_id'],
        ];

        $ebayApi = new EbayPostorderApi($config);
        $returnDetail = $ebayApi->getReturnDetail($return_id);

        if (empty($returnDetail['summary'])) {
            return false;
        }

        $ebayRequestModel = new EbayRequestModel();
        $lock = Cache::store('Lock');

        $lockParam = ['account_id' => $account_id, 'request_id' => $return_id];
        if ($lock->lockParams($lockParam)) {
            try {
                $cache = $ebayRequestModel->where($lockParam)->field('id,request_id,local_order_id,after_sale_id')->find();

                $data = [];
                $data['request_id'] = $return_id;
                $data['account_id'] = $account_id;
                $data['reason'] = isset($returnDetail['summary']['creationInfo']['reason']) ? $returnDetail['summary']['creationInfo']['reason'] : '';
                $data['initiates_time'] = isset($returnDetail['summary']['creationInfo']['creationDate']['value']) ? strtotime($returnDetail['summary']['creationInfo']['creationDate']['value']) : '';
                $data['status'] = $returnDetail['summary']['status'];
                $data['state'] = $returnDetail['summary']['state'];
                $data['is_close'] = 0;
                // 是否关闭
                if ($data['state'] == 'CLOSED') {
                    $data['is_close'] = 1;
                }
                $data['seller_account'] = $returnDetail['detail']['sellerLoginName'];
                $data['buyer_account'] = $returnDetail['detail']['buyerLoginName'];
                $data['flow_type'] = $returnDetail['summary']['creationInfo']['type'];
                $data['item_id'] = isset($returnDetail['summary']['creationInfo']['item']['itemId']) ? $returnDetail['summary']['creationInfo']['item']['itemId'] : '';
                $data['transaction_id'] = isset($returnDetail['summary']['creationInfo']['item']['transactionId']) ? $returnDetail['summary']['creationInfo']['item']['transactionId'] : '';

                // 通过交易号从缓存里面获取平台订单号
                //if ($data['transaction_id']) {
                //    $cacheOrderInfo = Cache::store('EbayOrder')->orderByTransid($data['transaction_id']);
                //    $data['order_id'] = param($cacheOrderInfo, 'order_id');
                //}

                $data['name'] = isset($returnDetail['detail']['sellerAddress']['name']) ? $returnDetail['detail']['sellerAddress']['name'] : '';
                $data['street1'] = isset($returnDetail['detail']['sellerAddress']['address']['addressLine1']) ? $returnDetail['detail']['sellerAddress']['address']['addressLine1'] : '';
                $data['street2'] = isset($returnDetail['detail']['sellerAddress']['address']['addressLine2']) ? $returnDetail['detail']['sellerAddress']['address']['addressLine2'] : '';
                $data['city'] = isset($returnDetail['detail']['sellerAddress']['address']['city']) ? $returnDetail['detail']['sellerAddress']['address']['city'] : '';
                $data['province'] = isset($returnDetail['detail']['sellerAddress']['address']['stateOrProvince']) ? $returnDetail['detail']['sellerAddress']['address']['stateOrProvince'] : '';

                $data['country'] = isset($returnDetail['detail']['sellerAddress']['address']['country']) ? $returnDetail['detail']['sellerAddress']['address']['country'] : '';
                $data['postal_code'] = isset($returnDetail['detail']['sellerAddress']['address']['postalCode']) ? $returnDetail['detail']['sellerAddress']['address']['postalCode'] : '';

                if (!empty($returnDetail['detail']['responseHistory'])) {
                    $data['response_history'] = json_encode($returnDetail['detail']['responseHistory'], JSON_UNESCAPED_UNICODE);
                    //处理售后处理单里面的部分退款；
                    if (!empty($cache['after_sale_id'])) {
                        $this->handelReturnPartialRefund($cache['after_sale_id'], $returnDetail['detail']['responseHistory']);
                    }
                }

                $data['request_type'] = EbayRequest::EBAY_REQUEST_RETURN;
                $data['response_due'] = isset($returnDetail['summary']['sellerResponseDue']['respondByDate']['value']) ? strtotime($returnDetail['summary']['sellerResponseDue']['respondByDate']['value']) : '';

                $data['comments'] = isset($returnDetail['summary']['creationInfo']['comments']['content']) ? $returnDetail['summary']['creationInfo']['comments']['content'] : '';
                $data['case_id'] = isset($returnDetail['summary']['escalationInfo']['caseId']) ? $returnDetail['summary']['escalationInfo']['caseId'] : '';
                $data['close_reason'] = isset($returnDetail['detail']['closeInfo']['returnCloseReason']) ? $returnDetail['detail']['closeInfo']['returnCloseReason'] : '';
                $data['current_type'] = isset($returnDetail['summary']['currentType']) ? $returnDetail['summary']['currentType'] : '';

                // 退款金额
                $seller_total_refund = 0;
                $refund_currency = '';
                if (isset($returnDetail['summary']['sellerTotalRefund']['actualRefundAmount']['value'])) {
                    $seller_total_refund = $returnDetail['summary']['sellerTotalRefund']['actualRefundAmount']['value']; // 实际退款金额
                    $refund_currency = $returnDetail['summary']['sellerTotalRefund']['actualRefundAmount']['currency'];
                } else {
                    if (isset($returnDetail['summary']['sellerTotalRefund']['estimatedRefundAmount']['value'])) {
                        $seller_total_refund = $returnDetail['summary']['sellerTotalRefund']['estimatedRefundAmount']['value']; // 将要退款金额
                        $refund_currency = $returnDetail['summary']['sellerTotalRefund']['estimatedRefundAmount']['currency'];
                    }
                }
                $data['seller_total_refund'] = $seller_total_refund;
                $data['refund_currency'] = $refund_currency;
                $data['update_time'] = time();

                /** 添加 本地订单号 **/
                if ((!empty($data['transaction_id']) || !empty($data['buyer_account'])) && empty($cache['local_order_id'])) {
                    $systemOrder = $this->gerSystemOrder($data['transaction_id'], $data['item_id'], $data['buyer_account'], $account_id, $account['code']);
                    if (!empty($systemOrder)) {
                        $data['local_order_id'] = $systemOrder['id'];
                        $data['order_id'] = $systemOrder['channel_order_number'];
                        $data['order_number'] = $systemOrder['order_number'];
                        $data['buyer_account'] = $systemOrder['buyer_id'];
                    }
                }

                if (empty($cache)) {
                    $data['created_time'] = time();
                    $ebayRequestModel->insert($data);
                } else {
                    $ebayRequestModel->update($data, ['id' => $cache['id']]);
                }

                $lock->unlockParams($lockParam);
            } catch (Exception $e) {
                $lock->unlockParams($lockParam);
                throw new Exception($e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
            }
        }

        return true;
    }


    public function handelReturnPartialRefund($after_sale_id, $history)
    {
        //1.先找出卖家申请部分退款的部分；
        $partial = [];
        $status = 4;    //付款中；
        foreach ($history as $val) {
            if ($val['activity'] == 'SELLER_OFFER_PARTIAL_REFUND' && !empty($val['attributes'])) {
                $partial['amount'] = $val['attributes']['partialRefundAmount']['value'] ?? 0;
                $partial['currency'] = $val['attributes']['partialRefundAmount']['currency'] ?? '';
                //很有可能再次进这里，如果再次进这里，那又要调到退款中；
                $status = 4;
            }
            //退款成功；
            if ($val['activity'] == 'BUYER_ACCEPTS_PARTIAL_REFUND' && !empty($partial) && !empty($val['attributes'])) {
                if (
                    $partial['amount'] == $val['attributes']['partialRefundAmount']['value'] &&
                    $partial['currency'] = $val['attributes']['partialRefundAmount']['currency']
                ) {
                    $status = 1;
                    break;
                }

                //拒绝退款，退款失败；
            } else if ($val['activity'] == 'BUYER_DECLINE_PARTIAL_REFUND' && !empty($partial) && !empty($val['attributes'])) {
                if (
                    $partial['amount'] == $val['attributes']['partialRefundAmount']['value'] &&
                    $partial['currency'] = $val['attributes']['partialRefundAmount']['currency']
                ) {
                    $status = 0;
                }
            }
        }

        //不存在部分退款的直接结束,还在退款中的，也不推过去；
        if (empty($partial) || $status == 4) {
            return;
        }
        $orderSalerServ = new OrderSaleService();
        $orderSalerServ->ebayPartialRefundCallBack($after_sale_id, $partial, $status);
    }


    /**
     * 64base 转 图片
     *
     * @param unknown $data
     * @param unknown $save_name
     * @throws Exception
     */
    public function stream2Image($data, $save_name)
    {
        header('Content:image/png');
        // 数据流不为空，则进行保存操作
        if (!empty($data)) {
            $data = base64_decode($data); // 解码
            $base_path = ROOT_PATH . '/public/upload/ebay_return';

            $dir = '';
            if (!is_dir($base_path) && !mkdir($base_path, 0666, true)) {
                throw new Exception('目录创建不成功');
            }
            // $save_name = 'ebay-return-'.date('YmdHis').'.jpg';
            $full_path = $base_path . '/' . $save_name;
            // 创建并写入数据流，然后保存文件
            if (@$fp = fopen($full_path, 'w+')) {
                fwrite($fp, $data);
                fclose($fp);
                return true;
            } else {
                return false;
            }
        } else {
            // 没有接收到数据流
            return false;
        }
    }

    /**
     * @node ebay获取case
     */
    public function getInquiriesList()
    {
        $accountList = Cache::store('EbayAccount')->getTableRecord();
        foreach ($accountList as $k => $v) {
            $token = $v['token'];
            if (!empty($token) && $v['is_invalid'] == 1) {

                $res = $this->downInquiries($v['id']);
                sleep(10);
            }
        }

        return false;
    }


    function downInquiries($account_id, $down_time = 0)
    {
        set_time_limit(0);
        $execute_start = time(); // 执行开始时间

        $createTimeFrom = time() - 3600 * 24 * 15;
        $createTimeTo = time();

        // 最后更新时间
        if (!empty($down_time)) {
            $createTimeFrom = strtotime('-' . $down_time . ' days');
        } else {
            $last_update = Cache::store('EbayAccount')->ebayLastUpdateTime($account_id, 'return');
            // 距离上次时间不能超过15天（暂时）
            if (!empty(isset($last_update['last_update_time']))) {
                if (time() - strtotime($last_update['last_update_time']) < 3600 * 24 * 15) {
                    $createTimeFrom = strtotime($last_update['last_update_time']);
                } else {
                    $createTimeFrom = strtotime("-15 day");
                }
            }
        }
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = [
            'userToken' => $account['token'],
            'account_id' => $account['id'],
            'account_name' => $account['account_name'],

            //开发者帐号相关信息；
            'devID' => $account['dev_id'],
            'appID' => $account['app_id'],
            'certID' => $account['cert_id'],
        ];

        $ebay = new EbayPostorderApi($config);
        $res = $ebay->serchInquiriesList($createTimeFrom, $createTimeTo);

        if ($res['state'] && !empty($res['datas'])) {
            $ebayCaseModel = new EbayCaseModel();
            $queue = new UniqueQueuer(EbayQuiriesByIdQueue::class);
            $lock = Cache::store('Lock');

            foreach ($res['datas'] as $vo) {
                $lockParam = ['account_id' => $account_id, 'case_id' => $vo['inquiryId']];
                if ($lock->lockParams($lockParam)) {
                    try {
                        $cache = $ebayCaseModel->where($lockParam)->field('id,case_id,local_order_id')->find();

                        $data = [];
                        $data['case_id'] = $vo['inquiryId'];
                        $data['transaction_id'] = $vo['transactionId'];

                        //装起全部交易号用来一次性找出所有的本地单
                        $tids[] = $data['transaction_id'];
                        $data['item_id'] = $vo['itemId'];

                        $data['account_id'] = $account_id;
                        $data['seller_account'] = $vo['seller'];
                        $data['buyer_account'] = $vo['buyer'];
                        $data['initiates_time'] = strtotime($vo['creationDate']['value']);
                        $data['status'] = $vo['inquiryStatusEnum'];
                        $data['case_type'] = '02';

                        $data['response_due'] = strtotime($vo['respondByDate']['value']);
                        $data['next_active'] = ''; // add value
                        if ($vo['inquiryStatusEnum'] == 'CLOSED' || $vo['inquiryStatusEnum'] == 'CS_CLOSED') {
                            $data['is_close'] = '1';
                        } else {
                            $data['is_close'] = '0';
                        }

                        $data['seller_total_refund'] = isset($vo['claimAmount']['value']) ? $vo['claimAmount']['value'] : 0;
                        $data['refund_currency'] = isset($vo['claimAmount']['currency']) ? $vo['claimAmount']['currency'] : '';

                        /** 添加 本地订单号 **/
                        if ((!empty($data['transaction_id']) || !empty($data['buyer_account'])) && empty($cache['local_order_id'])) {
                            $systemOrder = $this->gerSystemOrder($data['transaction_id'], $data['item_id'], $data['buyer_account'], $account_id, $account['code']);
                            if (!empty($systemOrder)) {
                                $data['local_order_id'] = $systemOrder['id'];
                                $data['order_id'] = $systemOrder['channel_order_number'];
                                $data['order_number'] = $systemOrder['order_number'];
                                $data['buyer_account'] = $systemOrder['buyer_id'];
                            }
                        }

                        if (empty($cache)) {
                            $data['created_time'] = $execute_start;
                            $ebayCaseModel->insert($data);
                        } else {
                            $ebayCaseModel->update($data, ['id' => $cache['id']]);
                        }

                        $lock->unlockParams($lockParam);
                    } catch (Exception $e) {
                        $lock->unlockParams($lockParam);
                        throw new Exception($e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
                    }
                }
                $queue->push(['account_id' => $account_id, 'inquiry_id' => $vo['inquiryId']]);
                //(new EbayQuiriesByIdQueue(['account_id' => $account_id, 'inquiry_id' => $vo['inquiryId']]))->execute();
            }

            $time = ($createTimeTo ? strtotime($createTimeTo) : time()) - 7200;
            $newStartTime = date('Y-m-d H:i:s', $time);
            $time_array = [
                'last_update_time' => $newStartTime,
                'last_download_time' => date('Y-m-d H:i:s'),
                'download_number' => count($res['datas']),
                'download_execute_time' => time() - $execute_start
            ];
            Cache::store('EbayAccount')->ebayLastUpdateTime($account_id, 'inquiries', $time_array);
            return true;
        }
        return false;
    }


    function downInquiriesById($account_id, $inquiry_id)
    {
        set_time_limit(0);
        $account = Cache::store('EbayAccount')->getTableRecord($account_id);
        $config = [
            'userToken' => $account['token'],
            'account_id' => $account['id'],
            'account_name' => $account['account_name'],

            //开发者帐号相关信息；
            'devID' => $account['dev_id'],
            'appID' => $account['app_id'],
            'certID' => $account['cert_id'],
        ];

        $ebay = new EbayPostorderApi($config);

        $caseDetail = $ebay->getInquiryDetail($inquiry_id);
        if (empty($caseDetail['inquiryId'])) {
            return false;
        }

        $ebayCaseModel = new EbayCaseModel();
        $lock = Cache::store('Lock');

        $lockParam = ['account_id' => $account_id, 'case_id' => $caseDetail['inquiryId']];
        if ($lock->lockParams($lockParam)) {
            try {
                $cache = $ebayCaseModel->where($lockParam)->field('id,case_id,local_order_id')->find();

                $data = [];
                $data['case_id'] = $caseDetail['inquiryId'];
                $data['transaction_id'] = $caseDetail['transactionId'];

                $data['item_id'] = $caseDetail['itemId'];

                $data['item_title'] = empty($caseDetail['itemDetails']['itemTitle']) ? '' : $caseDetail['itemDetails']['itemTitle'];

                if (empty($caseDetail['inquiryDetails']['escalationDate'])) {
                    $data['case_type'] = '02'; // 未收到货，普通纠纷。
                } elseif (isset($caseDetail['caseType']) && $caseDetail['caseType'] == 'RETURN') {
                    $data['case_type'] = '01'; // 未收到货，升级纠纷。
                }
                $data['return_id'] = $caseDetail['returnId']?? '';
                $data['account_id'] = $account_id;
                $data['seller_account'] = $caseDetail['seller'];
                $data['buyer_account'] = $caseDetail['buyer'];
                $data['initiates_time'] = strtotime($caseDetail['inquiryDetails']['creationDate']['value']);
                $data['status'] = $caseDetail['status'];
                $data['state'] = $caseDetail['state'];
                $data['reason'] = isset($caseDetail['inquiryHistoryDetails']['buyerrequested']) ? $caseDetail['inquiryHistoryDetails']['buyerrequested'] : '';
                $data['buyer_expected'] = isset($caseDetail['inquiryDetails']['buyerInitialExpectedResolution']) ? $caseDetail['inquiryDetails']['buyerInitialExpectedResolution'] : '';
                $data['response_due'] = strtotime($caseDetail['sellerMakeItRightByDate']['value']);
                $data['next_active'] = ''; // add value
                if ($data['status'] == 'CLOSED' || $data['status'] == 'CS_CLOSED') {
                    $data['is_close'] = '1';
                } else {
                    $data['is_close'] = '0';
                }

                $data['seller_total_refund'] = isset($caseDetail['claimAmount']['value']) ? $caseDetail['claimAmount']['value'] : 0;
                $data['refund_currency'] = isset($caseDetail['claimAmount']['currency']) ? $caseDetail['claimAmount']['currency'] : '';

                // 保存history
                $data['response_history'] = isset($caseDetail['inquiryHistoryDetails']['history']) ? json_encode($caseDetail['inquiryHistoryDetails']['history']) : '';
                $data['update_time'] = time();
                //$history_list = [];
                //if (isset($caseDetail['inquiryHistoryDetails']['history'])) {
                //    if (isset($caseDetail['inquiryHistoryDetails']['history'][0])) {
                //        $history_list = $caseDetail['inquiryHistoryDetails']['history'];
                //    } else {
                //        $history_list[] = $caseDetail['inquiryHistoryDetails']['history'];
                //    }
                //}
                //
                //$history_data = [];
                //foreach ($history_list as $history) {
                //    $auther = $history['actor'];
                //    switch ($auther) {
                //        case 'SYSTEM':
                //            $auther = 'EBAY';
                //            $autherId = 'ebay';
                //            break;
                //        case 'BUYER':
                //            $autherId = $data['buyer_account'];
                //            break;
                //        case 'SELLER':
                //            $autherId = $data['seller_account'];
                //            break;
                //        case 'CSR':
                //            $auther = 'EBAY_CSR';
                //            $autherId = 'ebay_csr';
                //            break;
                //        default:
                //            $autherId = 'unknown';
                //            break;
                //    }
                //
                //    $history_data[] = [
                //        'case_id' => $data['case_id'],
                //        'activity' => $history['action'],
                //        'creation_time' => strtotime($history['date']['value']),
                //        'auther' => $auther,
                //        'autherId' => $autherId,
                //        'notes' => $history['description'],
                //        'created_time' => time()
                //    ];
                //}
                //
                //$list['data'] = $data;
                //$list['history'] = $history_data;

                /** 添加 本地订单号 **/
                if ((!empty($data['transaction_id']) || !empty($data['buyer_account'])) && empty($cache['local_order_id'])) {
                    $systemOrder = $this->gerSystemOrder($data['transaction_id'], $data['item_id'], $data['buyer_account'], $account_id, $account['code']);
                    if (!empty($systemOrder)) {
                        $data['local_order_id'] = $systemOrder['id'];
                        $data['order_id'] = $systemOrder['channel_order_number'];
                        $data['order_number'] = $systemOrder['order_number'];
                        $data['buyer_account'] = $systemOrder['buyer_id'];
                    }
                }

                if (empty($cache)) {
                    $data['created_time'] = time();
                    $ebayCaseModel->insert($data);
                } else {
                    $ebayCaseModel->update($data, ['id' => $cache['id']]);
                }

                $lock->unlockParams($lockParam);
            } catch (Exception $e) {
                $lock->unlockParams($lockParam);
                throw new Exception($e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
            }
        }
        return true;
    }

    /**
     * 检查买家是否有纠纷
     *
     * @param string $buyer_id
     * @return boolean
     */
    function checkBuyerDispute($buyer_id = '')
    {
        // 是否发起纠纷
        $res = EbayCaseModel::field('id')->where([
            'buyer_account' => $buyer_id
        ])->find();
        if (empty($res)) {
            $res = EbayRequestModel::field('id')->where([
                'buyer_account' => $buyer_id
            ])->find();
        }
        if ($res) {
            return true;
        }
        return false;
    }


    /**
     * 获取ebay订单信息
     * @param string $transaction_id
     * @param int $channel_id
     */
    private function getEbayOrderInfo($transaction_id)
    {
        $order = EbayOrderModel::field('order_id')->where(['transaction_id' => $transaction_id])->find();
        return $order;
    }


    /**
     * 二维数组中某个值变成key
     * @param array $data
     * @param string $val
     * @return array
     */
    public function arrValToKey($data, $val)
    {
        $result = [];
        foreach ($data as $key => $vo) {
            $result[$vo[$val]] = $vo;
        }
        return $result;
    }


    /**
     * handel time / time to ebay time
     * */
    private function setTimeToEbayTime($time_str)
    {
        return gmdate("Y-m-d\TH:i:s.000\Z", strtotime($time_str));
    }


    public function getOrderDispute($local_order_id)
    {
        if (empty($local_order_id)) {
            return [];
        }
        $result = [];
        $requests = EbayRequestModel::where(['local_order_id' => $local_order_id])->column('id,request_id dispute_id,request_type,status', 'item_id');
        if (!empty($requests)) {
            foreach ($requests as $item_id => $val) {
                if ($val['status'] == 'CANCEL_CLOSED_FOR_COMMITMENT') {
                    $requesttype = EbayRequest::EBAY_DISPUTE_NOTPAID;
                } else {
                    $requesttype = EbayRequest::getDisputeType($val['request_type']);
                }
                $dispute = [
                    'id' => $val['id'],
                    'dispute_id' => $val['dispute_id'],
                    'dispute_type' => $requesttype
                ];
                $result[$item_id][] = $dispute;
            }
        }
        $cases = EbayCaseModel::where(['local_order_id' => $local_order_id])->column('id,case_id dispute_id,case_type', 'item_id');
        if (!empty($cases)) {
            foreach ($cases as $item_id => $val) {
                $dispute = [
                    'id' => $val['id'],
                    'dispute_id' => $val['dispute_id'],
                    'dispute_type' => EbayRequest::getDisputeType($val['case_type'])
                ];
                $result[$item_id][] = $dispute;
            }
        }
        $return = [];
        if (!empty($result)) {
            foreach ($result as $item_id => $dispute) {
                $return[] = ['item_id' => $item_id, 'dispute' => $dispute];
            }
        }
        return $return;
    }

}