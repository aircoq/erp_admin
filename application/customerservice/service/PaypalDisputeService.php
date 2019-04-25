<?php

namespace app\customerservice\service;

use app\common\cache\Cache;
use app\common\cache\driver\Lock;
use app\common\model\ebay\EbayAccount;
use app\common\model\ebay\EbayOrder;
use app\common\model\Order;
use app\common\model\paypal\PaypalAccount;
use app\common\model\paypal\PaypalAddress;
use app\common\model\paypal\PaypalDispute;
use app\common\model\paypal\PaypalDisputeDetail;
use app\common\model\paypal\PaypalDisputeRecord;
use app\common\model\paypal\PaypalOrder;
use app\common\service\Common;
use app\common\service\UniqueQueuer;
use app\customerservice\queue\PaypalDisputeByIdQueue;
use app\customerservice\queue\PaypalDisputeOperateQueue;
use app\order\queue\DownPaypalOrderByTxnId;
use paypal\PayPalDisputeApi;
use think\Db;
use think\Exception;

/**
 * Created by tb
 * User: tanbin
 * Date: 2016/12/6
 * Time: 18:14
 */
class PaypalDisputeService
{
    protected $model = null;

    protected $detailModel = null;

    protected $recordModel = null;

    protected $lock = null;

    public $operatInQueue = 0;

    public function __construct()
    {
        $this->model = new PaypalDispute();
        $this->detailModel = new PaypalDisputeDetail();
        $this->recordModel = new PaypalDisputeRecord();
        $this->lock = new Lock();
    }


    public function getOperateStatus() : int
    {
        return $this->operatInQueue ? 1 : 0;
    }


    /*
     * paypal纠纷列表；
     */
    public function getLists($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 50;
        $sort_field = empty($params['sortField']) ? 'dispute_create_time' : $params['sortField'];
        $sort_type = empty($params['sortType']) ? 'desc' : $params['sortType'];

        $where = $this->getCondition($params);
        
        $field = 'id,dispute_id,account_id,order_number,local_order_id,buyer_name,currency,gross_amount,reason,dispute_create_time,update_time,status,seller_response_due_date';
        $count = $this->model->where($where)->count();
        $lists = $this->model->where($where)->field($field)->page($page, $pageSize)->order($sort_field, $sort_type)->select();

        $new = [];
        if (!empty($lists)) {
            $accoun_ids = [];
            array_map(function ($val) use (&$accoun_ids) {
                array_push($accoun_ids, $val['account_id']);
            }, $lists);
            $accounts = PaypalAccount::where(['id' => ['in', $accoun_ids]])->column('account_name', 'id');
            foreach ($lists as $val) {
                $tmp = $val->toArray();
                $tmp['local_order_id'] = strval($tmp['local_order_id']);
                $tmp['account_name'] = $accounts[$val['account_id']] ?? '';
                $tmp['status'] = PaypalDisputeConfig::getStatusByInt($tmp['status']);
                $tmp['reason'] = PaypalDisputeConfig::getReasonByInt($tmp['reason']);
                $new[] = $tmp;
            }
        }
        $data = [
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize,
            'sort_field' => $sort_field,
            'sort_type' => $sort_type,
            'data' => $new
        ];
        return $data;
    }


    /**
     * paypal纠纷查询条件；
     * @param $params
     * @return array
     */
    public function getCondition($params)
    {
        $where = [];
        $where['update_time'] = ['>', 0];
        if (isset($params['status'])) {
            if ($params['status'] === '') {
                $where['status'] = ['<>', 5];
            } else {
                $where['status'] = $params['status'];
            }
        }
        if (isset($params['reason']) && $params['reason'] !== '') {
            $where['reason'] = $params['reason'];
        }
        if (!empty($params['account_id'])) {
            $where['account_id'] = $params['account_id'];
        }

        $startData = empty($params['startDate']) ? 0 : strtotime($params['startDate']);
        $endData = empty($params['endDate']) ? 0 : strtotime($params['endDate']) + 86400;
        if ($startData == 0 && $endData > 0) {
            $where['dispute_create_time'] = ['<', $endData];
        } else if ($startData > 0 && $endData == 0) {
            $where['dispute_create_time'] = ['>=', $startData];
        } else if ($startData > 0 && $endData > 0) {
            $where['dispute_create_time'] = ['BETWEEN', [$startData, $endData]];
        }

        if (!empty($params['snType']) && !empty($params['snText'])) {
            switch ($params['snType']) {
                case 'dispute_id':
                    $where['dispute_id'] = $params['snText'];
                    break;
                case 'transaction_id':
                    $where['transaction_id'] = $params['snText'];
                    break;
                case 'order_number':
                    $where['order_number'] = $params['snText'];
                    break;
                case 'buyer_name':
                    $where['buyer_name'] = $params['snText'];
                    break;
                case 'paypal_account':
                    $account = PaypalAccount::where(['account_name' => $params['snText']])->find();
                    if (empty($account)) {
                        $where['account_id'] = -1;
                    } else {
                        $where['account_id'] = $account['id'];
                    }
                    break;
                default:
                    break;
            }
        }

        return $where;
    }


    public function statistics()
    {
        $group = $this->model->where(['status' => ['<>', PaypalDisputeConfig::getStatusInt('RESOLVED')]])
            ->group('status')
            ->field('count(id) total,status')
            ->select();
        $column = ['all', 'WAITING_FOR_SELLER_RESPONSE', 'UNDER_REVIEW', 'WAITING_FOR_BUYER_RESPONSE', 'OPEN', 'OTHER', 'RESOLVED'];
        $data = [];
        $all = 0;
        foreach ($group as $val) {
            $all += $val['total'];
            $data[PaypalDisputeConfig::getStatusByInt($val['status'])] = $val['total'];
        }
        $data['all'] = $all;

        $return = [];
        foreach ($column as $status) {
            $tmp = [];
            $tmp['label'] = $status == 'all' ? '全部待处理' : PaypalDisputeConfig::$allStatusText[$status];
            $tmp['total'] = $data[$status] ?? '';
            $tmp['value'] = $status == 'all' ? '' : PaypalDisputeConfig::getStatusInt($status);
            $return[] = $tmp;
        }

        return $return;
    }


