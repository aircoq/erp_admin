<?php

namespace app\common\model;

use app\purchase\queue\YksPurchaseQueue;
use erp\ErpModel;
use function foo\func;
use think\Model;
use app\common\cache\Cache;
use think\Exception;
use app\common\service\UniqueQueuer;
use app\purchase\queue\GetLogisticsQueue as GetLogistics;
use app\common\traits\ModelFilter;
use app\purchase\service\PurchaseOrder as PurchaseOrderService;
use think\db\Query;

/** 采购订单
 * Created by PhpStorm.
 * User: XPDN
 * Date: 2016/10/28
 * Time: 9:13
 */
class PurchaseOrder extends ErpModel
{

    const STATUS = [
        0 => '等待下单',
        10 => '等待到货',
        11 => '部分到货等待剩余',
        15 => '不等待剩余待审',
        20 => '部分到货不等待剩余',
        21 => '全部到货',
        -1 => '作废',
    ];
    const INVOICE = [
        0 => '无',
        1 => '17%增值税专用发票',
        5 => '17%的增值税普通发票',
        2 => '3%增值税普通发票',
        3 => '3%普通发票',
        6 => '13%的增值税普通发票',
        9 => '13%的增值税专用发票',
        8 => '不能开票',
        4 => '无税',
        7 => '其他',
    ];

    const SUPPLY_CHAIN_FINANCE = [
        0 => '无',
        1 => '是',
        2 => '否',
    ];

    use ModelFilter;
    
