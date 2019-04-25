<?php

namespace app\common\model\aliexpress;

use app\common\cache\Cache;
use app\common\model\aliexpress\AliexpressOnlineOrderDetail;
use app\common\service\ChannelAccountConst;
use app\common\traits\ModelFilter;
use app\customerservice\service\MsgRuleHelp;
use erp\ErpModel;
use think\Db;
use think\Exception;
use think\Model;
use app\order\service\AliOrderServer;

class AliexpressOnlineOrder extends ErpModel
{
   
    use ModelFilter;
    
    public function scopeOrder(\erp\ErpQuery $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.account_id', 'in', $params);
        }
    }
    
    protected $autoWriteTimestamp = true;

    //protected $auto = ['order_status'];

    const PLACE_ORDER_SUCCESS = 1;
    const IN_CANCEL = 2;
    const WAIT_SELLER_SEND_GOODS = 3;
    const SELLER_PART_SEND_GOODS = 4;
    const WAIT_BUYER_ACCEPT_GOODS = 5;
    const FUND_PROCESSING = 6;
    const FINISH = 7;
    const IN_ISSUE = 8;
    const IN_FROZEN = 9;
    const WAIT_SELLER_EXAMINE_MONEY = 10;
    const RISK_CONTROL = 11;
    const ALI_ORDER_STATUS_DISPLAY = [
        self::PLACE_ORDER_SUCCESS => '等待买家付款',
        self::IN_CANCEL => '买家申请取消',
        self::WAIT_SELLER_SEND_GOODS => '等待您发货',
        self::SELLER_PART_SEND_GOODS => '部分发货',
        self::WAIT_BUYER_ACCEPT_GOODS => '等待买家收货',
        self::FUND_PROCESSING => '资金处理中',
        self::FINISH => '已结束的订单',
        self::IN_ISSUE => '含纠纷的订单',
        self::IN_FROZEN => '冻结中的订单',
        self::WAIT_SELLER_EXAMINE_MONEY => '等待您确认金额',
        self::RISK_CONTROL => '风控24小时',
    ];
    const ALI_ORDER_STATUS_PLATFORM = [
        self::PLACE_ORDER_SUCCESS => 'PLACE_ORDER_SUCCESS',
        self::IN_CANCEL => 'IN_CANCEL',
        self::WAIT_SELLER_SEND_GOODS => 'WAIT_SELLER_SEND_GOODS',
        self::SELLER_PART_SEND_GOODS => 'SELLER_PART_SEND_GOODS',
        self::WAIT_BUYER_ACCEPT_GOODS => 'WAIT_BUYER_ACCEPT_GOODS',
        self::FUND_PROCESSING => 'FUND_PROCESSING',
        self::FINISH => 'FINISH',
        self::IN_ISSUE => 'IN_ISSUE',
        self::IN_FROZEN => 'IN_FROZEN',
        self::WAIT_SELLER_EXAMINE_MONEY => 'WAIT_SELLER_EXAMINE_MONEY',
        self::RISK_CONTROL => 'RISK_CONTROL',
    ];

    const LOAN_STATUS = [
        'loan_none' => 0,//无放款
        'wait_loan' => 1,//等待放款
        'loan_ok' => 2//放款成功
    ];

    /**
     * PLACE_ORDER_SUCCESS:等待买家付款;
     * IN_CANCEL:买家申请取消; 
     * WAIT_SELLER_SEND_GOODS:等待您发货; 
     * SELLER_PART_SEND_GOODS:部分发货; 
     * WAIT_BUYER_ACCEPT_GOODS:等待买家收货; 
     * FUND_PROCESSING:买家确认收货后，等待退放款处理的状态; 
     * FINISH:已结束的订单; 
     * IN_ISSUE:含纠纷的订单; 
     * IN_FROZEN:冻结中的订单; 
     * WAIT_SELLER_EXAMINE_MONEY:等待您确认金额; 
     * RISK_CONTROL:订单处于风控24小时中，从买家在线支付完成后开始，持续24小时。
     */
    public function orderStatusAttr($value)
    {
        if (!$value) {
            //return '';
        }
        $orderStatus = array_flip(static::ALI_ORDER_STATUS_PLATFORM);
        $status = isset($orderStatus[$value]) ? $orderStatus[$value] : '';
        return $status;
    }

    public function getOrderStatusAttr($value)
    {
        $orderStatus = static::ALI_ORDER_STATUS_DISPLAY;
        $status = isset($orderStatus[$value]) ? $orderStatus[$value] : '';
        return $status;
    }

    public function setLoanStatusAttr($value)
    {
        $loanStatus = static::LOAN_STATUS;
        return $loanStatus[$value] ?? 0;
    }

    /**
     * 添加订单
     */
    public function addOrder($data = [], $isUpdate = FALSE)
    {
        $result = $this->data($data)->isUpdate($isUpdate)->save();
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * 统计用户是否已经拉取过订单
     * @param number $userId
     */
    public function findOrderByUserId($accountId = 0)
    {
        $result = $this->field('id')->where('account_id', $accountId)->find();
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * 更新订单状态
     * @param number $orderID
     * @param string $orderStatus
     * @param $flag PARENT  CHILD
     */
    public function updateOrderStatus($data = [], $orderID = 0)
    {
        $this->save($data, ['order_id' => $orderID]);
        //$code = $this->save($data,['order_id' => $orderID]);
        //return $code;
    }

    /**
     * 根据账号获取未支付的订单
     * @param number $accountId
     */
    //public function getWaitPayment($accountId = 0, $order_status = 1)
    public function getOrderByStatus($accountId = 0, $order_status = 1)
    {
        $where['account_id'] = $accountId;
        $result = self::scope('status', $order_status)->field('order_id, account_id')->where($where)->select();
        //$result = $this->field('order_id, account_id')->where($where)->select();
        return $result;
    }

    /**
     * 判断订单是否存在
     * @param number $orderId
     * @param number $gmtCreate
     */
    public function isHas($orderId = 0, $gmtCreate = 0)
    {
        $where['order_id'] = $orderId;
        $where['gmt_create'] = $gmtCreate;
        $result = $this->field('id')->where($where)->find();
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * 更新订单
     * @param number $orderId
     * @param number $gmtCreate
     */
    public function updateOrder($data = [], $orderId = 0, $gmtCreate = 0)
    {
        $where['gmt_create'] = $gmtCreate;
        $where['order_id'] = $orderId;
        if ($this->save($data, $where)) {
            return true;
        }
        return false;
    }

    /** 创建时间获取器
     * @param $value
     * @return int
     */
    public function getCreateTimeAttr($value)
    {
        if (is_numeric($value)) {
            return $value;
        } else {
            return strtotime($value);
        }
    }

    public static function getCount($where = [])
    {
        $count = self::where($where)->count();
        return $count;
    }

    public function detail()
    {
        return $this->hasMany(AliexpressOnlineOrderDetail::class, 'aliexpress_online_order_id', 'id');
    }

    /**
     * 保存订单数据
     * @param array $orderData
     * @param array $productData
     * @param string $lastTime 上次拉取的时间 //服务器时间
     * @return type
     * @throws Exception
     */
    public function saveOrder(array $orderData, array $productData, $lastTime = 0)
    {
        if (empty($orderData) || empty($productData)) {
            throw new Exception('订单数据不完整');
        }
        if (!Cache::store('partition')->getPartition('AliexpressOnlineOrder', $orderData['gmt_create'])) {
            Cache::store('partition')->setPartition('AliexpressOnlineOrder', $orderData['gmt_create']);
        }
        Db::startTrans();
        try {
            if (isset($orderData['id']) && $orderData['id']) {
                
                /*
                 * 检查订单是否需要拉入的人工审核
                 * wangwei 2019-1-22 18:06:52
                 */
                (new AliOrderServer())->checkStatusChangeOrder($orderData);
                
                $this->save($orderData, ['id' => $orderData['id']]);
                $orderDetailModel = new AliexpressOnlineOrderDetail();
                $orderDetailModel->updateOrderDetail($productData);
            } else {
                $this->isUpdate(false)->allowField(true)->data($orderData, true)->save();
                $id = intval($this->db()->getLastInsID());
                //写入主表ID
                foreach ($productData as &$val) {
                    $orderDetailModel = new AliexpressOnlineOrderDetail();
                    $val['aliexpress_online_order_id'] = $id;
                    $orderDetailModel->allowField(true)->save($val);
                }
            }
            
            /**
             * 触发订单事件
             * wangwei 2018-11-5 17:18:01
             */
            $event_name = '';
            $channel_id = ChannelAccountConst::channel_aliExpress;
            $account_id = $orderData['account_id'];
            $channel_order_number = $orderData['order_id'];
            //匹配近两个月订单
            if(param($orderData, 'gmt_create', 0) > strtotime('-60day')){
                if(param($orderData, 'gmt_pay_time', 0) > 0 && $orderData['order_status']=='WAIT_SELLER_SEND_GOODS'){//订单收到买家付款
                    $event_name = 'E2';
                }else if(param($orderData, 'gmt_pay_time', 0) == 0 && $orderData['order_status']=='PLACE_ORDER_SUCCESS'){//订单未付款
                    $event_name = 'E9';
                }
            }
//             defined('___WRITE_LOG___') || define('___WRITE_LOG___',true);
//             $msgRuleService = new MsgRuleHelp();
//             $msgRuleService->log('订单数据:' . print_r($orderData, 1));
//             $msgRuleService->log('订单明细:' . print_r($productData, 1));
            if($event_name){
                $channel_order = $orderData;
                $channel_order['id'] = isset($orderData['id']) && $orderData['id'] ? $orderData['id'] : $id;
                $channel_order['order_status'] = $this->setOrderStatusAttr($channel_order['order_status']);
                $channel_order['loan_status'] = $this->setLoanStatusAttr($channel_order['loan_status']);
                foreach ($productData as $pd){
                    $pd['son_order_status'] = (new AliexpressOnlineOrderDetail())->sonOrderStatusAttr($pd['son_order_status']);
                    $channel_order['channel_order_items'][] = $pd;
                }
                $order_data = [
                    'channel_id'=>$channel_id,//Y 渠道id
                    'account_id'=>$account_id,//Y 账号id
                    'channel_order_number'=>$channel_order_number,//Y 渠道订单号
                    'channel_order'=>$channel_order,//N 渠道订单数据
                    'receiver'=>$orderData['buyer_login_id'],//Y 买家登录ID
                ];
                (new MsgRuleHelp())->triggerEvent($event_name, $order_data);
            }
            
            Db::commit();
            $data = [
                'gmt_modified' => $orderData['gmt_modified'],
                'id' => isset($orderData['id']) && $orderData['id'] ? $orderData['id'] : $id
            ];
            Cache::store('AliexpressOnlineOrder')->setModifiedTime($orderData['account_id'], $orderData['order_id'], $data);

            /** 此队列 已改为即时计算 已废除 */
//            (new \app\common\service\UniqueQueuer(\app\order\queue\AliexpressOrderFeeQueue::class))->push($orderData['order_id']);
        } catch (Exception $ex) {
            Db::rollback();
            Cache::handler()->hSet('hash:aliexpress_order:add_error', $orderData['order_id'] . ' ' . date('Y-m-d H:i:s', time()), '订单添加异常'. $ex->getMessage());
            throw new Exception($ex->getMessage());
        }
    }

    protected function scopeStatus($query, $status)
    {
        if (is_array($status)) {
            $query->where('order_status', 'in', $status);
        } else {
            $query->where('order_status', $status);
        }
    }

    /**
     * 订单状态修改器
     * @param string $value
     * @return int
     */
    protected function setOrderStatusAttr($value)
    {
        return $this->orderStatusAttr($value);
    }

    /**
     * 订单创建时间修改器(Aliexpress返回时间与北京时间相差15小时)
     * @param $value
     * @return mixed
     */
//    public function setGmtCreateAttr($value)
//    {
//        if(!$value){
//            return $value;
//        }else{
//            return ($value+54000);
//        }
//    }
//    public function setGmtModifiedAttr($value)
//    {
//        if(!$value){
//            return $value;
//        }else{
//            return ($value+54000);
//        }
//    }
//    public function setGmtPayTimeAttr($value)
//    {
//        if(!$value){
//            return $value;
//        }else{
//            return ($value+54000);
//        }
//
//    }
//    public function setGmtSendGoodsTimeAttr($value)
//    {
//        if(!$value){
//            return $value;
//        }else{
//            return ($value+54000);
//        }
//    }
//    public function setSendGoodExpireAttr($value)
//    {
//        if(!$value){
//            return $value;
//        }else{
//            return ($value+54000);
//        }
//    }
}