    /**
     * 更新
     * @param $id
     * @return bool
     */
    public function update($id, $type = 1)
    {
        $paypal = $this->model->where(['id' => $id])->field('id,dispute_id,account_id,status')->find();
        if (empty($paypal)) {
            throw new Exception('更新失败，纠纷不存在');
        }
        if ($paypal['status'] == PaypalDisputeConfig::STATUS_RESOLVED) {
            throw new Exception('纠纷状态为已结束，停止更新');
        }
        if ($type) {
            $this->downLoadDisputeDetail($paypal['account_id'], $paypal['dispute_id']);
        } else {
            (new UniqueQueuer(PaypalDisputeByIdQueue::class))->push(['account' => $paypal['account_id'], 'dispute_id' => $paypal['dispute_id']]);
        }
        return true;
    }


    /**
     * 所有帐号；
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function accounts()
    {
        $accounts = PaypalAccount::where(null)->field('account_name label,id value')->limit(3000)->select();
        return $accounts;
    }


    /**
     * paypal详情；
     * @param $id
     * @return array
     */
    public function detail($id)
    {
        $data = [];
        $paypal = $this->model->where(['id' => $id])->find();
        $detail = $this->detailModel->where(['id' => $id])->find();
        if (empty($paypal)) {
            throw new Exception('无效参数：id,纠纷数据不存在');
        }

        $account = Cache::store('PaypalAccount')->getAccountById($paypal['account_id']);

        $data['id'] = $paypal['id'];
        $data['dispute_id'] = $paypal['dispute_id'];
        $data['account_id'] = $paypal['account_id'];
        $data['account_name'] = $account['account_name'];

        $data['order_number'] = $paypal['order_number'];
        $data['local_order_id'] = strval($paypal['local_order_id']);
        $data['buyer_name'] = $paypal['buyer_name'];

        $data['dispute_create_time'] = $paypal['dispute_create_time'];

        $data['reason_int'] = $paypal['reason'];
        $data['reason'] = PaypalDisputeConfig::getReasonByInt($paypal['reason']);
        $data['reason_text'] = PaypalDisputeConfig::getReasonText($paypal['reason'], $paypal['stage']);
        $data['stage'] = PaypalDisputeConfig::getStageByInt($paypal['stage']);
        $data['status'] = PaypalDisputeConfig::getStatusByInt($paypal['status']);
        $data['status_text'] = PaypalDisputeConfig::getStatusText($paypal['status']);

        $data['gross_amount'] = $paypal['gross_amount'];
        $data['dispute_amount'] = $paypal['dispute_amount'];
        $data['currency'] = $paypal['currency'];

        $data['message'] = PaypalDisputeConfig::convMessage($detail['message']);
        $data['offer'] = PaypalDisputeConfig::convOffer($detail['offer']);

        $data['communication_details'] = PaypalDisputeConfig::convCommunication($detail['communication_details']);
        $data['evidences'] = PaypalDisputeConfig::convEvidences($detail['evidences']);

        $data['operation'] = $this->getDetailOperation($detail['operation_type']);

        //操作记录日志
        $data['record'] = $this->getRecord($paypal['id'], $data['doc']);

        return $data;
    }


    /**
     * 拿取日志和提案
     * @param $tid
     * @param array $doc
     * @return array
     */
    public function getRecord($tid, &$doc = [])
    {
        $records = PaypalDisputeRecord::where(['tid' => $tid])->order('update_time', 'desc')->limit(100)->select();
        if (empty($records)) {
            return [];
        }
        $doc = [];
        $data = [];
        foreach ($records as $val) {
            if ($val['type'] == 0) {
                continue;
            }
            $tmp = [
                'update_time' => $val['update_time'],
                'operator' => 'seller',
                'operate_type' => PaypalDisputeConfig::getOperateText($val['type']),
                'status' => $val['status'],
                'error_message' => $val['error_message']
            ];
            array_unshift($data, $tmp);

            $type = PaypalDisputeConfig::$allOperatType[$val['type']];
            if ($type == 'provide_evidence' || $type == 'appeal') {
                $docTmp = [
                    'evidence_type' => PaypalDisputeConfig::$allEvidenceType[$val['evidence_type']],
                    'evidence_type_text' => PaypalDisputeConfig::getEvidenceTypeText($val['evidence_type']),
                    'note' => $val['note'],
                    'file_path' => $val['file_path'],
                    'carrier_name' => $val['carrier_name'],
                    'tracking_number' => $val['tracking_number'],
                    'update_time' => $val['update_time'],
                ];
                array_unshift($doc, $docTmp);
            }
        }

        return $data;
    }


    /**
     * 拿取详情页可用操作
     * @param $operation_type
     * @return array
     */
    public function getDetailOperation($operation_type)
    {
        if (empty($operation_type)) {
            return [];
        }
        if (!is_array($operation_type)) {
            $operation_type = json_decode($operation_type, true);
        }

        return $operation_type;
    }


    /**
     * 添加新地址；
     * @param $data
     * @throws Exception
     */
    public function saveAddress($data)
    {
        $time = time();
        $addressModel = new PaypalAddress();
        $user = Common::getUserInfo();
        $data['update_time'] = $time;
        $data['update_id'] = $user['user_id'];

        if (!empty($data['id'])) {
            $addressModel->update($data, ['id' => $data['id']]);
        } else {
            $data['create_time'] = $time;
            $data['creator_id'] = $user['user_id'];
            $addressModel->allowField(true)->save($data);
        }
    }


    /**
     * 添加新地址；
     * @param $data
     * @throws Exception
     */
    public function address($aid)
    {
        $addressModel = new PaypalAddress();
        $data = $addressModel->where(['account_id' => $aid])
            ->field('concat(postal_code, "|", province, area, city, address_line_1, address_line_2) label, id value')
            ->order('update_time', 'desc')->limit('50')->select();
        return $data;
    }


