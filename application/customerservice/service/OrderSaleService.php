<?php

namespace app\customerservice\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\exception\OrderSaleException;
use app\common\exception\RefundException;
use app\common\exception\ReportException;
use app\common\model\AfterSaleRuleSet;
use app\common\model\AfterSaleService;
use app\common\model\AfterRedeliverDetail;
use app\common\model\AfterWastageGoods;
use app\common\model\OrderNote;
use app\common\service\AfterSaleType;
use app\common\service\ChannelAccountConst;
use app\common\service\ChannelConst;
use app\common\service\Common;
use app\common\service\Filter;
use app\common\service\OrderStatusConst;
use app\common\service\Twitter as ServiceTwitter;
use app\common\model\Order;
use app\common\service\UniqueQueuer;
use app\common\traits\AfterSale;
use app\customerservice\filter\OrderSaleAccountFilter;
use app\customerservice\queue\DistributionStockInCallBack;
use app\customerservice\queue\OrderSaleAdoptCheckQueue;
use app\customerservice\queue\OrderSaleAdoptQueue;
use app\customerservice\validate\OrderSaleValidate;
use app\goods\service\GoodsSku;
use app\index\service\ChannelConfig;
use app\order\service\AmazonService;
use app\order\service\OrderHelp;
use app\order\service\OrderRuleExecuteService;
use app\order\service\OrderService;
use app\order\service\OrderStatisticService;
use app\order\service\PackageHelp;
use app\order\service\PackageService;
use app\order\service\PaypalOrderService;
use app\warehouse\service\OrderOos;
use app\warehouse\service\Warehouse;
use think\Db;
use think\Exception;
use app\common\model\OrderAddress;
use app\common\model\OrderSourceDetail;
use app\common\model\OrderDetail;
use app\common\model\OrderPackage;
use app\common\service\Order as OrderCommon;
use app\order\service\DeclareService;
use app\common\service\Report as ReportService;
use app\common\service\Common as CommonService;
use app\common\model\AfterSaleLog;
use app\common\service\Paypal;
use think\Request;
use app\common\traits\User;

/** 售后处理
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2017/3/18
 * Time: 10:59
 */
class OrderSaleService
{
    use User;
    use AfterSale;
    protected $afterServiceModel;  //定义售后订单模型
    protected $afterWastageModel;  //定义问题货品模型
    protected $afterRedeliverModel;  //定义补发货模型
    protected $afterSaleLogModel;    //定义售后日志模型
    protected $validate;
    protected $_order_sale_add = 'cache:order:sale:add';   //新建售后单

    /** 构造函数
     * OrderSaleHelp constructor.
     */
    public function __construct()
    {
        if (is_null($this->afterServiceModel)) {
            $this->afterServiceModel = new AfterSaleService();
        }
        if (is_null($this->afterRedeliverModel)) {
            $this->afterRedeliverModel = new AfterRedeliverDetail();
        }
        if (is_null($this->afterWastageModel)) {
            $this->afterWastageModel = new AfterWastageGoods();
        }
        if (is_null($this->afterSaleLogModel)) {
            $this->afterSaleLogModel = new AfterSaleLog();
        }
        $this->validate = new OrderSaleValidate();
    }

    /**
     * 审批状态
     */
    public function approval()
    {
        $status = [
            0 => [
                'code' => 0,
                'remark' => '全部',
                'number' => $this->countQuantity()
            ],
            1 => [
                'code' => 1,
                'remark' => '等待提交',
                'number' => $this->countQuantity(1)
            ],
            2 => [
                'code' => 2,
                'remark' => '等待审核',
                'number' => $this->countQuantity(2)
            ],
            3 => [
                'code' => 3,
                'remark' => '退回修改',
                'number' => $this->countQuantity(3)
            ],
            4 => [
                'code' => 4,
                'remark' => '审核通过',
                'number' => $this->countQuantity(4)
            ]
        ];
        return $status;
    }

