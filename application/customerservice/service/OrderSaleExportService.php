<?php
namespace app\customerservice\service;

use app\common\cache\Cache;
use app\common\exception\JsonErrorException;
use app\common\model\AfterSaleService;
use app\common\model\AfterRedeliverDetail;
use app\common\model\AfterServiceReason;
use app\common\model\AfterWastageGoods;
use app\common\model\OrderDetail;
use app\common\model\OrderProcess;
use app\common\model\PackageReturn;
use app\common\service\AfterSaleType;
use app\common\model\Order;
use app\common\traits\AfterSale;
use app\customerservice\queue\OrderSaleExportQueue;
use app\common\model\OrderPackage;
use app\common\service\Common;
use app\common\traits\FilterHelp;
use app\report\model\ReportExportFiles;
use app\common\service\ImportExport;
use think\Exception;
use think\Loader;
use app\order\service\AuditOrderService;
use app\common\traits\Export;
use app\common\service\CommonQueuer;
use think\Request;
use app\common\model\Channel;

Loader::import('phpExcel.PHPExcel', VENDOR_PATH);
/** 售后处理导出
 * Created by PhpStorm.
 * User: hecheng
 * Date: 2017/3/18
 * Time: 10:59
 */
class OrderSaleExportService
{
    use Export;
    use AfterSale;
    protected $afterServiceModel;  //定义售后订单模型
    protected $afterWastageModel;  //定义问题货品模型
    protected $afterRedeliverModel;  //定义补发货模型

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
    }

    /**
     * 标题
     */
    public function title()
    {
        $title = [
            'order_number' => [
                'title' => 'order_number',  //after_sale_service
                'remark' => '订单号',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'package_id' => [
                'title' => 'package_id',  //order_package
                'remark' => '包裹号',
                'is_show' => 1,
                'column' => [4]
            ],
            'pay_time' => [
                'title' => 'pay_time', //order
                'remark' => '付款时间',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'buyer_id' => [
                'title' => 'buyer_id', //after_sale_service
                'remark' => '买家ID',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'buyer_country_code' => [
                'title' => 'buyer_country_code',  //after_sale_service
                'remark' => '收件人国家/地区',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'status' => [
                'title' => 'status', //after_sale_service
                'remark' => '订单状态',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'shipping_time' => [
                'title' => 'shipping_time', //order
                'remark' => '发货时间',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'new_warehouse_id' => [
                'title' => 'new_warehouse_id', //order_package
                'remark' => '发货仓库',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'redeliver_order_id' => [
                'title' => 'redeliver_order_id',   //after_sale_service
                'remark' => '补发订单号',
                'is_show' => 1,
                'column' => [2]
            ],
            'redeliver_sku_quantity' => [
                'title' => 'redeliver_sku_quantity',
                'remark' => '补发货/退货SKU及数量',   //after_redeliver_detail
                'is_show' => 1,
                'column' => [2,4]
            ],
            'wastage_sku_delivery_quantity' => [
                'title' => 'wastage_sku_delivery_quantity', //after_wastage_goods
                'remark' => '原订单SKU及数量',
                'is_show' => 1,
                'column' => [2]
            ],
            'shipping_method_id' => [
                'title' => 'shipping_method_id', //order_package
                'remark' => '邮寄方式',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'warehouse_id' => [
                'title' => 'warehouse_id', //after_sale_service
                'remark' => '接受退货仓库',
                'is_show' => 1,
                'column' => [4]
            ],
            'buyer_return_carrier' => [
                'title' => 'buyer_return_carrier', //after_sale_service
                'remark' => '退货carrier',
                'is_show' => 1,
                'column' => [4]
            ],
            'buyer_return_tracking_num' => [
                'title' => 'buyer_return_tracking_num', //after_sale_service
                'remark' => '跟踪号',
                'is_show' => 1,
                'column' => [4]
            ],
            'redeliver_shipping_method' => [
                'title' => 'redeliver_shipping_method', //order_package
                'remark' => '补发货邮寄方式',
                'is_show' => 1,
                'column' => [2]
            ],
            'redeliver_shipping_time' => [
                'title' => 'redeliver_shipping_time', //order
                'remark' => '补发货时间',
                'is_show' => 1,
                'column' => [2]
            ],
            'reason_code' => [
                'title' => 'reason_code', //after_service_reason
                'remark' => '退款/补发原因',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'return_time' => [
                'title' => 'return_time',  //package_return表  return_type = 4 取 update_time 否则为 ''
                'remark' => '退货入库时间',
                'is_show' => 1,
                'column' => [4]
            ],
            'collection_account' => [
                'title' => 'collection_account',    //order
                'remark' => '收款PayPal帐号',
                'is_show' => 1,
                'column' => [1]
            ],
            'pay_code' => [
                'title' => 'pay_code',
                'remark' => '收款PayPal交易号',      //order
                'is_show' => 1,
                'column' => [1]
            ],
            'currency_code' => [
                'title' => 'currency_code', //order
                'remark' => '收款币种',
                'is_show' => 1,
                'column' => [1]
            ],
            'order_amount' => [
                'title' => 'order_amount', //order
                'remark' => '收款金额',
                'is_show' => 1,
                'column' => [1]
            ],
            'payment_account' => [
                'title' => 'payment_account',   //order
                'remark' => '买家PayPal帐号',
                'is_show' => 1,
                'column' => [1]
            ],
            'paypal_trx_id' => [
                'title' => 'paypal_trx_id', //after_sale_service
                'remark' => '退款PayPal交易号',
                'is_show' => 1,
                'column' => [1]
            ],
            'refund_currency' => [
                'title' => 'refund_currency',   //after_sale_service
                'remark' => '退款币种',
                'is_show' => 1,
                'column' => [1]
            ],
            'refund_amount' => [
                'title' => 'refund_amount',     //after_sale_service
                'remark' => '退款金额',
                'is_show' => 1,
                'column' => [1]
            ],
            'wastage_sku_quantity' => [
                'title' => 'wastage_sku_quantity',      //after_wastage_goods
                'remark' => '问题SKU及数量',
                'is_show' => 1,
                'column' => [1]
            ],
            'creator_id' => [
                'title' => 'creator_id',  //after_sale_service
                'remark' => '创建人',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'submitter_id' => [
                'title' => 'submitter_id',  //after_sale_service
                'remark' => '提交人',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'refund_time' => [
                'title' => 'refund_time',   //after_sale_service
                'remark' => '退款时间',
                'is_show' => 1,
                'column' => [1]
            ],
            'remark' => [
                'title' => 'remark',    //after_sale_service
                'remark' => '备注',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'delivery_type' => [
                'title' => 'delivery_type',    //order
                'remark' => '发货状态',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'channel_sku' => [
                'title' => 'channel_sku',    //order
                'remark' => '平台SKU',
                'is_show' => 1,
                'column' => [1,2,4]
            ],
            'sku_name' => [
                'title' => 'sku_name',    //order
                'remark' => '产品中文名称',
                'is_show' => 1,
                'column' => [1,2,4]
            ]
        ];
        return $title;
    }

    /**
     * 导出记录
     * @param array $package_ids
     * @param array $field
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function exportOnline(array $after_id = [], array $field = [], $params = [])
    {
        set_time_limit(0);
        $userInfo = Common::getUserInfo();
        try {
            if (empty($after_id)) {
                if (Cache::store('order')->isExport('export')) {
                    throw new JsonErrorException('全部导出正在生成数据,请使用部分导出');
                }
                Cache::store('order')->setExport($userInfo['user_id']);
            }
            //获取导出文件名
            $fileName = $this->newExportFileName($params);
            //判断是否存在筛选条件，更改导出名
            if(isset($fileName) && $fileName != ''){
                $setFileName = 1;
                $remake = $fileName . '售后处理列表';
                $fileName = $remake;
            }else{
                $setFileName = 0;
                $fileName = '售后处理' . date('YmdHis', time());
                $remake = $fileName;
            }
            $downLoadDir = '/download/sales/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            $titleData = $this->title();
            $condition = [];
            if (!empty($after_id)) {
                $condition['a.id'] = ['in', $after_id];
            }
            $remark = [];
            if (!empty($field)) {
                $title = [];
                foreach ($field as $k => $v) {
                    if (isset($titleData[$v])) {
                        array_push($title, $v);
                        array_push($remark, $titleData[$v]['remark']);
                    }
                }
            } else {
                $title = [];
                foreach ($titleData as $k => $v) {
                    if ($v['is_show'] == 1) {
                        array_push($title, $k);
                        array_push($remark, $v['remark']);
                    }
                }
            }
            $count = $this->doCount($condition, $params);
            $params['field'] = $field;
            if ($count > 200) {
                $this->exportApply($params, OrderSaleExportQueue::class, $remake ,$setFileName);
                Cache::store('order')->delExport($userInfo['user_id']);
                //throw new JsonErrorException('已加入导出队列');
                return ['join_queue' => 1, 'message' => '已加入导出队列'];
            }else{
                $this->saveExcel($params, $fullName, $condition);
                $auditOrderService = new AuditOrderService();
                $result = $auditOrderService->record($fileName, $saveDir . $fileName);
                Cache::store('order')->delExport($userInfo['user_id']);
                return $result;
            }
        } catch (Exception $e) {
            Cache::store('order')->delExport($userInfo['user_id']);
            throw new JsonErrorException($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * @title 生成导出用户名
     * @param $params
     * @return string
     */
    public function newExportFileName($params)
    {
        $fileName = '';
        if (isset($params['approve']) && !empty($params['approve'])) {  //审批状态
            $fileName .= '审批状态：' . $this->getApproval($params['approve']) . '|';
        }

        if (isset($params['status']) && !empty($params['status'])) {   //执行状态
            $fileName .= '审批状态：' . $this->getExecute($params['status']) . '|';
        }

        if (isset($params['channel_id']) && !empty($params['channel_id'])) {  //渠道id
            $channel = new Channel();
            $title = $channel->where('id' ,$params['channel_id'])->value('title');
            $fileName .= '平台' . $title . '|';
        }

        if (isset($params['submitter']) && !empty($params['submitter'])) {  //提交人
            $user = Cache::store('user')->getOneUser($params['submitter']);
            $fileName .= '提交人：' . $user['realname'] . '|';

        }

        if (isset($params['snDate'])) {
            $params['date_b'] = isset($params['date_b']) ? $params['date_b'] : '';
            $params['date_e'] = isset($params['date_e']) ? $params['date_e'] : '';
            switch ($params['snDate']) {
                case 'submit_time':
                    if (!empty($params['date_b']) && !empty($params['date_e'])) {
                        $fileName .= '提交时间：' . $params['date_b'] . '—' . $params['date_e'] . '|';
                    }
                    break;
                case 'approve_time':
                    if (!empty($params['date_b']) && !empty($params['date_e'])) {
                        $fileName .= '审批时间：' . $params['date_b'] . '—' . $params['date_e'] . '|';
                    }
                    break;
                case 'create_time':
                    if (!empty($params['date_b']) && !empty($params['date_e'])) {
                        $fileName .= '创建时间：' . $params['date_b'] . '—' . $params['date_e'] . '|';
                    }
                    break;
                default:
                    break;
            }
        }
        return $fileName;
    }

    /**
     * 导出申请
     * @param $params
     * @param $object
     * @param $remark
     */
    public function exportApply($params, $object, $remark = '售后处理数据' , $setFileName = 0)
    {
        $userId = Common::getUserInfo()->toArray()['user_id'];
        $cache = Cache::handler();
        $lastApplyTime = $cache->hget('hash:export_order_apply', $userId);
        if ($lastApplyTime && time() - $lastApplyTime < 5) {
            throw new JsonErrorException('请求过于频繁', 400);
        } else {
            $cache->hset('hash:export_order_apply', $userId, time());
        }
        $model = new ReportExportFiles();
        $data['applicant_id'] = $userId;
        $data['apply_time'] = time();
        if($setFileName = 0){
            $export_file_name = $this->createExportFileName($remark, $userId);
        }else{
            $export_file_name = $remark . '.xlsx';
        }
        $data['export_file_name'] = $export_file_name;
        $data['status'] = 0;
        $data['applicant_id'] = $userId;
        $model->allowField(true)->isUpdate(false)->save($data);
        $params['file_name'] = $data['export_file_name'];
        $params['apply_id'] = $model->id;
        (new CommonQueuer($object))->push($params);
    }

    /**
     * 获取审批状态
     */
    public function getApproval($approve)
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
        $approve = $status[$approve]['remark'];
        return $approve;
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
        $number = $this->afterServiceModel->alias('a')->join($join)->where($where)->count();
        return $number;
    }

    /**
     * 获取执行状态
     */
    public function getExecute($execute)
    {
        $status = [
            0 => [
                'code' => 0,
                'remark' => '全部'
            ],
            3 => [
                'code' => 3,
                'remark' => '已退款'
            ],
            2 => [
                'code' => 2,
                'remark' => '退款失败'
            ],
            4 => [
                'code' => 4,
                'remark' => '已补发货'
            ],
            5 => [
                'code' => 5,
                'remark' => '已退货'
            ]
        ];
        $execute = $status[$execute]['remark'];
        return $execute;
    }

    /**
     * 搜索条件
     * @param $params
     * @param $where
     * @param $join
     * @param $whereExp
     * @return array|\think\response\Json
     */
    public function where($params, &$where, &$join)
    {
        if (isset($params['approve'])) {  //审批状态
            $where['a.approve_status'] = ['=', $params['approve']];
        }
        if (isset($params['status'])) {   //执行状态
            if ($params['status'] == 4) {
                $where['a.reissue_returns_status'] = ['=', 1];
            } else {
                if ($params['status'] == 4) {
                    $where['a.reissue_returns_status'] = ['=', 2];
                } else {
                    $where['a.refund_status'] = ['=', $params['status']];
                }
            }
        }
        if (isset($params['channel_id']) && !empty($params['channel_id'])) {  //渠道id
            $where['a.channel_id'] = ['=', $params['channel_id']];
        }
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

        if (isset($params['type_ids']) && !empty($params['type_ids'])) {  //类型id
            $where['a.type'] = ['in', $params['type_ids']];
        }
    }

    /**
     * 查询总数
     * @param array $condition
     * @param array $params
     * @return int|string
     */
    protected function doCount(array $condition = [], $params = [])
    {
        $join = [];
        $this->where($params, $condition, $join);
        $field = 'a.id,a.sale_number,a.order_number,a.refund_status,a.reissue_returns_status,a.buyer_country_code,a.buyer_id,a.approve_status,a.buyer_return_tracking_num,a.type,a.submitter_id,a.creator_id,a.create_time,a.submit_time,a.approve_time,a.approve_status';
        if (!empty($join)) {
            $field .= ',b.sku';
        }
        $count = $this->afterServiceModel->alias('a')->field($field)->join($join)->where($condition)->count();
        return $count;
    }

    /**
     * 查询数据
     * @param array $condition
     * @param array $params
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function doSearch(array $condition = [], $params = [], $page = 0, $pageSize = 0)
    {
        $join = [];
        $this->where($params, $condition, $join);
        $field = 'a.id,a.order_id,a.sale_number,a.reason,a.order_number,a.new_warehouse_id,a.redeliver_order_id,a.warehouse_id,a.paypal_trx_id,a.buyer_return_carrier,a.refund_amount,a.refund_currency,a.refund_status,a.refund_time,a.reissue_returns_status,a.buyer_country_code,a.buyer_id,a.approve_status,a.buyer_return_tracking_num,a.type,a.remark,a.submitter_id,a.creator_id,a.create_time,a.submit_time,a.approve_time,a.approve_status,o.pay_time,o.status,o.shipping_time,o.collection_account,o.pay_code,o.currency_code,o.order_amount,o.payment_account,o.delivery_type,o.belong_type';
        if (!empty($join)) {
            $field .= ',b.sku';
        }
        $join[] = ['order o', 'o.id = a.order_id', 'left'];
        $orderBy = fieldSort($params);
        $orderBy .= 'create_time desc';
        $orderData = $this->afterServiceModel->alias('a')->field($field)->join($join)->where($condition)->order($orderBy)->page($page,
            $pageSize)->select();
        return $orderData;
    }

    /**
     * 组装数据
     * @param array $records
     * @param array $title
     * @return array
     * @throws Exception
     */
    private function assemblyData(array $records, array $title)
    {
            $exportData = [];
            $orderModel = new Order();
            $orderPackageModel = new OrderPackage();
            $afterServiceReason = new AfterServiceReason();
            $packageReturn = new PackageReturn();
            $orderDetailModel = new OrderDetail();
            foreach ($records as $key => $value) {
                $newOrderData = $value;
                $newOrderData['pay_time'] = $value['pay_time'] ? date('Y-m-d H:i:s',$value['pay_time']) : '';
                $newOrderData['status'] = !empty($value['type']) ? $this->getType($value['type']) : '';
                $newOrderData['shipping_time'] = !empty($value['shipping_time']) ? date('Y-m-d H:i:s',$value['shipping_time']) : '';
                $newOrderData['warehouse_id'] = !empty($value['warehouse_id']) ? Cache::store('warehouse')->getWarehouseNameById($value['warehouse_id']) : '';
                //获取提交人
                if ($value['submitter_id'] > 0) {
                    $user = Cache::store('user')->getOneUser($value['submitter_id']);
                    $submitter_id = $user['realname'] ?? '';
                } else {
                    $submitter_id = '';
                }
                $newOrderData['submitter_id'] = $submitter_id;
                //获取创建人
                if ($value['creator_id'] > 0) {
                    $user = Cache::store('user')->getOneUser($value['creator_id']);
                    $creator_id = $user['realname'] ?? '';
                } else {
                    $creator_id = '';
                }
                $newOrderData['creator_id'] = $creator_id;

                //补发货订单
                $orderInfo = $orderModel->field('order_number,shipping_time,related_order_id')->where(['id' => $value['redeliver_order_id']])->find();
                $newOrderData['redeliver_shipping_time'] = !empty($orderInfo['shipping_time']) ? date('Y-m-d H:i:s',$orderInfo['shipping_time']) : '';
                $newOrderData['redeliver_order_id'] = !empty($orderInfo['order_number']) ? $orderInfo['order_number'] : '';

                //补发货邮寄方式
                $redeliverOrderPackageInfo = $orderPackageModel->where(['order_id' => $orderInfo['related_order_id']])->value('shipping_id');
                $newOrderData['redeliver_shipping_method'] = !empty($redeliverOrderPackageInfo['shipping_id']) ? Cache::store('shipping')->getShippingName($redeliverOrderPackageInfo['shipping_id']) : '';

                $afterRedeliverDetail = $this->afterRedeliverModel->field('sku,quantity')->where(['after_sale_service_id' => $value['id']])->find();
                if ($afterRedeliverDetail['sku'] && $afterRedeliverDetail['quantity']) {
                    $newOrderData['redeliver_sku_quantity'] = $afterRedeliverDetail['sku']. ' * ' . $afterRedeliverDetail['quantity'];
                } else {
                    $newOrderData['redeliver_sku_quantity'] = '';
                }

                if (isset($value['refund_time']) && !empty($value['refund_time'])) {
                    $newOrderData['refund_time'] = date('Y-m-d H:i:s',$value['refund_time']);
                } else {
                    $newOrderData['refund_time'] = !empty($value['approve_time']) ? date('Y-m-d H:i:s',$value['approve_time']) : '';
                }

                if ($value['belong_type'] == 1) {
                    $package_ids = [];
                    //详情
                    $detailList = $orderDetailModel->field('package_id,order_id')->where(['order_id' => $value['order_id']])->select();
                    foreach ($detailList as $detail => $list) {
                        if (!in_array($list['package_id'], $package_ids)) {
                            array_push($package_ids, $list['package_id']);
                        }
                    }
                    $order_ids = [];
                    //查看所有的详情信息
                    $detailListAll = $orderDetailModel->field('package_id,order_id')->where('package_id', 'in',
                        $package_ids)->select();
                    foreach ($detailListAll as $detail => $list) {
                        if (!in_array($list['order_id'], $order_ids)) {
                            array_push($order_ids, $list['order_id']);
                        }

                    }
                    //查看发货仓库
                    $packageList = $orderPackageModel->field('id,package_collection_id,status,order_id,estimated_weight,prior,package_weight,providers_weight,providers_fee,providers_currency_code,declared_amount,declared_weight,declared_currency_code,number,estimated_fee,shipping_fee,shipping_currency_code,process_code,providers_fee,shipping_number,shipping_id,shipping_name,warehouse_id,distribution_time,package_upload_status,picking_id,shipping_time')->where('id',
                        'in', $package_ids)->select();
                    $package_id = '';
                    $shipping_method_id = '';
                    $new_warehouse_id = '';
                    foreach ($packageList as $pack => $list) {
                        if (isset($list['id']) && !empty($list['id'])) {
                            $package_id .= !empty($package_id) ?  ','.$list['id'] : $list['id'];
                        }
                        if (isset($list['shipping_id']) && !empty($list['shipping_id'])) {
                            $shipping_method_id .= !empty($shipping_method_id) ?  ','.Cache::store('shipping')->getShippingName($list['shipping_id']) : Cache::store('shipping')->getShippingName($list['shipping_id']);
                        }
                        if (isset($list['warehouse_id']) && !empty($list['warehouse_id'])) {
                            $new_warehouse_id .= !empty($new_warehouse_id) ?  ','.Cache::store('warehouse')->getWarehouseNameById($list['warehouse_id']) : Cache::store('warehouse')->getWarehouseNameById($list['warehouse_id']);
                        }
                    }
                    $newOrderData['package_id'] = $package_id;
                    $newOrderData['shipping_method_id'] = $shipping_method_id;
                    $newOrderData['new_warehouse_id'] = $new_warehouse_id;
                } else {
                    $orderPackageInfo = $orderPackageModel->field('id,shipping_id,warehouse_id')->where(['order_id' => $value['order_id']])->find();
                    $newOrderData['package_id'] = !empty($orderPackageInfo['id']) ? $orderPackageInfo['id'] : '';
                    $newOrderData['shipping_method_id'] = !empty($orderPackageInfo['shipping_id']) ? Cache::store('shipping')->getShippingName($orderPackageInfo['shipping_id']) : '';
                    $newOrderData['new_warehouse_id'] = !empty($orderPackageInfo['warehouse_id']) ? Cache::store('warehouse')->getWarehouseNameById($orderPackageInfo['warehouse_id']) : '';
                }
                $reason = $afterServiceReason->where(['id' => $value['reason']])->value('code');
                $newOrderData['reason_code'] = !empty($reason) ? $reason : '';

                $packageReturnInfo = $packageReturn->field('return_type,update_time')->where(['order_id' => $value['order_id']])->find();
                if ($packageReturnInfo['return_type'] == 4) {
                    $newOrderData['return_time'] = date('Y-m-d H:i:s',$packageReturnInfo['update_time']);
                } else {
                    $newOrderData['return_time'] = '';
                }

                $afterWastageGoodsInfo = $this->afterWastageModel->field('sku_id,sku,channel_sku,quantity,delivery_quantity')->where(['after_sale_service_id' => $value['id']])->select();
                $wastage_sku_quantity = '';
                $wastage_sku_delivery_quantity = '';
                $channel_sku = '';
                $sku_name = '';
                foreach ($afterWastageGoodsInfo as $k => $v) {
                    $wastage_sku_quantity .= !empty($wastage_sku_quantity) ? ',' . $v['sku']. ' * ' . $v['quantity'] : $v['sku']. ' * ' . $v['quantity'];
                    $wastage_sku_delivery_quantity .= !empty($wastage_sku_delivery_quantity) ? ',' . $v['sku']. ' * ' . $v['delivery_quantity'] : $v['sku']. ' * ' . $v['delivery_quantity'];
                    $channel_sku .= !empty($channel_sku) ? ',' . $v['channel_sku'] : $v['channel_sku'];
                    $sku = Cache::store('Goods')->getSkuInfo($v['sku_id']);
                    $sku_name .= !empty($sku_name) ? ',' . param($sku, 'spu_name') : param($sku, 'spu_name');
                }
                $newOrderData['wastage_sku_quantity'] = $wastage_sku_quantity;
                $newOrderData['wastage_sku_delivery_quantity'] = $wastage_sku_delivery_quantity;
                $newOrderData['channel_sku'] = $channel_sku;
                $newOrderData['sku_name'] = $sku_name;

                //获取发货状态
                $newOrderData['delivery_type'] = !empty($value['delivery_type']) ? $this->getDeliveryType($value['delivery_type']) : '';

                $temp = [];
                foreach ($title as $k => $v) {
                    $temp[$v] = $newOrderData[$v];
                }
                array_push($exportData, $temp);
            }
            return $exportData;
    }

    /**
     * 获取发货类型
     */
    public function getDeliveryType($type)
    {
        $delivery_type = [
            0 => [
                'remark' => '未发货'
            ],
            1 => [
                'remark' => '全部发货'
            ],
            2 => [
                'remark' => '部分发货'
            ]
        ];
        $status = $delivery_type[$type]['remark'];
        return $status;
    }

    /**
     * 导出数据至excel文件
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public function export(array $params)
    {
        try {
            //ini_set('memory_limit', '4096M');
            if (!isset($params['apply_id']) || empty($params['apply_id'])) {
                throw new Exception('导出申请id获取失败');
            }
            if (!isset($params['file_name']) || empty($params['file_name'])) {
                throw new Exception('导出文件名未设置');
            }
            $fileName = $params['file_name'];
            $downLoadDir = '/download/order_detail/';
            $saveDir = ROOT_PATH . 'public' . $downLoadDir;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0777, true)) {
                throw new Exception('导出目录创建失败');
            }
            $fullName = $saveDir . $fileName;
            //统计需要导出的数据行
            $count = $this->doCount([], $params);
            $pageSize = 2000;
            $loop = ceil($count / $pageSize);
            $typeArr = $this->countType($params);
            $num = 0;
            //创建excel对象
            $excel = new \PHPExcel();
            foreach ($typeArr as $key => $type_ids) {
                //如果没有数据，跳出当前循环
                $noData = $this->checkData($params, $type_ids);
                if (empty($noData)) {
                    continue;
                }
                $fields = $params['field'] ?? [];
                $titleData = $this->title();
                $title = [];
                //获取相应导出字段
                switch ($key)
                {
                    case '退款':
                        $this->getTitleData($fields,$titleData,$title,1);
                        break;
                    case '补发货':
                        $this->getTitleData($fields,$titleData,$title,2);
                        break;
                    case '退货':
                        $this->getTitleData($fields,$titleData,$title,4);
                        break;
                }
                if (!empty($num)) {
                    $excel->createSheet();
                }
                $excel->setActiveSheetIndex($num);
                $sheet = $excel->getActiveSheet();
                $sheet->setTitle($key);
                $excel->setActiveSheetIndexByName($key);
                $titleRowIndex = 1;
                $dataRowStartIndex = 2;
                list($titleMap, $dataMap) = $this->getExcelMap($titleData);
                end($titleMap);
                $lastCol = key($titleMap);
                //设置表头和表头样式
                foreach ($titleMap as $col => $set) {
                    $sheet->getColumnDimension($col)->setWidth($set['width']);
                    $sheet->getCell($col . $titleRowIndex)->setValue($set['title']);
                    $sheet->getStyle($col . $titleRowIndex)
                        ->getFill()
                        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E8811C');
                    $sheet->getStyle($col . $titleRowIndex)
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
                }
                $sheet->setAutoFilter('A1:' . $lastCol . '1');
                $params['type_ids'] = $type_ids;    //增加状态查询条件
                //分批导出
                for ($i = 0; $i < $loop; $i++) {
                    $data = $this->assemblyData($this->doSearch([], $params, $i + 1, $pageSize), $title);
                    foreach ($data as $a => $r) {
                        foreach ($dataMap as $field => $set) {
                            $cell = $sheet->getCell($set['col'] . $dataRowStartIndex);
                            switch ($set['type']) {
                                case 'time':
                                    if (empty($r[$field])) {
                                        $cell->setValue('');
                                    } else {
                                        $cell->setValue(date('Y-m-d H:i:s', $r[$field]));
                                    }
                                    break;
                                case 'numeric':
                                    $cell->setDataType(\PHPExcel_Cell_DataType::TYPE_NUMERIC);
                                    if (empty($r[$field])) {
                                        $cell->setValue(0);
                                    } else {
                                        $cell->setValue($r[$field]);
                                    }
                                    break;
                                default:
                                    if (is_null($r[$field])) {
                                        $r[$field] = '';
                                    }
                                    $cell->setValue($r[$field]);
                            }
                        }
                        $dataRowStartIndex++;
                    }
                    unset($data);
                }
                $num++;
            }
            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $writer->save($fullName);
            if (is_file($fullName)) {
                $applyRecord['exported_time'] = time();
                $applyRecord['download_url'] = $downLoadDir . $fileName;
                $applyRecord['status'] = 1;
                (new ReportExportFiles())->where(['id' => $params['apply_id']])->update($applyRecord);
            } else {
                throw new Exception('文件写入失败');
            }
        } catch (\Exception $ex) {
            Cache::handler()->hset(
                'hash:order_sale_export',
                $params['apply_id'].'_'.time(),
                '申请id: ' . $params['apply_id'] . ',导出失败:' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
            $applyRecord['status'] = 2;
            $applyRecord['error_message'] = $ex->getMessage();
        }
    }

    /**
     * 写入excel
     * @param $data
     * @param array $title
     * @param string $path
     * @throws Exception
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function saveExcel($params, $path = '', $condition)
    {
            //统计需要导出的数据行
            $count = $this->doCount($condition, $params);
            $pageSize = 2000;
            $loop = ceil($count / $pageSize);
            $typeArr = $this->countType($params);
            $num = 0;
            //创建excel对象
            $excel = new \PHPExcel();
            foreach ($typeArr as $key => $type_ids) {
                //如果没有数据，跳出当前循环
                $noData = $this->checkData($params, $type_ids, $condition);
                if (empty($noData)) {
                    continue;
                }
                $fields = $params['field'] ?? [];
                $titleData = $this->title();
                $title = [];
                //获取相应导出字段
                switch ($key)
                {
                    case '退款':
                        $this->getTitleData($fields,$titleData,$title,1);
                        break;
                    case '补发货':
                        $this->getTitleData($fields,$titleData,$title,2);
                        break;
                    case '退货':
                        $this->getTitleData($fields,$titleData,$title,4);
                        break;
                }
                if (!empty($num)) {
                    $excel->createSheet();
                }
                $excel->setActiveSheetIndex($num);
                $sheet = $excel->getActiveSheet();
                $sheet->setTitle($key);
                $excel->setActiveSheetIndexByName($key);
                $titleRowIndex = 1;
                $dataRowStartIndex = 2;
                list($titleMap, $dataMap) = $this->getExcelMap($titleData);
                end($titleMap);
                $lastCol = key($titleMap);
                //设置表头和表头样式
                foreach ($titleMap as $col => $set) {
                    $sheet->getColumnDimension($col)->setWidth($set['width']);
                    $sheet->getCell($col . $titleRowIndex)->setValue($set['title']);
                    $sheet->getStyle($col . $titleRowIndex)
                        ->getFill()
                        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E8811C');
                    $sheet->getStyle($col . $titleRowIndex)
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
                }
                $sheet->setAutoFilter('A1:' . $lastCol . '1');
                $params['type_ids'] = $type_ids;    //增加状态查询条件
                //分批导出
                for ($i = 0; $i < $loop; $i++) {
                    $data = $this->assemblyData($this->doSearch($condition, $params, $i + 1, $pageSize), $title);
                    foreach ($data as $a => $r) {
                        foreach ($dataMap as $field => $set) {
                            $cell = $sheet->getCell($set['col'] . $dataRowStartIndex);
                            switch ($set['type']) {
                                case 'time':
                                    if (empty($r[$field])) {
                                        $cell->setValue('');
                                    } else {
                                        $cell->setValue(date('Y-m-d H:i:s', $r[$field]));
                                    }
                                    break;
                                case 'numeric':
                                    $cell->setDataType(\PHPExcel_Cell_DataType::TYPE_NUMERIC);
                                    if (empty($r[$field])) {
                                        $cell->setValue(0);
                                    } else {
                                        $cell->setValue($r[$field]);
                                    }
                                    break;
                                default:
                                    if (is_null($r[$field])) {
                                        $r[$field] = '';
                                    }
                                    $cell->setValue($r[$field]);
                            }
                        }
                        $dataRowStartIndex++;
                    }
                    unset($data);
                }
                $num++;
            }
            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $writer->save($path);
    }

    /**
     * 创建导出文件名
     * @param string $remark
     * @param $userId
     * @return string
     */
    protected function createExportFileName($remark, $userId)
    {
        $fileName = $remark . '报表_' . $userId . '_' . date("Y_m_d_H_i_s") . '.xlsx';
        return $fileName;
    }

    /**
     * 获取传值
     * @param array $params
     * @return array
     */
    public function countType()
    {
        // 1-退款 2-补发货 4-退货 3-退款/补发货 5-退款/退货 6.补发货/退货 7-退款/补发货/退货'
        $typeArr = [
            '退款' => [AfterSaleType::Refund,AfterSaleType::RefundAndReturnGoods,AfterSaleType::RefundAndReplacementGoodsAndReturnGoods],
            '补发货' => [AfterSaleType::ReplacementGoods,AfterSaleType::RefundAndReplacementGoods,AfterSaleType::ReplacementGoodsAndReturnGoods,AfterSaleType::RefundAndReplacementGoodsAndReturnGoods],
            '退货' => [AfterSaleType::ReturnGoods,AfterSaleType::RefundAndReturnGoods,AfterSaleType::ReplacementGoodsAndReturnGoods,AfterSaleType::RefundAndReplacementGoodsAndReturnGoods]
        ];
        return $typeArr;
    }

    /**
     * 获取执行状态
     */
    public function getType($type)
    {
        $status = [
            1 => [
                'code' => 3,
                'remark' => '退款'
            ],
            2 => [
                'code' => 2,
                'remark' => '补发货'
            ],
            3 => [
                'code' => 1,
                'remark' => '退款/补发货'
            ],
            4 => [
                'code' => 5,
                'remark' => '退货'
            ],
            5 => [
                'code' => 4,
                'remark' => '退款/退货'
            ],
            6 => [
                'code' => 6,
                'remark' => '补发货/退货'
            ],
            7 => [
                'code' => 7,
                'remark' => '退款/补发货/退货'
            ]
        ];

        $typeName = isset($status[$type]['remark']) ? $status[$type]['remark'] : '';
        return $typeName;
    }

    /**
     * 获取导出标题和字段名
     * @param $fields
     * @param $titleData
     * @param $title
     */
    public function getTitleData($fields, &$titleData, &$title, $type)
    {
        if (!empty($fields)) {
            $titleNewData = [];
            foreach ($fields as $k => $v) {
                if (isset($titleData[$v]) && in_array($type,$titleData[$v]['column'])) {
                    array_push($title, $v);
                    $titleNewData[$v] = $titleData[$v];
                }
            }
            $titleData = $titleNewData;
        } else {
            foreach ($titleData as $k => $v) {
                if ($v['is_show'] == 0 || !in_array($type,$v['column'])) {
                    unset($titleData[$k]);
                } else {
                    array_push($title, $k);
                }
            }
        }
    }

    /**
     * 检查type类型下是否有数据
     * @param $params
     * @param $type_ids
     * @return int
     */
    public function checkData($params, $type_ids, array $condition = [])
    {
        $params['type_ids'] = $type_ids;
        $join = [];
        $this->where($params, $condition, $join);
        $field = 'a.id,a.order_id,a.sale_number,a.reason,a.order_number,a.new_warehouse_id,a.redeliver_order_id,a.warehouse_id,a.paypal_trx_id,a.buyer_return_carrier,a.refund_amount,a.refund_currency,a.refund_status,a.refund_time,a.reissue_returns_status,a.buyer_country_code,a.buyer_id,a.approve_status,a.buyer_return_tracking_num,a.type,a.remark,c.realname as submitter,a.submitter_id,a.create_time,a.submit_time,a.approve_time,a.approve_status,o.pay_time,o.status,o.shipping_time,o.collection_account,o.pay_code,o.currency_code,o.order_amount,o.payment_account';
        if (!empty($join)) {
            $field .= ',b.sku';
        }
        $join[] = ['user c', 'c.id = a.submitter_id', 'left'];
        $join[] = ['order o', 'o.id = a.order_id', 'left'];
        $orderData = $this->afterServiceModel->alias('a')->field($field)->join($join)->where($condition)->count();
        return $orderData;
    }
}