    /**
     * 拿物流公司；
     * @param $data
     * @throws Exception
     */
    public function refundOrder($id, $params)
    {
        $dispute = $this->model->where(['id' => $id])->find();
        if (empty($dispute)) {
            return [];
        }
        $seller_email = PaypalAccount::where(['id' => $dispute['account_id']])->value('account_name');
        if (empty($seller_email)) {
            return [];
        }

        $where = [
            'account_id' => $dispute['account_id'],
            'payer_email' => $seller_email
        ];

        $startData = empty($params['startDate']) ? 0 : strtotime($params['startDate']);
        $endData = empty($params['endDate']) ? 0 : strtotime($params['endDate']) + 86400;
        if ($startData == 0 && $endData > 0) {
            $where['payment_date'] = ['<', $endData];
        } else if ($startData > 0 && $endData == 0) {
            $where['payment_date'] = ['>=', $startData];
        } else if ($startData > 0 && $endData > 0) {
            $where['payment_date'] = ['BETWEEN', [$startData, $endData]];
        }
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 50;

        //付款方是买家；
        $orders = PaypalOrder::where($where)
            ->field('id,txn_id,receiver_email,amt,mc_currency,payment_date')
            ->page($page, $pageSize)
            ->select();
        return $orders;
    }


    /**
     * 拿物流公司；
     * @param $data
     * @throws Exception
     */
    public function carriers()
    {
        $carriers = [];
        foreach (PaypalDisputeConfig::$allCarrierName as $key => $val) {
            $tmp = ['value' => $key, 'label' => $val];
            $carriers[] = $tmp;
        }
        return $carriers;
    }


    /**
     * 接受赔偿的原因；
     * @param $data
     * @throws Exception
     */
    public function acceptReason()
    {
        $acceptReason = [
            //"DID_NOT_SHIP_ITEM" => '商家无法将商品运回客户',
            1 => '商家无法将商品运回客户',
            //"TOO_TIME_CONSUMING" => '商家需要很长时间才能完成订单',
            '商家需要很长时间才能完成订单',
            //"LOST_IN_MAIL" => '物品在邮件或运输途中丢失',
            '物品在邮件或运输途中丢失',
            //"NOT_ABLE_TO_WIN" => '商家无法找到足够的证据来赢得此争议',
            '商家无法找到足够的证据来赢得此争议',
            //"COMPANY_POLICY" => '商家接受客户声称遵守其内部公司政策',
            '商家接受客户声称遵守其内部公司政策',
            //"REASON_NOT_SET" => '上述原因均不适用，可以使用的默认值'
            '上述原因均不适用，可以使用的默认值'
        ];
        $carriers = [];
        foreach ($acceptReason as $key => $val) {
            $tmp = ['value' => $key, 'label' => $val];
            $carriers[] = $tmp;
        }
        return $carriers;
    }


    /**
     * 队列里处理记录
     * @param $record_id
     * @return bool
     * @throws Exception
     */
    public function operateByRecord($record_id)
    {
        $record = $this->recordModel->where(['id' => $record_id])->find();
        if (empty($record)) {
            return false;
        }
        //完成了就不操作了；
        if ($record['status'] == 1) {
            return true;
        }
        $this->disputeData = $this->model->where(['id' => $record['tid']])->find();
        //纠纷关闭了就不操作了；
        if (PaypalDisputeConfig::getStatusByInt($this->disputeData['status']) == 'RESOLVED') {
            return false;
        }

        $record = $record->toArray();
        $type = PaypalDisputeConfig::$allOperatType[$record['type']];

        $this->recordModel->update(['status' => 2, 'update_time' => time()], ['id' => $record['id']]);

        try {
            switch ($type) {
                case 'send_message':
                    $result = $this->sendMessage($record);
                    break;
                case 'accept_claim':
                    $result = $this->acceptClaim($record);
                    break;
                case 'make_offer':
                    $result = $this->makeOffer($record);
                    break;
                case 'provide_evidence':
                    $result = $this->provideEvidence($record);
                    break;
                case 'appeal':
                    $result = $this->appeal($record);
                    break;
                case 'acknowledge_return_item':
                    $result = $this->acknowledgeReturnItem($record);
                    break;
                default:
                    throw new Exception('未声明处理纠纷的类型');
            }
        } catch (Exception $e) {
            $this->failedRecord($record['id'], $e->getMessage());
            throw new Exception($e->getMessage());
        }

        //上面执行结果；
        if ($result) {
            $this->recordModel->update(['status' => 1, 'update_time' => time()], ['id' => $record['id']]);
            $downParam = ['account' => $this->disputeData['account_id'], 'dispute_id' => $this->disputeData['dispute_id']];
            (new UniqueQueuer(PaypalDisputeByIdQueue::class))->push($downParam);
            return true;
        }

        //执行结果为失败；
        $this->failedRecord($record['id'], '执行失败');
        return false;
    }


    /**
     * @param $recordId
     * @param string $errMessage 记录表里暂时没有错误信息字段，如需要，以后可以加
     */
    public function failedRecord($recordId, $errMessage = '')
    {
        if (empty($this->recordModel)) {
            $this->recordModel = new PaypalDisputeRecord();
        }
        $errMessage = mb_substr($errMessage, 0, 200, 'utf-8');
        $this->recordModel->update(['status' => 3, 'update_time' => time(), 'error_message' => $errMessage], ['id' => $recordId]);
    }


    private $disputeData = null;
    private $detailData = null;
    private $accountData = null;


    /**
     * 检查可否进行操作
     * @param $data
     * @param $type
     * @param $paypal
     * @param $detail
     * @param $account
     * @return int
     * @throws Exception
     */
    public function checkOperateData($data, $type)
    {
        if (empty($data['id'])) {
            throw new Exception('列表ID不能为空');
        }
        $this->disputeData = $this->model->where(['id' => $data['id']])->field('id,dispute_id,account_id,dispute_amount,currency,status,stage')->find();
        $this->detailData = $this->detailModel->where(['id' => $data['id']])->field('operation_type')->find();
        if (empty($this->disputeData) || empty($this->detailData)) {
            throw new Exception('paypal纠纷数据不存在');
        }
        if (empty($this->disputeData['account_id']) || empty($this->disputeData['dispute_id'])) {
            throw new Exception('paypal纠纷数据不完整');
        }
        $operation_type = [];
        if ($this->detailData['operation_type']) {
            $operation_type = json_decode($this->detailData['operation_type'], true);
        }
        $operation_type[] = $type;
        if (!in_array($type, $operation_type)) {
            throw new Exception('未经允许的操作方式');
        }
        if (array_search($type, PaypalDisputeConfig::$allOperatType) === false) {
            throw new Exception('操作方式未记录');
        }
        $this->accountData = Cache::store('PaypalAccount')->getAccountById($this->disputeData['account_id']);
        if (empty($this->accountData)) {
            throw new Exception('paypal帐号不存在');
        }

        if ($this->disputeData['stage'] == 2 && $type == "accept_claim" && !empty($data['refund_amount']) && empty($data['address_id'])) {
            throw new Exception('CHARGEBACK[索赔]阶段的纠纷，部分退 款时，必须填地址信息');
        }
    }


