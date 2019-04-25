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
class VirtualPurchaseOrder extends ErpModel
{

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

    public function detail()
    {
        return parent::hasMany('VirtualPurchaseOrderDetail', 'virtual_purchase_order_id');
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

}