    /** 统计数量
     * @param int $code
     * @return int
     */
    private function countQuantity($code = 0)
    {
        $where = [];
        $join = [];
        if (!empty($code)) {
            $where['approve_status'] = $code;
        }
        $request = Request::instance();
        $params = $request->param();
        if (isset($params['submitter'])) {  //提交人
            $where['a.submitter_id'] = ['=', trim($params['submitter'])];
        }
        if (isset($params['snDate'])) {
            $params['date_b'] = isset($params['date_b']) ? $params['date_b'] : '';
            $params['date_e'] = isset($params['date_e']) ? $params['date_e'] : '';
            switch ($params['snDate']) {
                case 'submit_time':
                    $condition = timeCondition($params['date_b'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $where['a.submit_time'] = $condition;
                    }
                    break;
                case 'approve_time':
                    $condition = timeCondition($params['date_b'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $where['a.approve_time'] = $condition;
                    }
                    break;
                case 'create_time':
                    $condition = timeCondition($params['date_b'], $params['date_e']);
                    if (!is_array($condition)) {
                        return json(['message' => '日期格式错误'], 400);
                    }
                    if (!empty($condition)) {
                        $where['a.create_time'] = $condition;
                    }
                    break;
                default:
                    break;
            }
        }
        if (isset($params['snType']) && isset($params['snText']) && !empty($params['snText'])) {
            $snText = trim($params['snText']);
            switch ($params['snType']) {
                case 'order_num':
                    $where['a.order_number'] = ['like', '%' . $snText . '%'];
                    break;
                case 'buyer_id':
                    $where['a.buyer_id'] = ['like', '%' . $snText . '%'];
                    break;
                case 'sale_num':
                    $where['a.sale_number'] = ['like', '%' . $snText . '%'];
                    break;
                case 'sku':
                    $where['b.sku'] = ['like', '%' . $snText . '%'];
                    $join[] = ['order_detail b', 'a.order_id = b.order_id', 'left'];
                    break;
                default:
                    break;
            }
        }
        //获取列表权限
        if (!$this->isAdmin()) {
            $object = new Filter(OrderSaleAccountFilter::class);
            $account_id = [];
            if ($object->filterIsEffective()) {
                $account_id = $object->getFilterContent();
            }
            if (isset($params['account_id']) && !empty($params['account_id'])) {
                $virtual = $params['channel_id'] * OrderType::ChannelVirtual + $params['account_id'];
                $account_id = array_push($account_id, $virtual);
            }
            if (!empty($account_id)) {
                $where['a.channel_account'] = ['in', $account_id];
            }
        }
        $number = $this->afterServiceModel->alias('a')->join($join)->where($where)->count();
        return $number;
    }

    /** 列表信息
     * @param $where 【查询条件】
     * @param $page 【当前页】
     * @param $pageSize 【每页显示数】
     * @param $join 【是否关联查询】
     * @param $orderBy 【排序】
     * @return array
     */
    public function index($where, $page = 1, $pageSize = 20, $join = [], $orderBy = '')
    {
        $field = 'a.id,a.sale_number,a.order_number,a.refund_status,a.reissue_returns_status,a.buyer_country_code,a.buyer_id,a.approve_status,a.buyer_return_tracking_num,a.type,c.realname as submitter,d.realname as creator,a.submitter_id,a.create_time,a.submit_time,a.approve_time,a.approve_status,a.source_type';
        if (!empty($join)) {
            $field .= ',b.sku';
        }
        $join[] = ['user c', 'c.id = a.submitter_id', 'left'];
        $join[] = ['user d', 'd.id = a.creator_id', 'left'];
        $orderBy .= 'create_time desc';
        $count = $this->afterServiceModel->alias('a')->field($field)->join($join)->where($where)->count();
        $orderSaleList = $this->afterServiceModel->alias('a')->field($field)->join($join)->where($where)->order($orderBy)->page($page,
            $pageSize)->select();
        $list = [];
        foreach ($orderSaleList as $k => $v) {
            $v['id'] = $v['id'] . '';
            $this->listInfo($v);
            array_push($list, $v);
        }
        $result = [
            'data' => $list,
            'page' => $page,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
        return $result;
    }

    /** 列表数据
     * @param $v
     */
    private function listInfo(&$v)
    {
        $reissue = ['1' => '已补发货', '2' => '已收到退货', '3' => '退货中'];
        $refund = ['1' => '未退款', '2' => '退款失败', '3' => '退款完成', '4' => '退款中'];
        $approve = ['1' => '等待提交', '2' => '等待审核', '3' => '退回修改', '4' => '审核通过'];
        $v['refund'] = '--';
        $v['redeliver'] = '--';
        $v['return'] = '--';
        switch ($v['type']) {
            case AfterSaleType::Refund:
                $this->status($v, $refund, $reissue, $approve, AfterSaleType::Refund);
                $v['type'] = [AfterSaleType::Refund];
                break;
            case AfterSaleType::ReplacementGoods:
                $this->status($v, $refund, $reissue, $approve, AfterSaleType::ReplacementGoods);
                $v['type'] = [AfterSaleType::ReplacementGoods];
                break;
            case AfterSaleType::ReturnGoods:
                $this->status($v, $refund, $reissue, $approve, AfterSaleType::ReturnGoods);
                $v['type'] = [AfterSaleType::ReturnGoods];
                break;
            case AfterSaleType::RefundAndReplacementGoods:
                $this->status($v, $refund, $reissue, $approve, AfterSaleType::RefundAndReplacementGoods);
                $v['type'] = [AfterSaleType::Refund, AfterSaleType::ReplacementGoods];
                break;
            case AfterSaleType::RefundAndReturnGoods:
                $this->status($v, $refund, $reissue, $approve, AfterSaleType::RefundAndReturnGoods);
                $v['type'] = [AfterSaleType::Refund, AfterSaleType::ReturnGoods];
                break;
            case AfterSaleType::ReplacementGoodsAndReturnGoods:
                $this->status($v, $refund, $reissue, $approve, AfterSaleType::ReplacementGoodsAndReturnGoods);
                $v['type'] = [AfterSaleType::ReplacementGoods, AfterSaleType::ReturnGoods];
                break;
            case AfterSaleType::RefundAndReplacementGoodsAndReturnGoods:
                $this->status($v, $refund, $reissue, $approve, AfterSaleType::RefundAndReplacementGoodsAndReturnGoods);
                $v['type'] = [AfterSaleType::Refund, AfterSaleType::ReplacementGoods, AfterSaleType::ReturnGoods];
                break;
        }
    }

    /** 退款退货补发类型
     * @param int $code
     * @return array
     */
    public function type($code = 0)
    {
        $type = '';
        if (!empty($code)) {
            switch ($code) {
                case AfterSaleType::Refund:
                    $type = '退款';
                    break;
                case AfterSaleType::ReplacementGoods:
                    $type = '补发货';
                    break;
                case AfterSaleType::ReturnGoods:
                    $type = '退货';
                    break;
                case AfterSaleType::RefundAndReplacementGoods:
                    $type = '退款、补发货';
                    break;
                case AfterSaleType::RefundAndReturnGoods:
                    $type = '退款、退货';
                    break;
                case AfterSaleType::ReplacementGoodsAndReturnGoods:
                    $type = '补发货、退货';
                    break;
                case AfterSaleType::RefundAndReplacementGoodsAndReturnGoods:
                    $type = '退款、补发货、退货';
                    break;
                case AfterSaleType::HoldUp:
                    $type = '拦截';
                    break;
            }
        }
        return $type;
    }

    /**
     * 列表退款的状态
     * @param $v
     * @param $refund
     * @param $reissue
     * @param $approve
     * @param $type
     */
    private function status(&$v, $refund, $reissue, $approve, $type)
    {
        switch ($type) {
            case AfterSaleType::Refund:
                $this->getRefundStatus($v, $refund, $approve);
                break;
            case AfterSaleType::ReplacementGoods:
                $this->getRedeliverStatus($v, $reissue, $approve);
                break;
            case AfterSaleType::ReturnGoods:
                $this->getReturnStatus($v, $reissue, $approve);
                break;
            case AfterSaleType::RefundAndReplacementGoods:
                $this->getRefundStatus($v, $refund, $approve);
                $this->getRedeliverStatus($v, $reissue, $approve);
                break;
            case AfterSaleType::RefundAndReturnGoods:
                $this->getRefundStatus($v, $refund, $approve);
                $this->getReturnStatus($v, $reissue, $approve);
                break;
            case AfterSaleType::ReplacementGoodsAndReturnGoods:
                $this->getRedeliverStatus($v, $reissue, $approve);
                $this->getReturnStatus($v, $reissue, $approve);
                break;
            case AfterSaleType::RefundAndReplacementGoodsAndReturnGoods:
                $this->getRefundStatus($v, $refund, $approve);
                $this->getRedeliverStatus($v, $reissue, $approve);
                $this->getReturnStatus($v, $reissue, $approve);
                break;
        }
    }

    /**
     * 执行状态
     */
    public function execute()
    {
        $status = [
            0 => [
                'code' => 0,
                'remark' => '全部'
            ],
            1 => [
                'code' => 3,
                'remark' => '已退款'
            ],
            2 => [
                'code' => 6,
                'remark' => '退款中'
            ],
            3 => [
                'code' => 2,
                'remark' => '退款失败'
            ],
            4 => [
                'code' => 4,
                'remark' => '已补发货'
            ],
            5 => [
                'code' => 5,
                'remark' => '已收到退货'
            ],
            6 => [
                'code' => 7,
                'remark' => '退货中'
            ]
        ];
        return $status;
    }

    /** 获取渠道信息
     * @return mixed
     * @throws \think\Exception
     */
    public function channel()
    {
        $channel = Cache::store('channel')->getChannel();
        $result[0] = [
            'code' => 0,
            'remark' => '全部'
        ];
        foreach ($channel as $k => $v) {
            $temp['code'] = $v['id'];
            $temp['remark'] = $v['title'];
            array_push($result, $temp);
        }
        array_push($result, ['code' => '999', 'remark' => '其他渠道']);
        return $result;
    }

    /** 读取售后信息
     * @param $id
     * @return string
     */
    public function read($id)
    {
        $saleInfo = $this->afterServiceModel->where(['id' => $id])->find();
        if (empty($saleInfo)) {
            return '记录不存在';
        }
        $saleInfo = $saleInfo->toArray();
        $result['id'] = $id;
        $result['order_id'] = $saleInfo['order_id'];
        $result['order_number'] = $saleInfo['order_number'];
        $result['reason'] = $saleInfo['reason'];
        $result['remark'] = $saleInfo['remark'];
        $result['approve_status'] = $saleInfo['approve_status'];
        $result['type'] = $saleInfo['type'];
        //查询补发货表
        $deliveryList = $this->afterRedeliverModel->with('skuInfo')->where(['after_sale_service_id' => $id])->select();
        $deliveryGoods = [];
        $returnGoods = [];
        foreach ($deliveryList as $key => $value) {
            $temp['sku'] = $value['skuInfo']['sku'];
            $temp['goods_name'] = $value['skuInfo']['spu_name'];
            $temp['quantity'] = $value['quantity'];
            $temp['goods_id'] = $value['goods_id'];
            $temp['sku_id'] = $value['sku_id'];
            switch ($value['type']) {
                case AfterSaleType::ReplacementGoods:
                    array_push($deliveryGoods, $temp);
                    break;
                case AfterSaleType::ReturnGoods:
                    array_push($returnGoods, $temp);
                    break;
            }
        }
        //查看问题货品表
        $orderSourceDetailModel = new OrderSourceDetail();
        $problemList = $this->afterWastageModel->field('goods_id,sku_id,sku as wastage_sku,delivery_quantity,quantity,order_id,channel_sku,channel_item_id')->with('skuInfo')->where(['after_sale_service_id' => $id])->select();
        $problemGoods = [];
        foreach ($problemList as $key => $value) {
            $temp['channel_sku'] = $value['channel_sku'];
            $temp['channel_item_id'] = $value['channel_item_id'];
            if (!empty($value['delivery_quantity'])) {
                $temp['channel_sku_quantity'] = $value['delivery_quantity'];
            } else {
                $channel_sku_quantity = $orderSourceDetailModel->field('channel_sku,channel_item_id,channel_sku_quantity')->where([
                    'order_id' => $value['order_id'],
                    'channel_sku' => $value['wastage_sku'],
                    'channel_item_id' => $value['channel_item_id']
                ])->value('channel_sku_quantity');
                $temp['channel_sku_quantity'] = $channel_sku_quantity;
            }
            $temp['sku'] = $value['skuInfo']['sku'];
            $temp['goods_name'] = $value['skuInfo']['spu_name'];
            if (empty($value['sku_id'])) {
                $temp['sku'] = $value['wastage_sku'];
                $temp['goods_name'] = '商品未知';
            }
            $temp['delivery_quantity'] = $value['delivery_quantity'];
            $temp['quantity'] = $value['quantity'];
            $temp['goods_id'] = $value['goods_id'];
            $temp['sku_id'] = $value['sku_id'];
            array_push($problemGoods, $temp);
        }
        switch ($result['type']) {
            case AfterSaleType::Refund:
                $result['type'] = [AfterSaleType::Refund];
                $this->refund($result, $saleInfo);
                break;
            case AfterSaleType::ReplacementGoods:
                $result['type'] = [AfterSaleType::ReplacementGoods];
                $this->delivery($result, $saleInfo, $deliveryGoods);
                break;
            case AfterSaleType::ReturnGoods:
                $result['type'] = [AfterSaleType::ReturnGoods];
                $this->back($result, $saleInfo, $returnGoods);
                break;
            case AfterSaleType::RefundAndReplacementGoods:
                $result['type'] = [AfterSaleType::Refund, AfterSaleType::ReplacementGoods];
                $this->refund($result, $saleInfo);
                $this->back($result, $saleInfo, $deliveryGoods);
                break;
            case AfterSaleType::RefundAndReturnGoods:
                $result['type'] = [AfterSaleType::Refund, AfterSaleType::ReturnGoods];
                $this->refund($result, $saleInfo);
                $this->back($result, $saleInfo, $returnGoods);
                break;
            case AfterSaleType::ReplacementGoodsAndReturnGoods:
                $result['type'] = [AfterSaleType::ReplacementGoods, AfterSaleType::ReturnGoods];
                $this->delivery($result, $saleInfo, $deliveryGoods);
                $this->back($result, $saleInfo, $returnGoods);
                break;
            case AfterSaleType::RefundAndReplacementGoodsAndReturnGoods:
                $result['type'] = [AfterSaleType::Refund, AfterSaleType::ReplacementGoods, AfterSaleType::ReturnGoods];
                $this->refund($result, $saleInfo);
                $this->delivery($result, $saleInfo, $deliveryGoods);
                $this->back($result, $saleInfo, $returnGoods);
                break;
        }
        $result['problem'] = $problemGoods;
        $logList = $this->afterSaleLogModel->field('operator,remark,create_time')->where(['sale_id' => $id])->order('create_time desc')->select();
        $result['log'] = !empty($logList) ? $logList : [];
        return $result;
    }

    /** 退款信息
     * @param $result
     * @param $saleInfo
     */
    private function refund(&$result, $saleInfo)
    {
        $result['refund'] = [
            'refund_amount' => $saleInfo['refund_amount'],
            'to_buyer_message' => $saleInfo['to_buyer_message'],
            'retired_amount' => 0   //已退金额
        ];
    }

    /** 补发货的
     * @param $result
     * @param $saleInfo
     * @param $deliveryGoods
     */
    private function delivery(&$result, $saleInfo, $deliveryGoods)
    {
        $this->listInfo($saleInfo);
        if ($saleInfo['reissue_returns_status'] == 1) {  //已补发货
            $result['delivery']['status'] = '已生成补发货订单';
            //查询订单
            $orderModel = new Order();
            $orderInfo = $orderModel->field('id,order_number')->where(['id' => $saleInfo['redeliver_order_id']])->find();
            $result['delivery']['order'] = !empty($orderInfo) ? $orderInfo : [];
        } else {
            $result['delivery']['status'] = $saleInfo['redeliver'];
            $result['delivery']['order'] = [];
        }
        $result['delivery']['goods'] = $deliveryGoods;
        $result['delivery']['address'] = [
            'buyer_name' => $saleInfo['buyer_name'],
            'buyer_address_one' => $saleInfo['buyer_address_one'],
            'buyer_address_two' => $saleInfo['buyer_address_two'],
            'buyer_city' => $saleInfo['buyer_city'],
            'buyer_state' => $saleInfo['buyer_state'],
            'buyer_country_code' => $saleInfo['buyer_country_code'],
            'buyer_postal_code' => $saleInfo['buyer_postal_code'],
            'buyer_phone' => $saleInfo['buyer_phone'],
            'buyer_mobile' => $saleInfo['buyer_mobile']
        ];
        $result['delivery']['new_warehouse_id'] = $saleInfo['new_warehouse_id'];
        $result['delivery']['shipping_method_id'] = $saleInfo['shipping_method_id'];
    }

    /** 退货
     * @param $result
     * @param $saleInfo
     * @param $deliveryGoods
     */
    private function back(&$result, $saleInfo, $deliveryGoods)
    {
        $result['return']['goods'] = $deliveryGoods;
        $result['return']['buyer_return_carrier'] = $saleInfo['buyer_return_carrier'];
        $result['return']['buyer_return_tracking_num'] = $saleInfo['buyer_return_tracking_num'];
        $result['return']['warehouse_id'] = $saleInfo['warehouse_id'];
    }

    /**
     * 新增
     * @param $params
     * @return array|mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add($params)
    {
        if (isset($params['is_submit'])) {
            $params['approve_status'] = AfterSaleType::Submitted;
            $params['submit_time'] = time();
            $params['submitter_id'] = $params['operator_id'];
        }
        if (!isset($params['order_id']) || empty($params['order_id']) || !isset($params['type']) || empty($params['type'])) {
            throw new JsonErrorException('参数值为空，非法操作', 400);
        }
        $type = json_decode($params['type'], true);
        if (in_array(3, $type)) {
            throw new JsonErrorException('退货类型错误，请联系前端修改');
        }
        $refund = [];
        $delivery = [];
        $return = [];
        $problem = [];
        $this->check($refund, $delivery, $return, $problem, $params);
        if (empty($type)) {
            throw new JsonErrorException('请选择要操作的类型！', 400);
        }
        if ((in_array(AfterSaleType::Refund, $type) && empty($refund)) || (in_array(AfterSaleType::ReplacementGoods,
                    $type) && empty($delivery)) || (in_array(AfterSaleType::ReturnGoods,
                    $type) && empty($return))
        ) {
            throw new JsonErrorException('请先填写操作类型相关内容！', 400);
        }
        if (!isset($problem['goods']) || empty($problem['goods'])) {
            throw new JsonErrorException('请先选择问题的货品！', 400);
        }
        //ebay纠纷建售后单验证
        if (isset($params['source_type']) && $params['source_type'] == 1 && isset($params['source_id']) && !empty($params['source_id'])) {
            $sourceArr = explode('-', $params['source_id']);
            if ($sourceArr[0] != 'shopee') {
                $ebayDisputeHelpSerivce = new EbayDisputeHelp();
                $result = $ebayDisputeHelpSerivce->checkDisputOrder($params['source_id']);
                if (empty($result['status'])) {
                    throw new JsonErrorException($result['message'], 400);
                }
            }
        }
        $orderModel = new Order();
        $orderDetailModel = new OrderDetail();
        $orderSourceDetailModel = new OrderSourceDetail();
        //状态
        $params['type'] = $this->setAfterType($type);
        $orderInfo = $orderModel->field(true)->where(['id' => $params['order_id']])->find();
        if ($orderInfo['status'] == OrderStatusConst::SaidInvalid) {
            throw new JsonErrorException('订单已作废，不可建售后单');
        }
        //查询是否存在全额退款售后单
        $fullRefund = $this->afterServiceModel->where(['order_id' => $orderInfo['id']])->sum('refund_amount');
        if ($fullRefund == $orderInfo['pay_fee']) {
            throw new JsonErrorException('订单创建的退款售后单，已经包含订单全部金额，不可再建售后单');
        }
        foreach ($problem['goods'] as $key => $value) {
            //查询产品来源表
            $sourceDetailInfo = [];
            if (isset($value['channel_sku']) && isset($value['channel_item_id'])) {
                $sourceDetailInfo = $orderSourceDetailModel->field('id,channel_sku,channel_sku_quantity')->where([
                    'order_id' => $orderInfo['id'],
                    'channel_sku' => $value['channel_sku'],
                    'channel_item_id' => $value['channel_item_id']
                ])->find();
            }
            $order_source_detail_id = 0;
            if (!empty($sourceDetailInfo)) {
                $order_source_detail_id = $sourceDetailInfo['id'];
            }
            //查询订单详情表
            $detailInfo = $orderDetailModel->field('sku_quantity,sku')->where([
                'sku_id' => $value['sku_id'],
                'order_id' => $orderInfo['id'],
                'order_source_detail_id' => $order_source_detail_id
            ])->select();
            if (empty($detailInfo) && empty($sourceDetailInfo)) {
                throw new JsonErrorException('订单不存在问题商品SKU【' . $value['sku'] . '】', 500);
            }
            foreach ($detailInfo as $k => $v) {
                if (($value['quantity'] > $v['sku_quantity']) && (!empty($sourceDetailInfo) && $value['quantity'] > $sourceDetailInfo['channel_sku_quantity'])) {
                    if (!empty($detailInfo)) {
                        throw new JsonErrorException('问题商品SKU【' . $v['sku'] . '】数量不能大于' . $v['sku_quantity'], 500);
                    } else {
                        throw new JsonErrorException('问题商品SKU【' . $sourceDetailInfo['channel_sku'] . '】数量不能大于' . $sourceDetailInfo['channel_sku_quantity'], 500);
                    }
                }
            }
        }
        $afterService = $params;
        $afterService['id'] = ServiceTwitter::instance()->nextId($orderInfo['channel_id'],
            $orderInfo['channel_account_id']);
        $afterService['sale_number'] = $afterService['id'];
        $afterService['order_number'] = $orderInfo['order_number'];
        $afterService['buyer_id'] = $orderInfo['buyer'];
        $afterService['create_time'] = time();
        $afterService['creator_id'] = $params['operator_id'];
        $afterService['channel_id'] = $orderInfo['channel_id'];
        $afterService['account_id'] = $orderInfo['channel_account_id'];
        if (!isset($afterService['buyer_country_code'])) {
            $afterService['buyer_country_code'] = $orderInfo['country_code'];
        }
        $order_status = $orderInfo['status'];
        if (!empty($refund)) {
            //退款要判断金额是否已经超出了
            $where['order_id'] = ['=', $orderInfo['id']];
            $where['refund_amount'] = ['>', 0];
//            $where['refund_status'] = ['<>', 2];  #解除售后单退款失败加入计算限制，防止创建售后单之后之前的售后单重新执行退款
            $apply_amount = $this->afterServiceModel->where($where)->sum('refund_amount');
            if (!empty($apply_amount)) {
                $can_refund_amount = $orderInfo['pay_fee'] - $apply_amount;
                $can_refund_amount = (string)$can_refund_amount;
                //判断金额是否有退款失败的金额
                $search['order_id'] = ['=', $orderInfo['id']];
                $search['refund_status'] = ['=', 2];
                $refund_amount = $this->afterServiceModel->field(true)->where($search)->sum('refund_amount');
                if ($refund['refund_amount'] > $can_refund_amount) {
                    if ($refund_amount > 0) {
                        throw new JsonErrorException('订单存在退款失败的售后单,退款总金额：' . $refund_amount .
                            '，当前订单剩余可退金额：' . $can_refund_amount);
                    } else {
                        throw new JsonErrorException('退款金额不能大于' . $can_refund_amount);
                    }
                }
            } else {
                if ($refund['refund_amount'] > $orderInfo['pay_fee']) {
                    throw new JsonErrorException('退款金额不能大于' . $orderInfo['pay_fee']);
                }
            }
            $afterService = array_merge($afterService, $refund);
            $afterService['refund_status'] = 1;
            $afterService['refund_currency'] = $orderInfo['currency_code'];
            //判断订单是否有退款失败的订单，如果存在订单状态不变,反之修改状态(同时判断订单为未发货)
            $refundFailedInfo = $this->afterServiceModel->where(['order_id' => $orderInfo['id'],
                'type' => AfterSaleType::Refund, 'refund_status' => ['<>', AfterSaleType::RefundCompleted]])->find();
            if (empty($refundFailedInfo)) {
                $order_status += OrderStatusConst::RefundStatus;   //申请退款
            }
        }
        if (!empty($delivery)) {
            if (!isset($delivery['new_warehouse_id']) || empty($delivery['new_warehouse_id'])) {
                throw new JsonErrorException('补发货仓库为必填');
            }
            if (!isset($delivery['shipping_method_id']) || empty($delivery['shipping_method_id'])) {
                throw new JsonErrorException('补发货物流方式为必填');
            }
            $afterService = array_merge($afterService, $delivery['address']);
            $afterService['new_warehouse_id'] = $delivery['new_warehouse_id'];
            $afterService['shipping_method_id'] = $delivery['shipping_method_id'];
            $order_status += OrderStatusConst::ReplacementStatus;   //补发货
        }
        if (!empty($return)) {
            if (!isset($return['warehouse_id']) || empty($return['warehouse_id'])) {
                throw new JsonErrorException('退回接收仓库为必填');
            }
            $afterService['buyer_return_carrier'] = $return['buyer_return_carrier'];
            $afterService['buyer_return_tracking_num'] = $return['buyer_return_tracking_num'];
            $afterService['warehouse_id'] = $return['warehouse_id'];
            $order_status += OrderStatusConst::RefundGoodsStatus;   //退货
        }
        //记录日志
        $log = [
            'sale_id' => $afterService['id'],
            'operator_id' => $params['operator_id'],
            'operator' => $params['operator'],
            'remark' => '新建售后单',
            'create_time' => time()
        ];
        //记录问题货品信息
        $problemInfo = $this->problemInfo($problem, $params, $afterService);
        //记录补发货
        $deliveryInfo = $this->deliveryInfo($delivery, $params, $afterService);
        //记录退货的信息
        $deliveryInfo = $this->returnInfo($return, $params, $afterService, $deliveryInfo);
        if (!$this->validate->check($afterService)) {
            throw new JsonErrorException($this->validate->getError(), 400);
        }
        $ok = Cache::handler()->set($this->_order_sale_add . $params['order_id'], 1);
        if (!$ok) {
            throw new JsonErrorException('数据在处理中，请不要重复点击');
        }
        Cache::handler()->expire($this->_order_sale_add . $params['order_id'], 300);  //5min过期
        Db::startTrans();
        try {
            $this->afterServiceModel->allowField(true)->isUpdate(false)->save($afterService);
            $after_id = $this->afterServiceModel->id;
            $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
            $this->afterWastageModel->allowField(true)->isUpdate(false)->saveAll($problemInfo);
            //插入补发货记录
            $this->afterRedeliverModel->allowField(true)->isUpdate(false)->saveAll($deliveryInfo);
            //更新订单
            $orderModel->where(['id' => $orderInfo['id']])->update(['status' => $order_status]);
            //售后更改包裹状态
            (new PackageHelp())->effectPackageByOrderId($orderInfo['id'], $order_status);
            //订单日志信息
            CommonService::addOrderLog($orderInfo['id'], '订单创建了售后单,单号为【' . $afterService['id'] . '】',
                $params['operator'], '', $params['operator_id']);
            //新增订单备注
            if (isset($afterService['remark']) && !empty($afterService['remark'])) {
                $orderNoteModel = new OrderNote();
                $orderNoteModel->allowField(true)->isUpdate(false)->save([
                    'order_id' => $orderInfo['id'],
                    'creator_id' => $params['operator_id'],
                    'note' => $afterService['remark'],
                    'create_time' => time()
                ]);
                //订单日志信息
                CommonService::addOrderLog($orderInfo['id'], '订单新增了备注【' . $afterService['remark'] . '】',
                    $params['operator'], '', $params['operator_id']);
            }
            //ebay纠纷关联新建售后单
            if (isset($params['source_type']) && $params['source_type'] == 1 && isset($params['source_id']) && !empty($params['source_id'])) {
                $sourceArr = explode('-', $params['source_id']);
                if ($sourceArr[0] == 'shopee') {
                    $returnsn = $sourceArr[1];
                    $shopeeDisputeService = new ShopeeDisputeService();
                    $result = $shopeeDisputeService->relateAfterSale($returnsn, $afterService['id']);
                    if (!$result) {
                        throw new JsonErrorException('shopee纠纷关联售后单失败', 400);
                    }
                } else {
                    $ebayDisputeHelpService = new EbayDisputeHelp();
                    $result = $ebayDisputeHelpService->recordAfterSales($params['source_id'], $afterService['id']);
                    if (empty($result['status'])) {
                        throw new JsonErrorException($result['message'], 400);
                    }
                }
            }
            //更新包裹不可生成捡获单
            $package_ids = (new PackageService())->getPackageIdByOrderId($orderInfo['id']);
            (new OrderPackage())->where(['package_collection_id' => 0])->where('id', 'in', $package_ids)->update(['is_push' => 0]);
            Db::commit();
            //组长自动审核
//            if (isset($params['is_submit'])) {
//                $uid = (new \app\index\service\User())->getLeader($params['operator_id']);
//                if ($uid == $params['operator_id']) {
//                    $operator = [];
//                    $operator['id'] = $params['operator_id'];
//                    $operator['operator'] = $params['operator'];
//                    $operator['type'] = 1;
//                    $this->adopt($afterService['id'], $operator);
//                }
//            }
        } catch (OrderSaleException $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
        //自动审核验证数据
        $param = [
            'id' => $afterService['id'],
            'order_id' => $orderInfo['id'],
            'remark' => $afterService['remark'],
            'channel_id' => $orderInfo['channel_id'],
            'site_code' => $orderInfo['site_code'],
            'channel_account_id' => $orderInfo['channel_account_id'],
            'currency_code' => $orderInfo['currency_code'],
            'refund_amount' => $refund['refund_amount'] ?? 0,
            'operator' => $params['operator'],
            'operator_id' => $params['operator_id'],
            'source_id' => $params['source_id'] ?? 0,
            'source_type' => $params['source_type'] ?? 0,
        ];
        //加入售后单自动审批验证队列
        (new UniqueQueuer(OrderSaleAdoptCheckQueue::class))->push($param);

        $result = $this->index(['a.id' => $after_id]);
        return isset($result['data']) ? $result['data'] : [];
    }

    /** 检查数据是否完整
     * @param $refund
     * @param $delivery
     * @param $return
     * @param $problem
     * @param $params
     */
    private function check(&$refund, &$delivery, &$return, &$problem, &$params)
    {
        if (isset($params['problem'])) {
            $problem = json_decode($params['problem'], true);
            unset($params['problem']);
        }
        if (isset($params['return'])) {
            $return = json_decode($params['return'], true);
            if (empty($return['goods'])) {
                throw new JsonErrorException('退回商品不能为空');
            }
            unset($params['return']);
        }
        if (isset($params['delivery'])) {
            $delivery = json_decode($params['delivery'], true);
            if (empty($delivery['goods'])) {
                throw new JsonErrorException('补发货商品不能为空');
            }
            unset($params['delivery']);
        }
        if (isset($params['refund'])) {
            $refund = json_decode($params['refund'], true);
            unset($params['refund']);
        }
    }

    /** 记录问题货品信息
     * @param $problem
     * @param $params
     * @param $afterService
     * @return array
     * @throws Exception
     */
    private function problemInfo($problem, $params, $afterService)
    {
        $problemInfo = [];
        $quantity = 0;
        if (isset($problem['goods'])) {
            foreach ($problem['goods'] as $p => $pp) {
                $temp = $pp;
                $temp['order_id'] = $params['order_id'];
                $temp['after_sale_service_id'] = $afterService['id'];
                $temp['create_time'] = time();
                $temp['creator_id'] = $params['operator_id'];
                $quantity += $pp['quantity'];
                array_push($problemInfo, $temp);
            }
        }
        if (empty($quantity)) {
            throw new Exception('问题订单数量不能全为零');
        }
        return $problemInfo;
    }

    /** 记录补发货
     * @param $delivery
     * @param $params
     * @param $afterService
     * @return array
     */
    private function deliveryInfo($delivery, $params, $afterService)
    {
        $deliveryInfo = [];
        if (isset($delivery['goods'])) {
            foreach ($delivery['goods'] as $d => $dd) {
                $temp = $dd;
                if (isset($dd['quantity']) && empty($dd['quantity'])) {
                    throw new JsonErrorException('补发货数量不能为0');
                }
                $temp['after_sale_service_id'] = $afterService['id'];
                $temp['create_time'] = time();
                $temp['creator_id'] = $params['operator_id'];
                $temp['type'] = AfterSaleType::ReplacementGoods;
                array_push($deliveryInfo, $temp);
            }
        }
        return $deliveryInfo;
    }

    /** 记录退货的信息
     * @param $return
     * @param $params
     * @param $afterService
     * @param $deliveryInfo
     * @return mixed
     */
    private function returnInfo($return, $params, $afterService, &$deliveryInfo)
    {
        if (isset($return['goods'])) {
            foreach ($return['goods'] as $g => $gg) {
                $temp = $gg;
                $temp['after_sale_service_id'] = $afterService['id'];
                $temp['create_time'] = time();
                $temp['creator_id'] = $params['operator_id'];
                $temp['type'] = AfterSaleType::ReturnGoods;
                array_push($deliveryInfo, $temp);
            }
        }
        return $deliveryInfo;
    }

    /** 更新
     * @param $params
     * @param $id
     * @return array
     */
    public function update($params, $id)
    {
        if (!isset($params['order_id']) || empty($params['order_id']) || !isset($params['type']) || empty($params['type'])) {
            throw new JsonErrorException('非法操作', 400);
        }
        $type = json_decode($params['type'], true);
        if (in_array(3, $type)) {
            throw new JsonErrorException('退货类型错误，请联系前端修改');
        }
        $refund = [];
        $delivery = [];
        $return = [];
        $problem = [];
        $this->check($refund, $delivery, $return, $problem, $params);
        if (empty($type)) {
            throw new JsonErrorException('请选择要操作的类型！', 400);
        }
        if ((in_array(AfterSaleType::Refund, $type) && empty($refund)) || (in_array(AfterSaleType::ReplacementGoods,
                    $type) && empty($delivery)) || (in_array(AfterSaleType::ReturnGoods,
                    $type) && empty($return))
        ) {
            throw new JsonErrorException('请选择要操作的类型！', 400);
        }
        if (empty($problem)) {
            throw new JsonErrorException('请先选择问题的货品！', 400);
        }
        $orderModel = new Order();
        $orderDetailModel = new OrderDetail();
        $orderSourceDetailModel = new OrderSourceDetail();
        //状态
        $params['type'] = $this->setAfterType($type);
        //查出原订单的信息
        $orderInfo = $orderModel->field(true)->where(['id' => $params['order_id']])->find();
        if ($orderInfo['status'] == OrderStatusConst::SaidInvalid) {
            throw new JsonErrorException('订单已作废，不可操作售后单');
        }
        foreach ($problem['goods'] as $key => $value) {
            $sourceDetailInfo = [];
            if (isset($value['channel_sku']) && isset($value['channel_item_id'])) {
                //查询产品来源表
                $sourceDetailInfo = $orderSourceDetailModel->field('id,channel_sku,channel_sku_quantity')->where([
                    'order_id' => $orderInfo['id'],
                    'channel_sku' => $value['channel_sku'],
                    'channel_item_id' => $value['channel_item_id']
                ])->find();
            }
            $order_source_detail_id = 0;
            if (!empty($sourceDetailInfo)) {
                $order_source_detail_id = $sourceDetailInfo['id'];
            }
            $detailInfo = $orderDetailModel->field('sku_quantity,sku')->where([
                'sku_id' => $value['sku_id'],
                'order_id' => $orderInfo['id'],
                'order_source_detail_id' => $order_source_detail_id
            ])->select();
            if (empty($detailInfo) && empty($sourceDetailInfo)) {
                throw new JsonErrorException('订单不存在问题商品SKU【' . $value['sku'] . '】', 500);
            }
            if (!empty($detailInfo)) {
                $bool = false;
                $message = '';
                foreach ($detailInfo as $k => $v) {
                    if (($value['quantity'] > $v['sku_quantity']) && (!empty($sourceDetailInfo) && $value['quantity'] > $sourceDetailInfo['channel_sku_quantity'])) {
                        if (!empty($detailInfo)) {
                            $message = '问题商品SKU【' . $v['sku'] . '】数量不能大于' . $v['sku_quantity'];
                        } else {
                            $message = '问题商品SKU【' . $sourceDetailInfo['channel_sku'] . '】数量不能大于' . $sourceDetailInfo['channel_sku_quantity'];
                        }
                    } else {
                        $bool = true;
                    }
                }
                if ($bool == false) {
                    throw new JsonErrorException($message, 500);
                }
            }
        }
        $afterService = $params;

        if (isset($params['is_submit'])) {
            $afterService['approve_status'] = 2;
            $afterService['submitter_id'] = $params['operator_id'];
            $afterService['submit_time'] = time();
        }
        $afterService['order_number'] = $orderInfo['order_number'];
        $afterService['update_time'] = time();
        $afterService['updater_id'] = $params['operator_id'];
        if (!empty($refund)) {
            //退款要判断金额是否已经超出了
            $where['order_id'] = ['=', $orderInfo['id']];
            $where['refund_amount'] = ['>', 0];
            $where['id'] = ['<>', $id];
//            $where['refund_status'] = ['<>', 2];
            $apply_amount = $this->afterServiceModel->where($where)->sum('refund_amount');
            if (!empty($apply_amount)) {
                $can_refund_amount = $orderInfo['pay_fee'] - $apply_amount;
                $can_refund_amount = (string)$can_refund_amount;
                if ($refund['refund_amount'] > $can_refund_amount) {
                    throw new JsonErrorException('退款金额不能大于' . $can_refund_amount);
                }
            } else {
                if ($refund['refund_amount'] > $orderInfo['pay_fee']) {
                    throw new JsonErrorException('退款金额不能大于' . $orderInfo['pay_fee']);
                }
            }
            $afterService = array_merge($afterService, $refund);
            $afterService['refund_status'] = 1;
        }
        if (!empty($delivery)) {
            $afterService = array_merge($afterService, $delivery['address']);
            $afterService['new_warehouse_id'] = $delivery['new_warehouse_id'];
            $afterService['shipping_method_id'] = $delivery['shipping_method_id'];
        }
        if (!empty($return)) {
            $afterService['buyer_return_carrier'] = $return['buyer_return_carrier'];
            $afterService['buyer_return_tracking_num'] = $return['buyer_return_tracking_num'];
            $afterService['warehouse_id'] = $return['warehouse_id'];
        }
        //记录日志
        $log = [
            'sale_id' => $id,
            'operator_id' => $params['operator_id'],
            'operator' => $params['operator'],
            'remark' => '修改售后单',
            'create_time' => time()
        ];
        $orderNoteModel = new OrderNote();
        Db::startTrans();
        try {
            //订单日志信息
            CommonService::addOrderLog($orderInfo['id'], '订单修改了售后单【' . $id . '】信息', $params['operator'], '',
                $params['operator_id']);
            $this->afterServiceModel->allowField(true)->isUpdate(true)->save($afterService, ['id' => $id]);
            $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
            //删除问题单，删除补发货的单
            $this->afterWastageModel->where(['after_sale_service_id' => $id])->delete();
            $this->afterRedeliverModel->where(['after_sale_service_id' => $id])->delete();
            //记录问题货品信息
            $problemInfo = $this->problemInfo($problem, $params, $afterService);
            $this->afterWastageModel->allowField(true)->isUpdate(false)->saveAll($problemInfo);
            //记录补发货，退货的信息
            $deliveryInfo = $this->deliveryInfo($delivery, $params, $afterService);
            $deliveryInfo = $this->returnInfo($return, $params, $afterService, $deliveryInfo);
            //插入补发货记录
            $this->afterRedeliverModel->allowField(true)->isUpdate(false)->saveAll($deliveryInfo);
            //新增订单备注
            if (isset($afterService['remark']) && !empty($afterService['remark'])) {
                $noteInfo = $orderNoteModel->where([
                    'order_id' => $orderInfo['id'],
                    'note' => $afterService['remark']
                ])->find();
                if (empty($noteInfo)) {
                    $orderNoteModel->allowField(true)->isUpdate(false)->save([
                        'order_id' => $orderInfo['id'],
                        'creator_id' => $params['operator_id'],
                        'note' => $afterService['remark'],
                        'create_time' => time()
                    ]);
                    //订单日志信息
                    CommonService::addOrderLog($orderInfo['id'], '订单新增了备注【' . $afterService['remark'] . '】',
                        $params['operator'], '', $params['operator_id']);
                }
            }
            Db::commit();
            //组长自动审核
//            if (isset($params['is_submit'])) {
//                $uid = (new \app\index\service\User())->getLeader($params['operator_id']);
//                if ($uid == $params['operator_id']) {
//                    $operator = [];
//                    $operator['id'] = $params['operator_id'];
//                    $operator['operator'] = $params['operator'];
//                    $operator['type'] = 1;
//                    $this->adopt($id, $operator);
//                }
//            }
            //查询记录
            $result = $this->index(['a.id' => $id]);
            return isset($result['data']) ? $result['data'] : [];
        } catch (OrderSaleException $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /** 删除
     * @param $id
     * @return array
     */
    public function delete($id)
    {
        if (!$this->afterServiceModel->isHas($id)) {
            return ['message' => '该记录不存在', 'code' => 400];
        }
        $orderModel = new Order();
        $orderPackageModel = new OrderPackage();
        $user = CommonService::getUserInfo();
        //启动事务
        Db::startTrans();
        try {
            //审批通过的不能删除
            $info = $this->afterServiceModel->where(['id' => $id])->find();
            if ($info['approve_status'] == AfterSaleType::Approval) {
                throw new JsonErrorException('审批通过的记录不能删除', 500);
            }
            //查询订单是否存在除了这个售后单之外的退款失败订单
            $unChange = $this->afterServiceModel->where(['id' => ['<>', $id], 'order_id' => $info['order_id'],
                'type' => AfterSaleType::Refund, 'refund_status' => ['<>', AfterSaleType::RefundCompleted]])->find();
            //删除规则
            $this->afterServiceModel->where(['id' => $id])->delete();
            //删除问题货品
            $this->afterRedeliverModel->where(['after_sale_service_id' => $id])->delete();
            //删除补发货的
            $this->afterWastageModel->where(['after_sale_service_id' => $id])->delete();
            //订单的状态还原
            $orderInfo = $orderModel->field('id,status,shipping_time')->where(['id' => $info['order_id']])->find();
            $order_status = $orderInfo['status'];
            switch ($info['type']) {
                case AfterSaleType::Refund:
                    if (empty($unChange)) {
                        $order_status = $order_status - OrderStatusConst::RefundStatus;
                    }
                    break;
                case AfterSaleType::ReplacementGoods:
                    $order_status = $order_status - OrderStatusConst::ReplacementStatus;
                    break;
                case AfterSaleType::ReturnGoods:
                    $order_status = $order_status - OrderStatusConst::RefundGoodsStatus;
                    break;
                case AfterSaleType::RefundAndReplacementGoods:
                    $order_status = $order_status - OrderStatusConst::RefundStatus;
                    $order_status = $order_status - OrderStatusConst::ReplacementStatus;
                    break;
                case AfterSaleType::RefundAndReturnGoods:
                    $order_status = $order_status - OrderStatusConst::RefundStatus;
                    $order_status = $order_status - OrderStatusConst::RefundGoodsStatus;
                    break;
                case AfterSaleType::ReplacementGoodsAndReturnGoods:
                    $order_status = $order_status - OrderStatusConst::ReplacementStatus;
                    $order_status = $order_status - OrderStatusConst::RefundGoodsStatus;
                    break;
                case AfterSaleType::RefundAndReplacementGoodsAndReturnGoods:
                    $order_status = $order_status - OrderStatusConst::RefundStatus;
                    $order_status = $order_status - OrderStatusConst::ReplacementStatus;
                    $order_status = $order_status - OrderStatusConst::RefundGoodsStatus;
                    break;
            }
            //更新信息
            $orderModel->where(['id' => $orderInfo['id']])->update(['status' => $order_status]);
            $orderPackageModel->where(['order_id' => $orderInfo['id']])->update(['status' => $order_status]);
            //订单日志信息
            CommonService::addOrderLog($orderInfo['id'], '订单删除了售后单【' . $info['id'] . '】',
                $user['realname'], '', $user['user_id']);
            Db::commit();
            //ebay纠纷售后单处理
            if ($info['source_type'] == 1) {
                $sourceArr = explode('-', $info['source_id']);
                if ($sourceArr[0] == 'shopee') {
                    $returnsn = $sourceArr[1];
                    $shopeeDisputeService = new ShopeeDisputeService();
                    $shopeeDisputeService->relateAfterSale($returnsn, 0);
                } else {
                    $ebayDisputeHelp = new EbayDisputeHelp();
                    $result = $ebayDisputeHelp->recordAfterSales($info['source_id'], 0);
                    if (empty($result['status'])) {
                        throw new JsonErrorException($result['message'], 400);
                    }
                }
            }
            return true;
        } catch (OrderSaleException $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine(), 500);
        }
    }

    /** 提交审批
     * @param $id
     * @param $operator_id
     * @return array
     * @throws Exception
     */
    public function submitApproval($id, $operator_id)
    {
        if (!$this->afterServiceModel->isHas($id)) {
            throw new JsonErrorException('该记录不存在', 400);
        }
        $data['approve_status'] = AfterSaleType::Submitted;
        $data['submit_time'] = time();
        $data['submitter_id'] = $operator_id;
        $this->afterServiceModel->where(['id' => $id])->update($data);
        //查询记录
        $result = $this->index(['a.id' => $id]);
        return isset($result['data']) ? $result['data'] : [];
    }

    /** 查找订单
     * @param $content
     * @return \think\response\Json
     */
    public function find($content)
    {
        $orderModel = new Order();
        $orderInfo = $orderModel->field(true)->where(['order_number' => $content])->find();
        if (!empty($orderInfo)) {
            if ($orderInfo['status'] == OrderStatusConst::SaidInvalid) {
                throw new JsonErrorException('该订单已作废，请先取消作废');
            }
            $orderService = new OrderService();
            return $orderService->read($orderInfo['id']);
        }
        throw new JsonErrorException('该订单不存在');
    }

    /** 退回修改
     * @param $id
     * @param $operator
     * @param $remark
     * @return array
     * @throws Exception
     */
    public function retreat($id, $operator, $remark)
    {
        $result = ['status' => false, 'data' => []];
        $info = $this->afterServiceModel->field(true)->where(['id' => $id])->find();
        if (empty($info)) {
            $result['message'] = '该记录不存在';
            return $result;
        }
        $orderNoteModel = new OrderNote();
        Db::startTrans();
        try {
            $data['approve_status'] = AfterSaleType::ReturnModification;
            $this->afterServiceModel->where(['id' => $id])->update($data);
            //记录日志
            $log = [
                'sale_id' => $id,
                'operator_id' => $operator['id'],
                'operator' => $operator['operator'],
                'remark' => '退回修改，原因为：' . $remark,
                'create_time' => time()
            ];
            $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
            //记录到订单备注上
            $note = [
                'order_id' => $info['order_id'],
                'creator_id' => $operator['id'],
                'note' => $remark,
                'create_time' => time()
            ];
            $orderNoteModel->allowField(true)->isUpdate(false)->save($note);
            Db::commit();
            //查询记录
//            $data = $this->index(['a.id' => $id]);
//            isset($data['data']) && $result['data'] = $data['data'];
            $result['status'] = true;
            $result['message'] = '操作成功';
            return $result;
        } catch (OrderSaleException $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            return $result;
        } catch (Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            return $result;
        }
    }

    /**
     * 设置错误参数
     * @param $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * 获取错误参数
     * @return mixed
     */
    public function getError()
    {
        if (isset($this->error)) {
            return $this->error;
        } else {
            return [];
        }
    }

    /** 审批通过
     * @param $id
     * @param $operator
     * @param string $remark
     * @return array
     */
    public function adopt($id, $operator, $remark = '')
    {
        $result = ['status' => false, 'data' => []];
        if (!$this->afterServiceModel->isHas($id)) {
            //删除审批缓存
            if ($this->existsCache($id)) {
                $this->delCache($id);
            }
            $result['message'] = '该记录不存在';
            return $result;
        }
        if (Cache::store('order')->isAfterBusy($id)) {
            //删除审批缓存
            if ($this->existsCache($id)) {
                $this->delCache($id);
            }
            $result['message'] = '该售后单正在处理中...';
            return $result;
        }
        $data['approve_status'] = AfterSaleType::Approval;
        $data['approve_time'] = time();
        $data['approver_id'] = $operator['id'];
        $is_ok = false;
        Db::startTrans();
        try {
            Cache::store('order')->setAfterBusy($id);
            //记录日志
            $log = [
                'sale_id' => $id,
                'operator_id' => $operator['id'],
                'operator' => $operator['operator'],
                'remark' => '审批通过',
                'create_time' => time()
            ];
            $type = isset($operator['type']) ? $operator['type'] : 0;
            if (!empty($type)) {
                $this->afterSaleLogModel->insert($log);
            } else {
                $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
            }
            $saleServiceInfo = $this->afterServiceModel->field(true)->where(['id' => $id])->find();
            if ($saleServiceInfo['approve_status'] == AfterSaleType::Approval) {
                //删除审批缓存
                if ($this->existsCache($id)) {
                    $this->delCache($id);
                }
                throw new JsonErrorException('该售后订单已经审批通过，不可重复操作');
            }
            //读取问题商品
            $wastageWhere['after_sale_service_id'] = ['eq', $id];
            $wastageWhere['quantity'] = ['<>', 0];
            $wastageGoodsDetail = $this->afterWastageModel->field(true)->where($wastageWhere)->select();
            $orderModel = new Order();
            $orderInfo = $orderModel->field(true)->where(['id' => $saleServiceInfo['order_id']])->find();
            if (empty($orderInfo)) {
                //删除审批缓存
                if ($this->existsCache($id)) {
                    $this->delCache($id);
                }
                throw new JsonErrorException('售后单原订单ID【' . $saleServiceInfo['order_id'] . '】不存在');
            }
            switch ($saleServiceInfo['type']) {
                case AfterSaleType::Refund:
                    $this->refundByAdopt($orderInfo, $saleServiceInfo, $wastageGoodsDetail, $data, $operator);
                    break;
                case AfterSaleType::ReplacementGoods:
                    $this->replaceByAdopt($orderInfo, $saleServiceInfo, $orderModel, $data, $operator);
                    break;
                case AfterSaleType::ReturnGoods:
                    $this->returnGoodsByAdopt($orderInfo, $saleServiceInfo, $orderModel, $data, $operator);
                    break;
                case AfterSaleType::RefundAndReplacementGoods:
                    $this->refundByAdopt($orderInfo, $saleServiceInfo, $wastageGoodsDetail, $data, $operator);
                    $this->replaceByAdopt($orderInfo, $saleServiceInfo, $orderModel, $data, $operator);
                    break;
                case AfterSaleType::RefundAndReturnGoods:
                    $this->returnGoodsByAdopt($orderInfo, $saleServiceInfo, $orderModel, $data, $operator);
                    $this->refundByAdopt($orderInfo, $saleServiceInfo, $wastageGoodsDetail, $data, $operator);
                    break;
                case AfterSaleType::ReplacementGoodsAndReturnGoods:
                    $this->replaceByAdopt($orderInfo, $saleServiceInfo, $orderModel, $data, $operator);
                    $this->returnGoodsByAdopt($orderInfo, $saleServiceInfo, $orderModel, $data, $operator);
                    break;
                case AfterSaleType::RefundAndReplacementGoodsAndReturnGoods:
                    $this->replaceByAdopt($orderInfo, $saleServiceInfo, $orderModel, $data, $operator);
                    $this->returnGoodsByAdopt($orderInfo, $saleServiceInfo, $orderModel, $data, $operator);
                    $this->refundByAdopt($orderInfo, $saleServiceInfo, $wastageGoodsDetail, $data, $operator);
                    break;
            }
            $this->afterServiceModel->where(['id' => $id])->update($data);
            Db::commit();
            $is_ok = true;
            Cache::store('order')->delAfterBusy($id);
            //删除审批缓存
            if ($this->existsCache($id)) {
                $this->delCache($id);
            }
        } catch (ReportException $e) {
            Db::rollback();
            Cache::store('order')->delAfterBusy($id);
            //删除审批缓存
            if ($this->existsCache($id)) {
                $this->delCache($id);
            }
            $result['message'] = $e->getMessage();
            return $result;
        } catch (OrderSaleException $e) {
            Db::rollback();
            Cache::store('order')->delAfterBusy($id);
            //删除审批缓存
            if ($this->existsCache($id)) {
                $this->delCache($id);
            }
            $result['message'] = $e->getMessage();
            return $result;
        } catch (JsonErrorException $e) {
            Db::rollback();
            Cache::store('order')->delAfterBusy($id);
            //删除审批缓存
            if ($this->existsCache($id)) {
                $this->delCache($id);
            }
            $result['message'] = $e->getMessage();
            return $result;
        } catch (Exception $e) {
            Db::rollback();
            Cache::store('order')->delAfterBusy($id);
            //删除审批缓存
            if ($this->existsCache($id)) {
                $this->delCache($id);
            }
            $result['message'] = $e->getMessage();
            return $result;
        }
        if ($is_ok) {
            $orderStatisticService = new OrderStatisticService();
            $orderStatisticService->updateReportByRefund($id);
        }
        //查询记录
//        $data = $this->index(['a.id' => $id]);
//        isset($data['data']) && $result['data'] = $data['data'];
        $result['status'] = true;
        $result['message'] = '操作成功';

        $error = $this->getError();
        if (isset($error['state']) && $error['state'] == 0) {
            $result['status'] = false;
            $result['message'] = $error['error_msg'];
            //添加错误信息缓存
            Cache::handler()->hSet('hash:sales:adopt:result' . date('Ymd') . ':' . date('H'),
                $id . '-' . $operator['operator'] . '-' . date('Y-m-d H:i:s'),
                json_encode($result, JSON_UNESCAPED_UNICODE));
            //添加售后单日志信息
            $log = [
                'sale_id' => $saleServiceInfo['order_id'],
                'operator_id' => $operator['id'],
                'operator' => $operator['operator'],
                'remark' => '售后单审批通过错误：' . $error['error_msg'],
                'create_time' => time()
            ];
            $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
        }
        return $result;
    }

    /**
     * 退款审核通过
     * @param $orderInfo
     * @param $saleServiceInfo
     * @param $wastageGoodsDetail
     * @param $data
     * @param $operator
     * @throws Exception
     */
    private function refundByAdopt($orderInfo, $saleServiceInfo, $wastageGoodsDetail, &$data, $operator)
    {
        //记录日志
        CommonService::addOrderLog($orderInfo['id'], '订单售后退款单【' . $saleServiceInfo['id'] . '】审批通过',
            $operator['operator'],
            $orderInfo['status'], $operator['id']);
        $updateData = [];
        //生成PayPal订单
        $accountInfo = Cache::store('account')->getAccounts();
        if (isset($accountInfo[$saleServiceInfo['channel_id']][$saleServiceInfo['account_id']]['code'])) {
            $refund_part['currency'] = $saleServiceInfo['refund_currency'];
            $refund_part['amount'] = sprintf("%.2f", $saleServiceInfo['refund_amount']);
            $refund_part['note'] = $saleServiceInfo['to_buyer_message'];
            switch ($orderInfo['channel_id']) {
                case ChannelAccountConst::channel_aliExpress:
                    $data['refund_status'] = AfterSaleType::RefundFailed;
                    //新增订单备注
                    $orderNoteModel = new OrderNote();
                    $orderNoteModel->allowField(true)->isUpdate(false)->save([
                        'order_id' => $saleServiceInfo['order_id'],
                        'note' => '速卖通不支持在线退款',
                        'creator_id' => $operator['id'],
                        'create_time' => time()
                    ]);
                    //订单日志信息
                    CommonService::addOrderLog($saleServiceInfo['order_id'],
                        '订单售后退款失败返回的错误原因【速卖通不支持在线退款】',
                        $operator['operator'], '', $operator['id']);
                    break;
                case ChannelAccountConst::channel_amazon:
                    $this->refundByAmazon($orderInfo, $saleServiceInfo, $wastageGoodsDetail, $data, $updateData,
                        $refund_part, $operator);
                    break;
                case ChannelAccountConst::channel_ebay:
                    $this->refundByEbay($orderInfo, $saleServiceInfo, $data, $refund_part, $operator);
                    break;
                case ChannelAccountConst::channel_Shopee:
                    $this->refundByShopee($orderInfo, $saleServiceInfo, $data, $operator);
                    break;
                default:
                    $data['refund_status'] = AfterSaleType::RefundFailed;
                    //新增订单备注
                    $orderNoteModel = new OrderNote();
                    $orderNoteModel->allowField(true)->isUpdate(false)->save([
                        'order_id' => $saleServiceInfo['order_id'],
                        'note' => '此平台不支持在线退款',
                        'creator_id' => $operator['id'],
                        'create_time' => time()
                    ]);
                    //订单日志信息
                    CommonService::addOrderLog($saleServiceInfo['order_id'],
                        '订单售后退款失败返回的错误原因【此平台不支持在线退款】',
                        $operator['operator'], '', $operator['id']);
                    break;
            }
        }
        $user['realname'] = $operator['operator'];
        $user['user_id'] = $operator['id'];
        //部分退款
        $this->adjustOrderByRefund($orderInfo, $saleServiceInfo, $updateData, $user, $data['refund_status']);
        //退款售后通过订单ID回写sku补贴数据
//        (new OrderHelp())->subsidyAmountByOrderId($saleServiceInfo['id'], $orderInfo['id'], $orderInfo['pay_time'], 2);
    }


//    private function refundByAdopt($orderInfo, $saleServiceInfo, $wastageGoodsDetail, &$data, $operator)
//    {
//        //记录日志
//        CommonService::addOrderLog($orderInfo['id'], '订单售后退款单【' . $saleServiceInfo['id'] . '】审批通过',
//            $operator['operator'],
//            $orderInfo['status'], $operator['id']);
//        $updateData = [];
//        //生成PayPal订单
//        $accountInfo = Cache::store('account')->getAccounts();
//        if (isset($accountInfo[$saleServiceInfo['channel_id']][$saleServiceInfo['account_id']]['account_name'])) {
//            $refund_part['currency'] = $saleServiceInfo['refund_currency'];
//            $refund_part['amount'] = sprintf("%.2f", $saleServiceInfo['refund_amount']);
//            $refund_part['note'] = $saleServiceInfo['remark'];
//            if (trim($orderInfo['pay_name']) == 'PayPal') {
//                //记录缓存
//                Cache::handler()->hSet('hash:sales:Paypal' . date('Ymd') . ':' . date('H'),
//                    $orderInfo['channel_order_number'] . '-' . date('Y-m-d H:i:s'),
//                    json_encode($refund_part));
//               $result = Paypal::paypalRefund($orderInfo['pay_code'], $refund_part);
//                $result['state'] = 1;
//                if ($result['state'] == 1) {
//                    $data['refund_status'] = AfterSaleType::RefundCompleted;
//                    CommonService::addOrderLog($orderInfo['id'], '订单生成PayPal退款单成功', $operator['operator'],
//                        $orderInfo['status'], $operator['id']);
//                } else {
//                    $data['refund_status'] = AfterSaleType::RefundFailed;
//                    //新增订单备注
//                    $orderNoteModel = new OrderNote();
//                    $orderNoteModel->allowField(true)->isUpdate(false)->save([
//                        'order_id' => $saleServiceInfo['order_id'],
//                        'note' => $result['error_msg'],
//                        'creator_id' => $operator['id'],
//                        'create_time' => time()
//                    ]);
//                    //订单日志信息
//                    CommonService::addOrderLog($saleServiceInfo['order_id'],
//                        '订单售后退款失败返回的错误原因【' . $result['error_msg'] . '】',
//                        $operator['operator'], '', $operator['id']);
//                }
//            } else {
//            switch ($orderInfo['channel_id']) {
//                case ChannelAccountConst::channel_aliExpress:
//                    $data['refund_status'] = AfterSaleType::RefundFailed;
//                    //新增订单备注
//                    $orderNoteModel = new OrderNote();
//                    $orderNoteModel->allowField(true)->isUpdate(false)->save([
//                        'order_id' => $saleServiceInfo['order_id'],
//                        'note' => '速卖通不支持在线退款',
//                        'creator_id' => $operator['id'],
//                        'create_time' => time()
//                    ]);
//                    //订单日志信息
//                    CommonService::addOrderLog($saleServiceInfo['order_id'],
//                        '订单售后退款失败返回的错误原因【速卖通不支持在线退款】',
//                        $operator['operator'], '', $operator['id']);
//                    break;
//                case ChannelAccountConst::channel_amazon:
//                    $this->refundByAmazon($orderInfo, $saleServiceInfo, $wastageGoodsDetail, $data, $updateData,
//                        $refund_part, $operator);
//                    break;
//                case ChannelAccountConst::channel_ebay:
//                    $this->refundByEbay($orderInfo, $saleServiceInfo, $data, $operator);
//                    break;
//            }
////            }
//        }
//        $user['realname'] = $operator['operator'];
//        $user['user_id'] = $operator['id'];
//        //部分退款
//        $this->adjustOrderByRefund($orderInfo, $saleServiceInfo, $updateData, $user);
//    }

    /**
     * 补发货审核通过
     * @param $orderInfo
     * @param $saleServiceInfo
     * @param Order $orderModel
     * @param $data
     * @param $operator
     * @throws Exception
     */
    private function replaceByAdopt($orderInfo, $saleServiceInfo, Order $orderModel, &$data, $operator)
    {
        $orderPackageModel = new OrderPackage();
        //记录日志
        CommonService::addOrderLog($orderInfo['id'], '订单售后补发货单【' . $saleServiceInfo['id'] . '】审批通过',
            $operator['operator'],
            $orderInfo['status'], $operator['id']);
        //关联的产品
        $saleGoodsList = $this->afterRedeliverModel->field('id,goods_id,after_sale_service_id,sku_id,sku,quantity as sku_quantity')->where([
            'after_sale_service_id' => $saleServiceInfo['id'],
            'type' => AfterSaleType::ReplacementGoods
        ])->select();
        //新建一个补发货单
        $newOrderInfo = $this->addDelivery($saleServiceInfo, $saleGoodsList);
        $data['reissue_returns_status'] = AfterSaleType::SupplementaryShipment;
        //更改原订单的状态，标记为已重发
        $orderModel->where(['id' => $saleServiceInfo['order_id']])->update([
            'status' => OrderStatusConst::HaveToResend,
            'related_order_id' => $newOrderInfo['id']
        ]);
        $data['redeliver_order_id'] = $newOrderInfo['id'];   //记录生产的补发单id
        $orderPackageModel->where(['order_id' => $saleServiceInfo['order_id']])->update(['status' => OrderStatusConst::HaveToResend]);
        CommonService::addOrderLog($saleServiceInfo['order_id'],
            '订单创建了补发单【' . $newOrderInfo['order_number'] . '】',
            $operator['operator'], OrderStatusConst::HaveToResend, $operator['id']);
    }

    /**
     * 退货审核通过
     * @param $orderInfo
     * @param $saleServiceInfo
     * @param Order $orderModel
     * @param $data
     * @param $operator
     * @throws Exception
     */
    private function returnGoodsByAdopt($orderInfo, $saleServiceInfo, Order $orderModel, &$data, $operator)
    {
        $orderPackageModel = new OrderPackage();
        //记录日志
        CommonService::addOrderLog($orderInfo['id'], '订单售后退货单【' . $saleServiceInfo['id'] . '】审批通过',
            $operator['operator'],
            $orderInfo['status'], $operator['id']);
        //退货，记录到入库里面
        //订单应该标记为申请退货
        $data['reissue_returns_status'] = AfterSaleType::Returning;
        $orderModel->where(['id' => $saleServiceInfo['order_id']])->update(['status' => OrderStatusConst::ToApplyForRefundGoods]);
        $orderPackageModel->where(['order_id' => $saleServiceInfo['order_id']])->update(['status' => OrderStatusConst::ToApplyForRefundGoods]);
    }

    /**
     * 亚马孙退款
     * @param $orderInfo
     * @param $saleServiceInfo
     * @param $wastageGoodsDetail
     * @param $data
     * @param $updateData
     * @param $refund_part
     * @param $operator
     * @throws Exception
     */
    private function refundByAmazon(
        $orderInfo,
        &$saleServiceInfo,
        $wastageGoodsDetail,
        &$data,
        &$updateData,
        $refund_part,
        $operator
    )
    {
        $orderSourceDetailModel = new OrderSourceDetail();
        $orderDetailModel = new OrderDetail();
        $amazonData['account_id'] = $orderInfo['channel_account_id'];
        $amazonData['detail'] = [];
        foreach ($wastageGoodsDetail as $ar => $detail) {
            //先查看来详情
            $detailInfo = $orderDetailModel->field(true)->where([
                'order_id' => $orderInfo['id'],
                'sku_id' => $detail['sku_id']
            ])->find();
            if (!empty($detailInfo)) {
                //查询交易号
                $source = $orderSourceDetailModel->field(true)->where([
                    'order_id' => $orderInfo['id'],
                    'channel_item_id' => $detailInfo['channel_item_id']
                ])->find();
                if (!empty($source)) {
                    $tempData = [
                        'adjustment_reason' => 'CustomerReturn',
                        'order_number' => $orderInfo['channel_order_number'],
                        'account_id' => $orderInfo['channel_account_id'],
                        'record_number' => $source['transaction_id'],
                        'item_id' => $source['channel_item_id'],
                        'refund_price' => $refund_part['amount'],
                        'currency' => $refund_part['currency']
                    ];
                    array_push($amazonData['detail'], $tempData);
                }
            }
        }
        //记录缓存
        $amazonService = new AmazonService();
        $result = $amazonService->orderRefund($amazonData);
        Cache::handler()->hSet('hash:sales:amazon:result' . date('Ymd') . ':' . date('H'),
            $orderInfo['channel_order_number'] . '-' . date('Y-m-d H:i:s'),
            json_encode($result));
        if (isset($result['status']) && $result['status']) {  //退款成功
            $data['refund_status'] = AfterSaleType::RefundCompleted;
            CommonService::addOrderLog($orderInfo['id'],
                '订单退款成功,feedSubmissionId【' . $result['feedSubmissionId'] . '】',
                $operator['operator'],
                $orderInfo['status'], $operator['id']);
        } else {  //退款失败
            $data['refund_status'] = AfterSaleType::RefundFailed;
            //新增订单备注
            $orderNoteModel = new OrderNote();
            $orderNoteModel->allowField(true)->isUpdate(false)->save([
                'order_id' => $saleServiceInfo['order_id'],
                'note' => $result['error_msg'],
                'creator_id' => $operator['id'],
                'create_time' => time()
            ]);
            //订单日志信息
            CommonService::addOrderLog($saleServiceInfo['order_id'],
                '订单售后退款失败返回的错误原因【' . $result['error_msg'] . '】',
                $operator['operator'], '', $operator['id']);
        }
        //订单佣金调整
        $key = 'amazon_channel_cost';
        $config = (new ChannelConfig(ChannelAccountConst::channel_amazon))->getConfig($key);
        $updateData['channel_cost'] = $saleServiceInfo['refund_amount'] * $config * 0.8;
    }

    /**
     * ebay平台退款
     * @param $orderInfo
     * @param $saleServiceInfo
     * @param $data
     * @param $refund_part
     * @param $operator
     * @throws Exception
     */
    private function refundByEbay($orderInfo, &$saleServiceInfo, &$data, $refund_part, $operator)
    {
        //调用ebay退款接口 
        $transaction_id = $orderInfo['pay_code'];
        $collection_account = $orderInfo['collection_account'];
        $extra = [
            'note' => $refund_part['note'],
            'amount' => $refund_part['amount'],
            'currency' => $refund_part['currency']
        ];
        $result = [];
        if (!empty($transaction_id)) {
            $paypalOrderService = new PaypalOrderService();
            //是否为纠纷退款
            if (isset($saleServiceInfo['source_type']) && !empty($saleServiceInfo['source_type'])) {
                $ebayDisputeHelpService = new EbayDisputeHelp();
                try {
                    $result = $ebayDisputeHelpService->afterSaleAutoRefund($saleServiceInfo['source_id'], $extra, $saleServiceInfo['id']);
                    $result['state'] = $result['status'];
                    $result['error_msg'] = $result['message'] ?? '';
                } catch (\Exception $e) {
                    $result['state'] = 0;
                    $result['error_msg'] = $e->getMessage();
                    $data['refund_status'] = AfterSaleType::RefundFailed;
                    //订单日志信息
                    CommonService::addOrderLog($saleServiceInfo['order_id'],
                        '订单售后退款失败返回的错误原因【' . $e->getMessage() . '】',
                        $operator['operator'], '', $operator['id']);
                    //售后单日志信息
                    $log = [
                        'sale_id' => $saleServiceInfo['order_id'],
                        'operator_id' => $operator['id'],
                        'operator' => $operator['operator'],
                        'remark' => '纠纷退款异常' . $e->getMessage(),
                        'create_time' => time()
                    ];
                    $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
                }
            } else {
                //是否为纠纷ebay退款
                try {
                    $result = $paypalOrderService->refund($transaction_id, $extra, $collection_account);
                } catch (\Exception $e) {
                    $result['state'] = 0;
                    $result['error_msg'] = $e->getMessage();
                    $data['refund_status'] = AfterSaleType::RefundFailed;
                    //订单日志信息
                    CommonService::addOrderLog($saleServiceInfo['order_id'],
                        '订单售后退款失败返回的错误原因【' . $e->getMessage() . '】',
                        $operator['operator'], '', $operator['id']);
                    //售后单日志信息
                    $log = [
                        'sale_id' => $saleServiceInfo['order_id'],
                        'operator_id' => $operator['id'],
                        'operator' => $operator['operator'],
                        'remark' => 'PayPal退款异常' . $e->getMessage(),
                        'create_time' => time()
                    ];
                    $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
                }
            }
            //存储错误信息
            if ($result['state'] == 0) {
                $this->setError($result);
            }
        } else {
            $result['state'] = 0;
            $result['error_msg'] = 'paypal付款交易号未知';
        }
        Cache::handler()->hSet('hash:sales:ebay:result' . date('Ymd') . ':' . date('H'),
            $orderInfo['channel_order_number'] . '-' . date('Y-m-d H:i:s'),
            json_encode($result, JSON_UNESCAPED_UNICODE));
        if (isset($result['state']) && !empty($result['state'])) {  //退款成功
            if ($result['state'] == 2) {
                $data['refund_status'] = AfterSaleType::Refunding;
                CommonService::addOrderLog($orderInfo['id'],
                    '订单退款中',
                    $operator['operator'],
                    $orderInfo['status'], $operator['id']);
            } else {
                $data['refund_status'] = AfterSaleType::RefundCompleted;
                CommonService::addOrderLog($orderInfo['id'],
                    '订单退款成功',
                    $operator['operator'],
                    $orderInfo['status'], $operator['id']);
            }
            $data['refund_paypal_account'] = $orderInfo['collection_account'];
            if (empty($saleServiceInfo['source_type'])) {
                $data['paypal_trx_id'] = $result['refund_transaction_id'];
            }
        } else {  //退款失败
            $data['refund_status'] = AfterSaleType::RefundFailed;
            //新增订单备注
            $orderNoteModel = new OrderNote();
            $orderNoteModel->allowField(true)->isUpdate(false)->save([
                'order_id' => $saleServiceInfo['order_id'],
                'note' => $result['error_msg'],
                'creator_id' => $operator['id'],
                'create_time' => time()
            ]);
            //订单日志信息
            CommonService::addOrderLog($saleServiceInfo['order_id'],
                '订单售后退款失败返回的错误原因【' . $result['error_msg'] . '】',
                $operator['operator'], '', $operator['id']);
        }
    }

    /**
     * shopee平台退款
     * @param $orderInfo
     * @param $saleServiceInfo
     * @param $data
     * @param $operator
     * @throws Exception
     */
    public function refundByShopee($orderInfo, &$saleServiceInfo, &$data, $operator)
    {
        //调用shopee退款接口
        $result = [];
        //是否为纠纷退款
        if (isset($saleServiceInfo['source_type']) && !empty($saleServiceInfo['source_type'])) {
            try {
                $sourceArr = explode('-', $saleServiceInfo['source_id']);
                $returnsn = $sourceArr[1];
                $shopeeDisputeService = new ShopeeDisputeService();
                $result = $shopeeDisputeService->confirmReturn($returnsn);
                $result['error_msg'] = $result['message'] ?? '';
            } catch (Exception $e) {
                $result['status'] = 0;
                $result['error_msg'] = $e->getMessage();
                $data['refund_status'] = AfterSaleType::RefundFailed;
                //订单日志信息
                CommonService::addOrderLog($saleServiceInfo['order_id'],
                    '订单售后退款失败返回的错误原因【' . $e->getMessage() . '】',
                    $operator['operator'], '', $operator['id']);
                //售后单日志信息
                $log = [
                    'sale_id' => $saleServiceInfo['order_id'],
                    'operator_id' => $operator['id'],
                    'operator' => $operator['operator'],
                    'remark' => '纠纷退款异常' . $e->getMessage(),
                    'create_time' => time()
                ];
                $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
            }
        } else {
            $result['status'] = 0;
            $result['error_msg'] = 'shopee暂只支持纠纷退款';
        }
        Cache::handler()->hSet('hash:sales:shopee:result' . date('Ymd') . ':' . date('H'),
            $orderInfo['channel_order_number'] . '-' . date('Y-m-d H:i:s'),
            json_encode($result, JSON_UNESCAPED_UNICODE));
        if (isset($result['status']) && !empty($result['status'])) {  //退款成功
            $data['refund_status'] = AfterSaleType::RefundCompleted;
            CommonService::addOrderLog($orderInfo['id'],
                '订单退款成功',
                $operator['operator'],
                $orderInfo['status'], $operator['id']);
            $data['refund_paypal_account'] = $orderInfo['collection_account'];
        } else {  //退款失败
            $data['refund_status'] = AfterSaleType::RefundFailed;
            //新增订单备注
            $orderNoteModel = new OrderNote();
            $orderNoteModel->allowField(true)->isUpdate(false)->save([
                'order_id' => $saleServiceInfo['order_id'],
                'note' => $result['error_msg'],
                'creator_id' => $operator['id'],
                'create_time' => time()
            ]);
            //订单日志信息
            CommonService::addOrderLog($saleServiceInfo['order_id'],
                '订单售后退款失败返回的错误原因【' . $result['error_msg'] . '】',
                $operator['operator'], '', $operator['id']);
        }
    }

    /**
     * 退款申请，订单调整
     * @param $orderInfo
     * @param $saleServiceInfo
     * @param $updateData
     * @param $user
     * @param $refund_status
     * @throws Exception
     * @throws \app\common\exception\OrderPackageException
     */
    private function adjustOrderByRefund($orderInfo, $saleServiceInfo, $updateData, $user, $refund_status)
    {
        try {
            $orderPackageModel = new OrderPackage();
            $orderService = new OrderService();
            $orderModel = new Order();
            $afterSaleServiceModel = new AfterSaleService();
            $packageList = $orderPackageModel->where(['order_id' => $orderInfo['id']])->select();
            //查询所有该订单的所有退款售后单的金额
            $refund_amount = $afterSaleServiceModel->where(['order_id' => $orderInfo['id'], 'type' => AfterSaleType::Refund,
            'refund_status' => AfterSaleType::RefundCompleted])->sum('refund_amount');
            $pack = [];
            $orderData = [];
            $orderData['status'] = $orderInfo['status'];
            if (!empty($updateData)) {
                $orderData['channel_cost'] = $updateData['channel_cost'];
            }
            //查询当前订单是否还有售后单为退款失败，如果不存在，再修改订单和包裹状态
            $refund_failed_num = $afterSaleServiceModel->where(['id' => ['<>', $saleServiceInfo['id']], 'order_id' => $orderInfo['id'], 'type' => AfterSaleType::Refund,
                'refund_status' => ['<>', AfterSaleType::RefundCompleted]])->count();
            //判断退款成功
            if ($refund_status == AfterSaleType::RefundCompleted) {
                //全部退款成功金额
                $refunded_amount = $refund_amount + $saleServiceInfo['refund_amount'];
                if ($orderInfo['pay_fee'] > $saleServiceInfo['refund_amount'] && $orderInfo['pay_fee'] > $refunded_amount) {
                    if (empty($orderInfo['shipping_time'])) {
                        //验证当前售后单退款状态，统计当前订单所有的
                        if ($refund_failed_num == 0) {
                            //有可能要释放库存等等操作
                            $orderService->changeByStatus($packageList, true, $pack, $orderData);
                            $orderData['status'] = OrderStatusConst::ForDistribution;
                            $orderModel->where(['id' => $saleServiceInfo['order_id']])->update($orderData);
                            $pack['status'] = OrderStatusConst::ForDistribution;
                            $orderPackageModel->where(['order_id' => $saleServiceInfo['order_id']])->update($pack);
                            CommonService::addOrderLog($orderInfo['id'], '订单为部分退款，系统自动标记为待分配', $user['realname'],
                                $orderInfo['status'], $user['user_id']);
                        } else {
                            CommonService::addOrderLog($orderInfo['id'], '订单为部分退款，检测到还存在退款失败售后单，
                        不变更订单和包裹状态', $user['realname'], $orderInfo['status'], $user['user_id']);
                        }
                    } else {
                        if ($refund_failed_num == 0) {
                            $orderData['status'] = $orderData['status'] - OrderStatusConst::RefundStatus;
                            $orderModel->where(['id' => $saleServiceInfo['order_id']])->update($orderData);
                        }
                    }
                } else {    //当前订单全额退款成功
                    //有可能要释放库存等等操作
                    $orderService->changeByStatus($packageList, true, $pack, $orderData);
                    $orderData['status'] = OrderStatusConst::HaveRefund;
                    //订单应该标记为申请退款
                    $orderModel->where(['id' => $saleServiceInfo['order_id']])->update($orderData);
                    $pack['status'] = OrderStatusConst::HaveRefund;
                    $orderPackageModel->where(['order_id' => $saleServiceInfo['order_id']])->update($pack);
                }
            }
            //（旧）部分退款处理
//            if ($orderInfo['pay_fee'] > $saleServiceInfo['refund_amount'] && $orderInfo['pay_fee'] > $refund_amount) {
//                if (empty($orderInfo['shipping_time'])) {
//                    //验证当前售后单退款状态，统计当前订单所有的
//                    if ($refund_failed_num == 0 && $refund_status == AfterSaleType::RefundCompleted) {
//                        //有可能要释放库存等等操作
//                        $orderService->changeByStatus($packageList, true, $pack, $orderData);
//                        $orderData['status'] = OrderStatusConst::ForDistribution;
//                        $orderModel->where(['id' => $saleServiceInfo['order_id']])->update($orderData);
//                        $pack['status'] = OrderStatusConst::ForDistribution;
//                        $orderPackageModel->where(['order_id' => $saleServiceInfo['order_id']])->update($pack);
//                        CommonService::addOrderLog($orderInfo['id'], '订单为部分退款，系统自动标记为待分配', $user['realname'],
//                            $orderInfo['status'], $user['user_id']);
//                    } else {
//                        CommonService::addOrderLog($orderInfo['id'], '订单为部分退款，检测到还存在退款失败售后单，
//                        不变更订单和包裹状态', $user['realname'], $orderInfo['status'], $user['user_id']);
//                    }
//                }
//            } else {
//                //未发货而且当前退款成功
//                if (empty($orderInfo['shipping_time']) && $refund_failed_num == 0 && $refund_status == AfterSaleType::RefundCompleted) {
//                    //有可能要释放库存等等操作
//                    $orderService->changeByStatus($packageList, true, $pack, $orderData);
//                    $orderData['status'] = OrderStatusConst::HaveRefund;
//                    //订单应该标记为申请退款
//                    $orderModel->where(['id' => $saleServiceInfo['order_id']])->update($orderData);
//                    $pack['status'] = OrderStatusConst::HaveRefund;
//                    $orderPackageModel->where(['order_id' => $saleServiceInfo['order_id']])->update($pack);
//                }
//                //已发货并且退款成功的
//                if (!empty($orderInfo['shipping_time']) && $refund_failed_num == 0 && $refund_status == AfterSaleType::RefundCompleted) {
//                    $orderData['status'] = OrderStatusConst::HaveRefund;
//                    $orderModel->where(['id' => $saleServiceInfo['order_id']])->update($orderData);
//                }
//            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /** 退款标记为完成
     * @param $id
     * @param $operator
     * @param $remark
     * @return array
     */
    public function complete($id, $operator, $remark = '')
    {
        $user = Common::getUserInfo();
        if (!$this->afterServiceModel->isHas($id)) {
            throw new JsonErrorException('该记录不存在');
        }
        Db::startTrans();
        try {
            $orderModel = new Order();
            $orderService = new OrderService();
            $orderPackageModel = new OrderPackage();
            $data['approve_status'] = AfterSaleType::Approval;
            $data['refund_status'] = AfterSaleType::RefundCompleted;
            $this->afterServiceModel->where(['id' => $id])->update($data);
            //记录日志
            $log = [
                'sale_id' => $id,
                'operator_id' => $operator['id'],
                'operator' => $operator['operator'],
                'remark' => '退款标记为已完成',
                'create_time' => time()
            ];
            $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
            //查询售后单对应的订单数据
            $saleServiceInfo = $this->afterServiceModel->field('id,order_id,refund_amount')->where(['id' => $id])->find();
            $orderInfo = $orderModel->field('id,status,pay_fee,shipping_time')->where(['id' => $saleServiceInfo['order_id']])->find();
            $packageList = $orderPackageModel->where(['order_id' => $orderInfo['id']])->select();
            //查询所有该订单的所有退款售后单的金额
            $refund_amount = $this->afterServiceModel->where(['order_id' => $orderInfo['id'], 'type' => AfterSaleType::Refund,
                'refund_status' => AfterSaleType::RefundCompleted])->sum('refund_amount');
            $pack = [];
            $orderData = [];
            $orderData['status'] = $orderInfo['status'];
            //查询当前订单是否还有售后单为退款失败，如果不存在，再修改订单和包裹状态
            $refund_failed_num = $this->afterServiceModel->where(['id' => ['<>', $saleServiceInfo['id']], 'order_id' => $orderInfo['id'], 'type' => AfterSaleType::Refund,
                'refund_status' => ['<>', AfterSaleType::RefundCompleted]])->count();
            //全部退款成功金额
            $refunded_amount = $refund_amount + $saleServiceInfo['refund_amount'];
            if ($orderInfo['pay_fee'] > $saleServiceInfo['refund_amount'] && $orderInfo['pay_fee'] > $refunded_amount) {
                if (empty($orderInfo['shipping_time'])) {
                    //验证当前售后单退款状态，统计当前订单所有的
                    if ($refund_failed_num == 0) {
                        //有可能要释放库存等等操作
                        $orderService->changeByStatus($packageList, true, $pack, $orderData);
                        $orderData['status'] = OrderStatusConst::ForDistribution;
                        $orderModel->where(['id' => $saleServiceInfo['order_id']])->update($orderData);
                        $pack['status'] = OrderStatusConst::ForDistribution;
                        $orderPackageModel->where(['order_id' => $saleServiceInfo['order_id']])->update($pack);
                        CommonService::addOrderLog($orderInfo['id'], '订单为部分退款，系统自动标记为待分配', $user['realname'],
                            $orderInfo['status'], $user['user_id']);
                    } else {
                        CommonService::addOrderLog($orderInfo['id'], '订单为部分退款，检测到还存在退款失败售后单，
                不变更订单和包裹状态', $user['realname'], $orderInfo['status'], $user['user_id']);
                    }
                } else {
                    if ($refund_failed_num == 0) {
                        $orderData['status'] = $orderData['status'] - OrderStatusConst::RefundStatus;
                        $orderModel->where(['id' => $saleServiceInfo['order_id']])->update($orderData);
                    }
                }
            } else {    //当前订单全额退款成功
                //有可能要释放库存等等操作
                $orderService->changeByStatus($packageList, true, $pack, $orderData);
                $orderData['status'] = OrderStatusConst::HaveRefund;
                //订单应该标记为申请退款
                $orderModel->where(['id' => $saleServiceInfo['order_id']])->update($orderData);
                $pack['status'] = OrderStatusConst::HaveRefund;
                $orderPackageModel->where(['order_id' => $saleServiceInfo['order_id']])->update($pack);
            }
            Db::commit();
            return true;
        } catch (OrderSaleException $e) {
            Db::rollback();
            throw new JsonErrorException('操作失败', 500);
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException('操作失败', 500);
        }
    }

    /** 退款重新执行
     * @param $id
     * @param $operator
     * @param $remark
     * @return bool
     */
    public function again($id, $operator, $remark = '')
    {
        set_time_limit(0);
        if (!$this->afterServiceModel->isHas($id)) {
            throw new JsonErrorException('该记录不存在');
        }
        Db::startTrans();
        try {
            //更新审批状态为已提交
            $data['approve_status'] = AfterSaleType::Submitted;
            $this->afterServiceModel->where(['id' => $id])->update($data);
//            $adoptInfo = $this->adopt($id, $operator, $remark);
            //重新加入退款审批队列
            $params = [
                'id' => $id,
                'operator' => $operator,
                'remark' => $remark,
            ];
            (new UniqueQueuer(OrderSaleAdoptQueue::class))->push($params);
            Db::commit();
            return true;
            //查询记录
//            $result = $this->index(['a.id' => $id]);
//            if ($adoptInfo['status']) {
//                $result['data'] = isset($result['data']) ? $result['data'] : [];
//                $result['status'] = true;
//            } else {
//                $result['status'] = $adoptInfo['status'];
//                $result['message'] = $adoptInfo['message'];
//            }
//            return $result;
        } catch (OrderSaleException $e) {
            Db::rollback();
            throw new JsonErrorException('操作失败', 500);
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException('操作失败', 500);
        }
    }

//    public function again($id, $operator, $remark = '')
//    {
//        if (!$this->afterServiceModel->isHas($id)) {
//            throw new JsonErrorException('该记录不存在');
//        }
//        Db::startTrans();
//        try {
//            $data['approve_status'] = AfterSaleType::Uncommitted;
//            $this->afterServiceModel->where(['id' => $id])->update($data);
//            //记录日志
//            $log = [
//                'sale_id' => $id,
//                'operator_id' => $operator['id'],
//                'operator' => $operator['operator'],
//                'remark' => '退款设置重新执行',
//                'create_time' => time()
//            ];
//            $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
//            Db::commit();
//            //查询记录
//            $result = $this->index(['a.id' => $id]);
//            return isset($result['data']) ? $result['data'] : [];
//        } catch (OrderSaleException $e) {
//            Db::rollback();
//            throw new JsonErrorException('操作失败', 500);
//        } catch (Exception $e) {
//            Db::rollback();
//            throw new JsonErrorException('操作失败', 500);
//        }
//    }

    /**
     * 补发货单
     * @param $saleServiceInfo 【售后单信息】
     * @param $saleGoodsList 【售后单商品信息】
     * @return mixed
     * @throws OrderSaleException
     */
    private function addDelivery($saleServiceInfo, $saleGoodsList)
    {
        $goods = $saleGoodsList;
        //新增订单
        $order = $this->order($saleServiceInfo);
        //订单地址
        $this->address($saleServiceInfo, $order);
        //订单原平台信息
        //$this->source($order, $goods);
        //订单产品详情
        $detail = $this->detail($order, $goods);
        //订单包裹信息
        $this->package($order, $detail);
        return $order;
    }

    /** 订单地址
     * @param $data
     * @param $order
     * @return array
     * @throws Exception
     */
    private function address($data, $order)
    {
        try {
            $address = [
                'id' => ServiceTwitter::instance()->nextId($order['channel_id'],
                    $order['channel_account_id']),
                'order_id' => $order['id'],
                'consignee' => $data['buyer_name'],
                'country_code' => $data['buyer_country_code'],
                'channel_id' => $order['channel_id'],
                'channel_account_id' => $order['channel_account_id'],
                'buyer_id' => $order['buyer_id'] ?? '',
                'area_info' => '',
                'city' => $data['buyer_city'],
                'city_id' => 0,
                'province' => $data['buyer_state'],
                'area_id' => 0,
                'address' => $data['buyer_address_one'],
                'address2' => $data['buyer_address_two'],
                'zipcode' => $data['buyer_postal_code'],
                'tel' => $data['buyer_phone'],
                'mobile' => $data['buyer_mobile'],
                'source_address' => '',
                'email' => '',
                'create_time' => time(),
                'update_time' => time()
            ];
            $addressModel = new OrderAddress();
            $addressModel->allowField(true)->isUpdate(false)->save($address);
        } catch (OrderSaleException $e) {
            throw new OrderSaleException($e->getMessage() . $e->getFile() . $e->getLine());
        } catch (Exception $e) {
            throw new OrderSaleException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /** 订单基本信息
     * @param $data
     * @return mixed
     * @throws Exception
     */
    private function order($data)
    {
        try {
            $order = [];
            //找到原来的订单
            $orderModel = new Order();
            $orderInfo = $orderModel->where(['id' => $data['order_id']])->find();
            if (!empty($orderInfo)) {
                $redeliver = $this->checkAfterOrder($data['order_id']);
                $order['id'] = ServiceTwitter::instance()->nextId($orderInfo['channel_id'],
                    $orderInfo['channel_account_id']);
                $order['old_order_number'] = $orderInfo['order_number'];
                $order['order_number'] = $orderInfo['order_number'] . '_' . $redeliver;
                $order['status'] = OrderStatusConst::ForDistribution;
                $order['create_time'] = time();
                $order['order_time'] = time();
                $order['update_time'] = time();
                $order['currency_code'] = $orderInfo['currency_code'];
                $order['country_code'] = $data['buyer_country_code'];
                $order['rate'] = $orderInfo['rate'];
                $order['related_order_id'] = $orderInfo['id'];
                $order['type'] = 1;
                $order['channel_id'] = $orderInfo['channel_id'];
                $order['channel_account_id'] = $orderInfo['channel_account_id'];
                $order['channel_id'] = $orderInfo['channel_id'];
                $order['buyer_id'] = $orderInfo['buyer_id'];
                $order['pay_note'] = '补发订单,系统自动生成';
                $order['pay_time'] = time();
                $order['check_time'] = time();
                $order['reason_for_audit'] = '补发单';
                $order['buyer'] = $orderInfo['buyer'];
                $order['seller'] = $orderInfo['seller'];
                $order['seller_id'] = $orderInfo['seller_id'];
                $order['channel_order_id'] = $orderInfo['channel_order_id'];
                $order['channel_order_number'] = $orderInfo['channel_order_number'];
                $order['order_amount'] = 0;
                $order['goods_amount'] = 0;
            }
            $order['new_warehouse_id'] = $data['new_warehouse_id'];
            $order['shipping_method_id'] = $data['shipping_method_id'];
            return $order;
        } catch (OrderSaleException $e) {
            throw new OrderSaleException($e->getMessage() . $e->getFile() . $e->getLine());
        } catch (Exception $e) {
            throw new OrderSaleException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 检查订单是否有多个稍后单
     * @param $order_id
     * @return string
     */
    private function checkAfterOrder($order_id)
    {
        $afterCount = $this->afterServiceModel->where([
            'reissue_returns_status' => AfterSaleType::SupplementaryShipment,
            'approve_status' => AfterSaleType::Approval,
            'order_id' => $order_id
        ])->count();
        $redeliver = '';
        if (!empty($afterCount)) {
            $afterCount = $afterCount + 1;
            for ($i = 0; $i < $afterCount; $i++) {
                $redeliver .= 'R';
            }
        } else {
            $redeliver = 'R';
        }
        return $redeliver;
    }

    /** 记录订单来源信息
     * @param $order
     * @param $goods
     * @throws Exception
     */
    private function source(&$order, &$goods)
    {
        try {
            $source = [];
            $sourceModel = new OrderSourceDetail();
            foreach ($goods as $k => $v) {
                $source[$k]['order_id'] = $order['id'];
                $source[$k]['create_time'] = time();
                $source[$k]['id'] = ServiceTwitter::instance()->nextId($order['channel_id'],
                    $order['channel_account_id']);
                $v['order_source_detail_id'] = $source[$k]['id'];
            }
            time_partition('OrderSourceDetail', time(), null, [], true);
            $sourceModel->allowField(true)->isUpdate(false)->save($source);
        } catch (OrderSaleException $e) {
            throw new OrderSaleException($e->getMessage() . $e->getFile() . $e->getLine());
        } catch (Exception $e) {
            throw new OrderSaleException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /** 商品详情
     * @param $order
     * @param $goods
     * @return array
     * @throws OrderSaleException
     */
    private function detail(&$order, $goods)
    {
        try {
            $orderCommon = new OrderCommon();
            $detail_list = [];
            $first_fee = 0;
            $cost = 0;
            $tariff = 0;
            foreach ($goods as $k => $v) {
                $skuData = Cache::store('goods')->getSkuInfo($v['sku_id']);
                $goodsData = $orderCommon->getGoods($skuData['goods_id']);
                $detail_list[$k]['sku'] = $skuData['sku'];
                $detail_list[$k]['sku_id'] = $skuData['id'];
                $detail_list[$k]['goods_id'] = $skuData['goods_id'];
                $detail_list[$k]['sku_title'] = $skuData['spu_name'];
                $detail_list[$k]['sku_thumb'] = $skuData['thumb'];
                $detail_list[$k]['sku_quantity'] = $v['sku_quantity'];
                $detail_list[$k]['sku_price'] = $skuData['retail_price'];
                $detail_list[$k]['cost_price'] = $orderCommon->getGoodsCostPriceBySkuId($skuData['id'], $order['new_warehouse_id']);
                $detail_list[$k]['weight'] = $skuData['weight'];
                $detail_list[$k]['create_time'] = $order['create_time'];
                $detail_list[$k]['update_time'] = $order['create_time'];
                //产品的一些重量，体积信息
                $orderCommon->goodsDetailInfo($detail_list, $k, $goodsData);
                $first_fee += !empty($goodsData) ? $goodsData['first_fee'] : 0;
                $cost += $detail_list[$k]['cost_price'];
                $tariff += !empty($goodsData) ? $goodsData['tariff'] : 0;
            }
            $order['first_fee'] = $first_fee;   //头程费
            $order['tariff'] = $tariff;   //关税
            $order['cost'] = $cost;
            return $detail_list;
        } catch (OrderSaleException $e) {
            throw new OrderSaleException($e->getMessage() . $e->getFile() . $e->getLine());
        } catch (Exception $e) {
            throw new OrderSaleException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /** 包裹信息
     * @param $order
     * @param $detail_list
     * @throws Exception
     * @throws OrderSaleException
     */
    private function package($order, $detail_list)
    {
        try {
            $orderPackage = $order;
            $orderCommon = new OrderCommon();
            $package = $orderCommon->getPackage($detail_list, $orderPackage);
            unset($orderPackage);
            $declareService = new DeclareService();
            if (is_object($order)) {
                $order = $order->toArray();
            }
            //保存到数据库里
            $orderModel = new Order();
            //新增订单
            $orderModel->allowField(true)->isUpdate(false)->save($order);
            //记录日记信息
            $log = [
                0 => [
                    'message' => $order['old_order_number'] . '补发产生新订单',
                    'operator' => '系统自动',
                    'process_id' => 0
                ]
            ];
            //新增包裹
            foreach ($package as $p => $pp) {
                $orderPackageModel = new OrderPackage();
                $orderDetailModel = new OrderDetail();
                if (is_object($pp)) {
                    $pp = $pp->toArray();
                }
                $pp['id'] = ServiceTwitter::instance()->nextId($order['channel_id'],
                    $order['channel_account_id']);
                $pp['warehouse_id'] = $order['new_warehouse_id'];
                $pp['shipping_id'] = $order['shipping_method_id'];
                $pp['order_amount'] = $order['order_amount'];
                $pp['goods_amount'] = $order['goods_amount'];
                $pp['pay_fee'] = $order['pay_fee'] ?? 0;
                $pp['shipping_name'] = !empty($order['shipping_method_id']) ? Cache::store('shipping')->getShippingName($order['shipping_method_id']) : '';
                $pp['order_id'] = $order['id'];
                $packageGoods = [];
                foreach ($detail_list as $k => $v) {
                    if (is_object($v)) {
                        $v = $v->toArray();
                    }
                    $list = $v;
                    //看看这个商品是属于哪个包裹的
                    if (isset($pp['contains'])) {
                        if (in_array($v['sku_id'], $pp['contains'])) {
                            $list['package_id'] = $pp['id'];
                            if (isset($list['id'])) {
                                $orderDetailModel->where(['id' => $list['id']])->update($list);   //更新
                            } else {
                                $list['id'] = ServiceTwitter::instance()->nextId($order['channel_id'],
                                    $order['channel_account_id']);
                                $list['order_id'] = $order['id'];
                                $orderDetailModel->allowField(true)->isUpdate(false)->save($list);
                            }
                            array_push($packageGoods, $v);
                        }
                    } else {
                        $list['package_id'] = $pp['id'];
                        if (isset($list['id'])) {
                            $orderDetailModel->where(['id' => $list['id']])->update($list);   //更新
                        } else {
                            $list['id'] = ServiceTwitter::instance()->nextId($order['channel_id'],
                                $order['channel_account_id']);
                            $list['order_id'] = $order['id'];
                            $orderDetailModel->allowField(true)->isUpdate(false)->save($list);
                            array_push($packageGoods, $v);
                        }
                    }
                }
                //包裹申报
                $packageDeclare = $declareService->matchDeclare($pp, $packageGoods, $log);
                $declareService->storage($packageDeclare);
                $orderPackageModel->allowField(true)->isUpdate(false)->save($pp);
            }
            foreach ($log as $key => $value) {
                CommonService::addOrderLog($order['id'], $value['message'], $value['operator'], $value['process_id']);
            }
        } catch (OrderSaleException $e) {
            throw new OrderSaleException($e->getMessage() . $e->getFile() . $e->getLine());
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /** 平台退款获取订单信息
     * @param $order_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws RefundException
     */
    public function refundOrderInfo($order_id)
    {
        if (empty($order_id) && !is_numeric($order_id)) {
            throw new RefundException('订单ID错误');
        }
        //查询售后单信息
        $where['approve_status'] = ['=', AfterSaleType::Approval];
        $where['refund_status'] = ['=', AfterSaleType::RefundCompleted];
        $where['type'] = [
            'in',
            [
                AfterSaleType::Refund,
                AfterSaleType::RefundAndReplacementGoods,
                AfterSaleType::RefundAndReturnGoods,
                AfterSaleType::RefundAndReplacementGoodsAndReturnGoods
            ]
        ];
        $where['order_id'] = ['=', $order_id];
        $saleInfo = $this->afterServiceModel->field('id,order_id,channel_id,account_id,reason,to_buyer_message')->where($where)->find();
        if (empty($saleInfo)) {
            throw new RefundException('售后信息有误');
        }
        $orderModel = new Order();
        $orderInfo = $orderModel->field('channel_order_number')->where(['id' => $saleInfo['order_id']])->find();
        $saleInfo['channel_order_number'] = $orderInfo['channel_order_number'];
        return $saleInfo;
    }

    /** 判断买家是否有记录
     * @param $buyer
     * @param $type
     * @return bool
     */
    public function saleByBuyer($buyer, $type)
    {
        switch ($type) {
            case 'refund':  //退款
                $where['type'] = [
                    'in',
                    [
                        AfterSaleType::Refund,
                        AfterSaleType::RefundAndReplacementGoods,
                        AfterSaleType::RefundAndReturnGoods,
                        AfterSaleType::RefundAndReplacementGoodsAndReturnGoods
                    ]
                ];
                break;
            case 'replace': //补发
                $where['type'] = [
                    'in',
                    [
                        AfterSaleType::ReplacementGoods,
                        AfterSaleType::RefundAndReplacementGoods,
                        AfterSaleType::ReplacementGoodsAndReturnGoods,
                        AfterSaleType::RefundAndReplacementGoodsAndReturnGoods
                    ]
                ];
                break;
        }
        //查询售后单信息
        $where['approve_status'] = ['=', AfterSaleType::Approval];
        $where['refund_status'] = ['=', AfterSaleType::RefundCompleted];
        $where['buyer_id'] = ['=', $buyer];
        $saleList = $this->afterServiceModel->field(true)->where($where)->select();
        if (!empty($saleList)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 通过追踪号 获取拦截信息
     * @param $shipping_number
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws Exception
     */
    public function getAfterSaleInfoByShippingNumber($shipping_number)
    {
        try {
            $packageInfo = (new PackageService())->getPackageInfoByShipping($shipping_number);
            if (!empty($packageInfo)) {
                $where['order_id'] = ['eq', $packageInfo['order_id']];
                $where['type'] = ['in', [AfterSaleType::ReturnGoods, AfterSaleType::RefundAndReturnGoods, AfterSaleType::RefundAndReplacementGoodsAndReturnGoods]];
                $where['approve_status'] = ['eq', AfterSaleType::Approval];
                $afterInfo = $this->afterServiceModel->field('order_id,order_number,sale_number,channel_id,account_id as channel_account_id,warehouse_id,reason')->where($where)->find();
                if (!empty($afterInfo)) {
                    $afterInfo['reason'] = (new SaleReasonService())->getReason($afterInfo['reason']);
                    $afterInfo = $afterInfo->toArray();
                    $orderInfo = (new OrderService())->getOrderInfo($afterInfo['order_id'], 'id,site_code,status,country_code,shipping_time');
                    $afterInfo = array_merge($afterInfo, $orderInfo);
                } else {
                    return [];
                }
                $packageInfo = array_merge($packageInfo, $afterInfo);
            }
            return $packageInfo;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 批量提交售后单
     * @param $afterIds
     * @param $operator
     * @return bool
     * @throws \Exception
     */
    public function batchUpdate($afterIds, $operator)
    {
        Db::startTrans();
        try {
            $afterService = [];
            foreach ($afterIds as $k => $v) {
                $temp = [];
                $temp['id'] = $v;
                $temp['approve_status'] = 2;
                $temp['submitter_id'] = $operator['operator_id'];
                $temp['submit_time'] = time();
                array_push($afterService, $temp);
            }
            $result = $this->afterServiceModel->saveAll($afterService);
            Db::commit();
            $res = [];
            if ($result) {
                $res['status'] = true;
                $res['message'] = '批量操作成功';
            } else {
                $res['status'] = false;
                $res['message'] = '批量操作失败';
            }
            return $res;
        } catch (Exception $e) {
            Db::rollback();
            throw new JsonErrorException('Message：' . $e->getMessage() . ' File：' . $e->getFile()
                . ' Line：' . $e->getLine());
        }
    }

    /**
     * 售后单队列缓存
     * @param $sale_id
     * @param $ttl
     */
    public function setCache($sale_id, $ttl = 0)
    {
        $cache = Cache::handler();
        $key = 'AdoptQueueSaleId:' . $sale_id;
        //将售后单id存入缓存
        $cache->set($key, time());
        if (!empty($ttl)) {
            $cache->expire($key, $ttl);
        } else {
            $cache->expire($key, 300);
        }
    }

    /**
     * 售后单队列删除缓存
     * @param $sale_id
     */
    public function delCache($sale_id)
    {
        $cache = Cache::handler();
        $key = 'AdoptQueueSaleId:' . $sale_id;
        //删除缓存
        $cache->del($key);
    }

    /**
     * 判断售后单缓存是否存在
     * @param $sale_id
     * @return bool
     */
    public function existsCache($sale_id)
    {
        $cache = Cache::handler();
        $key = 'AdoptQueueSaleId:' . $sale_id;
        if ($cache->exists($key)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  来源类型
     */
    public function sourceType()
    {
        $status = [
            0 => [
                'code' => '',
                'remark' => '全部',
            ],
            1 => [
                'code' => 1,
                'remark' => '纠纷',
            ],
            2 => [
                'code' => 0,
                'remark' => '系统',
            ]
        ];
        return $status;
    }

    /**
     * ebay纠纷部分退款回调方法
     * @param $after_id
     * @param $status
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ebayPartialRefundCallBack($after_id, $partial, $status)
    {
        $saleServiceInfo = $this->afterServiceModel
            ->field('order_id,channel_id,account_id,refund_amount,refund_currency,refund_status,approve_status')
            ->where(['id' => $after_id])->find();
        //不修改付款失败和付款成功的
        if (in_array($saleServiceInfo['refund_status'], array(3))) {
            return false;
        }
        //钱，或币种对不上，直接付款失败；
        if ($saleServiceInfo['refund_amount'] != $partial['amount'] || $saleServiceInfo['refund_currency'] != $partial['currency']) {
            $refund_status = AfterSaleType::RefundFailed;
        } else {
            //钱币对得上看状态；
            if ($status == 1) {
                $refund_status = AfterSaleType::RefundCompleted;
                CommonService::addOrderLog($saleServiceInfo['order_id'],
                    '订单退款成功');
            } else {
                $refund_status = AfterSaleType::RefundFailed;
                CommonService::addOrderLog($saleServiceInfo['order_id'],
                    '订单退款失败');
            }
        }

        if ($saleServiceInfo['refund_status'] == $refund_status) {
            return false;
        }

        $this->afterServiceModel->update(['refund_status' => $refund_status], ['id' => $after_id]);
        return true;
    }

    /**
     * 新建退货售后单
     * @param $data
     * @return array|mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnAfterSale($data)
    {
        foreach ($data as $key => $value) {
            //通过平台订单号查询订单是否存在
            $orderInfo = (new Order())->field(true)->where(['channel_order_number' => $value['channel_order_number']])->find();

            if (empty($orderInfo)) {
                throw new Exception('订单不存在', 400);
            }
            //通过仓库code获取仓库id
            $warehouseService = new Warehouse();
            $value['warehouse_id'] = $warehouseService->getIdByDistributionCode($value['warehouse_code']);

            $orderDetailModel = new OrderDetail();
            $orderSourceModel = new OrderSourceDetail();
            //定义problem数组
            $problem_goods = [];
            $return_goods = [];
            foreach ($value['goods'] as $k => $v) {
                //查询订单详情数据
                $v['sku_id'] = (new GoodsSku())->getSkuIdBySku($v['sku']);
                $detailList = $orderDetailModel->field('order_source_detail_id,sku_id,sku,sku_thumb,sku_title,sku_quantity,type,package_id,note')->where(['order_id' => $orderInfo['id'], 'sku_id' => $v['sku_id']])->find();
                //查询订单产品来源数据
                $sourceList = $orderSourceModel->field('id,channel_sku,channel_item_id,color,size,channel_item_link,delete_time,channel_sku_title,channel_sku_quantity')->where(['id' => $detailList['order_source_detail_id']])->find();
                //获取商品信息
                $skuInfo = Cache::store('goods')->getSkuInfo($v['sku_id']);
                //问题商品
                $problem_goods[$k]['sku'] = $v['sku'];
                $problem_goods[$k]['sku_id'] = $v['sku_id'];
                $problem_goods[$k]['channel_sku'] = $sourceList['channel_sku'];
                $problem_goods[$k]['channel_item_id'] = $sourceList['channel_item_id'];
                $problem_goods[$k]['sku_title'] = $detailList['sku_title'];
                $problem_goods[$k]['quantity'] = $v['quantity'];
                $problem_goods[$k]['delivery_quantity'] = $detailList['sku_quantity'];
                $problem_goods[$k]['good_id'] = $skuInfo['goods_id'];
                //退货商品
                $return_goods[$k]['spu_name'] = $detailList['sku_title'];
                $return_goods[$k]['sku'] = $v['sku'];
                $return_goods[$k]['sku_id'] = $v['sku_id'];
                $return_goods[$k]['quantity'] = $v['quantity'];
            }

            $problemInfo['goods'] = $problem_goods;
            $returnInfo['goods'] = $return_goods;
            $returnInfo['warehouse_id'] = $value['warehouse_id'];
            $returnInfo['buyer_return_carrier'] = $value['buyer_return_carrier'];
            $returnInfo['buyer_return_tracking_num'] = $value['buyer_return_tracking_num'];

            //售后单参数数组
            $params = [
                'approve_status' => AfterSaleType::Submitted,   //审批状态
                'submit_time' => time(),                        //提交时间
                'submitter_id' => 0,                            //提交人
                'order_id' => $orderInfo['id'],           //订单id
                'order_number' => $orderInfo['order_number'],   //订单编号
                'reason' => 5,                                  //原因
                'remark' => $value['remark'],               //原因说明
                'type' => AfterSaleType::ReturnGoods,           //默认为退货售后
                'problem' => json_encode($problemInfo),         //问题商品数据
                'return' => json_encode($returnInfo),           //退回商品数据
                'operator_id' => 0,
            ];

            $refund = [];
            $delivery = [];
            $return = [];
            $problem = [];
            $this->check($refund, $delivery, $return, $problem, $params);
            if (!isset($problem['goods']) || empty($problem['goods'])) {
                throw new Exception('请先选择问题的货品！', 400);
            }
            $orderModel = new Order();
            $orderDetailModel = new OrderDetail();
            $orderSourceDetailModel = new OrderSourceDetail();
            if ($orderInfo['status'] == OrderStatusConst::SaidInvalid) {
                throw new Exception('订单已作废，不可建售后单');
            }
            foreach ($problem['goods'] as $key => $value) {
                //查询订单详情表
                $detailInfo = $orderDetailModel->field('sku_quantity,sku')->where([
                    'sku_id' => $value['sku_id'],
                    'order_id' => $orderInfo['id']
                ])->find();
                //查询产品来源表
                $sourceDetailInfo = [];
                if (isset($value['channel_sku']) && isset($value['channel_item_id'])) {
                    $sourceDetailInfo = $orderSourceDetailModel->field('channel_sku,channel_sku_quantity')->where([
                        'order_id' => $orderInfo['id'],
                        'channel_sku' => $value['channel_sku'],
                        'channel_item_id' => $value['channel_item_id']
                    ])->find();
                }
                if (empty($detailInfo) && empty($sourceDetailInfo)) {
                    throw new Exception('订单不存在问题商品SKU【' . $value['sku'] . '】', 500);
                }
                if (($value['quantity'] > $detailInfo['sku_quantity']) && (!empty($sourceDetailInfo) && $value['quantity'] > $sourceDetailInfo['channel_sku_quantity'])) {
                    if (!empty($detailInfo)) {
                        throw new Exception('问题商品SKU【' . $detailInfo['sku'] . '】数量不能大于' . $detailInfo['sku_quantity'],
                            500);
                    } else {
                        throw new Exception('问题商品SKU【' . $sourceDetailInfo['channel_sku'] . '】数量不能大于' . $sourceDetailInfo['channel_sku_quantity'],
                            500);
                    }
                }
            }
            $afterService = $params;
            $afterService['id'] = ServiceTwitter::instance()->nextId($orderInfo['channel_id'],
                $orderInfo['channel_account_id']);
            $afterService['sale_number'] = $afterService['id'];
            $afterService['order_number'] = $orderInfo['order_number'];
            $afterService['buyer_id'] = $orderInfo['buyer'];
            $afterService['create_time'] = time();
            $afterService['creator_id'] = 0;
            $afterService['channel_id'] = $orderInfo['channel_id'];
            $afterService['account_id'] = $orderInfo['channel_account_id'];
            if (!isset($afterService['buyer_country_code'])) {
                $afterService['buyer_country_code'] = $orderInfo['country_code'];
            }
            $order_status = $orderInfo['status'];
            if (!empty($return)) {
                if (!isset($return['warehouse_id']) || empty($return['warehouse_id'])) {
                    throw new Exception('退回接收仓库为必填');
                }
                $afterService['buyer_return_carrier'] = $return['buyer_return_carrier'];
                $afterService['buyer_return_tracking_num'] = $return['buyer_return_tracking_num'];
                $afterService['warehouse_id'] = $return['warehouse_id'];
                $order_status += OrderStatusConst::RefundGoodsStatus;   //退货
            }
            //记录日志
            $log = [
                'sale_id' => $afterService['id'],
                'operator_id' => 0,
                'operator' => '系统自动',
                'remark' => '品连新建退货售后单',
                'create_time' => time()
            ];
            //记录问题货品信息
            $problemInfo = $this->problemInfo($problem, $params, $afterService);
            //记录补发货
            $deliveryInfo = [];
            //记录退货的信息
            $deliveryInfo = $this->returnInfo($return, $params, $afterService, $deliveryInfo);
            if (!$this->validate->check($afterService)) {
                throw new Exception($this->validate->getError(), 400);
            }
            $ok = Cache::handler()->set($this->_order_sale_add . $params['order_id'], 1);
            if (!$ok) {
                throw new Exception('数据在处理中，请不要重复点击');
            }
            Cache::handler()->expire($this->_order_sale_add . $params['order_id'], 300);  //5min过期
            Db::startTrans();
            try {
                $this->afterServiceModel->allowField(true)->isUpdate(false)->save($afterService);
                $after_id = $this->afterServiceModel->id;
                $this->afterSaleLogModel->allowField(true)->isUpdate(false)->save($log);
                $this->afterWastageModel->allowField(true)->isUpdate(false)->saveAll($problemInfo);
                //插入补发货记录
                $this->afterRedeliverModel->allowField(true)->isUpdate(false)->saveAll($deliveryInfo);
                //更新订单
                $orderModel->where(['id' => $orderInfo['id']])->update(['status' => $order_status]);
                //售后更改包裹状态
                (new PackageHelp())->effectPackageByOrderId($orderInfo['id'], $order_status);
                //订单日志信息
                CommonService::addOrderLog($orderInfo['id'], '订单创建了售后单,单号为【' . $afterService['id'] . '】',
                    '系统自动', '', 0);
                //新增订单备注
                if (isset($afterService['remark']) && !empty($afterService['remark'])) {
                    $orderNoteModel = new OrderNote();
                    $orderNoteModel->allowField(true)->isUpdate(false)->save([
                        'order_id' => $orderInfo['id'],
                        'creator_id' => 0,
                        'note' => $afterService['remark'],
                        'create_time' => time()
                    ]);
                    //订单日志信息
                    CommonService::addOrderLog($orderInfo['id'], '订单新增了备注【' . $afterService['remark'] . '】',
                        '系统自动', '', 0);
                }
                Db::commit();
            } catch (OrderSaleException $e) {
                Db::rollback();
                throw new Exception($e->getMessage() . $e->getFile() . $e->getLine(), 500);
            } catch (Exception $e) {
                Db::rollback();
                throw new Exception($e->getMessage() . $e->getFile() . $e->getLine(), 500);
            }
            //自动审核售后单
            try {
                $operator = [];
                $operator['operator'] = '系统自动';
                $operator['id'] = $params['operator_id'];
                $remark = $params['remark'] ?? '';
                (new OrderSaleService())->adopt($after_id, $operator, $remark);
            } catch (Exception $e) {
                throw new Exception($e->getMessage() . $e->getFile() . $e->getLine(), 500);
            }
        }
    }

    /**
     * 标记售后单状态
     * @param $shipping_number
     * @param $userInfo
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnStatus($shipping_number, $userInfo)
    {
        //查询是否有符合条件的售后单
        $where = [];
        $where['type'] = ['=', AfterSaleType::ReturnGoods];
        $where['reissue_returns_status'] = ['=', AfterSaleType::Returning];
        $where['buyer_return_tracking_num'] = ['=', $shipping_number];
        $afterService = $this->afterServiceModel->field(true)->where($where)->find();
        if ($afterService) {
            Db::startTrans();
            try {
                //更新售后单状态
                $after = [];
                $after['reissue_returns_status'] = AfterSaleType::ReturnedGoods;
                $this->afterServiceModel->where(['id' => $afterService['id']])->update($after);
                //如果是品连订单回调品连接口
                if ($afterService['channel_id'] == ChannelAccountConst::channel_Distribution) {
                    $channel_order_number = (new Order())->where(['id' => $afterService['order_id']])->value('channel_order_number');
                    $data = [
                        'channel_order_number' => $channel_order_number,
                    ];
                    (new UniqueQueuer(DistributionStockInCallBack::class))->push($data);
                }
                //添加日志
                $log = [
                    'sale_id' => $afterService['id'],
                    'operator_id' => $userInfo['user_id'],
                    'operator' => $userInfo['realname'],
                    'remark' => '售后单号【' . $afterService['id'] . '】 已收到退货',
                    'create_time' => time()
                ];
                (new AfterSaleLog())->allowField(true)->isUpdate(false)->save($log);
                //订单日志信息
                CommonService::addOrderLog($afterService['order_id'], '售后单号【' . $afterService['id'] . '】 已收到退货 ',
                    $userInfo['realname'], '', $userInfo['user_id']);
                Db::commit();
            } catch (Exception $ex) {
                Db::rollback();
                throw new Exception($ex->getMessage());
            }
        }
        return true;
    }

    /**
     * 通过物流跟踪号获取包裹信息
     * @param $tracking_num
     * @return array
     * @throws Exception
     */
    public function getAfterSaleInfoByTrackingNumber($tracking_num)
    {
        try {
            //通过物流跟踪号$shipping_number获取订单数据
            $packageInfo = [];
            $where['buyer_return_tracking_num'] = ['eq', $tracking_num];
            $where['type'] = ['in', [AfterSaleType::ReturnGoods, AfterSaleType::RefundAndReturnGoods, AfterSaleType::RefundAndReplacementGoodsAndReturnGoods]];
            $where['approve_status'] = ['eq', AfterSaleType::Approval];
            $afterInfo = $this->afterServiceModel->field('id as after_id,order_id,order_number,sale_number,channel_id,account_id as channel_account_id,warehouse_id,reason')->where($where)->find();
            if (!empty($afterInfo)) {
                $afterInfo['reason'] = (new SaleReasonService())->getReason($afterInfo['reason']);
                $afterInfo = $afterInfo->toArray();
                $orderInfo = (new OrderService())->getOrderInfo($afterInfo['order_id'], 'id,site_code,status,country_code,shipping_time');
                $afterInfo = array_merge($afterInfo, $orderInfo);
                $sku_id = (new AfterRedeliverDetail())->where(['after_sale_service_id' => $afterInfo['after_id']])->value('sku_id');
                $packageInfo = $this->getPackageInfo($afterInfo['order_id'], $sku_id);
                if (!empty($packageInfo)) {
                    $packageInfo = array_merge($packageInfo, $afterInfo);
                }
            } else {
                return [];
            }
            return $packageInfo;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 通过订单号和sku_id获取包裹信息
     * @param $order_id
     * @param $sku_id
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPackageInfo($order_id, $sku_id)
    {
        $field = 'p.id,p.channel_id,p.status,p.channel_account_id,p.order_id,p.package_confirm_status,p.package_collection_id,p.package_collection_time,p.packing_time,p.shipping_id,p.shipping_name,p.number,p.shipping_number,p.process_code,p.estimated_weight,p.package_weight,p.picking_id,p.shipping_time,p.warehouse_id';
        $where = [];
        $join = [];
        $where['p.order_id'] = ['eq', $order_id];
        $where['pd.sku_id'] = ['eq', $sku_id];
        $join['order_package_declare'] = ['order_package_declare pd', 'p.id = pd.package_id', 'left'];
        $orderPackageModel = new OrderPackage();
        $packageInfo = $orderPackageModel->alias('p')->join($join)->field($field)->where($where)->find();
        if (!empty($packageInfo)) {
            $shippingInfo = Cache::store('shipping')->getShipping($packageInfo['shipping_id']);
            if (!empty($shippingInfo)) {
                switch ($shippingInfo['label_used_number']) {
                    case 2:  //处理号
                        $packageInfo['label_used_number'] = $packageInfo['process_code'];
                        break;
                    case 3:  //包裹号
                        $packageInfo['label_used_number'] = $packageInfo['number'];
                        break;
                }
            }
            $packageService = new PackageService();
            $packageInfo['number'] = $packageService->getPackageNumber($packageInfo['number']);
            $bool = $packageService->isCanPickingExamine($packageInfo['id'], true);
            $packageInfo['package_is_cancel'] = $bool == true ? 0 : 1;
            if ($packageInfo['packing_time'] > 0 && $packageInfo['package_is_cancel'] == 0) {
                if ((new PackageHelp())->isPackingChange($packageInfo['id'])) {
                    $packageInfo['package_is_cancel'] = 1;
                }
            }
            $packageInfo['country_code'] = (new OrderHelp())->getCountryCode($packageInfo['order_id']);
        }
        return !empty($packageInfo) ? $packageInfo->toArray() : [];
    }

    /**
     * 品连单取消退货记录日志
     * @param $channel_order_number
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelAfterSale($channel_order_number)
    {
        //通过渠道订单号查询订单是否存在
        $orderInfo = (new Order())->field(true)->where(['channel_order_number' => $channel_order_number])->find();
        if (empty($orderInfo)) {
            throw new Exception('订单不存在', 400);
        }
        //查询当前订单的售后单数据
        $afterInfo = $this->afterServiceModel->field(true)->where(['order_id' => $orderInfo['id'], 'type' => 4])->find();
        if (empty($afterInfo)) {
            throw new Exception('售后单不存在', 400);
        }
        try {
            //为售后单添加日志
            $log = [
                'sale_id' => $afterInfo['id'],
                'operator_id' => 0,
                'operator' => '系统自动',
                'remark' => '售后单号【' . $afterInfo['id'] . '】 已取消退货',
                'create_time' => time()
            ];
            (new AfterSaleLog())->allowField(true)->isUpdate(false)->save($log);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage() . $ex->getFile() . $ex->getLine(), 500);
        }
    }

    /**
     * 创建售后自动审核验证
     * @param $params
     * @throws Exception
     */
    public function automaticAdoptCheck($params)
    {
        try {
            //验证售后规则
            $afterSaleRuleSet = new AfterSaleRuleSet();
            //规则列表
            $ruleSetList = $afterSaleRuleSet->field('id,title,action_value')->with('items')->where([
                'status' => 0,
                'channel_id' => ['in', [0, $params['channel_id']]],
            ])->order('channel_id desc,sort asc')->select();

            //售后规则匹配数据
            $afterSale = [];
            $afterSale['channel_id'] = $params['channel_id'];
            $afterSale['site_code'] = $params['site_code'];
            $afterSale['channel_account_id'] = $params['channel_account_id'];
            $afterSale['currency_code'] = $params['currency_code'];
            $afterSale['refund_amount'] = isset($params['refund_amount']) ? $params['refund_amount'] : 0;
            $afterSale['pay_fee'] = $afterSale['refund_amount'];
            $afterSale['creator_id'] = $params['operator_id'];
            $is_ok = false;
            foreach ($ruleSetList as $k => $v) {
                $rule_item_ids = array_column($v['items'], 'rule_item_id');
                //如果勾选售后金额，则仅验证售后单
                if (in_array(2, $rule_item_ids)) {
                    if (isset($params['source_type']) && $params['source_type'] == 1 && isset($params['source_id']) && !empty($params['source_id'])) {
                        foreach ($v['items'] as $kk => $vv) {
                            $item_value = json_decode($vv['param_value'], true);
                            $item_value = is_array($item_value) ? $item_value : [];
                            //获取对应规则
                            $rule_item_code = (new AfterSaleRuleService())->itemList()[$vv['rule_item_id']]['code'];
                            $is_ok = (new OrderRuleExecuteService())->check($rule_item_code, $item_value, $afterSale, [], $is_ok);
                            if (!$is_ok) {
                                break;
                            }
                        }
                    }
                } else {
                    foreach ($v['items'] as $kk => $vv) {
                        $item_value = json_decode($vv['param_value'], true);
                        $item_value = is_array($item_value) ? $item_value : [];
                        //获取对应规则
                        $rule_item_code = (new AfterSaleRuleService())->itemList()[$vv['rule_item_id']]['code'];
                        $is_ok = (new OrderRuleExecuteService())->check($rule_item_code, $item_value, $afterSale, [], $is_ok);
                        if (!$is_ok) {
                            break;
                        }
                    }
                }

                if ($is_ok) {
                    if ($v['action_value'] == 0) {
                        $action_value = '自动审批';
                    } else {
                        $action_value = '不自动审批';
                    }
                    //售后单日志信息
                    $log = [
                        'sale_id' => $params['id'],
                        'operator_id' => 0,
                        'operator' => '系统自动',
                        'remark' => '售后单号【' . $params['id'] . '】匹配申报规则【' . $v['title'] . '】是否自动审批【' . $action_value . '】 . ',
                        'create_time' => time()
                    ];
                    (new AfterSaleLog())->allowField(true)->isUpdate(false)->save($log);
                    //订单日志信息
                    CommonService::addOrderLog($params['order_id'], '售后单号【' . $params['id'] . '】匹配申报规则【' . $v['title'] . '】是否自动审批【' . $action_value . '】 . ',
                        '系统自动', '', 0);
                    if ($v['action_value'] == 0) {
                        //判断审批是否加入队列
                        $result = $this->existsCache($params['id']);
                        if ($result) {
                            break;
                        } else {
                            //将售后单id存入缓存
                            $this->setCache($params['id']);
                        }
                        $param = [];
                        $param['id'] = $params['id'];
                        $operator = [];
                        $operator['operator'] = $params['operator'];
                        $operator['id'] = $params['operator_id'];
                        $param['operator'] = $operator;
                        $param['remark'] = $params['remark'] ?? '';
                        (new UniqueQueuer(OrderSaleAdoptQueue::class))->push($param);
                        //如果是退款售后单，修改为退款中状态
                        if (!empty($params['refund_amount'])) {
                            $this->afterServiceModel->update(['refund_status' => 4], ['id' => $params['id']]);
                        }
                    }
                    break;
                } else {
                    //订单日志信息
                    CommonService::addOrderLog($params['order_id'], '售后单号【' . $params['id'] . '】匹配申报规则【' . $v['title'] . '】' . '失败',
                        '系统自动', '', 0);
                }
            }
        } catch (Exception $ex) {
            throw new Exception('错误信息' . $ex->getMessage());
        }

    }

}