    /**
     * 保存操作的数据,再根据操作的数据进队列还是当前执行;
     * @param $data
     * @return int
     */
    public function saveOperateData($data, $type)
    {
        //检测paypal存不存在，操作类别允不允许；
        $this->checkOperateData($data, $type);
        $user = Common::getUserInfo();

        $record = [];
        $record['tid'] = $data['id'];
        $record['type'] = array_search($type, PaypalDisputeConfig::$allOperatType);
        $record['accept_reason'] = $data['accept_reason'] ?? 0;
        $record['offer_type'] = $data['offer_type'] ?? 0;
        $record['evidence_type'] = $data['evidence_type'] ?? 0;

        //接受赔偿类型
        if ($type == 'accept_claim') {
            if (empty($record['accept_reason'])) {
                $record['accept_reason'] = array_search('REASON_NOT_SET', PaypalDisputeConfig::$allAcceptClaimReason);
            }
            if (empty(PaypalDisputeConfig::$allAcceptClaimReason[$record['accept_reason']])) {
                throw new Exception('退款类型参数accept_reason值：' . $record['accept_reason'] . '对应的数据不存在');
            }
            if (!empty($data['refund_amount']) && $data['refund_amount'] > $this->disputeData['dispute_amount']) {
                throw new Exception('退款金额不可以大于纠纷金额');
            }
        }

        //提议类型
        if ($type == 'make_offer') {
            if (empty(PaypalDisputeConfig::$allOfferType[$record['offer_type']])) {
                throw new Exception('提议类型参数offer_type值：' . $record['offer_type'] . '对应的数据不存在');
            }
            if ($data['offer_amount'] > $this->disputeData['dispute_amount']) {
                throw new Exception('提议退款金额不可以大于纠纷金额');
            }
        }

        //提供证据或者申诉
        if ($type == 'provide_evidence' || $type == 'appeal') {
            if (empty(PaypalDisputeConfig::$allEvidenceType[$record['evidence_type']])) {
                throw new Exception('证据类型参数evidence_type值：' . $record['evidence_type'] . '对应的数据不存在');
            }
        }


        $record['refund_amount'] = $data['refund_amount'] ?? 0;
        $record['offer_amount'] = $data['offer_amount'] ?? 0;
        $record['currency'] = $this->disputeData['currency'];

        $record['address_id'] = $data['address_id'] ?? 0;

        $record['carrier_name'] = $data['carrier_name'] ?? '';
        $record['tracking_number'] = $data['tracking_number'] ?? '';
        $record['refund_ids'] = $data['refund_ids'] ?? '';

        $record['note'] = $data['note'] ?? '';
        $record['message'] = $data['message'] ?? '';

        //证据文件；
        $file = [];
        if (!empty($data['file'])) {
            $filename = 'dispute_id_' . $this->disputeData['account_id']. '_aid_' . $this->disputeData['account_id'] . '_' . time();
            $file = $this->base64DecImg($data['file'], $filename);
        }

        $record['file_name'] = $file['fileName'] ?? '';
        $record['file_path'] = $file['filePath'] ?? '';

        $record['creator_id'] = $user['user_id'];
        $record['update_time'] = time();
        $record['create_time'] = time();

        //把存数据；
        $record['id'] = $this->recordModel->insertGetId($record);

        //如果是队列执行，就放进队列，并停止；
        if ($this->operatInQueue) {
            (new UniqueQueuer(PaypalDisputeOperateQueue::class))->push($record['id']);
            //(new PaypalDisputeOperateQueue($record['id']))->execute();
            return true;
        }

        //非队列执行，则放进执行的队列里；
        try {
            switch ($type) {
                case 'send_message':
                    $result = $this->sendMessage($record);
                    break;
                case 'accept_claim':
                    $result = $this->acceptClaim($record);
                    break;
                case 'make_offer':
                    $result = $this->makeOffer($record);
                    break;
                case 'provide_evidence':
                    $result = $this->provideEvidence($record);
                    break;
                case 'appeal':
                    $result = $this->appeal($record);
                    break;
                case 'acknowledge_return_item':
                    $result = $this->acknowledgeReturnItem($record);
                    break;
                default:
                    throw new Exception('未声明处理纠纷的类型');
            }
        } catch (Exception $e) {
            //执行结果为失败；
            $this->failedRecord($record['id']);
            throw new Exception($e->getMessage());
        }

        //上面执行结果；
        if ($result) {
            $this->recordModel->update(['status' => 1, 'update_time' => time()], ['id' => $record['id']]);
            $downParam = ['account' => $this->disputeData['account_id'], 'dispute_id' => $this->disputeData['dispute_id']];
            (new UniqueQueuer(PaypalDisputeByIdQueue::class))->push($downParam);
            return true;
        }

        //执行结果为失败；
        $this->failedRecord($record['id']);
        return false;
    }


    /**
     * 给卖家发送信息；
     * @param array $data
     * @return bool
     */
    public function sendMessage(Array $data)
    {
        $dispute = $this->disputeData ?? $this->model->where($data['tid'])->find();
        $account = $this->accountData ?? Cache::store('PaypalAccount')->getAccountById($dispute['account_id']);
        if (empty($account)) {
            throw new Exception('paypal帐号不存在');
        }
        $api = new PayPalDisputeApi($account);
        $result = $api->sendMessage($dispute['dispute_id'], $data);
        return $result;
    }


