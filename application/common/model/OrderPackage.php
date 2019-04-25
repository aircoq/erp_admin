<?php

namespace app\common\model;

use app\common\service\OrderStatusConst;
use erp\ErpModel;
use think\Model;
use app\common\traits\OrderStatus;
use app\common\traits\ModelFilter;
use think\db\Query;

/**
 * Created by PhpStorm.
 * User: PHILL
 * Date: 2016/10/28
 * Time: 9:13
 */
class OrderPackage extends ErpModel
{
    use OrderStatus;
    use ModelFilter;
    
    public function scopeOrderPackage(Query $query, $params)
    {
        $query->where('__TABLE__.channel_account', 'in', $params);
    }

    public function scopeOrder(Query $query, $params)
    {
        $query->where('__TABLE__.channel_account', 'in', $params);
    }

    /**
     * 订单
     */
    protected function initialize()
    {
        //需要调用 mdoel 的 initialize 方法
        parent::initialize();
    }

    /** 订单id转字符串
     * @param $attr
     * @return string
     */
    public function getOrderIdAttr($attr)
    {
        return (string) $attr;
    }

    /** 包裹id转字符串
     * @param $value
     * @return string
     */
    public function getIdAttr($value)
    {
        if (is_numeric($value)) {
            $value = $value . '';
        }
        return $value;
    }

    /** 获取状态名
     * @param $value
     * @return mixed|string
     */
    public function getStatusAttr($value)
    {
        return $value;
        //$orderProcess = new OrderProcess();
        //return $orderProcess->getStatusName($value);
    }

    public function getStatusName($value = '')
    {
        if (!$value) {
            $value = $this->status;
        }
        $orderProcess = new OrderProcess();
        return $orderProcess->getStatusName($value);
    }

    /** 创建包裹-关联详情
     * @param $detail_list
     * @param $order_id
     * @param $order
     */
    public function createPackage($detail_list, $order_id, $order)
    {
        $goodsModel = new Goods();
        $orderDetailModel = new OrderDetail();
        $weight = 0;
        //先判断总的商品是否已超重
        foreach ($detail_list as $k => $v) {
            if (isset($v['goods_id'])) {
                //查出商品的具体资料
                $goods_list = $goodsModel->where(['id' => $v['goods_id']])->find();
                if (!empty($goods_list)) {
                    //判断单个物品总数量是否超重
                    $weight = $weight + ($goods_list['weight'] * $v['sku_quantity']);
                    $goods_weight = $goods_list['weight'] * $v['sku_quantity'];
                    if ($goods_weight > 100) {
                        //拆
                    }
                }
            }
        }
        if ($weight > 10000) {
            //拆，分多个包裹
        } else {
            $temp = [];
            $temp['order_id'] = $order_id;
            $temp['create_time'] = time();
            $temp['update_time'] = time();
            $temp['package_weight'] = $weight;
            $temp['warehouse_id'] = isset($order['warehouse_id']) ? $order['warehouse_id'] : 0;
            $temp['shipping_id'] = isset($order['shipping_id']) ? $order['shipping_id'] : 0;
            $temp['channel_id'] = isset($order['channel_id']) ? $order['channel_id'] : 0;
            $temp['channel_account_id'] = isset($order['channel_account_id']) ? $order['channel_account_id'] : 0;
            $temp['status'] = isset($order['status']) ? $order['status'] : OrderStatusConst::ForDistribution;
            $temp['pay_time'] = isset($order['pay_time']) ? $order['pay_time'] : 0;
            $this->allowField(true)->isUpdate(false)->save($temp);
            //查询出包裹号
            $package_list = $this->where(['order_id' => $order_id])->find();
            foreach ($detail_list as $k => $v) {
                $list = $v;
                $list['package_id'] = $package_list['id'];
                $orderDetailModel->allowField(true)->isUpdate(false)->save($list);
            }
        }
    }

    /** 对应关系
     * @return \think\model\Relation
     */
    public function packageOrder()
    {
        return $this->belongsTo(Order::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'id', 'order_id');
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class, "package_id", "id");
    }

    public function address()
    {
        return $this->hasOne(OrderAddress::class,'order_id','order_id');
    }

    /** 对应关系
     * @return \think\model\Relation
     */
    public function warehouse()
    {
        return $this->hasOne(Warehouse::class, "id", "warehouse_id");
    }

    public function shipping()
    {
        return $this->hasOne(ShippingMethod::class, 'id', 'shipping_id');
    }

    public function warehouseName()
    {
        return $this->warehouse()->bind('name');
    }

    /** 获取
     * @return mixed
     */
    public function declareInfo()
    {
        return $this->hasMany(OrderPackageDeclare::class, 'package_id', 'id', [], 'left')->field('');
    }

    /** 获取字段名称
     * @param $field
     * @return string
     */
    public function fieldName($field)
    {
        $fieldName = [
            'estimated_weight' => '估计重量',
            'package_weight' => '包裹重量',
            'providers_weight' => '物流商称重',
            'providers_fee' => '物流商实际运费',
            'declared_amount' => '申报金额',
            'declared_weight' => '申报重量',
            'declared_currency_code' => '申报币种',
            'number' => '包裹号',
            'estimated_fee' => '估计运费',
            'shipping_fee' => '运费',
            'shipping_id' => '运输方式',
            'process_code' => '物流商单号',
            'shipping_number' => '运单号',
            'shipping_name' => '运输方式名称',
            'warehouse_id' => '仓库',
            'width' => '宽度',
            'height' => '高度',
            'length' => '长度'
        ];
        if (isset($fieldName[$field])) {
            return $fieldName[$field];
        }
        return '';
    }

}