    public function scopePurchase(Query $query, $params)
    {
        if (!empty($params)) {
            $query->where('__TABLE__.purchase_user_id', 'in', $params);
        }
    }
    
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /** 检查是否存在
     * @param array $data
     * @return bool
     */
    public function check(array $data)
    {
        $result = $this->get($data);
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /** 获取供应商支付方式
     * @return array
     */
    public static function getPayment()
    {
        $payment = [
            0 => [
                'label' => '',
                'name' => '请选择支付方式'
            ],
            1 => [
                'label' => 1,
                'name' => '现金'
            ],
            2 => [
                'label' => 2,
                'name' => '银行转账'
            ],
            3 => [
                'label' => 3,
                'name' => 'PayPal'
            ],
            4 => [
                'label' => 4,
                'name' => '支付宝'
            ]
        ];
        return $payment;
    }

    /**
     * 获得付款状态
     * @author YangJiafei
     * @date 2019-04-16
     * @return array
     */
    public function getPaymentStatus()
    {
        return [
            0 => [
                'label' => 7,
                'name' => '未付款'
            ],
            1 => [
                'label' => 8,
                'name' => '已付款'
            ],
            2 => [
                'label' => 9,
                'name' => '部分付款'
            ]
        ];
    }

    /** 检查代码或者用户名是否有存在了
     * @param $id
     * @param $company_name
     * @return bool
     */
    public function isHas($id, $company_name)
    {
        if (!empty($company_name)) {
            $result = $this->where(['company_name' => $company_name])->where('id', 'NEQ', $id)->select();
            if (!empty($result)) {
                return true;
            }
        }
        return false;
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function logistics()
    {
        return parent::hasMany('PurchaseOrderLogistics', 'purchase_id');
    }

    public function detail()
    {
        return parent::hasMany('PurchaseOrderDetail', 'purchase_order_id');
    }

    /**
     * @desc 获取仓库名称
     * @param type $value
     * @param array $data 本条数据
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-30 14:00:11
     */
    public function getWarehouseNameAttr($value, $data)
    {
        $res = Cache::store('warehouse')->getWarehouse($data['warehouse_id']);
        return $res['name'] ?? '';
    }

    /**
     * @desc 获取供应商名称
     * @param type $value
     * @param array $data 本条数据
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-30 14:00:11
     */
    public function getSupplierNameAttr($value, $data)
    {
        $res = Cache::store('supplier')->getSupplier($data['supplier_id']);
        return $res['company_name'] ?? '';
    }
    /**
     * @desc 获取供应商名称
     * @param type $value
     * @param array $data 本条数据
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-30 14:00:11
     */
    public function getPurchaseUserNameAttr($value, $data)
    {
        $res = Cache::store('user')->getOneUser($data['purchase_user_id']);
        return $res['realname'];
    }
    /**
     * @desc 初始化函数,注册回调事件
     * @author Jimmy <554511322@qq.com>
     * @date 2018-01-18 16:54:11
     */
    public static function init()
    {
        try {
            $old = null; //修改之前的数据
            //注册新增和修改之前的事件
            self::beforeWrite(function ($data) {
                global $old;
                //修改之前的数据
                $old = isset($data->id) ? self::get($data->id) : null;
            });
            //注册新增和修改之后的事件
            self::afterUpdate(function ($data){
                global $old;
                if(isset($data->external_number) && $data->external_number != $old->external_number){
                    if((new PurchaseOrderService())->is1688ExternalNumber($data->external_number)){
                        (new UniqueQueuer(GetLogistics::class))->push(json_encode(['id' => $data->id, 'external_number' => $data->external_number]));
                    }
                }
            });
            self::afterInsert(function ($data){
                if(isset($data->id) && isset($data->external_number)){
                    if((new PurchaseOrderService())->is1688ExternalNumber($data->external_number)){
                        (new UniqueQueuer(GetLogistics::class))->push(json_encode(['id' => $data->id, 'external_number' => $data->external_number]));
                    }
                }
            });
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function buildFieldSql(array $fields): array
    {
        $fieldList = [
            'purchase_order' => [
                'id' => 'id',
                'purchase_plan_id' => 'purchase_plan_id',
                'external_number' => 'external_number',
                'purchaser' => 'purchase_user_id',
                'warehouse_name' => 'warehouse_id',
                'purchase_time' => 'purchase_time',
                'expect_arrive_date' => 'expect_arrive_date',
                'commit_date' => 'purchase_plan_id',
                'status' => 'status',
                'supplier' => 'supplier_id',
                'supplier_balance_type' => 'supplier_balance_type',
                'shipping_cost' => 'shipping_cost',
                'supplier_code' => 'supplier_id',
                'purchase_status' => 'status',
                'amount' => 'amount',
                'payable_amount' => 'payable_amount',
                'payment_status' => 'payment_status',
                'remark' => 'remark',
                'first_lack_date' => 'warehouse_id',
                'supply_chain_finance' => 'supply_chain_finance',
                'invoice' => 'invoice',
                'tax_rate' => 'tax_rate',
                'tax_money' => 'tax_money',
            ],
            'purchase_order_detail' => [
                'sku_id' => 'sku_id',
                'sku' => 'sku',
                'sku_alias' => 'sku',
                'sku_attributes' => 'sku',
                'sku_name' => 'sku',
                'price' => 'price',
                'qty' => 'qty',
                'in_qty' => 'in_qty',
                'currency' => 'currency_code',
                'unpack_num' => 'sku',
                'real_arrive_date' => 'sku_id',
                'first_lack_date' => 'sku_id',
                'in_time' => 'in_time',
                'purchase_execept_type'=> 'expected_defective_qty'
            ],
        ];
        $buildFields = [];
        foreach ($fields as $item) {
            foreach ($fieldList as $table => $field) {
                if (in_array($item, array_keys($fieldList[$table]))) {
                    $buildFields[$table][] = $fieldList[$table][$item];
                }
            }
        }
        $buildFields['purchase_order']['id'] = 'id';
        if (isset($buildFields['purchase_order_detail'])) {
            if (!isset($buildFields['purchase_order_detail']['sku']) || !isset($buildFields['purchase_order_detail']['sku_id'])) {
                $buildFields['purchase_order_detail']['sku_id'] = 'sku_id';
                $buildFields['purchase_order_detail']['sku'] = 'sku';
            }
        }
        return $buildFields;
    }
}