    /**
     * 接受赔偿；
     * @param array $data
     * @return bool
     */
    public function acceptClaim(Array $data)
    {
        $dispute = $this->disputeData ?? $this->model->where($data['tid'])->find();
        $account = $this->accountData ?? Cache::store('PaypalAccount')->getAccountById($dispute['account_id']);
        if (empty($account)) {
            throw new Exception('paypal帐号不存在');
        }

        //备注
        $post['note'] = '';
        if (!empty($data['note'])) {
            $post['note'] = $data['note'];
        }
        //原因
        $post['accept_claim_reason'] = PaypalDisputeConfig::$allAcceptClaimReason[$data['accept_reason']] ?? 'REASON_NOT_SET';
        //地址
        if (!empty($data['address_id'])) {
            $post['return_shipping_address'] = $this->getPostAddress($data['address_id']);
        }

        //退款金额，如果不到则是退一半金额；
        if (!empty($data['refund_amount'])) {
            if (!is_numeric($data['refund_amount'])) {
                throw new Exception('退款金额格式不正确');
            }
            if ($data['refund_amount'] > $dispute['dispute_amount']) {
                throw new Exception('退款金额不可以大于纠纷金额');
            }
            $post['refund_amount'] = ['value' => $data['refund_amount'], 'currency_code' => $data['currency']];
        }

        $api = new PayPalDisputeApi($account);
        $result = $api->acceptClaim($dispute['dispute_id'], $post);
        return $result;
    }


    /**
     * 给卖家发送信息；
     * @param array $data
     * @return bool
     */
    public function makeOffer(Array $data)
    {
        $dispute = $this->disputeData ?? $this->model->where($data['tid'])->find();
        $account = $this->accountData ?? Cache::store('PaypalAccount')->getAccountById($dispute['account_id']);
        if (empty($account)) {
            throw new Exception('paypal帐号不存在');
        }

        if (empty(PaypalDisputeConfig::$allOfferType[$data['offer_type']])) {
            throw new Exception('offer_type值错误，未知提交类别');
        }

        //这些模式不会进行退款
        if (PaypalDisputeConfig::$allOfferType[$data['offer_type']] == 'REPLACEMENT_WITHOUT_REFUND') {
            if (!empty($data['offer_amount'])) {
                throw new Exception('offer_type和offer_amount值错误，此类别只用于替换物品调节方案，不进行退款');
            }
        }
        //else if (PaypalDisputeConfig::$allOfferType[$data['offer_type']] != 'REFUND') {
        //    if (empty($data['offer_amount'])) {
        //        throw new Exception('offer_type和offer_amount值错误，部分退款退换货提议时，不填写金额将会全额退款');
        //    }
        //}

        //备注
        if (!empty($data['note'])) {
            $post['note'] = $data['note'];
        }
        //原因
        $post['offer_type'] = PaypalDisputeConfig::$allOfferType[$data['offer_type']];
        //地址
        if (!empty($data['address_id'])) {
            $post['return_shipping_address'] = $this->getPostAddress($data['address_id']);
        }

        //退款金额，如果不到则是退一半金额；
        if (!empty($data['offer_amount'])) {
            if ($data['offer_amount'] > $dispute['dispute_amount']) {
                throw new Exception('退款金额不可以大于纠纷金额');
            }
            $post['offer_amount'] = ['value' => $data['offer_amount'], 'currency_code' => $data['currency']];
        }

        $api = new PayPalDisputeApi($account);
        $result = $api->makeOffer($dispute['dispute_id'], $post);
        return $result;
    }


    /**
     * 提供证据；
     * @param array $data
     * @return bool
     */
    public function appeal(Array $data)
    {
        $dispute = $this->disputeData ?? $this->model->where($data['tid'])->find();
        $account = $this->accountData ?? Cache::store('PaypalAccount')->getAccountById($dispute['account_id']);
        if (empty($account)) {
            throw new Exception('paypal帐号不存在');
        }

        if (empty(PaypalDisputeConfig::$allEvidenceType[$data['evidence_type']])) {
            throw new Exception('evidence_type参数错误，未知的证据文件类型' . $data['evidence_type']);
        }

        //需要发送的数据；
        $tmp = [];
        $tmp['input']['evidence_type'] = PaypalDisputeConfig::$allEvidenceType[$data['evidence_type']];

        if (!empty($data['carrier_name']) && !empty($data['tracking_number'])) {
            if (empty(PaypalDisputeConfig::$allCarrierName[$data['carrier_name']])) {
                $tmp['input']['evidence_info']['tracking_info'][] = [
                    'carrier_name' => 'OTHER',
                    'carrier_name_other' => $data['carrier_name'],
                    'tracking_number' => $data['tracking_number']
                ];
            } else {
                $tmp['input']['evidence_info']['tracking_info'][] = [
                    'carrier_name' => $data['carrier_name'],
                    'tracking_number' => $data['tracking_number']
                ];
            }
        }

        if (!empty($data['refund_ids'])) {
            $tmp['input']['evidence_info']['refund_ids'] = explode(',', $data['refund_ids']);
        }

        if (!empty($data['note'])) {
            $tmp['input']['notes'] = $data['note'];
        }

        try {
            $post['input'] = $this->jsonPath($tmp['input'], 'appeal'. $dispute['dispute_id']);
            if (!empty($data['file_path'])) {
                $post['file1'] = ROOT_PATH . 'public/'. $data['file_path'];
            }
            //地址
            if (!empty($data['address_id'])) {
                $address = $this->getPostAddress($data['address_id']);
                $post['return_shipping_address'] = $this->jsonPath($address, 'appeal_address_'. $dispute['dispute_id']);
            }

            $api = new PayPalDisputeApi($account);
            $result = $api->appeal($dispute['dispute_id'], $post);
            $this->delJsonPaths();
            return $result;
        } catch (\Exception $e) {
            $this->delJsonPaths();
            throw new Exception($e->getMessage());
        }
    }


    /**
     * 提供证据；
     * @param array $data
     * @return bool
     */
    public function provideEvidence(Array $data)
    {
        $dispute = $this->disputeData ?? $this->model->where($data['tid'])->find();
        $account = $this->accountData ?? Cache::store('PaypalAccount')->getAccountById($dispute['account_id']);
        if (empty($account)) {
            throw new Exception('paypal帐号不存在');
        }

        if (empty(PaypalDisputeConfig::$allEvidenceType[$data['evidence_type']])) {
            throw new Exception('evidence_type参数错误，未知的证据文件类型' . $data['evidence_type']);
        }

        //证据文件；
        if (empty($data['file_path'])) {
            throw new Exception('证据文件不存在');
        }

        //需要发送的数据；
        $tmp = [];
        $tmp['input']['evidence_type'] = PaypalDisputeConfig::$allEvidenceType[$data['evidence_type']];
        if (!empty($data['carrier_name']) && !empty($data['tracking_number'])) {
            if (empty(PaypalDisputeConfig::$allCarrierName[$data['carrier_name']])) {
                $tmp['input']['evidence_info']['tracking_info'][] = [
                    'carrier_name' => 'OTHER',
                    'carrier_name_other' => $data['carrier_name'],
                    'tracking_number' => $data['tracking_number']
                ];
            } else {
                $tmp['input']['evidence_info']['tracking_info'][] = [
                    'carrier_name' => $data['carrier_name'],
                    'tracking_number' => $data['tracking_number']
                ];
            }
        }

        if (!empty($data['refund_ids'])) {
            $tmp['input']['evidence_info']['refund_ids'] = explode(',', $data['refund_ids']);
        }

        if (!empty($data['note'])) {
            $tmp['input']['notes'] = $data['note'];
        }

        if (empty($tmp['input'])) {
            throw new Exception('证据内容不完整');
        }

        try {
            $post['input'] = $this->jsonPath($tmp['input'], 'provide_input_'. $dispute['dispute_id']);
            $post['file1'] = ROOT_PATH . 'public/'. $data['file_path'];
            //地址
            if (!empty($data['address_id'])) {
                $address = $this->getPostAddress($data['address_id']);
                $post['return_shipping_address'] = $this->jsonPath($address, 'provide_address_'. $dispute['dispute_id']);
            }

            $api = new PayPalDisputeApi($account);
            $result = $api->provideEvidence($dispute['dispute_id'], $post);
            $this->delJsonPaths();
            return $result;
        } catch (\Exception $e) {
            $this->delJsonPaths();
            throw new Exception($e->getMessage());
        }
    }


    public function jsonPath(array $data, $fileName = '')
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $dir = 'download/paypal/' . date('Y-m-d');
        $path = ROOT_PATH . 'public/'. $dir;
        try {
            if (!is_dir($path) && !mkdir($path, 0777, true)) {
                return false;
            }
            $path = $path. '/'. $fileName. '_'. time(). '.json';
            file_put_contents($path, $json);
            $this->jsonPaths[] = $path;
            return $path;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . '|' . $e->getLine() . '|' . $e->getFile());
        }
    }

    private $jsonPaths = [];

    public function delJsonPaths()
    {
        if (empty($this->jsonPaths)) {
            return true;
        }
        foreach ($this->jsonPaths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        return true;
    }


    /**
     * 确认收到客户货物；
     * @param array $data
     * @return bool
     */
    public function acknowledgeReturnItem(Array $data)
    {
        $dispute = $this->disputeData ?? $this->model->where($data['tid'])->find();
        $account = $this->accountData ?? Cache::store('PaypalAccount')->getAccountById($dispute['account_id']);
        if (empty($account)) {
            throw new Exception('paypal帐号不存在');
        }

        $api = new PayPalDisputeApi($account);
        $post = empty($data['note']) ? [] : ['note' => $data['note']];
        $result = $api->acknowledgeReturnItem($dispute['dispute_id'], $post);
        return $result;
    }


    /**
     * 反编译data/base64数据流并创建图片文件
     *
     * @author Lonny ciwdream@gmail.com
     * @param string $baseData data/base64数据流
     * @param string $Dir 存放图片文件目录
     * @param string $fileName 图片文件名称(不含文件后缀)
     * @return mixed 返回新创建文件路径或布尔类型
     */
    function base64DecImg($baseData, $fileName)
    {
        $dir = 'download/paypal/' . date('Y-m-d');
        $imgPath = ROOT_PATH . '/public/' . '/' . $dir;
        try {
            if (!is_dir($imgPath) && !mkdir($imgPath, 0777, true)) {
                return false;
            }
            $expData = explode(';', $baseData);
            $postfix = explode('/', $expData[0]);
            if (strstr($postfix[0], 'image')) {
                $postfix = $postfix[1] == 'jpeg' ? 'jpg' : $postfix[1];
                $storageDir = $imgPath . '/' . $fileName . '.' . $postfix;
                $export = base64_decode(str_replace("{$expData[0]};base64,", '', $baseData));
                file_put_contents($storageDir, $export);
                return [
                    'fileName' => $fileName,
                    'filePath' => $dir . '/' . $fileName . '.' . $postfix
                ];
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . '|' . $e->getLine() . '|' . $e->getFile());
        }
    }


    public function getPostAddress($address_id)
    {
        $field = 'address_line_1,address_line_2,country_code,province admin_area_1,city admin_area_2,area admin_area_3,postal_code';
        $address = PaypalAddress::where(['id' => $address_id])->field($field)->find();
        if (empty($address)) {
            return [];
        }
        return $address->toArray();
    }


    /**
     * 下载paypal纠纷；
     * @param $account_id
     * @param $down_time
     * @throws Exception
     */
    public function downLoadDispute($account_id, $down_time)
    {
        $account = $this->getPaypalAccount($account_id);
        if (empty($account)) {
            throw new Exception('Paypal帐号不存在');
        }

        //传过来的account_id可以是帐号名称，所以这里要重新附值一下；
        $account_id = $account['id'];
        $execute_time = time();

        //默认从7天前开始；
        $start_time = strtotime('-7 days');
        //如果限定了下载时间，则从下载时间开始；
        if (!empty($down_time) && $down_time > 0) {
            $start_time = strtotime('-' . $down_time . ' days');
        } else {
            //如果没有规定下载时间，则应该去查历史更新时间，按历史时间提前4小时；
            $last_update_time = Cache::store('PaypalAccount')->getDisputeSyncTime($account_id);
            if (!empty($last_update_time['last_update_time'])) {
                $start_time = strtotime($last_update_time['last_update_time']) - 3600 * 4;
            }
        }

        $gstart_time = gmdate("Y-m-d\TH:i:s.000\Z", $start_time);

        //开始抓取;
        $api = new PayPalDisputeApi($account);
        $lists = $api->getDispute(['start_time' => $gstart_time]);
        $this->handelDisputes($lists, $account_id);

        //抓完存缓存；
        $cache_time = ['last_update_time' => date('Y-m-d H:i:s', $execute_time), 'count' => count($lists)];
        Cache::store('PaypalAccount')->setDisputeSyncTime($account_id, $cache_time);
    }


    /**
     * 获取paypal帐号信息；
     * @param int $account_id
     * @return array
     */
    public function getPaypalAccount($account_id = 0)
    {
        if (empty($account_id)) {
            return Cache::store('PaypalAccount')->getTableRecord();
        }
        if (is_numeric($account_id)) {
            return Cache::store('PaypalAccount')->getTableRecord($account_id);
        } else {
            $account = PaypalAccount::where(['name' => $account_id])->find();
            if (empty($account)) {
                return [];
            } else {
                return $account->toArray();
            }
        }
    }


    /**
     * 处理抓下来的纠纷简化列表；
     * @param $lists
     * @param $account
     */
    public function handelDisputes($lists, $account_id)
    {
        if (empty($lists)) {
            return true;
        }

        $queue = new UniqueQueuer(PaypalDisputeByIdQueue::class);
        foreach ($lists as $val) {
            $where = $lockParams = ['account_id' => $account_id, 'dispute_id' => $val['dispute_id']];
            $lockParams['action'] = 'downloadPaypalDispute';
            try {
                //$this->lock->unlockParams($lockParams);
                if ($this->lock->lockParams($lockParams)) {
                    $old = $this->model->where($where)->find();
                    $time = time();
                    if (empty($old)) {
                        $old = [];
                    } else {
                        $old = $old->toArray();
                    }

                    //组合新数据；
                    $data['id'] = $old['id'] ?? 0;

                    $data['dispute_id'] = $val['dispute_id'];
                    $data['account_id'] = $account_id;

                    //reason,status
                    $data['reason'] = empty($val['reason']) ? 0 : PaypalDisputeConfig::getResonInt($val['reason']);
                    $data['status'] = empty($val['status']) ? 0 : PaypalDisputeConfig::getStatusInt($val['status']);

                    //纠纷金额
                    $data['dispute_amount'] = 0;
                    $data['currency'] = '';
                    if (!empty($val['dispute_amount'])) {
                        $data['dispute_amount'] = $val['dispute_amount']['value'];
                        $data['currency'] = $val['dispute_amount']['currency_code'];
                    }

                    //dispute创建时间
                    $data['dispute_create_time'] = strtotime($val['create_time']);
                    $data['dispute_update_time'] = strtotime($val['update_time']);

                    //本地创建更新时间
                    $data['update_time'] = $time;
                    if (empty($old)) {
                        $data['create_time'] = $time;
                    }

                    //保存数据；
                    if (empty($data['id'])) {
                        //把比较长的，可能用text类型的字段标出来，防止以后出错；
                        $detail = ['message' => '', 'evidences' => '', 'communication_details' => '', 'offer' => ''];
                        try {
                            //详情表数据；
                            Db::startTrans();
                            unset($data['id']);
                            $detail['id'] = $this->model->insertGetId($data);
                            $this->detailModel->insert($detail);
                            Db::commit();
                        } catch (Exception $e) {
                            Db::rollback();
                        }
                    } else {
                        $this->model->update($data, ['id' => $data['id']]);
                    }

                    //解锁，放进队列；
                    $this->lock->unlockParams($lockParams);
                    $queue->push(['account' => $account_id, 'dispute_id' => $val['dispute_id']]);
                    //(new PaypalDisputeByIdQueue(['account' => $account_id, 'dispute_id' => $val['dispute_id']]))->execute();
                }
            } catch (Exception $e) {
                $this->lock->unlockParams($lockParams);
                throw new Exception($e->getMessage() . '|' . $e->getLine() . '|' . $e->getFile());
            }
        }
        return true;
    }


    /**
     * 根据帐号ID和paypal纠纷ID下载纠纷;
     * @param $account_id
     * @param $dispute_id
     */
    public function downLoadDisputeDetail($account_id, $dispute_ids)
    {
        $account = $this->getPaypalAccount($account_id);
        if (empty($account)) {
            throw new Exception('Paypal帐号不存在');
        }

        //传过来的account_id可以是帐号名称，所以这里要重新附值一下；
        $account_id = $account['id'];
        if (!is_array($dispute_ids)) {
            $dispute_ids = explode(',', $dispute_ids);
        }

        //开始抓取;
        $api = new PayPalDisputeApi($account);
        foreach ($dispute_ids as $dispute_id) {
            $dispute = $api->getDisputeDetail($dispute_id);
            $this->handelDisputesDetail($dispute, $account_id);
        }
        return true;
    }


    public function handelDisputesDetail($dispute, $account_id)
    {
        $where = $lockParams = ['account_id' => $account_id, 'dispute_id' => $dispute['dispute_id']];
        $lockParams['action'] = 'downloadPaypalDispute';
        try {
            //$this->lock->unlockParams($lockParams);
            if ($this->lock->lockParams($lockParams)) {
                $old = $this->model->where($where)->find();
                $time = time();
                if (empty($old)) {
                    $old = [];
                } else {
                    $old = $old->toArray();
                }

                //组合新数据；
                $data['id'] = $old['id'] ?? 0;

                $data['dispute_id'] = $dispute['dispute_id'];
                $data['account_id'] = $account_id;

                //reason,status
                $data['reason'] = empty($dispute['reason']) ? 0 : PaypalDisputeConfig::getResonInt($dispute['reason']);
                $data['status'] = empty($dispute['status']) ? 0 : PaypalDisputeConfig::getStatusInt($dispute['status']);

                //纠纷金额
                $data['dispute_amount'] = 0;
                $data['currency'] = '';
                if (!empty($dispute['dispute_amount'])) {
                    $data['dispute_amount'] = $dispute['dispute_amount']['value'];
                    $data['currency'] = $dispute['dispute_amount']['currency_code'];
                }

                //dispute创建时间
                $data['dispute_create_time'] = strtotime($dispute['create_time']);
                $data['dispute_update_time'] = strtotime($dispute['update_time']);

                //本地创建更新时间
                $data['update_time'] = $time;
                if (empty($old)) {
                    $data['create_time'] = $time;
                }

                //以下为详情里面才会有的字段；
                //纠纷买家的交易号；
                $transaction = $this->getTransactionData($dispute, $account_id);
                $data = array_merge($data, $transaction);

                //目前纠纷处于哪个纠段；
                $data['stage'] = PaypalDisputeConfig::getStageInt($dispute['dispute_life_cycle_stage']);
                //渠道
                $data['dispute_channel'] = PaypalDisputeConfig::getChannelInt($dispute['dispute_channel']);

                //客户未按时回复时间
                $data['buyer_response_due_date'] = empty($dispute['buyer_response_due_date']) ? 0 : strtotime($dispute['buyer_response_due_date']);
                //商户未按时回复时间
                $data['seller_response_due_date'] = empty($dispute['seller_response_due_date']) ? 0 : strtotime($dispute['seller_response_due_date']);

                //如果有处理结果，才有这个字段
                if (!empty($dispute['dispute_outcome']['outcome_code'])) {
                    $data['outcome_code'] = PaypalDisputeConfig::getOutcomeCode($dispute['dispute_outcome']['outcome_code']);
                }
                //如果有退款金额，才记录；
                if (!empty($dispute['dispute_outcome']['amount_refunded']['value'])) {
                    $data['refund_amount'] = $dispute['dispute_outcome']['amount_refunded']['value'];
                }

                //详情
                $detail = [];
                $links = $dispute['links'] ?? [];
                $detail['operation_type'] = $this->getOperationType($links);

                //用于识别信用卡退款原因的代码
                $detail['external_reason_code'] = $dispute['external_reason_code'] ?? '';

                //信息
                $detail['message'] = empty($dispute['messages']) ? '' : json_encode($dispute['messages'], JSON_UNESCAPED_UNICODE);

                //证据文件
                $detail['evidences'] = empty($dispute['evidences']) ? '' : json_encode($dispute['evidences'], JSON_UNESCAPED_UNICODE);

                //商家提供给客户的联系方式，用于分享他们的证据文件。
                $detail['communication_details'] = empty($dispute['communication_details']) ? '' : json_encode($dispute['communication_details'], JSON_UNESCAPED_UNICODE);

                //商家提出的争议提议。
                $detail['offer'] = empty($dispute['offer']) ? '' : json_encode($dispute['offer'], JSON_UNESCAPED_UNICODE);

                //争议的结果
                $detail['dispute_outcome'] = empty($dispute['dispute_outcome']) ? '' : json_encode($dispute['dispute_outcome'], JSON_UNESCAPED_UNICODE);

                //保存数据；
                try {
                    //详情表数据；
                    Db::startTrans();
                    if (empty($data['id'])) {
                        unset($data['id']);
                        $detail['id'] = $this->model->insertGetId($data);
                        $this->detailModel->insert($detail);
                    } else {
                        $this->model->update($data, ['id' => $data['id']]);
                        if ($count = $this->detailModel->where(['id' => $data['id']])->count()) {
                            $this->detailModel->update($detail, ['id' => $data['id']]);
                        } else {
                            $detail['id'] = $data['id'];
                            $this->detailModel->insert($detail);
                        }
                    }
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    throw $e;
                }
                //解锁，放进队列；
                $this->lock->unlockParams($lockParams);
            }
        } catch (Exception $e) {
            $this->lock->unlockParams($lockParams);
            throw new Exception($e->getMessage() . '|' . $e->getLine() . '|' . $e->getFile());
        }
        return true;
    }


    /**
     * 找出纠纷里面的纠纷和客户相关的数据；
     * @param $dispute
     */
    public function getTransactionData($dispute, $account_id)
    {
        $data = [];
        if (empty($transaction = $dispute['disputed_transactions'][0])) {
            return $data;
        }
        $data['transaction_id'] = $transaction['seller_transaction_id'];
        $data['buyer_name'] = empty($transaction['buyer']['name']) ? '' : $transaction['buyer']['name'];
        if (!empty($transaction['gross_amount'])) {
            $data['gross_amount'] = $transaction['gross_amount']['value'];
        }

        //查看有没有本地paypal订单，没有就去下载；
        $paypalOrder = PaypalOrder::where(['txn_id' => $data['transaction_id']])->find();
        if (empty($paypalOrder)) {
            (new UniqueQueuer(DownPaypalOrderByTxnId::class))->push(['account' => $account_id, 'txn_id' => $data['transaction_id']]);
        } else {
            $data['paypal_order_id'] = $paypalOrder['id'];
        }

        //查看ebay订单系统订单
        $ebay_order = EbayOrder::where(['payment_transaction_id' => $data['transaction_id'], 'record_number' => ['>', 0]])->find();
        if (!empty($ebay_order)) {
            $code = EbayAccount::where(['id' => $ebay_order['account_id']])->value('code');
            $data['order_number'] = strval($code) . '-' . $ebay_order['record_number'];
            $order = Order::where(['order_number' => $data['order_number']])->find();
            if (!empty($order)) {
                $data['local_order_id'] = $order['id'];
            }
        }

        return $data;
    }


    /**
     * 找出当前这个纠纷有哪些操作
     * @param $links
     * @return array|string
     */
    public function getOperationType($links)
    {
        if (empty($links)) {
            return '';
        }
        $typeArr = [];
        foreach ($links as $val) {
            if ($val['rel'] !== 'self') {
                $typeArr[] = $val['rel'];
            }
        }
        if (empty($typeArr)) {
            return '';
        }
        return json_encode($typeArr, JSON_UNESCAPED_UNICODE);
    }
}